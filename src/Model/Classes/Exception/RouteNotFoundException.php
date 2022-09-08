<?php

namespace Yanntyb\Router\Model\Classes\Exception;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct(string $path, string $message = ""){
        parent::__construct("Route with " . $path . " as path not found " . $message);
    }

}