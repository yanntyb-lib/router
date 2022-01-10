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

    public function __construct(Route $defaultRoute){
        $this->defaultRoute = $defaultRoute;
    }

    public function getRouteCollection(): array{
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
    public function getRoute(string $name): Route{
        if(!$this->has($name)){
            throw new RouteNotFoundException();
        }
        return $this->getRoutes()[$name];
    }

    /**
     * @param string $path
     * @return Route
     */
    public function matchPath(string $path): Route{
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
    public function call(string $path){
        return $this->matchPath($path)->call($path);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool{
        return isset($this->getRoutes()[$name]);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function handleQuery(string $query){
        $this->matchPath($query)->call($query);
    }
}