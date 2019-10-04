#!/bin/sh

while getopts 'i' param
do
  case $param in
    i) INSTALL=1 ;;
  esac
done

echo "Building Deckle"
box build

if [ "$VERBOSE" = 1 ]; then
echo "Copying deckle.phar to /usr/local/bin/deckle..."
cp deckle.phar /usr/local/bin/deckle
fi
