<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace {
    require_once __DIR__ . "/../annotations.php";
}

namespace MineREST {

    use MineREST\Exception\KernelException;
    use MineREST\Exception\ForbiddenAccessException;
    use MineREST\util\Config;
    use MineREST\Exception\LoggerException;

    class Kernel
    {
        private static $env;

        public static function handle($config, $env = "prod")
        {
            try {
                self::run($config, $env);
            } catch (LoggerException $loggerException) {

            } catch (KernelException $kernelException) {

            } catch (ForbiddenAccessException $forbiddenAccessException) {

            } catch (\Exception $exception) {

            }
        }

        private static function run($config, $env)
        {
            $envs = array("prod", "dev");

            if (!in_array($env, $envs)) {
                throw new KernelException("$env is not a valid environment value. Valid values are: " . implode(" ", $envs));
            }

            self::$env = $env;

            Config::set($config);

            if (Config::get('security.https', false) === true) {
                if ($_SERVER["HTTPS"] != "on") {
                    throw new KernelException('SSL is mandatory.');
                }
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $IP = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $IP = $_SERVER['REMOTE_ADDR'];
            }

            if (Config::get('security.ip') != $IP) {
                throw new ForbiddenAccessException();
            }

            if (!file_exists(__DIR__ . "/../../cache/routes.php") || self::$env == 'dev') {
                $pluginsDir = opendir(__DIR__ . "/Plugin");
                $routes = "<?php\n";
                while ($entry = @readdir($pluginsDir)) {
                    if (!is_dir($entry)) {
                        $className = preg_replace("#\.php#", '', $entry);
                        $reflection = new \ReflectionClass('Plugin\\' . $className);

                        foreach ($reflection->getMethods() as $method) {
                            $ref = new \ReflectionAnnotatedMethod('Plugin\\' . $className, $method->name);
                            $routes .= "\$routes['/$className" . $ref->getAnnotation('Route')->value . "']['" . strtoupper($ref->getAnnotation('Method')->value) . "'] = '" . $className . '::' . $method->name . "';\n";
                        }
                    }
                }
                closedir($pluginsDir);
                file_put_contents(__DIR__ . "/../../cache/routes.php", $routes);
            }

            //return Router::run();
        }

        public static function env()
        {
            return self::$env;
        }
    }
}