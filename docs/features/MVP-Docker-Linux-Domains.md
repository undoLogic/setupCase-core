# FEATURE: Docker Linux Domains

SetupCase by undoLogic, core feature. A new folder in SetupCase core, next to the existing Docker Linux folder. The old folder's only change is one added file, `0allstop.sh` (see section 7). Each project gets its own `.localhost` domains through a shared local Traefik reverse proxy, so several projects run at once with no port juggling and no cookie clashes.

The folder is named after the concept, not the tool. If Traefik is ever swapped for another proxy, the name still holds.

---

## 1. Goals

- Run 3–5 projects locally at the same time.
- No published ports to remember. Each project is reached by name.
- Each project stays a self-contained checkout: Docker, scripts and source files all live in the repo.
- Stay close to the metal: plain Docker and Compose, no hidden magic. Scripts print the Docker commands they run.
- The original Docker Linux folder remains a working fallback.

---

## 2. Addresses

Every project normally has three containers.

| Container | Address | Networks |
|---|---|---|
| Web (PHP/Apache) | `{PROJECT_NAME}.localhost` | Project network + shared `domains` network |
| phpMyAdmin | `pma.{PROJECT_NAME}.localhost` | Project network + shared `domains` network |
| Database | none | Project network only (private) |

`.localhost` resolves to the local machine in browsers and on Fedora/Aurora. No hosts file edits needed. Never reuse live domains locally.

A basic site may have only the web container.

---

## 3. How it works

1. One Traefik container listens on port 80 of the machine. It is the only thing that publishes a port.
2. Traefik watches Docker and only routes containers that opt in with labels (`exposedByDefault=false`). It does not scan files.
3. Each project's compose file carries labels declaring its own domains. There is no central list of subdomains.
4. Project containers publish no ports. Inside Docker each web container listens on port 80 on its own address.
5. Traefik's settings live as flags inside the start script. No separate Traefik config file.
6. Optional: Traefik dashboard at `traefik.localhost`, showing only running, labelled projects.

Example labels on the web service:

```yaml
services:
  web:
    container_name: ${PROJECT_NAME}-web
    networks: [default, domains]
    labels:
      - traefik.enable=true
      - traefik.docker.network=domains
      - traefik.http.routers.${PROJECT_NAME}.rule=Host(`${PROJECT_NAME}.localhost`)
      - traefik.http.services.${PROJECT_NAME}.loadbalancer.server.port=80

  phpmyadmin:
    container_name: ${PROJECT_NAME}-pma
    networks: [default, domains]
    labels:
      - traefik.enable=true
      - traefik.docker.network=domains
      - traefik.http.routers.${PROJECT_NAME}-pma.rule=Host(`pma.${PROJECT_NAME}.localhost`)
      - traefik.http.services.${PROJECT_NAME}-pma.loadbalancer.server.port=80

  db:
    container_name: ${PROJECT_NAME}-db
    networks: [default]          # private, no address
    volumes:
      - db_data:/var/lib/mysql

networks:
  domains:
    external: true

volumes:
  db_data:
    name: ${PROJECT_NAME}_db_data   # fixed name, shared with the original Docker Linux folder
```

---

## 4. Folder structure

Same structure as the existing Docker Linux folder:

- `.env` holds `PROJECT_NAME` only (same as today)
- `docker-compose.yml`
- `docker-common.sh` (own copy: Docker access check, `PROJECT_NAME` validation, subnet picking)
- `sites-available/`, `ini/`, `control/`
- `sql/`
- `secrets.env.example` (copied to `secrets.env`, never committed)
- `web/` (renamed from `web81`, whose name is already stale: its Dockerfile uses PHP 8.3). The Dockerfile holds the PHP version and everything else directly, with no build variables. An upgrade edits the Dockerfile's `FROM` line and the folder name never goes stale.
- the numbered scripts below

First-time setup in a project: copy the folder in, set `PROJECT_NAME` in `.env`, run `1buildDocker.sh`.

---

## 5. Scripts

Numbering: 0 = stop commands, 1 = normal in-order flow, 2 = working inside the container, 5 = auxiliary, 9 = database reset.

| Script | What it does |
|---|---|
| `0allstop.sh` | Stops **every** running Docker container on the machine, including Traefik. The name makes clear it's not just this project. (`0a` + tab) An identical copy lives in the old Docker Linux folder (see section 7). |
| `0stop.sh` | Stops the current project only. |
| `1buildDocker.sh` | Builds the project's images (same as today). |
| `1reStartDocker.sh` | Restarts the current project. |
| `1startDocker.sh` | Ensures the domains proxy is up, then starts the project (see section 6). Pure start, not a restart. |
| `2loginDockerContainer.sh` | Opens a shell in the project's web container (for Composer etc.). |
| `5startDomains.sh` | Auxiliary: manually starts Traefik and the shared network. |
| `9resetDatabase.sh` | Reloads the database from `sql/` while it keeps running (same as today). |

All scripts echo the real Docker command before running it.

---

## 6. 1startDocker.sh logic

0. Same checks as today: Docker access, `PROJECT_NAME` valid, pick the project subnet. The subnet check reads `${PROJECT_NAME}-domains_default` (this variant's Compose project name), not `${PROJECT_NAME}_default`.
1. Create the shared `domains` network if it does not exist, with a fixed `172.16.x.0/24` subnet picked the same way as project subnets. A plain `docker network create` can land on `192.168.x` and take the router's address (see "Local Docker Network Troubleshooting" in AGENTS.md).
2. **Check** whether a container named `traefik` is running (ask Docker; don't try-and-fail).
3. If running: print a warning ("domains proxy already running, skipping"), not an error. Optionally warn if its version differs from the one this folder expects.
4. If not running: start Traefik with a single `docker run` (fixed name `traefik`, port 80, Docker socket read-only, `exposedByDefault=false`, attached to `domains`).
5. Start the project with `docker compose up -d`.
6. Print the project's addresses.

Sketch:

```bash
#!/usr/bin/env bash
set -e
source .env
TRAEFIK_IMAGE="traefik:v3"

run() { echo "> $*"; "$@"; }

docker network inspect domains >/dev/null 2>&1 || run docker network create --subnet "$DOMAINS_SUBNET" domains

if [ "$(docker ps -q -f name=^traefik$)" ]; then
  echo "WARNING: domains proxy already running, skipping"
else
  docker rm traefik >/dev/null 2>&1 || true   # remove a stopped leftover
  run docker run -d --name traefik --restart unless-stopped \
    -p 80:80 \
    -v /var/run/docker.sock:/var/run/docker.sock:ro \
    --network domains \
    "$TRAEFIK_IMAGE" \
    --providers.docker=true \
    --providers.docker.exposedbydefault=false \
    --entrypoints.web.address=:80
fi

run docker compose -p "${PROJECT_NAME}-domains" up -d

echo "Site:       http://${PROJECT_NAME}.localhost"
echo "phpMyAdmin: http://pma.${PROJECT_NAME}.localhost"
```

Every project ships the identical script, so the first project started brings up Traefik and later ones skip it. Keep the script identical everywhere and update it through SetupCase core, because whichever project starts Traefik first decides its version and settings.

Note for Aurora / Fedora Silverblue: binding port 80 needs either rootful Docker or allowing unprivileged ports. Verify on first setup.

---

## 7. Fallback to the original folder

- Run only one variant of a project at a time.
- **Port 80 is the guard.** The original folder publishes port 80 on its web container; here Traefik holds port 80. So the two variants can't run together: whichever starts second fails loudly ("port is already allocated").
  - Container names do not collide: the original folder sets no `container_name`, so its containers are `${PROJECT_NAME}-web-1` etc., while this folder uses `${PROJECT_NAME}-web`. The old folder's compose file is not changed, so it does not get names to match.
  - The original `1startDocker.sh` stops other Compose projects holding port 80. Traefik is started with `docker run`, not Compose, so it is not stopped by that and the start fails as above.
- Both variants use **different Compose project names** (`${PROJECT_NAME}` vs `${PROJECT_NAME}-domains`). If these matched, Docker would quietly swap containers instead of erroring.
- The database volume has a **fixed name** in both variants (`${PROJECT_NAME}_db_data`, which is the name the original folder already gets), so either one sees the same data. Compose may warn that the volume was created by another project; that is expected. Verify on first run that it is a warning, not an error.
- **Fallback routine (always the same):** run `0allstop.sh`, then start the old Docker Linux folder with its localhost ports. Traefik must stop to free port 80, so this stops every other running project too. That is accepted.
- `0allstop.sh` is added to the old Docker Linux folder as well, identical to the one in this folder. Keep the two copies in sync: a change to one is made to both in the same change. This is the only change to the old folder, and it only adds a file.

---

## Testing

| # | Test | Expected |
|---|---|---|
| 1 | Fresh machine, run `1startDocker.sh` in project A | Network and Traefik created, A reachable at `a.localhost` and `pma.a.localhost` |
| 2 | Run `1startDocker.sh` in project B | Warning that the proxy is running, B starts, both reachable |
| 3 | Log into A and B in the same browser | Sessions independent |
| 4 | `0stop.sh` in A | Only A stops; B and Traefik keep running |
| 5 | Start A in the original Docker Linux folder while A-domains runs | Fails with "port is already allocated" (port 80 held by Traefik) |
| 6 | `0allstop.sh`, then start A in the original folder | Works on localhost port, same database data |
| 6b | Original A running, then `1startDocker.sh` in A-domains | Traefik fails to bind port 80; script stops with an error, not a warning |
| 7 | `0allstop.sh` | Every container stopped, including Traefik |
| 8 | `5startDomains.sh` then start a project | Works; script reports proxy already running |
| 9 | Connect to the database from outside Docker | Not reachable (no address, no published port) |

---

## 8. Notes

- Each project gets its own domain, so session cookies no longer clash between projects (cookies ignore ports).
- Password managers (e.g. Bitwarden): add `{PROJECT_NAME}.localhost` as an extra address on the live login and set match detection to host, so local projects don't share logins.
- Possible later addition: a mail catcher at `mail.{PROJECT_NAME}.localhost`.
- Public repo: docs and examples name no clients; refer to any client as CUSTOMER.
