<?php


namespace Adimeo\Deckle\Container;


use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

}
