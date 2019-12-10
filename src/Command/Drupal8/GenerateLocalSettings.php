<?php


namespace Adimeo\Deckle\Command\Drupal8;


use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLocalSettings extends AbstractDrupal8Command
{
    protected function configure()
    {
        $this->setName('drupal8:generate:settings')
            ->setAliases(['d8:gls'])
            ->addOption('template', 't',InputOption::VALUE_OPTIONAL, 'Template file to generate settings.local.php', 'deckle/settings.local.dist.php')
            ->setDescription('Generate settings.local.php using deckle configuration');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $templateFile = $input->getOption('template');
        if(!is_file($templateFile)) {
            $this->error('Template file "%s" not found', [$templateFile]);
        }
        $this->copyTemplateFile($templateFile, 'web/sites/default/settings.local.php');
    }


}
