# Docker Scripts

Linux shell scripts used to:

- Start containers
- Manage Docker services
- Standardize environment bootstrapping

## Native Linux Setup

Install Docker Engine and the Compose plugin using Docker's instructions for
your Linux distribution.

Allow your user to access Docker without `sudo`:

```shell
sudo usermod -aG docker "$USER"
```

Log out of Linux and log back in so the group change takes effect. For the
current terminal only, you can instead run:

```shell
newgrp docker
```

Verify Docker access:

```shell
docker info
docker compose version
```

If Docker is not running:

```shell
sudo systemctl enable --now docker
```

## Project Name (do this first for every new project)

Every project gets its own Docker names so projects never overwrite each other.
Set a unique name in `dockerLinux/.env`:

```shell
PROJECT_NAME=my-project
```

- Lowercase letters, digits, `-` and `_` only.
- A new project copied from SetupCase Core still says `setupcase-core` - the scripts stop and ask you to change it.
- Change it **before** the first build. Changing it later creates a new, empty stack (the old one stays under the old name).

The name is used for:

| Item | Name |
|---|---|
| Web (PHP) container | `<name>-web-1` |
| Database container | `<name>-db-1` |
| phpMyAdmin container | `<name>-phpmyadmin-1` (stock image, nothing to build) |
| Web image | `<name>_web81:local` |
| Database volume | `<name>_db_data` |

## Build Once, Then Switch

```shell
./dockerLinux/1buildDocker.sh     # first time per project, or after changing web81/Dockerfile
./dockerLinux/1startDocker.sh     # every other time - also to switch to this project
```

- All projects use ports `80`, `443` and `8081`, so only one runs at a time.
- `1startDocker.sh`, `1reStartDocker.sh` and `1buildDocker.sh` first **stop** any other project holding those ports. Its containers and database are kept, ready for its own `1startDocker.sh`.
- Switching projects never needs a rebuild.

## Database Persistence

Each project's database lives in its own volume (`<name>_db_data`) and survives stop, restart and switching projects.

- `sql/*.sql` (including `init.sql`) only runs the **first** time the volume is created.
- To reset a project's database to `init.sql`:

```shell
./dockerLinux/9resetDatabase.sh   # asks you to type the project name, then deletes and reloads ONLY this project's database
```

`0stop.sh` never deletes the database.

## Scripts

Run the scripts from anywhere in the repository:

```shell
./dockerLinux/1buildDocker.sh           # build this project's image (first time), then start
./dockerLinux/1startDocker.sh           # start / switch to this project
./dockerLinux/1reStartDocker.sh         # recreate this project's containers (database kept)
./dockerLinux/0stop.sh                  # stop and remove this project's containers (database kept)
./dockerLinux/2loginDockerContainer.sh  # shell inside this project's web container
./dockerLinux/9resetDatabase.sh         # DELETE this project's database and reload init.sql (asks to confirm)
```

## Cleaning Up Old Projects

```shell
docker ps -a                          # containers per project: <name>-web-1, <name>-db-1, ...
docker volume ls                      # databases: <name>_db_data
docker compose -p <name> down -v      # remove a project's containers and database
docker image rm <name>_web81:local    # remove a project's web image
```

## SQL MODE

Recommended:

```shell
SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION';
```

Verify with:

```shell
SELECT @@GLOBAL.sql_mode, @@SESSION.sql_mode;
```
