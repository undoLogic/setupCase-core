#!/usr/bin/env bash
# Stops EVERY running Docker container on this machine, including the domains proxy (Traefik).
# Containers are stopped, not removed: databases and other data are kept.
# Fallback routine: ./0allstop.sh, then start the dockerLinux folder with its ports.
# Identical copy in dockerLinux/ and dockerLinuxDomains/ - change both together.
set -e

running="$(docker ps -q)"

if [ -z "$running" ]; then
    echo "No running containers."
    exit 0
fi

echo "> docker stop \$(docker ps -q)"
docker stop $running
