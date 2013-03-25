<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use MineREST\Kernel;
use MineREST\Exception\LoggerException;

class Logger
{
    private $log;
    private static $instance = null;

    private function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    private function __clone()
    {
    }

    private function __construct()
    {
        if (!is_dir(__DIR__ . '/../../logs')) {
            if (!mkdir(__DIR__ . '/../../logs')) {
                throw new LoggerException('The log directory is not writable.');
            }
        }

        $this->log = new MonoLogger('MineREST');
        $this->log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/' . strtolower(Kernel::env()) . '.log'));
    }

    public static function __callStatic($name, $args)
    {
        $_this = self::getInstance();

        return call_user_func_array(array($_this->log, $name), $args);
    }
}
