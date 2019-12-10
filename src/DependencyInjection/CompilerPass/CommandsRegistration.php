<?php


namespace Adimeo\Deckle\DependencyInjection\CompilerPass;


use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CommandsRegistration implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder): void
    {

        $applicationDefinition = $containerBuilder->getDefinition(Deckle::class);

        foreach ($containerBuilder->getDefinitions() as $name => $definition) {
            if (is_a($definition->getClass(), Command::class, true)) {
                $applicationDefinition->addMethodCall('add', [new Reference($name)]);
            }
        }
    }
}
