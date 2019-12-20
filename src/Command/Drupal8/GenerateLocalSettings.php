<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLocalSettings extends AbstractDrupal8Command
{
    protected function configure()
    {
        $this->setName('drupal8:gls')
            ->setDescription('Generate "settings.local.php" using Deckle project configuration');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $templateFile = 'deckle/settings.local.dist.php';
        if(!is_file($templateFile)) {
            Deckle::error('Template file "%s" not found', [$templateFile]);
        }
        $this->copyTemplateFile($templateFile, 'web/sites/default/settings.local.php');

        return 0;
    }


}
