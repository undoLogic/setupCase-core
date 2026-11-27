#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access

# Auxiliary: starts only the shared domains network and Traefik proxy (no project).
domains_start
