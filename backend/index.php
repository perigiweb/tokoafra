<?php declare(strict_types=1);

$rootPath = dirname(__DIR__);

define('SECTION', 'backend');
define('BASEPATH', __DIR__);
define('ROOTPATH', $rootPath);
define('ASSETPATH', dirname(__DIR__) . '/public/assets');
define('APPPATH', $rootPath .  '/app/multi-gudang');
define('THEMEPATH', $rootPath .  '/app/themes');
define('TMPPATH', $rootPath . '/tmp');

unset($rootPath);

require ROOTPATH . '/bootstrap.php';