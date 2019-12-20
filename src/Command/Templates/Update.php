<?php


namespace Adimeo\Deckle\Command\Templates;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends AbstractDeckleCommand
{

    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    protected function configure()
    {
        parent::configure();
        $this->setName('templates:update')
            ->setDescription('Update local templates copy');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $this->fs()->expandTilde(Install::DECKLE_HOME);

        if (!is_dir($target)) {
            Deckle::halt('No config found in "%s". You probably should reinstall Deckle', $target);
        }

        $this->templates()->fetch();

    }


}
