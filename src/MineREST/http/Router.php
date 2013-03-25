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
use MineREST\Kernel;

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

        $plugin = null;

        foreach ($routes as $url => $route) {
            if (preg_match('#' . $url . '#', $requestUrl, $matches)) {
                for ($i = 0; $i < count($route[strtoupper($requestMethod)][2]); $i++) {
                    $_GET[$route[strtoupper($requestMethod)][2][$i]] = $matches[$i + 1];
                }
                $plugin = new $route[strtoupper($requestMethod)][0];
                $plugin->setRequestMethod($requestMethod);
                $method = $route[strtoupper($requestMethod)][1];
                break;
            }
        }

        // error 404
        if ($plugin == null) {
            throw new RouterException();
        }

        $response = $plugin->$method();

        if (!($response instanceof Response) && Kernel::env() == 'prod') {
            $response = new Response(Response::ERROR);
        }

        $response->send();
    }
}
