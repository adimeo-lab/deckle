#!/bin/sh
echo "Reducing dependencies..."
composer install --no-dev
composer dumpautoload

echo "Building deckle.phar\n"
box build

echo "Restoring dependencies..."
composer install

echo "Geenrating .version file"
php -r "echo sha1_file('deckle.phar');" > .version
echo ""
echo "./deckle.phar has been built successfully!";
