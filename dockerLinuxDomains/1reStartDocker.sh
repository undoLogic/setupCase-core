#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Recreates this project's containers. The database volume is kept.
run docker compose down

domains_start
domains_project_up
