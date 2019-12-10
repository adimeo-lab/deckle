#!/bin/sh
APP_ENV=dev php deckle.php deckle:clear -q
APP_ENV=dev php deckle.php $*

