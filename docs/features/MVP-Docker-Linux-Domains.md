# FEATURE: Docker Linux Domains

SetupCase by undoLogic, core feature. A new folder in SetupCase core, next to the existing Docker Linux folder (which stays untouched). Each project gets its own `.localhost` domains through a shared local Traefik reverse proxy, so several projects run at once with no port juggling and no cookie clashes.

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

- `.env` holds `PROJECT_NAME` and `PHP_VERSION`
- `docker-compose.yml`
- `sites-available/`
- `sql/`
- `web/` (renamed from `web8.1`; the PHP version is set in `.env` and read by the Dockerfile, so upgrades change one line)
- the numbered scripts below

First-time setup in a project: copy the folder in, set `PROJECT_NAME` in `.env`, run `1-build-docker.sh`.

---

## 5. Scripts

Numbering: 0 = stop commands, 1 = normal in-order flow, 2 = working inside the container, 5 = auxiliary, 9 = database reset.

| Script | What it does |
|---|---|
| `0-all-stop.sh` | Stops **every** running Docker container on the machine, including Traefik. The name makes clear it's not just this project. (`0a` + tab) |
| `0-stop.sh` | Stops the current project only. |
| `1-build-docker.sh` | Builds the project's images (same as today). |
| `1-restart-docker.sh` | Restarts the current project. |
| `1-start-docker.sh` | Ensures the domains proxy is up, then starts the project (see section 6). Pure start, not a restart. |
| `2-login-docker-container.sh` | Opens a shell in the project's web container (for Composer etc.). |
| `5-start-domains.sh` | Auxiliary: manually starts Traefik and the shared network. |
| `9-reset-database.sh` | Reloads the database from `sql/` while it keeps running (same as today). |

All scripts echo the real Docker command before running it.

---

## 6. 1-start-docker.sh logic

1. Create the shared `domains` network if it does not exist.
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

docker network inspect domains >/dev/null 2>&1 || run docker network create domains

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
- Both variants use the **same container names**, so starting one while the other runs fails loudly ("name already in use").
- Both variants use **different Compose project names** (e.g. `${PROJECT_NAME}` vs `${PROJECT_NAME}-domains`). If these matched, Docker would quietly swap containers instead of erroring.
- The database volume has a **fixed name** in both variants, so either one sees the same data.
- To fall back: `0-stop.sh` here, then start the original Docker Linux folder with its localhost ports.

---

## 8. Test plan

| # | Test | Expected |
|---|---|---|
| 1 | Fresh machine, run `1-start-docker.sh` in project A | Network and Traefik created, A reachable at `a.localhost` and `pma.a.localhost` |
| 2 | Run `1-start-docker.sh` in project B | Warning that the proxy is running, B starts, both reachable |
| 3 | Log into A and B in the same browser | Sessions independent |
| 4 | `0-stop.sh` in A | Only A stops; B and Traefik keep running |
| 5 | Start A in the original Docker Linux folder while A-domains runs | Fails with "name already in use" |
| 6 | Stop A-domains, start A in the original folder | Works on localhost port, same database data |
| 7 | `0-all-stop.sh` | Every container stopped, including Traefik |
| 8 | `5-start-domains.sh` then start a project | Works; script reports proxy already running |
| 9 | Connect to the database from outside Docker | Not reachable (no address, no published port) |

---

## 9. Notes

- Each project gets its own domain, so session cookies no longer clash between projects (cookies ignore ports).
- Password managers (e.g. Bitwarden): add `{PROJECT_NAME}.localhost` as an extra address on the live login and set match detection to host, so local projects don't share logins.
- Possible later addition: a mail catcher at `mail.{PROJECT_NAME}.localhost`.
- Public repo: docs and examples name no clients; refer to any client as CUSTOMER.
