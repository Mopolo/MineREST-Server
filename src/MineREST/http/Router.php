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
        $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        // we need the cached routes
        if (!file_exists(__DIR__ . '/../../../cache/routes.php')) {
            throw new RouterException();
        }

        $routes = require __DIR__ . '/../../../cache/routes.php';

        $plugin = null;
        $data = array();

        foreach ($routes as $url => $route) {
            if (preg_match('#' . $url . '#', $requestUrl, $matches) && isset($route[strtoupper($requestMethod)])) {
                if (count($matches) > 1) {
                    for ($i = 0; $i < count($route[strtoupper($requestMethod)][2]); $i++) {
                        $data[$route[strtoupper($requestMethod)][2][$i]] = $matches[$i + 1];
                    }
                }
                $plugin = new $route[strtoupper($requestMethod)][0];
                $plugin->setRequestMethod($requestMethod, $data);
                $plugin->setScript(Config::get('server.script', '/etc/init.d/minecraft'));
                $method = $route[strtoupper($requestMethod)][1];
                break;
            }
        }

        // error 404
        if ($plugin == null) {
            throw new RouterException("Error 404");
        }

        $response = $plugin->$method();

        if (!($response instanceof Response) && Kernel::env() == 'prod') {
            $response = new Response(Response::ERROR);
        }

        $response->send();
    }
}
