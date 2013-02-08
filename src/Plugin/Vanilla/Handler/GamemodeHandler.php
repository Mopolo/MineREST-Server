<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;

/**
 * StatusHandler
 */
class StatusHandler extends Handler {
    public function post() {
        if ($this->isRunning()) {
            return $this->ok("on");
        }

        return $this->ok("off");
    }
}