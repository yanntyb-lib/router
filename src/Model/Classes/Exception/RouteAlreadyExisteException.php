<?php

namespace Yanntyb\Router\Model\Classes\Exception;

use Exception;

class RouteAlreadyExisteException extends Exception
{
    public function __construct($route){
        parent::__construct($route . " route already exists");
    }
}