<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\http;

use MineREST\Exception\RouterException;

class Router
{
    public static function run()
    {
        // first we get the requested url and method
        $requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
        $requestUrl = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';

        // we need the cached routes
        if (!file_exists(__DIR__ . '/../../../cache/routes.php')) {
            throw new RouterException();
        }

        $routes = require __DIR__ . '/../../../cache/routes.php';

        // error 404
        if (!isset($routes[$requestUrl][strtoupper($requestMethod)])) {
            throw new RouterException();
        }

        $route = $routes[$requestUrl][strtoupper($requestMethod)];

        call_user_func_array($route, array());
    }
}
