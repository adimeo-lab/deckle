<?php


namespace Adimeo\Deckle;


use Adimeo\Deckle\Command\Deckle\PushDockerConfig;
use ErrorException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Deckle extends Application
{
    /**
     * @override
     * @param string $name
     * @param string $version
     */
    public function __construct($name = 'Deckle', $version = '@git-version@')
    {
        // convert errors to exceptions
        set_error_handler(
            function ($code, $message, $file, $line) {
                if (error_reporting() & $code) {
                    throw new ErrorException($message, 0, $code, $file, $line);
                }
                // @codeCoverageIgnoreStart
            }
        // @codeCoverageIgnoreEnd
        );


        parent::__construct($name, $version);
    }

    /**
     * @override
     */
    public function getLongVersion()
    {
        if (('@' . 'git-version@') !== $this->getVersion()) {
            return sprintf(
                '<info>%s</info> version <comment>%s</comment> build <comment>%s</comment>',
                $this->getName(),
                $this->getVersion(),
                '@git-commit@'
            );
        }

        return '<info>' . $this->getName() . '</info> (development version)';
    }

    /**
     * @override
     * @throws \Exception
     */
    public function run(
        InputInterface $input = null,
        OutputInterface $output = null
    ) {


        $output = $output ?: new ConsoleOutput();

        $output->getFormatter()->setStyle(
            'error',
            new OutputFormatterStyle('red')
        );

        $output->getFormatter()->setStyle(
            'question',
            new OutputFormatterStyle('cyan')
        );

        return parent::run($input, $output);
    }

}
