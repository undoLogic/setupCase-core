#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Starts the domains proxy if it is not running yet, then this project. Pure start, not a restart.
domains_start
domains_project_up
