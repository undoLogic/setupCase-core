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

# Reads PROJECT_NAME from ./.env (the same file docker compose reads) and validates it
docker_project_init()
{
    PROJECT_NAME="$(sed -n 's/^PROJECT_NAME=//p' .env 2>/dev/null | tail -n 1 | tr -d '[:space:]')"

    if [ -z "$PROJECT_NAME" ]; then
        echo "PROJECT_NAME is not set in $(pwd)/.env" >&2
        echo "Add a line like: PROJECT_NAME=my-project (see README.md)" >&2
        return 1
    fi

    if ! [[ "$PROJECT_NAME" =~ ^[a-z0-9][a-z0-9_-]*$ ]]; then
        echo "PROJECT_NAME '$PROJECT_NAME' is invalid: use lowercase letters, digits, - and _ only." >&2
        return 1
    fi

    # A new project copied from the SetupCase Core template still has the template's name
    local repoFolder
    repoFolder="$(basename "$(cd .. && pwd)" | tr '[:upper:]' '[:lower:]')"
    if [ "$PROJECT_NAME" = "setupcase-core" ] && [ "$repoFolder" != "setupcase-core" ]; then
        echo "PROJECT_NAME is still 'setupcase-core' (the template default)." >&2
        echo "Set a unique name for this project in $(pwd)/.env, e.g. PROJECT_NAME=$repoFolder" >&2
        return 1
    fi

    echo "Docker project: $PROJECT_NAME"
}

# All projects use ports 80/443/8081, so only one can run at a time.
# Stops (does not remove) any other compose project holding those ports - its data is kept.
docker_stop_other_projects()
{
    local port project others=""

    for port in 80 443 8081; do
        while IFS= read -r project; do
            [ -z "$project" ] && continue
            [ "$project" = "$PROJECT_NAME" ] && continue
            case " $others " in *" $project "*) ;; *) others="$others $project" ;; esac
        done < <(docker ps --filter "publish=$port" --format '{{.Label "com.docker.compose.project"}}')
    done

    for project in $others; do
        echo "Stopping other project: $project"
        docker ps -q --filter "label=com.docker.compose.project=$project" | xargs -r docker stop >/dev/null
    done
}
