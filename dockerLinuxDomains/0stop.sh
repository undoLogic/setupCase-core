#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Stops and removes this project's containers only. The database volume, the domains proxy
# and other projects keep running.
run docker compose down
