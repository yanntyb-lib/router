<?php

namespace Yanntyb\Router\Model\Classes;

use Exception;

class ClassNotFound extends Exception
{
    public function __construct(string $class,string $route){
        parent::__construct($class . " not found at " . $route);
    }
}