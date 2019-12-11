curl \
  -u 'gauthier' \
  -d '{"scopes":["repo"], "note":"Publish to GitHub Releases"}' \
  https://api.github.com/authorizations
