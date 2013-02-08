<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;

/**
 * GamemodeHandler
 */
class GamemodeHandler extends Handler {
    const SURVIVAL = 0;
    const CREATIVE = 1;

    public function post() {
        if (!$this->isRunning()) {
            return $this->error("The server is not running");
        }

        if (!isset($this->data['player'])) {
            return $this->error('Parameter missing: player');
        }

        if (!isset($this->data['gamemode'])) {
            return $this->error('Parameter missing: gamemode');
        }

        $player = $this->data['player'];

        switch ($this->data['gamemode']) {
            case 0:
            case '0':
            case 'survival':
                $gamemode = 'survival';
                break;

            case 1:
            case '1':
            case 'creative':
                $gamemode = 'creative';
                break;

            case 2:
            case '2':
            case 'adventure':
                $gamemode = 'adventure';
                break;
            
            default:
                $gamemode = 'survival';
                break;
        }

        $this->minecraft("gamemode $gamemode $player");

        return $this->ok();
    }
}