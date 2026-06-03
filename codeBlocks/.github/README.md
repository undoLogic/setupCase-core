# Ci Testing

## Troubleshooting
- If you are getting 'ci-status.json' errors this is most likely due to composer not having the latest files so it is running 'composer install' and the running has different permissions
- RESOLUTION: run 'composer update' and save that to the repo so new files will not be created from the github actions runner

