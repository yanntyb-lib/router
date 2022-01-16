<?php

namespace Yanntyb\Router\Model\Classes;

use JetBrains\PhpStorm\Pure;
use ReflectionException;
use ReflectionGenerator;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];
    private Route $defaultRoute;

    public function __construct(Route $defaultRoute, bool $htaccess = true){
        $this->defaultRoute = $defaultRoute;
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
                //Si matchPath est appelé par handleQuery alors on a besoin d'appeler la route précédent la principale
                if($testPath){
                    //Si la route a une route précédente alors on va chercher celle-ci
                    if($route->getPathBeforeAccessingRouteName()){
                        //Trouve la route
                        $routeBeforeAccessingMainRoute = $this->matchPath($route->getPathBeforeAccessingRouteName());
                        //Si la route trouver est bien celle cherché et pas la defaultRoute;
                        if($route->getPathBeforeAccessingRouteName() === $routeBeforeAccessingMainRoute->getPath()){

                            //TODO check if $routeBeforeAccessingMainRoute->call() return a bool
                            //var_dump((new ReflectionGenerator($routeBeforeAccessingMainRoute->call()))->getFunction());
                            //Si le call de la route trouvé ne retourne pas true alors on va chercher la route définie pour ce cas
                            if(!$routeBeforeAccessingMainRoute->call()) {
                                //Trouve la route
                                if ($route->getPathIfRouteBeforeAccessingReturnFalse()) {
                                    $defaultsRouteIfRouteBeforeReturnFalse = $this->matchPath($route->getPathIfRouteBeforeAccessingReturnFalse());
                                    //Si la route trouvé est bien celle cherchée et pas la defaultRoute
                                    if ($route->getPathIfRouteBeforeAccessingReturnFalse() === $defaultsRouteIfRouteBeforeReturnFalse->getPath()) {

                                        return $defaultsRouteIfRouteBeforeReturnFalse;
                                    }
                                }
                                else{
                                    return $this->defaultRoute;
                                }
                            }
                        }
                    }
                }
                return $route;
            }
        }
        return $this->defaultRoute;
    }


    /**
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
     * @param string $name
     * @param string $path
     * @param callable|array $callable
     * @param string|null $pathBeforeAccessingRoute
     * @param string|null $pathIfRouteBeforeAccessingReturnFalse
     * @return $this
     * @throws ReflectionException
     * @throws RouteAlreadyExisteException
     * @throws RouteNotFoundException
     */
    public function addRoute(string $name, string $path, callable|array $callable, string $pathBeforeAccessingRoute = null, string $pathIfRouteBeforeAccessingReturnFalse = null): self
    {
        if($pathBeforeAccessingRoute){
            if(!$this->matchPath($pathBeforeAccessingRoute)){
                throw new RouteNotFoundException();
            }
            if($pathIfRouteBeforeAccessingReturnFalse){
                if(!$this->matchPath($pathIfRouteBeforeAccessingReturnFalse)){
                    throw new RouteNotFoundException();
                }
            }
        }

        $route = new Route($name,$path,$callable, $pathBeforeAccessingRoute, $pathIfRouteBeforeAccessingReturnFalse);

        if($this->has($route->getName())){
            throw new RouteAlreadyExisteException();
        }

        $this->routes[$route->getName()] = $route;
        return $this;
    }

    public function handleQuery(){
        $query = str_replace("/index.php","",$_SERVER['REQUEST_URI']);
        $this->matchPath($query,true)->call($query);
    }
}