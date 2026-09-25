#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Stops and removes this project's containers. The database volume is kept.
docker compose down
