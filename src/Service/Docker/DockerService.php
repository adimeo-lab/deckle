<?php


namespace Adimeo\Deckle\Service\Docker;


use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Shell\Script\Location\Container;
use Adimeo\Deckle\Service\Shell\Script\Location\DeckleMachine;
use Adimeo\Deckle\Service\Shell\Script\Location\ShellScriptLocationInterface;
use Adimeo\Deckle\Service\Shell\ShellTrait;

class DockerService extends AbstractDeckleService
{

    use ShellTrait;

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        $ch = curl_init($this->getConfig('docker.host') . '/_ping');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ping = curl_exec($ch);

        if ($ping) {
            if ($ping === 'OK') {
                return true;
            }
        }
        return false;
    }



    public function getContainerId(string $containerName, $short = true)
    {
        $url = $this->getConfig('docker.host');
        if (!$url) {
            $this->output()->error('No Docker Host found in config.');
            exit (-1);
        }
        $url .= '/containers/json';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $containers = curl_exec($ch);
        $infos = curl_getinfo($ch);

        if (isset($infos['http_code']) && $infos['http_code'] == 200 && $containers = json_decode($containers, true)) {
            foreach ($containers as $container) {
                foreach ($container['Names'] as $name) {
                    if ($containerName == trim($name, '/')) {
                        return $short ? substr($container['Id'], 0, 16) : $container['Id'];
                    }
                }
            }
        } else {
            $this->output()->error('Something went wrong while fetching containers infos on ' . $url);
        }

        return false;
    }

    /**
     * @param bool $short
     * @return string
     * @throws \Adimeo\Deckle\Exception\DeckleException
     */
    public function getAppContainerId($short = true)
    {
        $appContainer = $this->getConfig('app.service');
        $return = $this->sh()->exec('docker-compose ps -q ' . $appContainer, new DeckleMachine($this->getConfig('docker.path')));
        if(!$return->isErrored() && $return->getOutput()) {
            return $short ? substr($return->getOutput()[0], 0, 16) : $return->getOutput()[0];
        }

        return null;
    }


}
