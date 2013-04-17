<?php

require_once '../vendor/autoload.php';

use MineREST\Kernel;

$conf = require_once '../conf.php';

Kernel::handle($conf, "dev");