<?php
require_once __DIR__ . '/vendor/autoload.php';

use Adimeo\Deckle\AppKernel;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Kernel;

$cwd = getcwd();
$env = 'prod';
$debug =  false;
error_reporting(0);



$kernel = new AppKernel($env, $debug);
$kernel->boot();
$app = $kernel->getContainer()->get(Deckle::class);
$app->run();

