<?php


namespace Adimeo\Deckle\Service;


use Adimeo\Deckle\Container\ContainerAwareInterface;
use Adimeo\Deckle\Container\ContainerAwareTrait;
use Adimeo\Deckle\Deckle;
use Adimeo\Deckle\Service\Config\DeckleConfig;
use ObjectivePHP\ServicesFactory\ServicesFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AbstractDeckleService
 * @package Adimeo\Deckle\Service
 */
class AbstractDeckleService implements DeckleServiceInterface, ContainerAwareInterface
{

    use ContainerAwareTrait;

    /**
     * @var DeckleConfig
     */
    protected $config;

    /**
     * AbstractDeckleCommand constructor.
     * @param ServicesFactory $servicesFactory
     * @param DeckleConfig $config
     */
    public function __construct(ServicesFactory $servicesFactory, DeckleConfig $config)
    {
        $this->setContainer($servicesFactory);
        $this->config = $config;
    }

    /**
     * @return InputInterface
     */
    public function input(): InputInterface
    {
        return Deckle::input(null);
    }

    /**
     * @return OutputInterface|SymfonyStyle
     */
    public function output(): OutputInterface
    {
        return Deckle::output();
    }

    /**
     * @param DeckleConfig $config
     */
    public function setConfig(DeckleConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function hasConfig(): bool
    {
        return (bool)$this->config;
    }

    /**
     * @param null $key
     * @param null $default
     * @return DeckleConfig|mixed
     */
    protected function getConfig($key = null, $default = null)
    {
        return $this->config ? ($key ? $this->config->get($key, $default) : $this->config) : null;
    }

}
