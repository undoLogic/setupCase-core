#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# First time per project (or after changing web81/Dockerfile): builds this project's image.
# Switching between projects only needs 1startDocker.sh - no rebuild.
docker compose down

docker compose build --no-cache

docker_stop_other_projects
docker compose up -d

sleep 10
