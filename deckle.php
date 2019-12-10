<?php
require_once __DIR__ . '/vendor/autoload.php';

use Adimeo\Deckle\AppKernel;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Kernel;

$cwd = getcwd();
$env = getenv('APP_ENV') ?: 'dev';
$debug = getenv('DEBUG') ?: false;

if($env == 'dev') {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

try {
    $app = new Deckle();
    $app->run();
} catch (\Throwable $e) {
    chdir($cwd);
    printf('Uncaught exception %s thrown in %s:%s' . PHP_EOL, get_class($e), $e->getFile(), $e->getLine());
    echo 'Message: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString();
}


