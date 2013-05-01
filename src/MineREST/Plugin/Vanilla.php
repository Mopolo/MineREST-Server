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
        if (Properties::get('enable-query') == 'false') {
            return $this->error('Query is not enabled.');
        }

        if (Config::get('server.ip', false) === false) {
            return $this->error("server.ip must be configured!");
        }

        $timeout = 2;

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => (int)$timeout, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => (int)$timeout, 'usec' => 0));

        if ($socket === false || @socket_connect($socket, Config::get('server.ip'), 25565) === false) {
            return $this->error("Querry error");
        }

        socket_send($socket, "\xFE\x01", 2, 0);
        $len = socket_recv($socket, $data, 512, 0);
        socket_close($socket);

        if ($len < 4 || $data[0] !== "\xFF") {
            return $this->error("Querry empty");
        }

        $data = substr($data, 3); // Strip packet header (kick message packet and short length)
        $data = iconv('UTF-16BE', 'UTF-8', $data);

        // Are we dealing with Minecraft 1.4+ server?
        if ($data[1] === "\xA7" && $data[2] === "\x31") {
            $data = explode("\x00", $data);
            $infos = array(
                'motd' => $data[3],
                'players' => intval($data[4]),
                'maxplayers' => intval($data[5]),
                'protocol' => intval($data[1]),
                'version' => $data[2]
            );
        } else {
            $data = explode("\xA7", $data);
            $infos = array(
                'motd' => substr($data[0], 0, -1),
                'players' => isset($data[1]) ? intval($data[1]) : 0,
                'maxplayers' => isset($data[2]) ? intval($data[2]) : 0,
                'protocol' => 0,
                'version' => '1.3'
            );
        }

        return $this->ok($infos);
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

        // date
        $logs = preg_replace('#[0-9-]{10,10} #', '', $logs);

        // > and < are escaped
        $logs = preg_replace('#>#', '&gt;', $logs);
        $logs = preg_replace('#<#', '&lt;', $logs);

        // console colors
        $logs = preg_replace('#\[0;39m#', '</span><span class="console-0">$2', $logs);
        $logs = preg_replace('#\[3([1-9a-f]{1,1});1m#', '</span><span class="console-$1">$2', $logs);
        $logs = preg_replace('#\[3([1-9a-f]{1,1});22m#', '</span><span class="console-$1">$2', $logs);
        $logs = preg_replace('#\[m#', '</span>', $logs);

        // when a player executes a command it displays it in a nice way
        $logs = preg_replace('#\[PLAYER_COMMAND\] ([0-9a-zA-Z-_]+): (.+)#', '// $1 <span class="console-a">$2</span>', $logs);
        $logs = preg_replace('#([0-9a-zA-Z-_]+) issued server command: (.+)#', '// $1 <span class="console-a">$2</span>', $logs);

        // WARNING in orange
        $logs = preg_replace('#\[WARNING\]#', '[<span class="console-e">WARNING</span>]', $logs);

        // SEVERE in red
        $logs = preg_replace('#\[SEVERE\]#', '[<span class="console-1">SEVERE</span>]', $logs);

        // highlights a wrong movement
        $logs = preg_replace('#moved wrongly#', '<span class="console-4">moved wrongly</span>', $logs);

        // login and logout
        $logs = preg_replace('#([0-9a-zA-Z-_]+) lost connection: (.+)#', '<span class="console-e">&lt;&lt; <span class="console-3">$1</span> logged out</span>', $logs);
        $logs = preg_replace('#([0-9a-zA-Z-_]+)\[/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]{1,5}\] logged in with entity id ([0-9]+) at (.+)#', '<span class="console-e">&gt;&gt; <span class="console-3">$1</span> logged in.</span>', $logs);

        return $this->okHTML(nl2br($logs));
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
