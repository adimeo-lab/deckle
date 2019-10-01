<?php


namespace Adimeo\Deckle\Command\Deckle;


use Adimeo\Deckle\Service\Recipes\RecipesManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecipesList extends Command
{

    /** @var RecipesManager */
    protected $recipesManager;

    /**
     * RecipesList constructor.
     * @param RecipesManager $recipesManager
     */
    public function __construct(RecipesManager $recipesManager)
    {
        $this->recipesManager = $recipesManager;

        parent::__construct(null);
    }


    protected function configure()
    {
        $this->setName("recipes:list")
            ->setAliases(['rl'])
            ->setDescription("List available recipes");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>Listing all available recipes</info>');
        $output->writeln('');
        $recipes = $this->recipesManager->list();

        foreach ($recipes as $vendor => $vendorRecipes) {
            $output->write('<comment>' . $vendor . '</comment>');
            foreach ($vendorRecipes as $vendorRecipe) {
                $output->writeln("\n - $vendor" . '/' . $vendorRecipe);
            }
        }

    }


}
