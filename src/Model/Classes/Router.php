<?php

namespace Yanntyb\Router\Model\Classes;

use JetBrains\PhpStorm\Pure;
use ReflectionException;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];
    private Route $defaultRoute;

    public function __construct(Route $defaultRoute, bool $htaccess = false){
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

    private function getRouteCollection(): array{
        return $this->getRoutes();
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable|array $callable
     * @return $this
     * @throws RouteAlreadyExisteException
     */
    public function addRoute(string $name, string $path, callable|array $callable): self
    {
        $route = new Route($name,$path,$callable);

        if($this->has($route->getName())){
            throw new RouteAlreadyExisteException();
        }

        $this->routes[$route->getName()] = $route;
        return $this;
    }

    /**
     * @param string $name
     * @return Route|null
     * @throws RouteNotFoundException
     */
    private function getRoute(string $name): Route{
        if(!$this->has($name)){
            throw new RouteNotFoundException();
        }
        return $this->getRoutes()[$name];
    }

    /**
     * @param string $path
     * @return Route
     */
    private function matchPath(string $path): Route{
        foreach ($this->getRoutes() as $route) {
            if($route->test($path)){
                return $route;
            }
        }
        return $this->defaultRoute;
    }

    /**
     * @param string $path
     * @return false|mixed
     * @throws ReflectionException
     */
    private function call(string $path){
        return $this->matchPath($path)->call($path);
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

    public function handleQuery(){
        $query = str_replace("/index.php","",$_SERVER['REQUEST_URI']);
        $this->matchPath($query)->call($query);
    }
}