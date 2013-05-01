<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST;

use MineREST\Logger;
use MineREST\http\Response;
use MineREST\util\Config;

class MineRESTPlugin
{
    protected $database;
    protected $script;
    protected $request_method;
    protected $data = array();

    public function setScript($script) {
        $this->script = $script;
    }

    // To execute a bash command
    // The & char is not allowed to avoid multiple commands
    protected function shell($cmd)
    {
        if (preg_match('#&#', $cmd)) {
            return false;
        }

        return shell_exec($cmd);
    }

    // To execute the init script
    protected function init($cmd)
    {
        return $this->shell(Config::get('server.script') . ' ' . $cmd);
    }

    // To execute a Minecraft command in-game
    protected function minecraft($cmd)
    {
        return $this->init('command "' . $cmd . '"');
    }

    // To make the server talk
    protected function say($message)
    {
        $message = preg_replace("#'#", "'\''", $message);
        return $this->init('say "' . $message . '"');
    }

    // To get the server status
    protected function isRunning()
    {
        $status = $this->init('status');

        if ($status == Config::get('server.jar') . " is running.\n") {
            return true;
        }

        return false;
    }

    protected function db()
    {
        if ($this->database === null) {
            $host = Config::get('database.host');
            $port = Config::get('database.port');
            $username = Config::get('database.username');
            $password = Config::get('database.password');
            $base = Config::get('database.base');

            try {
                $this->database = new \PDO("mysql:host=$host:$port;dbname=$base", $username, $password);
            } catch (\Exception $e) {
                $this->database = false;
                Logger::addError('The API could not connect to the database, check the config.');
            }
        }

        return $this->database;
    }

    public function setRequestMethod($request_method, $data = null)
    {
        $this->request_method = $request_method;

        switch ($this->request_method) {
            case 'get':
                $this->data = $_GET;
                break;
            case 'post':
                $this->data = $_POST;
                break;
            case 'put':
            case 'delete':
                parse_str(file_get_contents('php://input'), $put_vars);
                $this->data = $put_vars;
                break;
        }

        if ($data != null) {
            $this->data = array_merge($this->data, $data);
        }
    }

    // This method return an instance of MineREST\http\Response
    // Use it for successfull requests
    protected function ok($data = array())
    {
        if (!is_array($data)) {
            $data = array('message' => $data);
        }

        return new Response(Response::OK, $data);
    }

    protected function okHTML($html) {
        return new Response(Response::OK, $html, true);
    }

    // This method return an instance of MineREST\http\Response
    // Use it for requests with errors
    protected function error($message)
    {
        return new Response(Response::ERROR, $message);
    }
}
