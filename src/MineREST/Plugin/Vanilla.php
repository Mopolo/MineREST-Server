<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\Plugin;

use MineREST\Kernel;
use MineREST\MineRESTPlugin;
use MineREST\util\Config;

class Vanilla extends MineRESTPlugin
{
    const GAMEMODE_SURVIVAL = 0;
    const GAMEMODE_CREATIVE = 1;
    const GAMEMODE_ADVENTURE = 2;

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
     * @Route('/ping')
     * @Method('GET')
     */
    public function ping() {
        return $this->ok(array('version' => Kernel::VERSION));
    }

    /**
     * @Route('/start')
     * @Method('GET')
     */
    public function start()
    {
        return $this->ok($this->init('start'));
    }

    /**
     * @Route('/stop')
     * @Method('GET')
     */
    public function stop()
    {
        return $this->ok($this->init('stop'));
    }

    /**
     * @Route('/restart')
     * @Method('GET')
     */
    public function restart()
    {
        return $this->ok($this->init('restart'));
    }

    /**
     * @Route('/kill')
     * @Method('GET')
     */
    public function kill()
    {
        return $this->ok($this->init('kill'));
    }

    /**
     * @Route('/status')
     * @Method('GET')
     */
    public function status()
    {
        $query = array();

        $beginning_time = microtime(true);

        $socket = @fsockopen('localhost', 25566, $errno, $errstr, 30);

        if ($socket) {
            $end_time = microtime(true);

            fwrite($socket, "QUERY\n");

            $response = "";

            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }

            $response = explode("\n", $response);

            // Player count
            $query['playerCount'] = explode(" ", $response[1], 2);
            $query['playerCount'] = $query['playerCount'][1];

            // Max players
            $query['maxPlayers'] = explode(" ", $response[2], 2);
            $query['maxPlayers'] = $query['maxPlayers'][1];

            // Player list
            $query['playerList'] = explode(" ", $response[3], 2);
            $query['playerList'] = explode(", ", trim($query['playerList'][1], "[]"));

            $query['latency'] = ($end_time - $beginning_time) * 1000;
        }

        if ($this->isRunning()) {
            $query['status'] = 'on';
            //return $this->ok(array("status" => "on"));
        }

        $query['status'] = 'off';
        //return $this->ok(array("status" => "off"));

        return $this->ok($query);
    }

    /**
     * @Route('/logs/?([0-9]+)?')
     * @Params({'lines'})
     * @Method('GET')
     */
    public function getLogs()
    {
        if (!isset($this->data['lines'])) {
            $this->data['lines'] = 30;
        }

        $logs = $this->shell('tail -n ' . $this->data['lines'] . ' ' . Config::get('server.path', '/home/minecraft/minecraft') . '/server.log');
        return $this->ok(array('logs' => $logs));
    }

    /**
     * @Route('/whitelist')
     * @Method('GET')
     */
    public function whitelistGet()
    {
        return $this->ok(array('players' => $this->players));
    }

    /**
     * @Route('/whitelist')
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
     * @Route('/whitelist')
     * @Method('DELETE')
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

    /**
     * @Route('/gamemode')
     * @Method('POST')
     */
    public function setGamemode()
    {
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
            case self::GAMEMODE_SURVIVAL:
            case 'survival':
                $gamemode = 'survival';
                break;

            case self::GAMEMODE_CREATIVE:
            case 'creative':
                $gamemode = 'creative';
                break;

            case self::GAMEMODE_ADVENTURE:
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
