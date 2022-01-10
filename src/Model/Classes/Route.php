<?php

namespace Yanntyb\Router\Model\Classes;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;

class Route
{
    private string $name;
    private string $path;
    /**
     * @var array|callable
     */
    private $callable;

    /**
     * @param string $name
     * @param string $path
     * @param array|callable $callable
     */
    public function __construct(string $name, string $path, callable|array $callable)
    {
        $this->name = $name;
        $this->path = $path;
        $this->callable = $callable;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function test(string $path): bool{
        $pattern = str_replace("/","\/", $this->getPath());
        $pattern = sprintf("/^%s$/",$pattern);
        $pattern = preg_replace("/(\{\w+\})/", "(.+)", $pattern);
        return preg_match_all($pattern, $path);
    }

    /**
     * @param string $path
     * @return false|mixed
     * @throws ReflectionException
     */
    public function call(string $path): mixed
    {
        $pattern = str_replace("/","\/", $this->getPath());
        $pattern = sprintf("/^%s$/",$pattern);
        $pattern = preg_replace("/(\{\w+\})/", "(.+)", $pattern);
        preg_match($pattern, $path, $matches);
        preg_match_all("/\{(\w+)\}/", $this->getPath(), $paramMatches);
        array_shift($matches);
        $parameters = $paramMatches[1];

        $argsValue = [];

        if(count($parameters) > 0){
            $parameters = array_combine($parameters, $matches);
            if(is_array($this->callable)){
                $reflectionFunc = (new ReflectionClass($this->callable[0]))->getMethod($this->callable[1]);
            }
            else{
                $reflectionFunc = new ReflectionFunction($this->callable);
            }

            $args = array_map(fn (ReflectionParameter $params) => $params->getName(), $reflectionFunc->getParameters());

            $argsValue = array_map(function(string $name) use ($parameters){
                return $parameters[$name];
            }, $args );
        }

        $callable = $this->callable;
        if(is_array($callable)){
            $callable = [new $callable[0](), $callable[1]];
        }

        return call_user_func_array($callable, $argsValue);
    }


}