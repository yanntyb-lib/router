<?php

namespace Yanntyb\Router\Classes\DOT;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use Yanntyb\Router\Classes\Exception\ClassNotFound;
use Yanntyb\Router\Classes\Exception\MethodeNotFound;

class Route
{
    private string $name;
    private string $path;
    private $callable;

    private string|null $pathBeforeAccessingRouteName = null;
    private string|null $pathIfRouteBeforeAccessingReturnFalse;
    private array|string $directBeforeCallback = [];
    private array|string $directAfterCallback = [];

    private bool $ajax = false;
    private bool $checkHeader = true;
    private bool $groupePermission = true;

    private bool $isPost = false;

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
        $this->pathIfRouteBeforeAccessingReturnFalse = "/403/DOM";
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
     * @param bool $return
     * @return Route
     * @throws ClassNotFound
     * @throws MethodeNotFound
     */
    public function call(string $path, bool $return = true): mixed
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
                try{
                    $reflectionFunc = (new ReflectionClass($this->callable[0]))->getMethod($this->callable[1]);
                }
                catch(ReflectionException $e){
                    throw new ClassNotFound($this->callable[0],$this->getPath());
                }
            }
            else{
                try{
                    $reflectionFunc = new ReflectionFunction($this->callable);
                }
                catch(ReflectionException $e){
                    throw new MethodeNotFound($this->getPath());
                }
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
        if($return){
            return call_user_func_array($callable, $argsValue);
        }
        call_user_func_array($callable, $argsValue);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPathBeforeAccessingRouteName(): ?string
    {
        return $this->pathBeforeAccessingRouteName;
    }


    /**
     * @return string|null
     */
    public function getPathIfRouteBeforeAccessingReturnFalse(): ?string
    {
        return $this->pathIfRouteBeforeAccessingReturnFalse;
    }

    /**
     * Make Route only accessible with AJAX call
     * @return $this
     */
    public function isAjax(): self
    {
        $this->ajax = true;
        $this->pathIfRouteBeforeAccessingReturnFalse = "/403/AJAX";
        return $this;
    }

    public function getAjax(): bool
    {
        return $this->ajax;
    }

    /**
     * Change $pathBeforeAccessingRouteName
     * @return $this
     */
    public function needPermission(string $path): self
    {
        $this->pathBeforeAccessingRouteName = $path;
        return $this;
    }

    public function defaultRoute(string $path): self{
        $this->pathIfRouteBeforeAccessingReturnFalse = $path;
        return $this;
    }

    public function noCheckHeader(): self{
        $this->checkHeader = false;
        return $this;
    }

    public function getCheckHeader(): bool{
        return $this->checkHeader;
    }


    public function needGlobalPermission(): bool
    {
        return $this->groupePermission;
    }

    public function noGroupePermission(): self{
        $this->groupePermission = false;
        return $this;
    }

    /**
     * @return array
     */
    public function getBeforeCallback(): array
    {
        return $this->directBeforeCallback;
    }

    /**
     * @param string[] $directCallback
     * @return self
     */
    public function setBeforeCallback(array|string $directCallback): self
    {
        $this->directBeforeCallback = $directCallback;
        return $this;
    }

    /**
     * @return array
     */
    public function getDirectAfterCallback(): array|string
    {
        return $this->directAfterCallback;
    }

    /**
     * @param array|string $directAfterCallback
     * @return self
     */
    public function setAfterCallback(array|string $directAfterCallback): self
    {
        if(is_string($directAfterCallback)) $directAfterCallback = [$directAfterCallback];
        $this->directAfterCallback = $directAfterCallback;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRequestMethode(): bool
    {
        return $this->isPost;
    }

    /**
     * @param bool $isPost
     * @return self
     */
    public function isPost(): self
    {
        $this->isPost = true;
        return $this;
    }


}