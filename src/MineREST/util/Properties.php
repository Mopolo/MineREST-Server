<?php

/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\util;

class Properties {
    private static $instance;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function get($key, $default = null)
    {
        $properties = @parse_ini_file(Config::get('server.path') . '/server.properties');

        if (isset($properties[$key])) return $properties[$key];

        return $default;
    }
}