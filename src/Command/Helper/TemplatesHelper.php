<?php


namespace Adimeo\Deckle\Command\Helper;


use Adimeo\Deckle\Command\Deckle\InstallMacOs;
use Adimeo\Deckle\Exception\Config\ConfigException;
use Adimeo\Deckle\Exception\DeckleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Trait CloneTemplates
 *
 * @property OutputInterface $output
 * @property InputInterface $input
 * @package Adimeo\Deckle\Command\Deckle\Helper
 */
trait TemplatesHelper
{

    protected function getTemplatesDirectory()
    {
        return  $this->expandTilde(InstallMacOs::DECKLE_HOME);
    }

    protected function cacheTemplates()
    {
        $conf = $this->loadGlobalConfiguration();
        $repositories = $conf['providers'] ?? [];

        if (!$repositories) {
            $this->output->writeln('<comment>No providers are defined in Deckle configuration</comment>');
        } else {
            foreach ($repositories as $repository) {

                $targetRepository = $this->sanitizeProviderName($repository);

                if (!is_dir($targetRepository)) {
                    $this->output->writeln('Cloning <info>' . $repository . '</info> in <info>' . $targetRepository . '</info>');
                    $operation = 'cloning';
                    exec('git clone ' . escapeshellarg($repository) . ' ' . $targetRepository . ' 2>&1', $output, $errno);

                } else {
                    $this->output->writeln('<info>Updating <info>' . $repository . '</info>');
                    $operation = 'pulling';
                    chdir($targetRepository);
                    system('git pull 2>&1', $errno);
                    chdir('..');
                }

                if ($errno) {
                    $this->error(
                        'Something went wrong while %s "%s". You should maybe reinstall Deckle...',
                        [
                        $operation,
                        $repository
                    ]);
                }
            }
            $this->output->writeln('Done ' . $operation . ' repositories!');
        }

    }

    protected function sanitizeProviderName($repository)
    {
        return strtr($repository, ['/'=>'-', '@'=>'_at_', ' ' => '_' ]);
    }

    /**
     * @return string
     */
    protected function currentTemplatePath()
    {
        return './deckle/.template/' . $this->env . '/';
    }

    protected function listAvailableTemplates() {

    }

    protected function loadGlobalConfiguration() : array
    {
        // TODO check configuration content
        $target = $this->getDeckleHomeDirectory();
        if(is_file($target . '/deckle.conf.yml' )) {
            return Yaml::parseFile($target . '/deckle.conf.yml');
        } else {
            if($this->getName() !== 'install') {
                throw new ConfigException([
                    'deckle.conf.yml was not found in "%s" folder. You may need to reinstall Deckle.',
                    InstallMacOs::DECKLE_HOME
                ]);
            }
        }
    }

    protected function getDeckleHomeDirectory()
    {
        return $this->expandTilde(InstallMacOs::DECKLE_HOME);
    }

    public function resolveTemplateProvider($template) {
        $conf = $this->loadGlobalConfiguration();
        $providers = $conf['providers'] ?? [];

        $cwd = getcwd();
        chdir($this->expandTilde(InstallMacOs::DECKLE_HOME));

        foreach($providers as $provider) {
            $path = 'cache/' . $this->sanitizeProviderName($provider) . '/' . $template;
            if(is_dir($path)) {
                chdir($cwd);
                return $provider;
            }
        }
        chdir($cwd);
    }

    /**
     * @param $template
     * @param string|array $providers
     * @return bool|string
     * @throws ConfigException
     */
    public function resolveTemplatePath($template, $providers = null)
    {
        $conf = $this->loadGlobalConfiguration();
        $availableProviders = $conf['providers'] ?? [];

        if(!$providers) {
            $providers = $availableProviders;
        } else {
            $providers = (array) $providers;
            foreach($providers as $provider) {
                if(!in_array($provider, $availableProviders)) {
                    $this->error('Unknown provider "%s". Please specify a provider among available ones (%s)', [$provider, implode(', ', [$availableProviders])]);
                }
            }
        }

        $cwd = getcwd();
        chdir($this->expandTilde(InstallMacOs::DECKLE_HOME));

        foreach($providers as $provider) {
            $path = 'cache/' . $this->sanitizeProviderName($provider) . '/' . $template;
            if(is_dir($path)) {
                $realpath = realpath($path);
                chdir($cwd);
                return $realpath;
            }
        }

        chdir($cwd);
    }
}
