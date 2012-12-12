<?php

namespace MineREST;

class Exception extends \Exception {
    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    const ALERT = 550;
    const EMERGENCY = 600;

    private $levels = array(
        100 => 'Debug',
        200 => 'Info',
        250 => 'Notice',
        300 => 'Warning',
        400 => 'Error',
        500 => 'Critical',
        550 => 'Alert',
        600 => 'Emergency'
    );

    public function __construct($message, $level) {
        parent::__construct($message);

        $call = 'add' . $this->levels[$level];
        Logger::$call($message);
    }
}