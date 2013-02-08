<?php

namespace MineREST;

use Spyc;
use MineREST\Exception;
use MineREST\Logger;

class Config {
    const NOT_FOUND = '%notfound%';
    
    private static $instance;
    
    private $config;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct() {}
    private function __clone() {}

    public static function set($config) {
        $_this = self::getInstance();
        
        $_this->config = $config;
    }

    public static function get($value, $default = null) {
        $_this = self::getInstance();
        
        if (isset($_this->config[$value])) {
            return $_this->config[$value];
        }

        return $default;
    }
}