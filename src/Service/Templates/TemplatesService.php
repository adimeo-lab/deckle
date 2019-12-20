<?php


namespace Adimeo\Deckle\Service\Templates;


use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Filesystem\FilesystemTrait;
use Adimeo\Deckle\Service\Git\GitTrait;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Adimeo\Deckle\Service\Shell\ShellTrait;

class TemplatesService extends AbstractDeckleService
{
    use FilesystemTrait;
    use ShellTrait;
    use GitTrait;

    public function getPath()
    {
        return $this->fs()->expandTilde(Install::DECKLE_HOME);
    }

    public function fetch()
    {
        $cacheDirectory = $this->fs()->expandTilde(Install::DECKLE_HOME . '/cache');
        if(!file_exists($cacheDirectory)) {
            $return = $this->sh()->exec('mkdir ' . $cacheDirectory);
            if($return->isErrored()) {
                Deckle::halt('Unable to create cache directory "%s"', $cacheDirectory);
            } else {
                Deckle::print('Directory <info>%s</info> successfully created.', $cacheDirectory);
            }
        }
        $repositories = $this->getConfig('providers', []);
        if (!$repositories) {
            Deckle::warning('No providers are defined in Deckle configuration');
        } else {
            Deckle::print('Fetching project <info>templates</info>...');
            foreach ($repositories as $repository) {

                $targetRepository = $cacheDirectory . '/' . $this->sanitizeProviderName($repository);

                if (!is_dir($targetRepository)) {
                    Deckle::print("\t" . 'Cloning templates from <info>%s</info>...', $repository);
                    $return = $this->git()->clone($repository, new LocalPath(dirname($targetRepository)), $this->sanitizeProviderName($repository));

                } else {
                    if (!$this->git()->isUpToDate(new LocalPath($targetRepository))) {
                        if (Deckle::isVerbose()) {
                            Deckle::print('Updating <info>%s</info>', $repository);
                        }
                        $return = $this->git()->pull(new LocalPath($targetRepository));
                    } else {
                        $return = 0;
                        Deckle::print("\t" . '<info>' . $repository . '</info> is up to date');
                    }
                }

                if ($return) {
                    Deckle::error('Something went wrong while fetching templates. You should maybe reinstall Deckle...');
                }
            }

        }

    }

    public function sanitizeProviderName($repository)
    {
        return strtr($repository, ['/' => '-', '@' => '_at_', ' ' => '_']);
    }

    public function getDeckleHomeDirectory()
    {
        return $this->fs()->expandTilde(Install::DECKLE_HOME);
    }

    public function resolveTemplateProvider($template)
    {
        $providers = $conf['providers'] ?? [];

        $cwd = getcwd();
        chdir($this->fs()->expandTilde(Install::DECKLE_HOME));

        foreach ($providers as $provider) {
            $path = 'cache/' . $this->sanitizeProviderName($provider) . '/' . $template;
            if (is_dir($path)) {
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
     */
    public function resolveTemplatePath($template, $providers = null)
    {
        $availableProviders = $this->getConfig('providers', []);

        if (!$providers) {
            $providers = $availableProviders;
        } else {
            $providers = (array)$providers;
            foreach ($providers as $provider) {
                if (!in_array($provider, $availableProviders)) {
                    Deckle::error('Unknown provider "%s". Please specify a provider among available ones (%s)',
                        [$provider, implode(', ', [$availableProviders])]);
                }
            }
        }

        $cwd = getcwd();
        chdir($this->fs()->expandTilde(Install::DECKLE_HOME));

        foreach ($providers as $provider) {
            $path = 'cache/' . $this->sanitizeProviderName($provider) . '/' . $template;
            if (is_dir($path)) {
                $realpath = realpath($path);
                chdir($cwd);
                return $realpath;
            }
        }
        chdir($cwd);
    }


}
