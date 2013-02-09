<?php

namespace MineREST;

use Toro\Toro;
use MineREST\HTTP\Response;
use MineREST\Config;

class Kernel {
    private static $env;

    public static function handle($env = 'prod', $config) {
        self::$env = $env;

        // big badass try catch to send a JSON response if any error occurs
        try {
            Config::set($config);

            if (Config::get('security.https', false) === true) {
                if($_SERVER["HTTPS"] != "on") {
                    return new Response(Response::ERROR, 'HTTPS is enabled.');
                }
            }

            // first we generate the cache if necessairy (dev environment or cache not created)
            $cache = new Cache();
            if (self::$env == 'dev' || !$cache->exists()) {
                $cache->generate();
                Logger::addInfo('Cache generation');
            }

            // For now the only security is the ip adress of the client:
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
                $IP = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $IP = $_SERVER['REMOTE_ADDR'];
            }

            $ips = Config::get('security.ip', '127.0.0.1');
            if (!is_array($ips)) $ips = array($ips);

            if (!in_array($IP, $ips)) {
                return new Response(Response::FORBIDDEN);
            }

            // we get the routing cache
            $routes = require __DIR__ . '/../../cache/routes.php';

            // pass it to Toro
            $response = Toro::serve($routes);

            // If the response is null that means the request doesn't exist
            // Then we create an error Response 
            if ($response == null) {
                $response = new Response(Response::NOT_FOUND, 'Invalid request.');
            }
        } catch (Exception $e) {
            // If we get here that means something anormal occured
            $response = new Response(Response::ERROR, $e->getMessage());
        }

        return $response;
    }

    // Static method to get the environment anywhere in the API
    public static function getEnv() {
        return self::$env;
    }
}