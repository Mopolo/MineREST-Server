<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;
use MineREST\Config;

/**
 * LogsHandler
 */
class LogsHandler extends Handler {
    public function get($lines) {
        $logs = $this->shell('tail -n ' . $lines . ' ' . Config::get('server.path', '/home/minecraft/minecraft') . '/server.log');

        return $this->ok(array('logs' => $logs));
    }
}