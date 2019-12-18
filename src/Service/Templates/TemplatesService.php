<?php


namespace Adimeo\Deckle\Service\Templates;


use Adimeo\Deckle\Command\Deckle\Install;
use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Filesystem\FilesystemTrait;
use Adimeo\Deckle\Service\Git\GitTrait;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;

class TemplatesService extends AbstractDeckleService
{
    use FilesystemTrait;
    use GitTrait;

    public function getPath()
    {
        return $this->fs()->expandTilde(Install::DECKLE_HOME);
    }

    public function fetch()
    {
        $repositories = $this->getConfig('providers', []);

        if (!$repositories) {
            $this->output()->warning('No providers are defined in Deckle configuration');
        } else {
            $this->output()->writeln('Fetching project <info>templates</info>...');
            foreach ($repositories as $repository) {

                $targetRepository = $this->sanitizeProviderName($repository);

                if (!is_dir($targetRepository)) {
                    $operation = 'cloning';
                    $return = $this->git()->clone('git clone ' . escapeshellarg($repository), new LocalPath($targetRepository));

                } else {
                    if (!$this->git()->isUpToDate(new LocalPath($targetRepository))) {
                        if ($this->output()->isVerbose()) {
                            $this->output()->writeln('Updating <info>' . $repository . '</info>');
                        }
                        $operation = 'pulling';
                        $return = $this->callFrom($targetRepository, 'git pull');
                    } else {
                        $return = 0;
                        $this->output()->writeln("\t" . '<info>' . $repository . '</info> is up to date');
                    }
                }

                if ($return) {
                    $this->error(
                        'Something went wrong while %s "%s". You should maybe reinstall Deckle...',
                        [
                            $operation,
                            $repository
                        ]);
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
                    $this->error('Unknown provider "%s". Please specify a provider among available ones (%s)',
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
