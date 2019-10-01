<?php


namespace Adimeo\Deckle\Service\Recipes;


class RecipesManager
{
    public function list()
    {
        $rootPath = __DIR__ . '/../../../recipes/';
        $recipes = [];
        $vendors = new \DirectoryIterator($rootPath);

        foreach($vendors as $vendor)
        {
            if($vendor->isDot()) continue;
            $recipes[$vendor->getBasename()] = [];

            $vendorRecipes = new \DirectoryIterator($rootPath . $vendor);

            foreach ($vendorRecipes as $vendorRecipe) {
                if($vendorRecipe->isDot()) continue;
                $recipes[$vendor->getBasename()][] = $vendorRecipe->getBasename();
            }
        }

        return $recipes;
    }
}
