<?php


namespace Tests\AdimeoLab\Deckle\Service\Placeholder;


use Adimeo\Deckle\Command\AbstractDeckleCommand;
use Adimeo\Deckle\Service\Placeholder\Placeholder;
use Adimeo\Deckle\Service\Placeholder\PlaceholdersManager;
use Codeception\TestCase\Test;
use Symfony\Component\Yaml\Yaml;

class PlaceholdersManagerTest extends Test
{
    public function testPlaceholdersExtraction()
    {
        $content = file_get_contents(__DIR__ . '/samples/deckle.yml');
        /** @var PlaceholdersManager $manager */
        $manager = $this->makeEmptyExcept(PlaceholdersManager::class, 'extractPlaceholders');
        $placeholders = $manager->extractPlaceholders($content);

        $this->assertEquals(new Placeholder('conf<extra.google-api-key>', 'conf', ['extra.google-api-key']), current($placeholders));

    }

    public function testPlaceholdersSubstitution()
    {
        $content = file_get_contents(__DIR__ . '/samples/deckle.yml');
        /** @var PlaceholdersManager $manager */
        $manager = $this->make(PlaceholdersManager::class);
        /** @var AbstractDeckleCommand $command */
        $command = $this->make(AbstractDeckleCommand::class);
        $command->setProjectConfig(Yaml::parseFile(__DIR__ . '/samples/deckle.yml'));
        $placeholders = $manager->extractPlaceholders($content);

        /** @var Placeholder $placeholder */
        foreach($placeholders as $placeholder) {
            $value = $command->resolvePlaceholderValue($placeholder);
            $content = $manager->substitutePlaceholder($content, $placeholder, $value);
        }

        $processed = Yaml::parse($content);

        $this->assertEquals('1234', $processed['extra']['api-key']);
    }
}
