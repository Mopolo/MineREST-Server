<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\util;

class Config
{
    private static $instance;
    private $config;

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

    public static function set($keyOrConfigArray, $value = null)
    {
        $_this = self::getInstance();

        if ($value == null) {
            $_this->config = $keyOrConfigArray;
        } else {
            $_this->config[$keyOrConfigArray] = $value;
        }
    }

    public static function get($key, $default = null)
    {
        $_this = self::getInstance();

        if (isset($_this->config[$key])) {
            return $_this->config[$key];
        }

        return $default;
    }
}
