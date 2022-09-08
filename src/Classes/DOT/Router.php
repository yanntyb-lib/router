<?php

namespace Yanntyb\Router\Classes\DOT;

use Yanntyb\Router\Classes\Exception\ClassNotFound;
use Yanntyb\Router\Classes\Exception\MethodeNotFound;
use Yanntyb\Router\Classes\Exception\RouteAlreadyExisteException;
use Yanntyb\Router\Classes\Exception\RouteNotFoundException;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];
    private array $permissions = [];

    public function __construct(bool $htaccess = true){
        if($htaccess){
            $conf = file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/.confRouter");
            if($conf !== "true"){
                $path = $_SERVER["DOCUMENT_ROOT"] . "/.htaccess";
                $content = file_get_contents($path);
                $content .= "RewriteEngine on\nRewriteCond  %{REQUEST_FILENAME} !-f\nRewriteCond  %{REQUEST_FILENAME} !-d\nRewriteRule  .* index.php";
                file_put_contents($path,$content);

                file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/.confRouter", "true");
            }
        }
        $this->initDefaultRoutes();

    }

    /**
     * Return Route matching path
     * @param string $path
     * @param bool $testPath
     * @return Route
     * @throws RouteNotFoundException
     * @throws MethodeNotFound|ClassNotFound
     */
    private function matchPath(string $path,bool $testPath = false): Route{
        foreach ($this->getRoutes() as $route) {
            //If route match given path
            if($route->test($path)){

                if($route->getCheckHeader() && $route->getRequestMethode() && ($_SERVER['REQUEST_METHOD'] !== "POST")){
                    if($this->isXmlHttpRequest()){
                        return $this->routes["403 AJAX"];
                    }
                    else{
                        return $this->routes["403 DOM"];
                    }
                }
                else if($route->getCheckHeader() && !$route->getRequestMethode() && ($_SERVER["REQUEST_METHOD"] === "POST")){
                    if($this->isXmlHttpRequest()){
                        if(!$route->getAjax()){
                            return $this->routes["403 AJAX"];
                        }
                    }
                    else{
                        if($route->getAjax()){
                            return $this->routes["403 DOM"];
                        }
                    }
                }
                if($this->isXmlHttpRequest() && $route->getCheckHeader()){
                    if(!$route->getAjax()){
                        return $this->routes["403 AJAX"];
                    }
                }
                else{
                    if($route->getAjax()){
                        return $this->routes["403 DOM"];
                    }
                }
                if($route->getBeforeCallback()){
                    foreach($route->getBeforeCallback() as $routeCallback){
                        $routeCallbackFromMatch = $this->matchPath($routeCallback);

                        if($routeCallback === $routeCallbackFromMatch->getPath()){
                            $routeCallbackFromMatch->call($routeCallbackFromMatch->getPath());
                        }
                    }
                }

                //If mathode is called from handleQuery then we check if found route need a permission or if a route to be executed before it is specified
                if($testPath){
                    //If we didnt specifed that the route dont need permission we check all permission to find the matching one
                    if($route->needGlobalPermission()){
                        foreach($this->getPermissions() as $permission){
                            //If permission path match with route path then we find route of this permission to call its callback
                            if(str_contains($route->getPath(),$permission["path"])){
                                $permRoute = $this->matchPath($permission["route"]);
                                //If callback of the permission dont return true then it means that the route cant be access
                                //So the router return permission denied route
                                if(!$permRoute->call($permRoute->getPath())){
                                    if($permission["denied"] !== ""){
                                        return $this->matchPath($permission["denied"]);
                                    }
                                    if($this->isXmlHttpRequest()){
                                        return $this->routes["403 AJAX"];
                                    }
                                    else{
                                        return $this->routes["403 DOM"];
                                    }
                                }
                                return $route;
                            }
                        }
                    }

                    //If route doesnt need permission but have a route than need to be called before it manualy specified
                    //Then same than permission, we check if a route with specified path match
                    //If the callback of it doesnt return true then we return the route specified for this case
                    if($route->getPathBeforeAccessingRouteName()){
                        $routeBeforeAccessingMainRoute = $this->matchPath($route->getPathBeforeAccessingRouteName());
                        //Si les deux paths ne correspondent pas on throw
                        if($route->getPathBeforeAccessingRouteName() !== $routeBeforeAccessingMainRoute->getPath()){
                            throw new RouteNotFoundException($route->getPathBeforeAccessingRouteName(), " ( path routeToCheck after " . $route->getPath() . " )");
                        }
                        if(!$routeBeforeAccessingMainRoute->call($routeBeforeAccessingMainRoute->getPath())) {
                            //return specified route ( by default route /403/DOM )
                            return $this->matchPath($route->getPathIfRouteBeforeAccessingReturnFalse());
                        }
                    }
                }

                return $route;
            }
        }
        if($this->isXmlHttpRequest()){
            return $this->routes["404 AJAX"];
        }
        return $this->routes["404 DOM"];

    }

    /**
     * Return true if router already have a route named like $name
     * @param string $name
     * @return bool
     */
    private function has(string $name): bool{
        return isset($this->getRoutes()[$name]);
    }

    /**
     * Return Routes
     * @return Route[]
     */
    private function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Add a route to handle
     * @param string $name
     * @param string $path
     * @param callable|array $callable
     * @return Route $route;
     * @throws RouteAlreadyExisteException
     */
    public function addRoute(string $name, string $path, callable|array $callable): Route
    {

        $route = new Route($name,$path,$callable);

        if($this->has($route->getName())){
            throw new RouteAlreadyExisteException($route->getName());
        }

        $this->routes[$route->getName()] = $route;
        return $route;
    }

    /**
     * Call the route matching url path
     * @return void
     * @throws ClassNotFound
     * @throws MethodeNotFound|RouteNotFoundException
     */
    public function handleQuery(): void
    {
        $query = str_replace("/index.php", "", $_SERVER['REQUEST_URI']);
        /**
         * @var Route $route
         */
        $route = $this->matchPath($query, true)->call($query, false);

        //Then if the route has an after calling callback set we execute it
        if($route->getDirectAfterCallback()){
            foreach ($route->getDirectAfterCallback() as $after){
                $routeAfter = $this->matchPath($after);
                if($routeAfter->getPath() === $after){
                    $routeAfter->call($routeAfter->getPath());
                }
            }
        }
    }

    /**
     * Check if router is called from AJAX of not
     * @return bool
     */
    protected function isXmlHttpRequest(): bool{
        $header = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        return ($header === 'XMLHttpRequest');
    }

    /**
     * Modify access denied route from dom
     * @param callable|array $callable
     * @return Router
     */
    public function setAccessDeniedRoutesDOM(callable|array $callable): self
    {
        $this->modifyRoute("403 DOM", "/403/DOM", $callable);
        return $this;
    }

    /**
     * Modify access denied route from ajax
     * @param callable|array $callable
     * @return $this
     */
    public function setAccessDeniedRoutesAJAX(callable|array $callable): self{
        $this->modifyRoute("403 AJAX", "/403/AJAX", $callable);
        return $this;
    }

    /**
     * Modify route not found from dom
     * @param callable|array $callable
     * @return Router
     */
    public function setDefaultRouteDOM(callable|array $callable): self{
        $this->modifyRoute("404 DOM", "/404/DOM", $callable);
        return $this;
    }

    /**
     * Modify route not found from ajax
     * @param callable|array $callable
     * @return $this
     */
    public function setDefaultRouteAJAX(callable|array $callable): self{
        $this->modifyRoute("404 AJAX", "/404/AJAX", $callable);
        return $this;
    }

    /**
     * Generic fonction to modify a route
     * @param string $name
     * @param string $path
     * @param callable|array $callable
     * @return void
     */
    private function modifyRoute(string $name, string $path, callable|array $callable){
        $this->routes[$name] = new Route($name, $path, $callable);
    }

    /**
     * Default DOM error templates
     * @param int $errorCode
     * @param string $message
     * @return string
     */
    private function errorDOMTemplate(int $errorCode, string $message): string
    {
        return '<!DOCTYPE html>
                <html lang="en">
                <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <meta http-equiv="X-UA-Compatible" content="ie=edge">
                  <style>* {margin:0;padding: 0;}body{background: #233142; padding: 20%}h1{margin-bottom: 20px;color: #facf5a;text-align: center;font-family: Raleway;font-size: 90px;font-weight: 800;}h2{color: #455d7a;text-align: center;font-family: Raleway;font-size: 30px;text-transform: uppercase;}</style>
                  <title>Document</title>
                </head>
                <body>
                  
                <h1>' . $errorCode . '</h1>
                <h2>' . $message . '</h2>
                </body>
                </html>
               ';
    }

    /**
     * Init all default route (403 404)
     * @return Router
     * @throws RouteAlreadyExisteException
     */
    protected function initDefaultRoutes(): self
    {
        /**
         * Access denied default routes
         */
        $this->addRoute("403 DOM", "/403/DOM", function() {echo $this->errorDOMTemplate(403,"NOT THIS TIME, ACCESS FORBIDDEN!");});
        $this->addRoute("403 AJAX", "/403/AJAX", function() {echo json_encode(["error" => "403 access forbidden"]);})->isAjax();
        $this->addRoute("404 DOM", "/404/DOM", function() {echo $this->errorDOMTemplate(404, "NOT FOUND");});
        $this->addRoute("404 AJAX", "/404/AJAX", function() {echo json_encode(["error" => "404 not found"]);})->isAjax();
        return $this;
    }

    /**
     * Make a groupe of route needing an other route permission before calling them
     * @param string $basePath
     * @param string $routeToCheck
     * @return Router
     */
    public function makeGroupedPermission(string $basePath, string $routeToCheck, string $permissionDeniedRoute = ""): self
    {
        $this->permissions[] = [
            "path" => $basePath,
            "route" => $routeToCheck,
            "denied" => $permissionDeniedRoute
        ];
        return $this;
    }

    /**
     * Get permissions array
     * @return array
     */
    protected function getPermissions(): array{
        return $this->permissions;
    }
}