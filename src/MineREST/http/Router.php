<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\http;

class Router
{
    public static function run()
    {
        $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $requestUrl = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';


    }
}
