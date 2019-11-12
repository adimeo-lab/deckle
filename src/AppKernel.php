<?php

namespace Adimeo\Deckle;


use Adimeo\Deckle\Command\Drupal8\Drupal8CommandInterface;
use Adimeo\Deckle\DependencyInjection\CompilerPass\CommandsRegistration;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(dirname(__DIR__) . '/config/services.yml');
    }

    protected function build(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addCompilerPass(new CommandsRegistration());
        //$containerBuilder->registerForAutoconfiguration(DeckleDrupal8CommandInterface::class)->addTag('deckle.drupal8');
    }

}
