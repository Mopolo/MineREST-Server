<?php

namespace MineREST;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use MineREST\Kernel;

/**
 * Logger
 * Logging class wich uses Monolog
 *
 * All static calls are converted in methods on a Monolog Logger :
 *     MineREST\Logger::addInfo('Something');
 *     becomes
 *     $this->log->addInfo('Something');
 */
class Logger {
    private $log;
    private static $instance = null;

    private function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    private function __clone() {}

    private function __construct() {
        $this->log = new MonoLogger('MineREST');
        $this->log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/' . Kernel::getEnv() . '.log'));
    }

    public static function __callStatic($name, $args) {
        $_this = self::getInstance();

        return call_user_func_array(array($_this->log, $name), $args);
    }
}