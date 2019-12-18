<?php


namespace Adimeo\Deckle\Command\Vm;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SshCopyId extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{
    protected function configure()
    {
        $this->setName('vm:ssh:copy-id')
            ->setDescription('Copy your SSH public key to Deckle Machine')
            ->addOption('identity', 'i', InputOption::VALUE_OPTIONAL, 'RSA public key to copy to Deckle Machine', '~/.ssh/id_rsa.pub')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getConfig();
        $key = $input->getOption('identity');

        $machine = $this->getDeckleMachineLocation();
        $host = $machine->getHost();
        $user = $machine->getUser();

        if(!$host || !$user) {
            $this->error('No Deckle Machine configuration found. If you\‘re outside of a Deckle project, please define vm[host] and vm[user] in your %s/deckle.local.yml configuration file.', [InstallMacOs::DECKLE_HOME]);
        }

        $output->writeln('Copying <info>' .  $key . '</info> to <comment>'. $user . '@' . $host .'</comment> ...');
        $return  =$this->sh()->exec(sprintf('ssh-copy-id -i %s %s@%s', $key, $user, $host));

        if(!$return->isErrored()) {
            $this->output()->success('Your RSA Id has been successfully copied to your Deckle Machine');
        } else {
            $message = ['Something went wrong while copying your RSA Id to your Deckle Machine.'];
            if(!$output->isVerbose()) {
                $message[] = 'Use "-v" to get more information.';
            } else {
                $message[] = 'More details about this error below.';
            }
            $this->output->warning($message);
            if($this->output()->isVerbose()) {
                $output->writeln($return->getOutput());
            }
        }

    }


}
