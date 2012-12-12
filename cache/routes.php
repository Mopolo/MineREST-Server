<?php

$routes = array(
	'/Vanilla/start' => 'Plugin\Vanilla\Handler\StartHandler',
	'/Vanilla/stop' => 'Plugin\Vanilla\Handler\StopHandler',
	'/Vanilla/restart' => 'Plugin\Vanilla\Handler\RestartHandler',
	'/Vanilla/players' => 'Plugin\Vanilla\Handler\PlayersHandler',
	'/Vanilla/whitelist' => 'Plugin\Vanilla\Handler\WhitelistHandler',
	'/Vanilla/logs/:number' => 'Plugin\Vanilla\Handler\LogsHandler',
	'/Vanilla/gamemode' => 'Plugin\Vanilla\Handler\GamemodeHandler',
	'/Vanilla/command' => 'Plugin\Vanilla\Handler\CommandHandler'
);

return $routes;

?>