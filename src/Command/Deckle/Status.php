<?php
namespace Adimeo\Deckle\Command\Deckle;

use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Misc\NetTool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Status extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('status')
            ->setDescription('Display various information about current Deckle project.')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if(!is_file('./deckle/deckle.yml')) {
            Deckle::halt('It seems you are not in a deckle project directory. Use <info>deckle bootstrap</info> to make your project available to deckle.');
        }

        // deckle
        Deckle::print("Deckle version \t<info>%s</info>", $this->getVersion());
        Deckle::br();

        // project
        Deckle::print('Project');
        Deckle::print(" - name \t<info>%s</info>", $this->getConfig()['project']['name']);
        Deckle::print(" - type \t<info>%s</info>", $this->getConfig()['project']['type']);
        Deckle::br();

        // VM
        Deckle::print('Deckle Machine');
        $ip = $this->vm()->ip();
        $vmHost = $this->getConfig()['vm']['host'];
        $ping = NetTool::ping($vmHost) ? 'OK' : 'KO';
        Deckle::print(sprintf(" - host \t<info>%s</info> (ip: %s, ping is %s)", $vmHost, $ip, $ping));
        Deckle::print(sprintf(" - user \t<info>%s</info>", $this->getConfig()['vm']['user']));
        Deckle::print('');

        // Docker
        Deckle::print('Docker');
        $dockerHost = $this->getConfig()['docker']['host'];
        $status = $this->docker()->isRunning() ? 'OK' : 'KO';
        Deckle::print(" - host \t<info>%s</info> (status: %s)", [$dockerHost, $status]);
        Deckle::print(" - path \t<info>%s</info>", $this->getConfig()['vm']['user']);
        Deckle::br();

    }

}
