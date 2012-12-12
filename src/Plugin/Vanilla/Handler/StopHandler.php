<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;

/**
 * StopHandler : handles server stop
 */
class StopHandler extends Handler {
    public function get() {
        $out = $this->init('stop');

        return $this->ok($out);
    }
}