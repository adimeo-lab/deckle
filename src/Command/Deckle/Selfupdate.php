<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Selfupdate extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('selfupdate')
            ->addOption('unstable', null, InputOption::VALUE_NONE, 'Allow updating to unstable version')
            ->setDescription('Update deckle binary to latest version (if available)')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $updater = new Updater(null, false);



        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setPackageName('adimeo-lab/deckle');
        $updater->getStrategy()->setPharName('deckle.phar');
        $updater->getStrategy()->setCurrentLocalVersion('@package_version@');

        if($input->getOption('unstable')) {
            $updater->getStrategy()->setStability('unstable');
        }
        //$updater->getStrategy()->setPharUrl('http://deckle.adimeo.eu/releases/latest.phar');
        //$updater->getStrategy()->setVersionUrl('http://deckle.adimeo.eu/releases/latest.version');
        try {
            $result = $updater->update();
            if ($result) {
                $new = $updater->getNewVersion();
                $old = $updater->getOldVersion();
                Deckle::print(sprintf('Deckle has been updated from <info>%s</info> to <info>%s</info>', $old, $new));

            } else {
                $stability = $input->getOption('unstable') ? 'unstable' : 'stable';
                Deckle::print('You are already running the latest deckle ' . $stability . ' version: <info>' . $this->getVersion() . '</info>');
            }
        } catch (\Throwable $e) {
            Deckle::error('Something wen wrong while trying to update deckle binary. Use -v to get more information.', [], $e);
        }
    }
}
