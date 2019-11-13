<?php
require_once __DIR__ . '/vendor/autoload.php';
error_reporting(E_ALL);
use Adimeo\Deckle\AppKernel;
use Symfony\Component\Console\Application;
$cwd = getcwd();
$env = getenv('APP_ENV') ?: 'prod';
if($env == 'prod')
{
  $debug = false;
} else {
    $debug = true;
}
try {
    $kernel = new AppKernel($env, $debug);
    $kernel->boot();
    $app = $kernel->getContainer()->get(Application::class);
    $app->run();
}catch (\Throwable $e) {
    chdir($cwd);
    printf('Uncaught exception %s thrown in %s:%s' . PHP_EOL, get_class($e), $e->getFile(), $e->getLine());
    echo 'Message: ' . $e->getMessage() . PHP_EOL;
}


