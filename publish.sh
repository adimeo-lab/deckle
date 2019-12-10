#!/bin/sh
echo "Publishing deckle.phar on deckle.adimeo.eu"
scp deckle.phar root@deckle.adimeo.eu:/var/www/html/deckle/public/releases/latest
echo "Done!"
