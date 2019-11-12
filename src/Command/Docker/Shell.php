<?php


namespace Adimeo\Deckle\Command\Docker;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Shell extends AbstractDeckleCommand
{
    protected function configure()
    {
        $this->setName('docker:shell')
            ->setAliases(['sh'])
            ->addArgument('container', InputArgument::OPTIONAL, 'Container to log in')
            ->addOption('shell', 's', InputOption::VALUE_OPTIONAL, 'Shell to open');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $input->getArgument('container') ?: $this->projectConfig['app']['container'];
        $containerId = $this->getContainerId($container);
        $shell = $input->getOption('shell') ?? $this->projectConfig['defaults']['shell'] ?? 'bash';
        $command = sprintf('exec -ti %s %s', $containerId, $shell);
        $this->docker($command);
    }

    protected function docker(string $command, $host = 'localhost')
    {
        if($this->output->isVeryVerbose()) {
            $this->output->writeln('About to run docker command: <comment>' . $command . '</comment> from <comment>' . $host . '</comment>');
        }
        passthru('docker ' . $command . ';' . PHP_EOL);
    }


}
