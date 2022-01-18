<?php

namespace Yanntyb\Router\Model\Classes;

use ReflectionException;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];

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
     * @param string $path
     * @param bool $testPath
     * @return Route
     * @throws ReflectionException
     */
    private function matchPath(string $path,bool $testPath = false): Route{
        //Parcoure toutes les routes
        foreach ($this->getRoutes() as $route) {
            //Trouve la route correspondant au path
            if($route->test($path)){
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
                //Si matchPath est appelé par handleQuery alors on a besoin d'appeler la route précédent la principale
                if($testPath){
                    //Si la route a une route précédente alors on va chercher celle-ci
                    if($route->getPathBeforeAccessingRouteName() !== null){
                        //Trouve la route
                        $routeBeforeAccessingMainRoute = $this->matchPath($route->getPathBeforeAccessingRouteName());
                        //Si le call de la route trouvé ne retourne pas true alors on va chercher la route définie pour ce cas
                        if(!$routeBeforeAccessingMainRoute->call($routeBeforeAccessingMainRoute->getPath())) {
                            //Trouve la route
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
            throw new RouteAlreadyExisteException();
        }

        $this->routes[$route->getName()] = $route;
        return $route;
    }

    /**
     * Call the route matching url path
     * @return void
     * @throws ReflectionException
     */
    public function handleQuery()
    {
        $query = str_replace("/index.php", "", $_SERVER['REQUEST_URI']);
        $this->matchPath($query, true)->call($query);
    }


    protected function isXmlHttpRequest(): bool{
        $header = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        return ($header === 'XMLHttpRequest');
    }


    public function setAccessDeniedRoutesDOM(callable|array $callable){
        $this->modifyRoute("403 DOM", "/403/DOM", $callable);
    }

    public function setAccessDeniedRoutesAJAX(callable|array $callable){
        $this->modifyRoute("403 AJAX", "/403/AJAX", $callable);
    }

    public function setDefaultRouteDOM(callable|array $callable){
        $this->modifyRoute("404 DOM", "/404/DOM", $callable);
    }

    public function setDefaultRouteAJAX(callable|array $callable){
        $this->modifyRoute("404 AJAX", "/404/AJAX", $callable);
    }

    private function modifyRoute(string $name, string $path, callable|array $callable){
        $this->routes[$name] = new Route($name, $path, $callable);
    }


    private function errorDOMTemplate(int $errorCode, string $message){
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

    private function initDefaultRoutes(){
        /**
         * Access denied default routes
         */

        $this->addRoute("403 DOM", "/403/DOM", function() {echo $this->errorDOMTemplate(403,"NOT THIS TIME, ACCESS FORBIDDEN!");});
        $this->addRoute("403 AJAX", "/403/AJAX", function() {echo json_encode(["error" => "403 access denied"]);});
        $this->addRoute("404 DOM", "/404/DOM", function() {echo $this->errorDOMTemplate(404, "NOT FOUND");});
        $this->addRoute("404 AJAX", "/404/AJAX", function() {echo json_encode(["error" => "404 not found"]);});
    }
}