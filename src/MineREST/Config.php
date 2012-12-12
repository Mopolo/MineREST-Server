<?php

namespace MineREST;

use Spyc;
use MineREST\Exception;
use MineREST\Logger;

class Config {
    const NOT_FOUND = '%notfound%';
    
    private static $instance;
    
    private $config;
    private static $default;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = require __DIR__ . '/../../cache/config.php';
    }
    
    private function __clone() {}

    public static function generateDefault() {
        $default = array(
            'security' => array(
                'ip' => 'change this to your api client ip'
            ),
            'server' => array(
                'jar' => 'craftbukkit.jar',
                'path' => '/home/minecraft/minecraft',
                'script' => '/etc/init.d/minecraft'
            ),
            'database' => array(
                'host' => 'localhost',
                'port' => 3306,
                'username' => 'root',
                'password' => '',
                'base' => 'minecraft'
            )
        );

        file_put_contents(__DIR__ . '/../../config/config.yml', Spyc::YAMLDump($default, 4));

        Logger::addInfo('The main config file is missing. Writing a default one.');
    }

    public static function get($value, $default = null) {
        $_this = self::getInstance();
        
        if (isset($_this->config[$value])) {
            return $_this->config[$value];
        }

        return $default;
    }

    public static function exists() {
        return file_exists(__DIR__ . '/../../config/config.yml');
    }
}