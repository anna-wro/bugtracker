<?php

function __autoload($class)
{
    $path = preg_replace('/\\\/', '/', $class);
    require_once dirname(dirname(__FILE__)).'/'.$path.'.php';
}
