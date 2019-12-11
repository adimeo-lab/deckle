#!/bin/sh



TAG=$1
COMMIT_LOG=`git log -1 --format='%ci %H %s'`



# create the tag
git tag -a "${TAG}"

# build the phar
./build.sh

# push it
git push --tags

github-release upload \
  --owner=adimeo-lab \
  --repo=deckle \
  --tag="${TAG}" \
  --name="${TAG}" \
  --body="${COMMIT_LOG}" \
  "deckle.phar"

