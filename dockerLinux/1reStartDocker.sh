#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Recreates this project's containers. The database volume is kept.
docker compose down

sleep 1

docker_stop_other_projects
docker compose up -d
