# Ci Testing

## Activation
- The workflow is disabled while this repository is used as boilerplate.
- Rename `workflows/ci.yml.disabled` to `workflows/ci.yml` when the required project files are available.

## Troubleshooting
- If you are getting 'ci-status.json' errors this is most likely due to composer not having the latest files so it is running 'composer install' and the running has different permissions
- RESOLUTION: run 'composer update' and save that to the repo so new files will not be created from the github actions runner

