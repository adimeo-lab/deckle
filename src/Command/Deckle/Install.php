<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class Install extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{

    use TemplatesHelper;

    const DECKLE_HOME = '~/.deckle';

    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Create (or reset) default Deckle configuration and clone templates locally')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Should existing configuration be overwritten?')
            ->addOption('vmip', null, InputOption::VALUE_OPTIONAL, 'Deckle VM IP', 'auto')
            ->addOption('brew-binary', null, InputOption::VALUE_OPTIONAL, 'Homebrew binary path',
                '/usr/local/bin/brew');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->error('This feature is not stable yet, sorry!');
        return 1;
        $fs = new Filesystem();
        $this->output = $output;
        $this->input = $input;
        $target = $this->getDeckleHomeDirectory();
        $helper = $this->getHelper('question');

        if (is_dir($target)) {
            $overwrite = false;
            if ($input->isInteractive() && !$input->getOption('reset')) {

                $question = new ConfirmationQuestion('<question>Deckle configuration folder "' . $target . '" already exists. Should it be overwritten? [y/N]</question>',
                    false);
                $overwrite = $helper->ask($input, $output, $question);
            } elseif ($input->getOption('reset')) {
                $overwrite = true;
            }

            if ($overwrite) {
                if ($input->getOption('verbose')) {
                    $output->writeln('<info>Deleting "' . $target . '" directory...');
                }
                // preserve existing custom config
                if(file_exists($target . '/deckle.local.yml')) {
                    $localConfig = file_get_contents($target . '/deckle.local.yml');
                }
                $fs->remove($target);
                if (is_dir($target)) {
                    $this->error('Failed deleting "%s"', [$target]);
                }

            } else {
                $this->error('Cannot install Deckle because of a previous installation not having been cleared. Please use "--reset" switch if you want to force reinstallation.');
                return;
            }



        }


        $output->writeln(sprintf('Creating "<info>%s"</info>" directory...', $target));


        mkdir($target);
        $output->writeln(sprintf('Copying default Deckle configuration to <info>%s</info>', $target));
        $defaultConf = <<<END
providers:
  - https://github.com/adimeo-lab/deckle-templates
END;

        file_put_contents($target . '/deckle.conf.yml', $defaultConf );

        // restore local config
        if(isset($localConfig)) {
            file_put_contents($target . '/deckle.local.yml', $target . '/deckle.local.yml' );
        }

        $cwd = getcwd();
        chdir($target);
        if (!is_dir('cache')) {
            mkdir('cache', 0755);
        }
        chdir('cache');
        $this->cacheTemplates();
        chdir($cwd);


        // brew dependencies
        if (is_file($input->getOption('brew-binary'))) {
            $this->output->writeln('Installing <info>docker</info> (if needed)');
            shell_exec('brew install docker');
            $this->output->writeln('Installing <info>dnsmasq</info>(if needed)');
            shell_exec('brew install dnsmasq');
            $this->output->writeln('Setting up <info>dnsmasq</info>');
            if ($vmip = $input->getOption('vmip') == 'auto') {
                $vmip = $this->findVmAddressInHosts() ?: '10.211.55.6'; // default ip for original vm
                $question = new ConfirmationQuestion('<question>What is your deckle VM IP addresse? [' . $vmip . ']</question>',
                    true);
                $answer = $helper->ask($input, $output, $question);
                $vmip = $answer ?: $vmip;
                $output->writeln('Generating <info>dnsmasq</info> configuration in <info>/usr/local/etc/dnsmasq.conf</info>');
                file_put_contents('/usr/local/etc/dnsmasq.conf', "\n" . 'address=/.deckle.local/' . $vmip . "\n",
                    FILE_APPEND);
                if (!is_dir('/etc/resolver')) {
                    $output->writeln('Creating <info>/etc/resolver</info> directory');
                    shell_exec('sudo bash -c "mkdir /etc/resolver"');
                }
                if (file_exists('/etc/resolver/deckle.local')) {
                    shell_exec('sudo bash -c "rm /etc/resolver/deckle.local"');
                }
                $output->writeln('Generating resolver for <info>*.deckle.local</info> in <info>/etc/resolver/docker.local</info>');
                shell_exec('sudo bash -c "echo \'nameserver 127.0.0.1\' >> /etc/resolver/deckle.local"');


            }


        } else {
            $this->error('Homebrew does not seem to be installed on this system. Binary file <comment>%s</comment> was not found. If it has been installed in another location, plese specify it using <comment>--brew-binary=/path/to/brew</comment>. Otherwise, please install brew (<info>http://brew.sh</info>) and reinstall Deckle.',
                [$input->getOption('brew-binary')]);
        }
    }


}
