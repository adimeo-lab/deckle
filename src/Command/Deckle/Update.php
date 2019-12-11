<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Command\Helper\TemplatesHelper;
use Adimeo\Deckle\Exception\Config\ConfigException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Update extends AbstractDeckleCommand
{

    use TemplatesHelper;

    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    protected function configure()
    {
        parent::configure();
        $this->setName('update')
            ->setDescription('Update local templates copy');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $this->output = $output;
        $this->input = $input;
        $target = $this->expandTilde(InstallMacOs::DECKLE_HOME);

        if (!is_dir($target)) {
            throw new ConfigException(['No config found in "%s". You probably should reinstall Deckle', $target]);
        }

        $cwd = getcwd();
        chdir($target);
        $conf = Yaml::parseFile('deckle.conf.yml');
        chdir('cache');
        $this->cacheTemplates();
        chdir('..');
        chdir($cwd);
    }


}
