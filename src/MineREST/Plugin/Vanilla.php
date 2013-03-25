<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\Plugin;

use MineREST\MineRESTPlugin;
use MineREST\util\Config;

class Vanilla extends MineRESTPlugin
{
    private $players;

    public function __construct()
    {
        // We list all the whitelisted players
        $plrs = $this->shell('cat ' . realpath(Config::get('server.path') . '/white-list.txt'));

        $plrs = explode("\n", $plrs);

        if (count($plrs) == 0) {
            $this->players = array();
        } else {
            foreach ($plrs as $player) {
                if (substr($player, 0, 1) != '#' && strlen($player) > 0) $this->players[] = $player;
            }
        }
    }

    /**
     * @Route('/whitelist')
     * @Method("GET")
     */
    public function whitelistGet()
    {
        return $this->ok(array('players' => $this->players));
    }

    /**
     * @Route("/whitelist")
     * @Method('PUT')
     */
    public function whitelistAdd()
    {
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

    /**
     * @Route("/whitelist")
     * @Method("DELETE")
     */
    public function whitelistDelete()
    {
        if (!isset($this->data['player'])) {
            return $this->error("Parameter missing: player");
        }

        for ($i = 0; $i < count($this->players); $i++) {
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
