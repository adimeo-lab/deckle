<?php

namespace Adimeo\Deckle\Command\Deckle\Installer;

use Adimeo\Deckle\Deckle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MacOsInstaller extends AbstractUnixInstaller
{

    protected $packageInstallCommand = 'brew install';


    protected function configure()
    {
        $this->setName('install:macos')
            ->setHidden(true)
            ->setDescription('Create (or reset) default Deckle configuration and clone templates locally')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Should existing configuration be overwritten?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        Deckle::print('Installing deckle on <info>macOs</info>');
        Deckle::br();


        $this->installTemplates();
        $this->installBrew();
        $this->installGit();
        $this->installDocker();
        $this->installMutagen();
        $this->installVm();
        $this->setUpHosts();
        $this->installDnsmasq();
        $this->setUpResolver();
        $this->copyId();

        return 0;
    }

    protected function installBrew()
    {
        if (!$this->fs()->isInPath('brew')) {
            Deckle::print('<info>Homebrew</info> seems missing from your system.');
            if ($this->confirm('Would you like to install <info>Homebrew</info> now?')) {
                $homebrewUrl = 'https://raw.githubusercontent.com/Homebrew/install/master/install';
                $this->sh()->exec(
                    '/usr/bin/ruby -e "$(curl -fsSL ' . $homebrewUrl . ')"',
                    null, false
                );
            } else {
                Deckle::error('Cannot install <info>Deckle</info> on macOs without <info>Homebrew</info>');
                return 1;
            }
        } else {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>Homebrew</info> is already installed.');
            }
        }
    }

    protected function setUpResolver()
    {
        if (!is_dir('/etc/resolver')) {
            Deckle::print('Creating <info>/etc/resolver</info> directory');
            $this->sh()->exec('sudo bash -c "mkdir /etc/resolver"');
        }

        if (!is_file('/etc/resolver/deckle.local')) {
            Deckle::print(
                'Generating resolver for <info>*.deckle.local</info> in <info>/etc/resolver/deckle.local</info>'
            );
            $this->sh()->exec('sudo bash -c "echo \'nameserver 127.0.0.1\' > /etc/resolver/deckle.local"');
        } else {
            if (trim(file_get_contents('/etc/resolver/deckle.local')) != 'nameserver 127.0.0.1') {
                Deckle::print(
                    'Updating resolver for <info>*.deckle.local</info> in <info>/etc/resolver/deckle.local</info>'
                );
                $this->sh()->exec('sudo bash -c "echo \'nameserver 127.0.0.1\' > /etc/resolver/deckle.local"');
            }
        }

    }


    protected function guessShell()
    {
        $parts = explode('/', getenv('SHELL'));
        return array_pop($parts);
    }

}
