<?php

namespace Adimeo\Deckle\Command\Deckle\Installer;

use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LinuxInstaller extends AbstractUnixInstaller
{

    protected $packageInstallCommand = 'sudo apt-get install';



    protected function configure()
    {
        $this->setName('install:linux')
            ->setHidden(true)
            ->setDescription('Create (or reset) default Deckle configuration and clone templates locally')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Should existing configuration be overwritten?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       // dd($input->getOptions());
        Deckle::print('Installing deckle on <info>Linux</info>');
        Deckle::br();

        $this->installTemplates();
        $this->installGit();
        $this->installDocker();
        $this->installMutagen();
        $this->installVm();
        if(!$this->vm()->ip()) {
            Deckle::error('Deckle Machine seems not to be set up correctly. Please check its status and run "install" again');
        }
        $this->setUpHosts();
        $this->installDnsmasq();
        $this->setUpResolver();
        $this->copyId();

        return 0;
    }

    protected function installMutagen()
    {
        $mutagenInstalled = $this->fs()->isInPath('mutagen');

        if ($mutagenInstalled) {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>mutagen</info> is already installed');
            }
            return;
        }

        Deckle::print('Installing <info>mutagen</info> from <info>tarball</info>');
        $this->sh()->exec('sudo apt install wget');
        $cwd = chdir('/tmp');
        $this->sh()->exec('wget https://github.com/mutagen-io/mutagen/releases/download/v0.10.2/mutagen_linux_amd64_v0.10.2.tar.gz');
        $this->sh()->exec('tar -xf mutagen_linux_amd64_v0.10.2.tar.gz');
        $this->exec('move mutagen /usr/local/bin');
        $this->exec('move mutagen-agents.tar.gz /usr/local/bin');
        $this->sh()->exec('rm mutagen_linux_amd64_v0.10.2.tar.gz');
        chdir($cwd);
    }

    protected function installDnsmasq()
    {
        $dnsmasqInstalled = $this->fs()->isInPath('dnsmasq');

        if ($dnsmasqInstalled) {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>dnsmasq</info> already installed');
            }
        } else {
            Deckle::print('Installing <info>dnsmasq</info> using <info>apt</info>');
            $this->sh()->exec('sudo apt-get install dnsmasq');
        }
        
        $resolvconfInstalled = $this->fs()->isInPath('resolvconf');

        if ($resolvconfInstalled) {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>resolvconf</info> already installed');
            }
        } else {
            Deckle::print('Installing <info>resolvconf</info> using <info>apt</info>');
            $this->sh()->exec('sudo apt-get install resolvconf');
        }


        // setting up dnsmasq
        if (is_file('/etc/dnsmasq.conf')) {
            $conf = file('/etc/dnsmasq.conf');
            $newLline = 'address=/.deckle.local/' . $this->vm()->ip() . PHP_EOL;
            foreach ($conf as &$line) {
                if (preg_match('/address=\/.deckle.local\/(.*)/', $line, $matches)) {
                    if (trim($line) != trim($newLline)) {
                        Deckle::print('Updating <info>dnsmasq</info> configuration in <info>/etc/dnsmasq.conf</info>');
                        $line = $newLline;
                        exec('sudo chmod o+w /etc/dnsmasq.conf');
                        file_put_contents('/etc/dnsmasq.conf', implode("", $conf));
                        exec('sudo chmod o-w /etc/hosts');
                        Deckle::print('Restarting <info>dnsmasq</info>...');
                        $this->sh()->exec('sudo service dnsmasq restart');
                        return;
                    }
                }
            }

            // config file exists but does not contain deckle.local related configuration
            Deckle::print('Updating <info>dnsmasq</info> configuration in <info>/etc/dnsmasq.conf</info>');
            exec('sudo chmod o+w /etc/dnsmasq.conf');
            file_put_contents('/etc/dnsmasq.conf', $newLline, FILE_APPEND);
            exec('sudo chmod o-w /etc/dnsmasq.conf');
            Deckle::print('Restarting <info>dnsmasq</info>...');
            $this->sh()->exec('sudo service dnsmasq restart');
        } else {
            Deckle::print('Generating <info>dnsmasq</info> configuration in <info>/etc/dnsmasq.conf</info>');
            exec('sudo chmod o+w /etc/dnsmasq.conf');
            file_put_contents('/etc/dnsmasq.conf',
                "\n" . 'address=/.deckle.local/' . $this->vm()->ip() . "\n");
            exec('sudo chmod o-w /etc/dnsmasq.conf');
            Deckle::print('Restarting <info>dnsmasq</info>...');
            $this->sh()->exec('sudo service dnsmasq restart');

        }

    }



    protected function setUpResolver()
    {
        if (!is_dir('/etc/resolver')) {
            Deckle::print('Creating <info>/etc/resolver</info> directory');
            $this->sh()->exec('sudo bash -c "mkdir /etc/resolver"');
        }

        if (!is_file('/etc/resolver/deckle.local')) {
            Deckle::print('Generating resolver for <info>*.deckle.local</info> in <info>/etc/resolver/deckle.local</info>');
            $this->sh()->exec('sudo bash -c "echo \'nameserver 127.0.0.1\' > /etc/resolver/deckle.local"');
        } else {
            if (trim(file_get_contents('/etc/resolver/deckle.local')) != 'nameserver 127.0.0.1') {
                Deckle::print('Updating resolver for <info>*.deckle.local</info> in <info>/etc/resolver/deckle.local</info>');
                $this->sh()->exec('sudo bash -c "echo \'nameserver 127.0.0.1\' > /etc/resolver/deckle.local"');
            }
        }

    }

}
