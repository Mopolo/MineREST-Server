<?php

// Composer autoloading
require __DIR__ . '/../vendor/autoload.php';

use MineREST\Kernel;

// Kernel launch (with environment)
$response = Kernel::handle('dev');
// We send the Response
$response->send();