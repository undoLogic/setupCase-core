#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"
source ./docker-common.sh
docker_require_access
docker_project_init

# Deletes THIS project's database volume (<name>_db_data) and recreates it from sql/*.sql.
# The volume is shared with dockerLinux/, so that folder sees the reset too.
# Other projects' databases are not touched.
echo ""
echo "This DELETES the '$PROJECT_NAME' database and reloads it from sql/."
read -r -p "Type the project name to confirm: " confirm

if [ "$confirm" != "$PROJECT_NAME" ]; then
    echo "Cancelled - nothing was changed."
    exit 1
fi

run docker compose down

# Removed by its fixed name: it may have been created by the dockerLinux project, which
# "docker compose down -v" here would not remove.
if ! run docker volume rm "${PROJECT_NAME}_db_data"; then
    echo "Could not delete the volume - a dockerLinux container may still hold it." >&2
    echo "Run ../dockerLinux/0stop.sh, then try again." >&2
    exit 1
fi

domains_start
domains_project_up

echo "Database reset. MySQL may need a few seconds to finish loading sql/."
