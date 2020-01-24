<?php


namespace Adimeo\Deckle\Command\Deckle\Installer;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\ProjectIndependantCommandInterface;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;

class AbstractUnixInstaller extends AbstractDeckleCommand implements ProjectIndependantCommandInterface
{

    /**
     * @var string 
     */
    protected $packageInstallCommand;

    protected $packages = [];

    public function installTemplates()
    {

        $target = $this->templates()->getPath();

        if (is_dir($target)) {
            $overwrite = false;
            if (Deckle::input(null)->getOption('reset')) {
                $overwrite = true;
            } elseif (Deckle::input(null)->isInteractive()) {
                $overwrite = Deckle::confirm(sprintf(
                    'Deckle configuration folder "%s" already exists. Should it be overwritten?',
                    $target
                ));
            }

            if (!$overwrite) {
                Deckle::halt(
                    [
                    'Cannot install Deckle because of a previous installation not having been cleared.',
                    '',
                    'Please use "--reset" switch if you want to force reinstall.'
                    ]
                );
            }
        }

        if (!is_dir($target)) {
            Deckle::print(sprintf('Creating "<info>%s"</info>" directory...', $target));
            mkdir($target);
        }

        Deckle::print(sprintf('Copying default Deckle configuration to <info>%s</info>', $target));
        $defaultConf = <<<END
providers:
  - https://github.com/adimeo-lab/deckle-templates

vm:
  host: deckle-vm
  user: deckle
  
docker:
  host: deckle-vm:4243

END;

        file_put_contents($target . '/deckle.conf.yml', $defaultConf);

        // restore local config
        if (isset($localConfig)) {
            file_put_contents($target . '/deckle.local.yml', $target . '/deckle.local.yml');
        }

        // reload config
        $this->loadConfig();

        $cwd = getcwd();
        chdir($target);
        if (!is_dir('cache')) {
            mkdir('cache', 0755);
        }
        chdir('cache');
        $this->templates()->fetch();
        chdir($cwd);
    }


    protected function setUpHosts()
    {
        $hosts = file('/etc/hosts');
        foreach ($hosts as &$line) {
            if (preg_match('/(\d+\.\d+\.\d+\.\d+)\s+(.*?deckle-vm.*)/', $line, $matches)) {
                if ($matches[1] != $this->vm()->ip()) {
                    Deckle::print('Updating <info>deckle-vm</info> entry in <info>/etc/hosts</info>');
                    $newLine = $this->vm()->ip() . "\t" . $matches[2] . "\n";
                    $line = $newLine;
                    exec('sudo chmod o+w /etc/hosts');
                    file_put_contents('/etc/hosts', implode("", $hosts));
                    exec('sudo chmod o-w /etc/hosts');
                    return;
                }
            }
        }

        // no previous entry was found, appending the new entry
        Deckle::print('Adding <info>deckle-vm</info> entry in <info>/etc/hosts</info>');
        $newLine = $this->vm()->ip() . "\tdeckle-vm\n";
        exec('sudo chmod o+w /etc/hosts');
        file_put_contents('/etc/hosts', $newLine, FILE_APPEND);
        exec('sudo chmod o-w /etc/hosts');
    }

    protected function copyId()
    {
        if (is_file($this->fs()->expandTilde('~/.ssh/id_rsa'))) {
            Deckle::runCommand('vm:ssh:copy-id');
        } else {
            Deckle::note(
                [
                    'No RSA key detected.',
                    'You should create one and copy it to your', 'Deckle Machine using "deckle vm:ssh:copy-id"'
                ]
            );
        }
    }

    protected function installVm()
    {
        // check vagrant availability
        if (!$this->fs()->isInPath('vagrant')) {
            Deckle::print('Vagrant not detected. Trying to install Vagrant.');
            $return = $this->sh()->exec('sudo apt -y install vagrant', null, false);
            if ($return->isErrored()) {
                Deckle::warning(
                    'Vagrant installation seems to have failed.
                    Please manually install Vagrant from "https://www.vagrantup.com/downloads.html"
                    and run "deckle install" again.'
                );
                return;
            }

        }


        // check vagrant folder
        $vagrantPath = $this->fs()->expandTilde('~/.deckle/vagrant');

        if (!is_dir($vagrantPath)) {
            mkdir($vagrantPath);
            Deckle::print('Cloning Deckle Vagrant VM configuration in <info>' . $vagrantPath . '</info>');
            // TODO make vagrant configuration file dynamic
            $this
                ->git()
                ->clone(
                    'https://github.com/adimeo-lab/deckle-vagrant', new LocalPath(dirname($vagrantPath)),
                    'vagrant'
                );
            Deckle::print('Provisioning Deckle Vagrant VM <info>' . $vagrantPath . '</info>');
            $return = $this->sh()->exec('vagrant up', new LocalPath($vagrantPath), false);
        } else {
            if (!$this->git()->isUpToDate(new LocalPath('~/.deckle/vagrant'))) {
                Deckle::print(
                    'Fetching the latest Deckle Vagrant VM configuration in <info>' . $vagrantPath . '</info>'
                );
                $this->git()->pull(new LocalPath($vagrantPath));
                Deckle::print('Provisioning Deckle Vagrant VM <info>' . $vagrantPath . '</info>');
                $return = $this->sh()->exec('vagrant up --provision', new LocalPath($vagrantPath), false);
            } else {
                Deckle::print('Deckle Vagrant VM configuration is <info>up to date</info>');
                if (!$this->vm()->isUp()) {
                    Deckle::print('Starting up <info>Deckle Vagrant VM</info>');
                    $return = $this->sh()->exec('vagrant up', new LocalPath($vagrantPath));
                } else {
                    return;
                }
            }
        }

        if (isset($return) && !$return->isErrored()) {
            Deckle::success('Deckle Vagrant VM is up and running.', [], false);
        } else {
            Deckle::warning(
                'It seems like something went wrong while provisioning or starting the VM.
                Please che Vagrant output and logs.'
            );
        }
    }

    protected function installGit()
    {
        $gitInstalled = $this->fs()->isInPath('git');

        if ($gitInstalled) {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>git</info> is already installed');
            }
            return;
        }

        Deckle::print('Installing <info>git</info> using <info>brew</info>');
        $this->sh()->exec($this->packageInstallCommand . ' git');

    }

    protected function installDocker()
    {
        $dockerInstalled = $this->fs()->isInPath('docker');

        if ($dockerInstalled) {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>docker</info> is already installed');
            }
            return;
        }

        Deckle::print('Installing <info>docker</info> using <info>brew</info>');
        $this->sh()->exec($this->packageInstallCommand . ' docker');

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

        Deckle::print('Installing <info>mutagen</info>');
        $this->sh()->exec($this->packageInstallCommand . ' mutagen-io/mutagen/mutagen');
    }

    protected function installDnsmasq()
    {
        $dnsmasqInstalled = $this->fs()->isInPath('dnsmasq') || is_dir('/usr/local/Cellar/dnsmasq');

        if ($dnsmasqInstalled) {
            if (Deckle::isVerbose()) {
                Deckle::print('<info>dnsmasq</info> already installed');
            }
        } else {
            Deckle::print('Installing <info>dnsmasq</info> using <info>brew</info>');
            $this->sh()->exec($this->packageInstallCommand . ' dnsmasq');
            $this->sh()->exec('sudo brew services start dnsmasq');

        }


        // setting up dnsmasq
        if (is_file('/usr/local/etc/dnsmasq.conf')) {
            $conf = file('/usr/local/etc/dnsmasq.conf');
            $newLline = 'address=/.deckle.local/' . $this->vm()->ip() . PHP_EOL;
            foreach ($conf as &$line) {
                if (preg_match('/address=\/.deckle.local\/(.*)/', $line, $matches)) {
                    if (trim($line) != trim($newLline)) {
                        Deckle::print(
                            'Updating <info>dnsmasq</info> configuration in <info>/usr/local/etc/dnsmasq.conf</info>'
                        );
                        $line = $newLline;
                        file_put_contents('/usr/local/etc/dnsmasq.conf', implode("", $conf));
                        Deckle::print('Restarting <info>dnsmasq</info>...');
                        $this->sh()->exec('sudo brew services restart dnsmasq');
                        $this->sh()->exec('dscacheutil -flushcache');
                        $this->sh()->exec('sudo killall -HUP mDNSResponder');
                    }
                    return;
                }
            }

            // config file exists but does not contain deckle.local related configuration
            Deckle::print('Updating <info>dnsmasq</info> configuration in <info>/usr/local/etc/dnsmasq.conf</info>');
            file_put_contents('/usr/local/etc/dnsmasq.conf', $newLline, FILE_APPEND);
            Deckle::print('Restarting <info>dnsmasq</info>...');
            $this->sh()->exec('sudo brew services restart dnsmasq');
            $this->sh()->exec('dscacheutil -flushcache');
            $this->sh()->exec('sudo killall -HUP mDNSResponder');

        } else {
            Deckle::print('Generating <info>dnsmasq</info> configuration in <info>/usr/local/etc/dnsmasq.conf</info>');
            file_put_contents(
                '/usr/local/etc/dnsmasq.conf',
                "\n" . 'address=/.deckle.local/' . $this->vm()->ip() . "\n"
            );
            Deckle::print('Restarting <info>dnsmasq</info>...');
            $this->sh()->exec('sudo brew services restart dnsmasq');
            $this->sh()->exec('dscacheutil -flushcache');
            $this->sh()->exec('sudo killall -HUP mDNSResponder');
        }

    }
}
