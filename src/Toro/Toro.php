<?php

namespace Toro;

use Toro\ToroHook;
use MineREST\Logger;

class Toro {
    public static function serve($routes) {
        ToroHook::fire('before_request');

        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
        $discovered_handler = NULL;
        $regex_matches = array();

        $return = null;

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
            $IP = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $IP = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($routes[$path_info])) {
            $discovered_handler = $routes[$path_info];
        }
        elseif ($routes) {
            $tokens = array(
                ':string' => '([a-zA-Z]+)',
                ':number' => '([0-9]+)',
                ':alpha'  => '([a-zA-Z0-9-_]+)'
            );
            foreach ($routes as $pattern => $handler_name) {                
                $pattern = strtr($pattern, $tokens);
                if (preg_match('#^/?' . $pattern . '/?$#', $path_info, $matches)) {
                    $discovered_handler = $handler_name;
                    $regex_matches = $matches;
                    break;
                }
            }
        }

        if ($discovered_handler && class_exists($discovered_handler)) {
            unset($regex_matches[0]);
            $handler_instance = new $discovered_handler($request_method, $path_info, $IP);

            if (self::xhr_request() && method_exists($discovered_handler, $request_method . '_xhr')) {
                header('Content-type: application/json');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                $request_method .= '_xhr';
            }

            if (method_exists($handler_instance, $request_method)) {
                ToroHook::fire('before_handler');
                $return = call_user_func_array(array($handler_instance, $request_method), $regex_matches);
                ToroHook::fire('after_handler');
            }
            else {
                ToroHook::fire('404');
            }
        }
        else {
            ToroHook::fire('404');
        }

        ToroHook::fire('after_request');

        return $return;
    }

    private static function xhr_request() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}