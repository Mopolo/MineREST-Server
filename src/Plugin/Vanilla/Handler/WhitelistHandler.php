<?php

namespace Plugin\Vanilla\Handler;

use MineREST\REST\Handler;
use MineREST\Config;

/**
 * WhitelistHandler : Handles operations on the whitelist
 *
 * GET : list all whitelisted players
 * PUT : add a player to the whitelist
 * DELETE : remove a plyer from the whitelist
 *
 * All methods work even if the server is not running
 * 
 */
class WhitelistHandler extends Handler {
    private $players;

    function __construct($request_method, $path_info, $IP) {
        parent::__construct($request_method, $path_info, $IP);

        // We list all the whitelisted players
        $plrs = $this->shell('cat ' . Config::get('server.path') . '/white-list.txt');

        $plrs = explode("\n", $plrs);
        
        if (count($plrs) == 0) {
            $this->players = array();
        } else {
            foreach ($plrs as $player) {
                if (substr($player, 0, 1) != '#' && strlen($player) > 0) $this->players[] = $player;
            }
        }
    }

    // list of whitelisted players
    public function get() {
        return $this->ok(array('players' => $this->players));
    }

    // add a specified player to the whitlelist
    public function put() {
        // The player name must be specified.
        // If not, we send an error
        if (!isset($this->data['player'])) {
            return $this->error('Parameter missing: player');
        }

        // If the server is running we use the init script
        if ($this->isRunning()) {
            $this->init('whitelist-add ' . $this->data['player']);
        // If the server is not running, we edit the white-list.txt file
        } else {
            if (!in_array($this->data['player'], $this->players)) {
                $this->players[] = $this->data['player'];
                file_put_contents(Config::get('server.path') . '/white-list.txt', implode("\n", $this->players));
            }
        }

        return $this->ok();
    }

    // delete a specified player from the whitelist
    // This function works like the put() method
    public function delete() {
        if (!isset($this->data['player'])) {
            return $this->error("Parameter missing: player");
        }

        for ($i=0; $i < count($this->players); $i++) { 
            if ($this->players[$i] == $this->data['player']) {
                unset($this->players[$i]);

                $this->players = array_values($this->players);

                file_put_contents(Config::get('server.path') . '/white-list.txt', implode("\n", $this->players));

                break;
            }
        }
        
        return $this->ok();
    }
}