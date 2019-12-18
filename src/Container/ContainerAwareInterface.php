<?php


namespace Adimeo\Deckle\Container;


use Psr\Container\ContainerInterface;

/**
 * Interface ContainerAwareInterface
 * @package Adimeo\Deckle\Container
 */
interface ContainerAwareInterface
{
    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @param ContainerInterface $container
     * @return mixed
     */
    public function setContainer(ContainerInterface $container);
}
