#!/bin/sh
echo "Reducing dependencies..."
composer install --no-dev
composer dumpautoload

echo "Warming up cache...\n"
APP_ENV=prod php ./deckle.phar.php

echo "Building deckle.phar\n"
APP_ENV=prod box build

echo "Restoring dependencies..."
composer install

echo ""
echo "./deckle.phar has been built successfully!";
