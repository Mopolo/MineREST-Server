<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST;
use MineREST\Exception\KernelException;
use MineREST\http\Router;
use MineREST\Exception\ForbiddenAccessException;
use MineREST\util\Config;
use MineREST\Exception\LoggerException;
use Addendum\ReflectionAnnotatedMethod;

class Kernel
{
    const VERSION = '0.1';
    private static $env;

    public static function handle($config, $env = "prod")
    {
        try {
            self::run($config, $env);
        } catch (LoggerException $loggerException) {
            echo "loggerException";
        } catch (KernelException $kernelException) {
            echo "kernelException";
        } catch (ForbiddenAccessException $forbiddenAccessException) {
            echo "forbiddenAccessException";
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    private static function run($config, $env)
    {
        $envs = array("prod", "dev");
        if (!in_array($env, $envs)) {
            $env = "dev";
        }

        self::$env = $env;

        Config::set($config);

        if (Config::get('security.https', false) === true) {
            if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
                throw new ForbiddenAccessException('SSL is mandatory.');
            }
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $IP = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $IP = $_SERVER['REMOTE_ADDR'];
        }

        if (is_array(Config::get('security.ip'))) {
            if (!in_array($IP, Config::get('security.ip'))) {
                throw new ForbiddenAccessException();
            }
        } elseif (Config::get('security.ip') != $IP) {
            throw new ForbiddenAccessException();
        }

        if (!file_exists(__DIR__ . "/../../cache/routes.php") || self::$env == 'dev') {
            $pluginsDir = opendir(__DIR__ . "/Plugin");
            $out = "<?php\n";
            while ($entry = @readdir($pluginsDir)) {
                if (!is_dir($entry)) {
                    $className = preg_replace("#\.php#", '', $entry);
                    $reflection = new \ReflectionClass('MineREST\\Plugin\\' . $className);

                    foreach ($reflection->getMethods() as $method) {
                        if ($method->class == 'MineREST\\Plugin\\' . $className && !preg_match('#__#', $method->name)) {
                            $ref = new ReflectionAnnotatedMethod('MineREST\\Plugin\\' . $className, $method->name);
                            $params = $ref->getAnnotation('Params');
                            if ($params !== false) {
                                $params = $params->value;
                                for ($i = 0; $i < count($params); $i++) {
                                    $params[$i] = "'" . $params[$i] . "'";
                                }
                                $params = implode(',', $params);
                                $out .= "\$routes['/$className" . $ref->getAnnotation('Route')->value . "']['" . strtoupper($ref->getAnnotation('Method')->value) . "'] = array('MineREST\\Plugin\\" . $className . '\', \'' . $method->name . "', array(" . $params . "));\n";
                            } else {
                                $out .= "\$routes['/$className" . $ref->getAnnotation('Route')->value . "']['" . strtoupper($ref->getAnnotation('Method')->value) . "'] = array('MineREST\\Plugin\\" . $className . '\', \'' . $method->name . "');\n";
                            }
                        }
                    }
                }
            }
            $out .= "return \$routes;";
            closedir($pluginsDir);
            @mkdir(__DIR__ . "/../../cache/");
            if (file_put_contents(__DIR__ . "/../../cache/routes.php", $out) === false) {
                throw new KernelException("Unable to write in cache directory.");
            }
        }

        return Router::run();
    }

    public static function env()
    {
        return self::$env;
    }
}