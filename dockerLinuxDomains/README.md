# Docker Linux Domains

Run several projects at the same time, each on its own `.localhost` address, with no ports to remember.

One shared Traefik proxy listens on port 80 and sends each request to the right project by its domain. The project containers publish no ports. The rules and decisions behind this folder are in [docs/features/MVP-Docker-Linux-Domains.md](../docs/features/MVP-Docker-Linux-Domains.md).

The old `dockerLinux/` folder still works as a fallback (see "Switching back to dockerLinux" below).

## Before you start

- Docker Engine and the Compose plugin are installed, and your user can run `docker` without `sudo`. Follow "Native Linux Setup" in [dockerLinux/README.md](../dockerLinux/README.md).
- Port 80 is free. If a `dockerLinux` project is running, stop it first with `./dockerLinux/0allstop.sh`.

## First time in a project

1. **Set the project name** in `dockerLinuxDomains/.env`:

   ```shell
   PROJECT_NAME=my-project
   ```

   - Use the same name as `dockerLinux/.env`, so both folders share the same database.
   - Lowercase letters, digits and `-` only. The name becomes the domain (`my-project.localhost`), and domains cannot contain `_`.
   - `.env` ships with the placeholder `my-project`. The scripts stop and ask you to change it until you do. They also stop on `setupcase-core` (the template's name) in any project other than SetupCase Core itself.
   - Set it **before** the first build. Changing it later starts a new, empty database under the new name.

2. **Optional: test secrets.** Copy `secrets.env.example` to `secrets.env` and fill it in. It is gitignored and never committed.

3. **Build and start:**

   ```shell
   ./dockerLinuxDomains/1buildDocker.sh
   ```

4. **Open the site in your browser.** This is a step, not a file. `my-project` below stands for your `PROJECT_NAME`.

   When `1buildDocker.sh` finishes, it prints the addresses:

   ```text
   Site:       http://my-project.localhost
   phpMyAdmin: http://pma.my-project.localhost
   ```

   | What | Address | Notes |
   |---|---|---|
   | Repository root | http://my-project.localhost | Apache serves the whole repository, the same as `http://localhost` with `dockerLinux/`. |
   | Install a new project | http://my-project.localhost/init-web/4.x | Run `1-Install.php` (see the main [README.md](../README.md), "Initialize the Project"). |
   | The app | http://my-project.localhost/sourceFiles/en | After the install has built `sourceFiles/`. |
   | phpMyAdmin | http://pma.my-project.localhost | Log in with user `root`, password `undologic`. Databases: `LIVE_database`, `test_automation`. |
   | Database | none | Only the project's own containers can reach it (host `db`). No port is published. |

   - `.localhost` always points at your own computer, so no hosts file changes are needed. Chrome and Firefox both support it, including `pma.` subdomains.
   - Use `http://`, not `https://`. The proxy only listens on port 80.
   - On the first start, MySQL needs a few seconds to load `sql/` before the site and phpMyAdmin can connect. If you see a database error right away, wait and refresh.
   - Seeing `404 page not found` (plain text, from the proxy) means the address doesn't match a running project. Check the name in `.env` and run `docker ps`.
   - To check from a terminal: `curl -I http://my-project.localhost` should return an Apache response, not a proxy `404`.

## Day to day

Run the scripts from anywhere in the repository:

```shell
./dockerLinuxDomains/1startDocker.sh           # start the proxy if needed, then this project
./dockerLinuxDomains/1reStartDocker.sh         # recreate this project's containers (database kept)
./dockerLinuxDomains/1buildDocker.sh           # rebuild the web image (after changing web/Dockerfile), then start
./dockerLinuxDomains/0stop.sh                  # stop this project only (database kept; proxy and other projects keep running)
./dockerLinuxDomains/0allstop.sh               # stop EVERY running container on this machine, including the proxy
./dockerLinuxDomains/2loginDockerContainer.sh  # shell inside this project's web container (Composer etc.)
./dockerLinuxDomains/5startDomains.sh          # start only the shared network and proxy
./dockerLinuxDomains/9resetDatabase.sh         # DELETE this project's database and reload sql/ (asks to confirm)
```

Every script prints the Docker command before it runs it (lines starting with `>`).

## Running several projects

Each project has its own copy of this folder. Run `1startDocker.sh` in each one:

- The first project creates the shared `domains` network and starts the proxy.
- The next ones print `WARNING: domains proxy already running, skipping` and just start. That warning is normal.
- Each project is on its own domain, so logins in the same browser no longer clash.

The proxy's version and settings come from whichever project started it first. Keep `docker-common.sh` identical in every project by updating it from SetupCase Core. If a project expects a different version, it prints a warning. Run `0allstop.sh`, then start again to switch.

## Configuration

| What | Where |
|---|---|
| Project name | `.env` |
| PHP version and extensions | `web/Dockerfile` (edit the `FROM` line, then run `1buildDocker.sh`) |
| PHP settings (memory, upload size) | `ini/php.ini` |
| Apache site | `sites-available/000-default.conf` |
| Database URLs, salt and other environment | `docker-compose.yml` (`web` -> `environment`) |
| Test-only secrets | `secrets.env` (copied from `secrets.env.example`) |
| Starting database | `sql/*.sql` |
| Proxy image and settings | `docker-common.sh` (`TRAEFIK_IMAGE`, `domains_start()`) |

After changing `.env`, `docker-compose.yml`, `secrets.env` or `ini/php.ini`, run `1reStartDocker.sh`. After changing `web/Dockerfile`, run `1buildDocker.sh`.

The names Docker uses:

| Item | Name |
|---|---|
| Web container | `<name>-web` |
| phpMyAdmin container | `<name>-pma` |
| Database container | `<name>-db` |
| Web image | `<name>_web:local` |
| Database volume | `<name>_db_data` (shared with `dockerLinux/`) |
| Compose project | `<name>-domains` |
| Proxy container | `traefik` (one per machine) |

## Database

- The database lives in the volume `<name>_db_data`. It survives stop, restart and switching to `dockerLinux/`, which uses the same volume.
- `sql/*.sql` only runs the **first** time the volume is created.
- If the volume was first created by `dockerLinux/`, Compose may print that it "already exists but was created for project ..." That warning is expected.
- `9resetDatabase.sh` deletes the volume and reloads `sql/`. The reset also applies to `dockerLinux/`, since it is the same database.
- Locally uploaded files (`private/files`, gitignored) are **not** shared with `dockerLinux/`. Each folder has its own.

## Switching back to dockerLinux

Always the same routine:

```shell
./dockerLinuxDomains/0allstop.sh   # stops every running container, including the proxy, to free port 80
./dockerLinux/1startDocker.sh      # the old folder, on its usual localhost ports
```

This stops every other running project too. To come back, run `./dockerLinux/0allstop.sh` and then `./dockerLinuxDomains/1startDocker.sh`. Both folders have the same `0allstop.sh`.

## Troubleshooting

**`ERROR: could not start the domains proxy. Port 80 is probably in use`**
Something else is on port 80, usually a `dockerLinux` project. Run `0allstop.sh`, then start again. To see what holds the port: `docker ps --filter publish=80`.

**The browser shows `404 page not found`**
The proxy is running but no project answers on that domain. Check that the project is running (`docker ps`) and that the address matches `PROJECT_NAME` in `.env`.

**The page doesn't load at all**
The proxy isn't running. Run `1startDocker.sh` or `5startDomains.sh`. Check with `docker ps --filter name=traefik`.

**`9resetDatabase.sh` says it could not delete the volume**
A `dockerLinux` container still holds the database. Run `./dockerLinux/0stop.sh`, then try again.

**Slow uploads or internet dropouts while projects run**
Follow "Troubleshooting" and the "Runbook for AI assistants" in [dockerLinux/README.md](../dockerLinux/README.md). This folder picks its subnets the same way: each project network, and the shared `domains` network, gets its own `172.16.x.0/24`.

## Cleaning up

```shell
docker ps -a                                  # containers: <name>-web, <name>-pma, <name>-db, traefik
docker volume ls                              # databases: <name>_db_data
docker compose -p <name>-domains down         # remove a project's containers (database kept)
docker image rm <name>_web:local              # remove a project's web image
docker rm -f traefik                          # remove the proxy (the next start recreates it)
docker network rm domains                     # remove the shared network (only when no project uses it)
```

Never use `docker compose down -v` or `docker volume prune` to clean up. They delete project databases.
