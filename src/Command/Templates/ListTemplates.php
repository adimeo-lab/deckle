<?php


namespace Adimeo\Deckle\Command\Templates;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListTemplates extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{

    protected function configure()
    {
        $this->setName("templates:list")
            ->setDescription("List available templates");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Deckle::print('');
        Deckle::print('<info>Locally available templates</info>');
        Deckle::print('');

        $templatesLocation = $this->fs()->expandTilde('~/.deckle/cache');

        $providers = $this->getConfig()['providers'];

        $currentWorkingDirectory = getcwd();
        chdir($this->templates()->getPath() . '/cache');
        foreach ($providers as $provider) {
            Deckle::print('From <comment>' . $provider . '</comment>');
            $vendors = new \DirectoryIterator($this->templates()->sanitizeProviderName($provider));
            foreach ($vendors as $vendor) {

                if (!$vendor->isDir() || $vendor->isDot() || $vendor->getBasename() == '.git') {
                    continue;
                }
                $templates = new \DirectoryIterator($vendor->getRealPath());

                foreach ($templates as $template) {
                    if (!$template->isDir() || $template->isDot()) {
                        continue;
                    }
                    Deckle::print("\t<info>" . $vendor->getBasename() . '/' . $template->getBasename() );
                }
            }
        }
        Deckle::print('');

        chdir($currentWorkingDirectory);
    }

}
