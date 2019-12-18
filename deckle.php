<?php
require_once __DIR__ . '/vendor/autoload.php';

use Adimeo\Deckle\AppKernel;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
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
    $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());
    $output->error($e->getMessage());
}


