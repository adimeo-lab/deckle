<?php


namespace Adimeo\Deckle\Service\Git;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Adimeo\Deckle\Service\Shell\Script\Location\ShellScriptLocationInterface;
use Adimeo\Deckle\Service\Shell\Script\ShellScriptInterface;
use Adimeo\Deckle\Service\Shell\ShellService;
use Adimeo\Deckle\Service\Shell\ShellTrait;

class GitService extends AbstractDeckleService
{

    use ShellTrait;

    /**
     * @param ShellScriptLocationInterface $workingCopyPath
     * @return bool
     * @throws DeckleException
     */
    public function isUpToDate(ShellScriptLocationInterface $workingCopyPath): bool
    {
        $return = $this->sh()->exec('git diff --quiet remotes/origin/HEAD', $workingCopyPath);
        return !$return->isErrored();
    }

    public function clone(string $repository, ShellScriptLocationInterface $location)
    {
        if (!$location instanceof LocalPath) {
            Deckle::halt('Cloning git repositories is currently only supported to local paths');
        }

        if (Deckle::isVerbose()) {
            Deckle::print('Cloning <info>' . $repository . '</info> in <info>' . $location->getPath() . '</info>');
        }

        $this->sh()->exec(sprintf('git clone %s %s', $repository, $location->getFullyQualifiedPath()));
    }

    public function pull(ShellScriptLocationInterface $location)
    {
        if (!$location instanceof LocalPath) {
            Deckle::warning('Cloning git repositories is currently only supported to local paths');
            exit(-1);
        }

        if (Deckle::isVerbose()) {
            Deckle::print('Updating <info>' . $location->getPath() . '</info>');
        }

        $this->sh()->exec(sprintf('git pull %s', $location->getFullyQualifiedPath()));
    }
}
