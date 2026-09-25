#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Deletes THIS project's database volume (<name>_db_data) and recreates it from sql/*.sql (init.sql).
# Other projects' databases are not touched.
echo ""
echo "This DELETES the '$PROJECT_NAME' database and reloads it from sql/init.sql."
read -r -p "Type the project name to confirm: " confirm

if [ "$confirm" != "$PROJECT_NAME" ]; then
    echo "Cancelled - nothing was changed."
    exit 1
fi

docker compose down -v

docker_stop_other_projects
docker compose up -d

echo "Database reset. MySQL may need a few seconds to finish loading init.sql."
