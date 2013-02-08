<?php

// Composer autoloading
require __DIR__ . '/../vendor/autoload.php';

use MineREST\Kernel;

$config = array(
    'security.ip' => 'change this to your api client ip',
    'server.jar' => 'craftbukkit.jar',
    'server.path' => '/home/minecraft/minecraft',
    'server.script' => '/etc/init.d/minecraft',
    'database.host' => 'localhost',
    'database.port' => 3306,
    'database.username' => 'root',
    'database.password' => '',
    'database.base' => 'minecraft'
);

// Kernel launch (with environment)
$response = Kernel::handle('dev', $config);
// We send the Response
$response->send();