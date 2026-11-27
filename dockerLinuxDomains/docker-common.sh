#!/usr/bin/env bash
# Shared helpers for the dockerLinuxDomains scripts (see docs/features/MVP-Docker-Linux-Domains.md).
# Own copy - not shared with dockerLinux/docker-common.sh.

# The shared proxy. Every project ships the same values: whichever project starts Traefik first decides them.
# Traefik v3.6+ is needed for Docker Engine 29+ (older versions use a Docker API that 29 refuses).
TRAEFIK_IMAGE="traefik:v3.6"
TRAEFIK_NAME="traefik"
DOMAINS_NETWORK="domains"

# Prints the real Docker command, then runs it
run()
{
    echo "> $*"
    "$@"
}

docker_require_access()
{
    if ! command -v docker >/dev/null 2>&1; then
        echo "Docker is not installed or is not available in PATH." >&2
        return 1
    fi

    if docker info >/dev/null 2>&1; then
        return 0
    fi

    echo "Cannot connect to the Docker daemon." >&2

    if getent group docker >/dev/null 2>&1 && ! id -nG | tr ' ' '\n' | grep -qx docker; then
        echo "This login session has not picked up your docker group membership." >&2
        echo "Run: newgrp docker" >&2
        echo "Or log out of Linux and log back in." >&2
        return 1
    fi

    if ! systemctl is-active --quiet docker 2>/dev/null; then
        echo "Start it with: sudo systemctl start docker" >&2
        return 1
    fi

    echo "Add your user to the docker group, then log out and back in:" >&2
    echo "  sudo usermod -aG docker \$USER" >&2
    return 1
}

# Reads PROJECT_NAME from ./.env (the same file docker compose reads) and validates it
docker_project_init()
{
    PROJECT_NAME="$(sed -n 's/^PROJECT_NAME=//p' .env 2>/dev/null | tail -n 1 | tr -d '[:space:]')"

    if [ -z "$PROJECT_NAME" ]; then
        echo "PROJECT_NAME is not set in $(pwd)/.env" >&2
        echo "Add a line like: PROJECT_NAME=my-project" >&2
        return 1
    fi

    # Stricter than dockerLinux: the name becomes a domain, and domains cannot contain _
    if ! [[ "$PROJECT_NAME" =~ ^[a-z0-9][a-z0-9-]*$ ]]; then
        echo "PROJECT_NAME '$PROJECT_NAME' is invalid: use lowercase letters, digits and - only (it becomes $PROJECT_NAME.localhost)." >&2
        return 1
    fi

    local repoFolder
    repoFolder="$(basename "$(cd .. && pwd)" | tr '[:upper:]' '[:lower:]')"

    # .env ships with the placeholder name from README.md
    if [ "$PROJECT_NAME" = "my-project" ]; then
        echo "PROJECT_NAME is still 'my-project' (the placeholder)." >&2
        echo "Set this project's name in $(pwd)/.env - the same name as dockerLinux/.env, e.g. PROJECT_NAME=$repoFolder" >&2
        return 1
    fi

    # A name copied from dockerLinux/.env of the SetupCase Core template
    if [ "$PROJECT_NAME" = "setupcase-core" ] && [ "$repoFolder" != "setupcase-core" ]; then
        echo "PROJECT_NAME is still 'setupcase-core' (the template default)." >&2
        echo "Set a unique name for this project in $(pwd)/.env, e.g. PROJECT_NAME=$repoFolder" >&2
        return 1
    fi

    # Compose project is <name>-domains (see docker-compose.yml), so its network is <name>-domains_default
    PROJECT_SUBNET="$(docker_pick_subnet "${PROJECT_NAME}-domains_default" "$PROJECT_NAME")" || return 1
    export PROJECT_SUBNET

    echo "Docker project: ${PROJECT_NAME}-domains (network $PROJECT_SUBNET)"
}

# Prints a 172.16.x.0/24 subnet for a network.
# Without this, Docker can run out of its 172.x ranges and fall back to 192.168.0.0/20, which
# takes a home/office router's address (192.168.0.1) and breaks uploads on that network.
# Uses 172.16.x.0/24: Docker never auto-assigns 172.16.x, and home/office routers rarely use it.
# Keeps the network's current subnet if it already has one there; otherwise starts at a slot
# derived from the seed and skips any slot used by another Docker network or a local route.
# Usage: docker_pick_subnet <network name> <seed>
docker_pick_subnet()
{
    local network="$1" seed="$2" current used routes start i candidate

    current="$(docker network inspect "$network" \
        --format '{{range .IPAM.Config}}{{.Subnet}}{{end}}' 2>/dev/null)"
    if [[ "$current" == 172.16.*.0/24 ]]; then
        echo "$current"
        return 0
    fi

    used=" $(docker network inspect $(docker network ls -q) \
        --format '{{range .IPAM.Config}}{{.Subnet}} {{end}}' 2>/dev/null | tr '\n' ' ') "
    start=$(( $(printf '%s' "$seed" | cksum | cut -d' ' -f1) % 256 ))

    for ((i = 0; i < 256; i++)); do
        candidate="172.16.$(( (start + i) % 256 )).0/24"
        case "$used" in *" $candidate "*) continue ;; esac
        # Skip it if the current Wi-Fi/LAN/VPN already routes this range (ignoring the default route)
        routes="$(ip -4 route show match "$candidate" 2>/dev/null | grep -v '^default')"
        [ -n "$routes" ] && continue
        echo "$candidate"
        return 0
    done

    echo "No free 172.16.x.0/24 subnet left for Docker - remove unused networks with: docker network prune" >&2
    return 1
}

# Makes sure the shared domains network and the Traefik proxy are up.
# Traefik's settings live here as flags - there is no separate Traefik config file.
domains_start()
{
    local subnet runningImage

    if ! docker network inspect "$DOMAINS_NETWORK" >/dev/null 2>&1; then
        subnet="$(docker_pick_subnet "$DOMAINS_NETWORK" "$DOMAINS_NETWORK")" || return 1
        run docker network create --subnet "$subnet" "$DOMAINS_NETWORK"
    fi

    if [ -n "$(docker ps -q --filter "name=^${TRAEFIK_NAME}$")" ]; then
        echo "WARNING: domains proxy already running, skipping"
        runningImage="$(docker inspect --format '{{.Config.Image}}' "$TRAEFIK_NAME")"
        if [ "$runningImage" != "$TRAEFIK_IMAGE" ]; then
            echo "WARNING: running proxy is $runningImage, this folder expects $TRAEFIK_IMAGE (run ./0allstop.sh, then start again to switch)"
        fi
        return 0
    fi

    # Remove a stopped leftover so the fixed name is free
    docker rm "$TRAEFIK_NAME" >/dev/null 2>&1 || true

    # label=disable: lets Traefik read the Docker socket when SELinux is enforced
    if ! run docker run -d --name "$TRAEFIK_NAME" --restart unless-stopped \
        -p 80:80 \
        -v /var/run/docker.sock:/var/run/docker.sock:ro \
        --security-opt label=disable \
        --network "$DOMAINS_NETWORK" \
        "$TRAEFIK_IMAGE" \
        --providers.docker=true \
        --providers.docker.exposedbydefault=false \
        --providers.docker.network="$DOMAINS_NETWORK" \
        --entrypoints.web.address=:80; then
        docker rm "$TRAEFIK_NAME" >/dev/null 2>&1 || true
        echo "" >&2
        echo "ERROR: could not start the domains proxy. Port 80 is probably in use (e.g. a dockerLinux project)." >&2
        echo "Run ./0allstop.sh, then start again." >&2
        return 1
    fi
}

# Starts this project's containers (the proxy must already be up)
domains_project_up()
{
    run docker compose up -d
    echo ""
    echo "Site:       http://${PROJECT_NAME}.localhost"
    echo "phpMyAdmin: http://pma.${PROJECT_NAME}.localhost"
}
