<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Service\Recipes\TemplatesManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TemplatesList extends AbstractDeckleCommand
{

    use TemplatesHelper;

    protected function configure()
    {
        parent::configure();
        $this->setName("templates:list")
            ->setAliases(['rl'])
            ->setDescription("List available recipes");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>Locally available templates</info>');
        $output->writeln('');

        $templatesLocation = $this->expandTilde('~/.deckle/cache');

        $providers = $this->loadGlobalConfiguration()['providers'];

        chdir($this->getDeckleHomeDirectory() . '/cache');
        foreach ($providers as $provider) {
            $output->writeln('From <comment>' . $provider . '</comment>');
            $vendors = new \DirectoryIterator($this->sanitizeProviderName($provider));
            foreach ($vendors as $vendor) {

                if (!$vendor->isDir() || $vendor->isDot() || $vendor->getBasename() == '.git') {
                    continue;
                }
                $templates = new \DirectoryIterator($vendor->getRealPath());

                foreach ($templates as $template) {
                    if (!$template->isDir() || $template->isDot()) {
                        continue;
                    }
                    $output->writeln("\t<info>" . $vendor->getBasename() . '/' . $template->getBasename());
                }
            }
        }

        $output->writeln('');

    }

}
