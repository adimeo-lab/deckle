#!/bin/sh
TAG=$1
github-release delete \
  --owner adimeo-lab \
  --repo deckle \
  --tag "${TAG}"
