<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;

/**
 * CommandHandler
 */
class CommandHandler extends Handler {
    // Executes a command in-game
    public function post() {
        if (!$this->isRunning()) {
            return $this->error("The server is not running");
        }

        if (!isset($this->data['command'])) {
            return $this->error('Parameter missing: command');
        }

        $this->minecraft($this->data['command']);

        return $this->ok();
    }
}