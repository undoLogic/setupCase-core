#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Starts this project (stopping any other project first). Builds the image only if it does not exist yet.
docker_stop_other_projects
docker compose up -d

sleep 5
