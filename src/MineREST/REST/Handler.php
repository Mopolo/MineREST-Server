<?php

namespace MineREST\REST;

use MineREST\Config;
use MineREST\HTTP\Response;
use MineREST\Logger;

/**
 * Handler
 * Abstract handler class
 *
 * This class contains several methods to interact with the Minecraft server
 */
class Handler {
    protected $script;
    protected $request_method;
    protected $path_info;
    protected $IP;
    protected $data = array();

    public function __construct($request_method, $path_info, $IP) {
        $this->request_method = $request_method;
        $this->path_info = $path_info;
        $this->IP = $IP;
        $this->script = Config::get('server.script');

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

        Logger::addInfo('[ACCESS][' . $this->IP . '] ' . strtoupper($this->request_method) . ' ' . $this->path_info, $this->data);
    }

    // To execute a bash command
    // The & char is not allowed to avoid multiple commands
    protected function shell($cmd) {
        if (preg_match('#&#', $cmd)) {
            return false;
        }
        
        return shell_exec($cmd);
    }

    // To execute the init script
    protected function init($cmd) {
        return $this->shell($this->script . ' ' . $cmd);
    }

    // To execute a Minecraft command in-game
    protected function minecraft($cmd) {
        return $this->shell($this->script . ' command "' . $cmd . '"');
    }
    
    // To make the server talk
    protected function say($message) {
        $message = preg_replace("#'#", "'\''", $message);
        return $this->shell($this->script . ' say "' . $message . '"');
    }

    // To get the server status
    protected function isRunning() {
        $status = $this->init('status');
        
        if ($status == Config::get('server.jar', 'craftbukkit.jar') . " is running.\n") return true;
        
        return false;
    }

    // This method return an instance of MineREST\HTTP\Response
    // Use it for successfull requests
    protected function ok($data = array()) {
        if (!is_array($data)) {
            $data = array('message' => $data);
        }

        return new Response(Response::OK, $data);
    }

    // This method return an instance of MineREST\HTTP\Response
    // Use it for requests with errors
    protected function error($message) {
        return new Response(Response::ERROR, $message);
    }

    protected function database() {
        if ($this->database === null) {
            $host = Config::get('database.host');
            $port = Config::get('database.port');
            $usernme = Config::get('database.username');
            $password = Config::get('database.password');
            $base = Config::get('database.base');
            
            try {
                $this->database = new \PDO("mysql:host=$host:$port;dbname=$base", $username, $password);
            } catch (\Exception $e) {
                $this->database = false;
                Logger::addError('The API could not connect to the database, check the global config file.');
            }
        }
        
        return $this->database;
    }
}