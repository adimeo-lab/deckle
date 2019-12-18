<?php


namespace Adimeo\Deckle\Service\Filesystem;


use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Shell\ShellService;
use Adimeo\Deckle\Service\Shell\ShellTrait;

class FilesystemService extends AbstractDeckleService
{
    use ShellTrait;

    public function isInPath($binary)
    {
        $return = $this->sh()->exec('which ' . $binary);

        return !$return->isErrored();
    }

    /**
     * Replace '~' with current user home directory path
     *
     * @param $path
     * @return mixed
     */
    public function expandTilde($path)
    {
        if (extension_loaded('posix') && strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return $path;
    }

}
