<?php

namespace Yanntyb\Router\Model\Classes;

use Exception;

class MethodeNotFound extends Exception
{
    public function __construct(string $path){
        parent::__construct("Callable not found at " . $path);
    }
}