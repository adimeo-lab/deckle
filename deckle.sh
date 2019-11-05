#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use Adimeo\Deckle\AppKernel;
use Symfony\Component\Console\Application;
$cwd = getcwd();
try {
    $kernel = new AppKernel('dev', true);
    $kernel->boot();
    $app = $kernel->getContainer()->get(Application::class);
    $app->run();
}catch (\Throwable $e) {
    chdir($cwd);
}
