<?php


namespace Adimeo\Deckle\Service\Shell;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Docker\DockerTrait;
use Adimeo\Deckle\Service\Shell\Script\Location\AppContainer;
use Adimeo\Deckle\Service\Shell\Script\Location\Container;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Adimeo\Deckle\Service\Shell\Script\Location\ShellScriptLocationInterface;
use Adimeo\Deckle\Service\Shell\Script\Location\SshHost;
use Adimeo\Deckle\Service\Shell\Script\ShellScriptReturn;
use Adimeo\Deckle\Service\Shell\Script\ShellScriptReturnInterface;

class ShellService extends AbstractDeckleService
{

    use DockerTrait;


    public function exec(
        string $script,
        ShellScriptLocationInterface $location = null,
        $silent = true
    ): ShellScriptReturnInterface {
        if (is_null($location)) {
            $location = new LocalPath('.');
        }

        // handle silent dynamically
        if (Deckle::isVeryVerbose()) {
            $silent = false;
        } elseif (Deckle::isQuiet()) {
            $silent = true;
        }

        switch (true) {
            case $location instanceof LocalPath:
                return $this->execLocally($script, $location, $silent);

            case $location instanceof SshHost:
                return $this->execRemotely($script, $location, $silent);

            case $location instanceof Container:
                return $this->execInContainer($script, $location, $silent);

            default:
                $type = is_scalar($location) ? gettype($location) : is_array($location) ? 'array' : get_class($location);
                throw new DeckleException([
                    "Target parameter must be an instance of %s. The provided parameter was a(n) %s",
                    ShellScriptLocationInterface::class,
                    $type
                ]);
                break;
        }
    }

    protected function execLocally($script, LocalPath $target, $silent)
    {


        if ($target->getPath() != '.') {
            $cwd = 'cd ' . $target->getPath() . ' && ';
        } else {
            $cwd = '';
        }

        if (Deckle::isVeryVerbose()) {
            Deckle::print('Executing <info>%s</info> in <info>%s</info>', [$script, $target->getPath()]);
        }


        if ($silent) {
            // TODO find a way to prevent exec() output to be dumped in terminal but get collected in $output
            // with commands like scp
            exec($cwd . $script , $output, $return);

        } else {
            $output = [system($cwd . $script, $return)];
        }

        return new ShellScriptReturn($return, $output);
    }

    protected function execRemotely(string $script, SshHost $target, bool $silent)
    {
        if ($target instanceof DeckleMachine) {
            $this->completeDeckleMachineLocation($target);
        }

        $user = $target->getUser();
        $host = $target->getHost();
        $port = $target->getPort();

        if (!$host) {
            Deckle::error('Missing "host" in SshHost location');
            return -1;
        }

        if ($target->getPath() != '~') {
            $script = 'cd ' . $target->getPath() . ' && ' . $script;
        }

        $script = escapeshellarg($script);

        $command = $script;
        $sshCommand = 'ssh ';
        if ($user) {
            $sshCommand .= $user . '@';
        }
        $sshCommand .= $host;
        if ($port) {
            $sshCommand .= ' -p ' . $port;
        }
        $sshCommand .= ' ' . $command;

        $return = $this->exec($sshCommand, new LocalPath('.'), $silent);

        if ($return->isErrored()) {
            if (Deckle::isVerbose()) {
                Deckle::warning(array_merge(['Something went wrong while executing a command on a remote host...'],
                    $return->getOutput()));
            }
        }

        return $return;
    }

    protected function execInContainer(string $script, Container $container, $silent)
    {
        if (!$container->getName()) {
            $container->setName($this->getConfig('app.container'));
        }
        $containerId = $this->docker()->getContainerId($container->getName());

        if (!$containerId) {
            Deckle::error('Unknown container ' . $container->getName());
            return -1;
        }

        $cwd = $container->getPath() ? 'cd ' . $container->getPath() . ' && ' : '';

        if (in_array($script, ['bash', 'sh', 'zsh'])) {
            $cmd = 'docker exec -ti ' . $containerId . ' ' . $script;
        } else {
            $cmd = 'docker exec -t ' . $containerId . ' bash -c ' . escapeshellarg($cwd . $script);
        }
        if (Deckle::isVeryVerbose()) {
            Deckle::print('Executing <comment>' . $cmd . '</comment> on Docker remote host <comment>' . $this->getConfig('docker.host') . '</comment>');
        }

        // handle Docker Environment
        putenv("DOCKER_HOST=" . $this->getConfig('docker.host', $this->getConfig('vm.host')));

        if ($silent) {
            exec($cmd . ' 2>&1 > /dev/null', $output, $return);
        } else {
            passthru($cmd, $return);
            $output = [];
        }

        $return = new ShellScriptReturn($return, $output);

        if ($return->isErrored()) {
            if (Deckle::isVerbose()) {
                Deckle::warning(array_merge(['Something went wrong while executing a command in a container...'],
                    $return->getOutput()));
            }
        }

        return $return;

    }

    public function scp(ShellScriptLocationInterface $source, ShellScriptLocationInterface $destination)
    {
        if ($source instanceof DeckleMachine) {
            $this->completeDeckleMachineLocation($source);
        }
        if ($destination instanceof DeckleMachine) {
            $this->completeDeckleMachineLocation($destination);
        }

        $args = '-r';

        $scpCommand = 'scp ' . $args . ' "' . $source->getFullyQualifiedPath() . '" "' . $destination->getFullyQualifiedPath() . '"';

        $return = $this->exec($scpCommand);

        if ($return->isErrored()) {
            if (Deckle::isVerbose()) {
                Deckle::warning(array_merge(['Something went wrong while executing a scp command...'],
                    $return->getOutput()));
            }
        }

        return $return;
    }

    public function cp(ShellScriptLocationInterface $source, ShellScriptLocationInterface $target)
    {

        if($source instanceof AppContainer) {
            $source->setName($this->docker()->getAppContainerId());
        }

        if($target instanceof AppContainer) {
            $target->setName($this->docker()->getAppContainerId());
        }

        $sourcePath = $source->getFullyQualifiedPath();
        $targetPath = $target->getFullyQualifiedPath();


        switch (true) {

            case $source instanceof Container || $target instanceof Container:
                $cpCommand = 'docker cp';
                break;

            case $source instanceof SshHost || $target instanceof SshHost:
                $cpCommand = 'scp';
                break;

            default:
                $cpCommand = 'cp';
                break;
        }


        $command = sprintf('%s %s %s', $cpCommand, $sourcePath, $targetPath);
        if(Deckle::isVeryVerbose()) {
            Deckle::print('Copying <info>' . $sourcePath . '</info> to <info>' . $targetPath . '</info> using <info>' . $cpCommand .'</info>');
        }
        return $this->exec($command);

    }

    public function completeDeckleMachineLocation(DeckleMachine $machine)
    {
        if (!$machine->getHost()) {
            $machine->setHost($this->getConfig('vm.host'));
        }
        if (!$machine->getUser() && $user = $this->getConfig('vm.user')) {
            $machine->setUser($user);
        }
        if (!$machine->getPort()) {
            $machine->setPort($this->getConfig('vm.port', 22));
        }
    }

}
