<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;

/**
 * RestartHandler : handles server restarting
 */
class RestartHandler extends Handler {
    public function get() {
        $out = $this->init('restart');

        return $this->ok($out);
    }
}