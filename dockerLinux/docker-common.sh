#!/usr/bin/env bash

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
