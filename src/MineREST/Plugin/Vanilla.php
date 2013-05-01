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
use MineREST\util\Properties;
use MinecraftQuery\MinecraftQuery;
use MinecraftQuery\MinecraftQueryException;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

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
    public function ping()
    {
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
        if ($this->isRunning() === true) {
            $query['status'] = 'on';
        } else {
            $query['status'] = 'off';
        }

        return $this->ok($query);
    }

    /**
     * @Route('/infos')
     * @Method('GET')
     */
    public function infos()
    {
        if (Properties::get('enable-query') == false) {
            return $this->error('Query is not enabled.');
        }

        $port = Properties::get('query.port', 25565);

        $q = new minecraftQuery();

        try {
            $q->connect('localhost', $port, 3);
            return $this->ok(array('infos' => $q->getInfo()));
        } catch (MinecraftQueryException $e) {
            return $this->error('Query error: ' . $e->getMessage());
        }
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

        $filename = realpath(Config::get('server.path', '/home/minecraft/minecraft') . '/server.log');
        $lines_to_display = $this->data['lines'];

        if (!$open_file = fopen($filename, 'r')) {
            return false;
        }

        $pointer = -2; // Ignore new line characters at the end of the file
        $char = '';
        $beginning_of_file = false;
        $lines = array();

        for ($i = 1; $i <= $lines_to_display; $i++) {
            if ($beginning_of_file == true) {
                continue;
            }

            /**
             * Starting at the end of the file, move the pointer back one
             * character at a time until it lands on a new line sequence.
             */
            while ($char != "\n") {
                // If the beginning of the file is passed
                if (fseek($open_file, $pointer, SEEK_END) < 0) {
                    $beginning_of_file = true;
                    // Move the pointer to the first character
                    rewind($open_file);
                    break;
                }

                // Subtract one character from the pointer position
                $pointer--;

                // Move the pointer relative to the end of the file
                fseek($open_file, $pointer, SEEK_END);

                // Get the current character at the pointer
                $char = fgetc($open_file);
            }

            array_push($lines, fgets($open_file));

            // Reset the character.
            $char = '';
        }

        // Close the file.
        fclose($open_file);

        $logs = implode("", array_reverse($lines));

        $converter = new AnsiToHtmlConverter();

        return $this->okHTML(nl2br($converter->convert($logs)));
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
