#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# First time per project (or after changing web/Dockerfile): builds this project's image.
# Day to day only 1startDocker.sh is needed - no rebuild.
run docker compose down

run docker compose build --no-cache

domains_start
domains_project_up
