#!/bin/sh
echo "Publishing deckle.phar on deckle.adimeo.eu"
scp deckle.phar root@deckle.adimeo.eu:/var/www/html/deckle/public/releases/latest.phar
"$(deckle.phar version)" > .version
scp .version root@deckle.adimeo.eu:/var/www/html/deckle/public/releases/latest.version
echo "Done!"
