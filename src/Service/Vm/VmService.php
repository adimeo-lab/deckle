<?php


namespace Adimeo\Deckle\Service\Vm;


use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Exception\DeckleException;
use Adimeo\Deckle\Service\AbstractDeckleService;
use Adimeo\Deckle\Service\Filesystem\FilesystemTrait;
use Adimeo\Deckle\Service\Shell\Script\Location\LocalPath;
use Adimeo\Deckle\Service\Shell\ShellTrait;

class VmService extends AbstractDeckleService
{

    use FilesystemTrait;
    use ShellTrait;

    public function start (): bool {
        if (!$this->isUp()) {
            $return = $this->sh()->exec('VBoxManage startvm deckle-vm --type headless');
            foreach ($return->getOutput() as $line) {
                if(preg_match('/successfully\s+started./', $line)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws DeckleException
     */
    public function isUp() : bool
    {
        $return = $this->sh()->exec('vagrant status', new LocalPath('~/.deckle/vagrant'));

        foreach ($return->getOutput() as $line) {
            if(preg_match('/deckle\s+running/', $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return VM IP Address
     *
     * @return string|null
     */
    public function ip() : ?string
    {
        $ip = null;
        $guessers[] = [$this, 'findVmAddressFromVBoxManage'];
        $guessers[] = [$this, 'findVmAddressInHosts'];
        foreach($guessers as $guesser) {
            if($ip = $guesser()) return $ip;
        }

        return null;
    }


    /**
     * @return string|null
     */
    protected function findVmAddressInHosts() : ?string
    {
        if(Deckle::isVerbose()) Deckle::print('Looking for <info>deckle-vm</info> in <info>/etc/hosts</info>');
        $entries = file('/etc/hosts');
        foreach ($entries as $entry) {
            if (strpos(trim($entry), '#') === 0) {
                continue;
            }
            [$ip, $names] = preg_split('/\s+/', $entry, 2);
            $names = preg_split('/\s+/', $names);

            if (in_array($this->getConfig('vm.host'), $names)) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * @return mixed
     * @throws DeckleException
     */
    protected function findVmAddressFromVBoxManage() : ?string
    {
        if(Deckle::isVeryVerbose()) Deckle::print('Looking for <info>deckle-vm</info> IP using <info>VBoxManage</info>');
        if($this->isRunningOnVbox()) {
            $return = $this->sh()->exec('VBoxManage guestproperty enumerate deckle-vm');

            foreach($return->getOutput() as $outputLine) {
                preg_match('/\/VirtualBox\/GuestInfo\/Net\/1\/V4\/IP, value: (\d+\.\d+\.\d+\.\d+)/', $outputLine, $matches);
                if(isset($matches[1])) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    protected function isRunningOnVbox()
    {
        if($this->fs()->isInPath('VBoxManage')) {

            $return = $this->sh()->exec('VBoxManage guestproperty enumerate deckle-vm');

            foreach($return->getOutput() as $outputLine) {
                if(strpos($outputLine, 'VBOX_E_OBJECT_NOT_FOUND')) return false;
            }

            return true;

        } else return false;
    }
}
