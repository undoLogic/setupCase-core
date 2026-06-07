#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access

docker volume prune -f

docker compose down

sleep 1

docker compose up -d
