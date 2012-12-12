<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;

/**
 * StartHandler : handles server starting
 */
class StartHandler extends Handler {
    public function get() {
        $out = $this->init('start');

        return $this->ok($out);
    }
}