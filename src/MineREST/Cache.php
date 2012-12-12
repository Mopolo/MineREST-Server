<?php

namespace MineREST;

use Spyc;
use MineREST\Exception;
use MineREST\Config;

class Cache {
    public function __construct() {}

    public function generate() {
        // if the cache directory doesn't exist we create it
        if (!$this->exists()) {
            if (!@mkdir(__DIR__ . '/../../cache')) {
                throw new Exception("MineREST is unable to create the cache directory.", Exception::CRITICAL);
            }
        // if the directory exists we delete and recreate it
        } else {
            if (!@$this->rrmdir(__DIR__ . '/../../cache')) {
                throw new Exception("MineREST is unable to delete the cache directory.", Exception::ERROR);
            }

            if (!@mkdir(__DIR__ . '/../../cache')) {
                throw new Exception("MineREST is unable to create the cache directory.", Exception::CRITICAL);
            }
        }

        // if we get here, then we can generate the cache
        $plugins_dir = opendir(__DIR__ . '/../Plugin');
        $out = "<?php\n\n\$routes = array(\n";
        while ($dir = @readdir($plugins_dir)) { // we read the plugins directory and look for the good ones
            if (is_dir(__DIR__ . '/../Plugin/' . $dir) && $dir != '.' && $dir != '..') {
                if ($this->dir_valid($dir)) {
                    // if the plugin is valid, we generate its cache
                    $plugin_name = $dir;

                    $yml = Spyc::YAMLLoad(__DIR__ . '/../Plugin/' . $plugin_name . '/config/routing.yml');
                    foreach ($yml as $handler => $route) {
                        $routes[] = array(
                            'route' => '/' . $plugin_name . $route,
                            'handler' => 'Plugin\\' . $plugin_name . '\Handler\\' . ucfirst($handler) . 'Handler'
                        );
                    }

                    $i = 0;
                    foreach ($routes as $route) {
                        $i++;
                        $out .= "\t'" . $route['route'] . "' => '" . $route['handler'] . "'";
                        if ($i < count($routes)) $out .= ",";
                        $out .= "\n";
                    }

                    $out .= ");\n\nreturn \$routes;\n\n?>";

                    file_put_contents(__DIR__ . '/../../cache/routes.php', $out);
                }
            }
        }

        if (!Config::exists()) Config::generateDefault();
        
        $config = Spyc::YAMLLoad(__DIR__ . '/../../config/config.yml');

        $out = "<?php\n\n\$config = array(\n";
        
        /*
        $out .= "\t'security.ips' => array(";
        for ($i=0; $i < count($config['security']['ips']); $i++) { 
            $config['security']['ips'][$i] = "'" . $config['security']['ips'][$i] . "'";
        }

        $out .= implode(', ', $config['security']['ips']) . "),\n";
        */

        $out .= "\t'security.ip' => '" . $config['security']['ip'] . "',\n";
        $out .= "\t'server.jar' => '" . $config['server']['jar'] . "',\n";
        $out .= "\t'server.path' => '" . $config['server']['path'] . "',\n";
        $out .= "\t'server.script' => '" . $config['server']['script'] . "',\n";
        $out .= "\t'database.host' => '" . $config['database']['host'] . "',\n";
        $out .= "\t'database.port' => '" . $config['database']['port'] . "',\n";
        $out .= "\t'database.username' => '" . $config['database']['username'] . "',\n";
        $out .= "\t'database.password' => '" . $config['database']['password'] . "',\n";
        $out .= "\t'database.base' => '" . $config['database']['base'] . "'\n";
        
        $out .= ");\n\nreturn \$config;\n\n?>";

        file_put_contents(__DIR__ . '/../../cache/config.php', $out);
    }

    /**
     * This function checks the validity of a Plugin directory
     *
     * To be valid, a Plugin directory needs:
     *  - a /config directory wich contains:
     *      - routing.yml
     *  - a /Handler directory
     */
    private function dir_valid($name) {
        $dir = __DIR__ . '/../Plugin/' . $name;

        $errors = 0;

        if (!is_dir($dir . '/config')) {
            $errors++;
            Logger::addError('[Plugin ' . $name . '] The ' . $name . 'plugin directory is not valid :');
            Logger::addError('[Plugin ' . $name . '] The config directory is missing.');
        } else {
            if (!file_exists($dir . '/config/routing.yml')) {
                $errors++;
                if ($errors == 1) Logger::addError('[Plugin ' . $name . '] The ' . $name . 'plugin directory is not valid :');
                Logger::addError('[Plugin ' . $name . '] The routing.yml file is missing in the config directory.');
            }
        }

        if (!is_dir($dir . '/Handler')) {
            $errors++;
            if ($errors == 1) Logger::addError('[Plugin ' . $name . '] The ' . $name . 'plugin directory is not valid :');
            Logger::addError('[Plugin ' . $name . '] The Handler directory is missing.');
        }

        if ($errors > 0) {
            Logger::addError('[Plugin ' . $name . '] -------------------------- END --------------------------');
            return false;
        }
        
        return true;
    }

    public function exists() {
        if (is_dir(__DIR__ . '/../../cache')) return true;

        return false;
    }

    private function rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file)) {
                rrmdir($file);
            } else {
                @unlink($file);
            }
        }

        return @rmdir($dir);
    }
}