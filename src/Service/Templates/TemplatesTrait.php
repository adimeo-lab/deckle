<?php


namespace Adimeo\Deckle\Service\Templates;


/**
 * Trait TemplatesTrait
 * @package Adimeo\Deckle\Service\Templates
 */
trait TemplatesTrait
{

    /**
     * @return TemplatesService
     */
    public function templates()
    {
        return $this->getContainer()->get(TemplatesService::class);
    }

}
