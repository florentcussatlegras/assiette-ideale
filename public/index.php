<?php

use App\Kernel;
use App\CacheKernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// dans public/index.php ou config/bootstrap.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');


return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};