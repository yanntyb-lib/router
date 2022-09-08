<?php

namespace Yanntyb\Router\Model\Classes\DOT;

use Yanntyb\Router\Model\Classes\Exception\ClassNotFound;
use Yanntyb\Router\Model\Classes\Exception\MethodeNotFound;
use Yanntyb\Router\Model\Classes\Exception\RouteAlreadyExisteException;
use Yanntyb\Router\Model\Classes\Exception\RouteNotFoundException;

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
        //Parcoure toutes les routes
        foreach ($this->getRoutes() as $route) {
            //Trouve la route correspondant au path
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

                //Si matchPath est appelé par handleQuery alors on a besoin d'appeler la route précédent la principale
                if($testPath){
                    //Si on a pas spécifé le fait de que la route n'est pas dans un groupe alors on va check les permission de ce groupe
                    if($route->needGlobalPermission()){
                        //On parcoure toute les permissions
                        foreach($this->getPermissions() as $permission){
                            //Si le path d'une permission match avec le path de notre route alors on va chercher la route correspondante au premier path
                            if(str_contains($route->getPath(),$permission["path"])){
                                $permRoute = $this->matchPath($permission["route"]);
                                //Si le call de la route de permission ne retourne pas true alors l'accès est restrain
                                if(!$permRoute->call($permRoute->getPath())){
                                    //Si une route de permission restrainte est spécifié alors on va la retourné
                                    if($permission["denied"] !== ""){
                                        return $this->matchPath($permission["denied"]);
                                    }
                                    //Sinon on retourne les routes par default d'erreur 403
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

                    //Si la route a une route précédente alors on va chercher celle-ci
                    if($route->getPathBeforeAccessingRouteName()){
                        //Trouve la route
                        $routeBeforeAccessingMainRoute = $this->matchPath($route->getPathBeforeAccessingRouteName());
                        //Si les deux paths ne correspondent pas on throw
                        if($route->getPathBeforeAccessingRouteName() !== $routeBeforeAccessingMainRoute->getPath()){
                            throw new RouteNotFoundException($route->getPathBeforeAccessingRouteName(), " ( path routeToCheck after " . $route->getPath() . " )");
                        }

                        //Sinon si le call de la route trouvé ne retourne pas true alors on va chercher la route définie pour ce cas
                        if(!$routeBeforeAccessingMainRoute->call($routeBeforeAccessingMainRoute->getPath())) {
                            //retourne la route
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
    public function handleQuery()
    {
        $query = str_replace("/index.php", "", $_SERVER['REQUEST_URI']);
        /**
         * @var Route $route
         */
        $route = $this->matchPath($query, true)->call($query, false);
        /**
         * Direct after callback
         */
        if($route->getDirectAfterCallback()){
            if(!is_array($route->getDirectAfterCallback())){
                $routeAfter = $this->matchPath($route->getDirectAfterCallback());
                if($routeAfter->getPath() === $route->getDirectAfterCallback()){
                    $routeAfter->call($routeAfter->getPath());
                }
            }
            else{
                foreach ($route->getDirectAfterCallback() as $after){
                    $routeAfter = $this->matchPath($after);
                    if($routeAfter->getPath() === $after){
                        $routeAfter->call($routeAfter->getPath());
                    }
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