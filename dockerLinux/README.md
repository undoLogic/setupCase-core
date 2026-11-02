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

- `sql/*.sql` (including `09-SetupCase-testing_data_delete.sql`) only runs the **first** time the volume is created.
- To reset a project's database to `09-SetupCase-testing_data_delete.sql`:

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
./dockerLinux/9resetDatabase.sh         # DELETE this project's database and reload 09-SetupCase-testing_data_delete.sql (asks to confirm)
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

## Troubleshooting

### Internet uploads are very slow (downloads are fine) after starting a project

**Symptoms**

- Uploads crawl or stall: pushing to Git, uploading files, video calls. Downloads and browsing still look normal.
- Some sites time out, or the connection drops for a few minutes and comes back after reconnecting Wi-Fi.
- Other computers on the same network work fine.
- It often starts after moving the laptop between networks (home, office, café).

**Cause**

Each project gets its own Docker network, and Docker gives that network an IP range.
Docker's automatic ranges are `172.17.x` to `172.31.x`, one per network. Once old
projects use all of them, Docker falls back to `192.168.0.0/20`, the same range many
home and office routers use.

Docker only checks for clashes **when it creates the network**. If you start a project
on a network that doesn't use `192.168.0.x` (for example at home) and later join one
that does (for example at the office), the Docker network keeps the address
`192.168.0.1`. That is the router's address, so your computer now thinks the router's
IP is itself. Normal traffic still reaches the router, but some of the router's replies
(including "packet too big, send smaller ones") look like they come from your own
address and Linux drops them. Large outgoing packets then keep being resent, so uploads
stall while downloads mostly keep working.

Stopping the containers is not enough: the network, and the address it holds, stays
until it is removed with `0stop.sh` / `docker compose down`, and `docker ps` does not
show networks at all.

**Check**

```shell
ip route get "$(ip route | awk '/default/{print $3; exit}')"
```

- Normal: `192.168.0.1 dev wlp0s20f3 src 192.168.0.150 ...`
- Clash: `local 192.168.0.1 dev lo table local ...`. Your computer owns the router's address.

Find the Docker network that holds it:

```shell
ip -br addr | grep br-                     # look for an UP/DOWN bridge with the router's address
docker network ls
docker network inspect <network> --format '{{range .IPAM.Config}}{{.Subnet}}{{end}}'
```

**Fix it now**

```shell
./dockerLinux/0stop.sh                     # removes this project's network (database is kept)
docker network rm <network>                # or remove a network left behind by another project
docker network prune                       # removes every network no container is using
```

Uploads return to normal as soon as the network is gone.

**How the scripts prevent it**

`docker-common.sh` (`docker_pick_subnet`) gives each project its own `/24` network
in `172.16.x.0/24`, a range Docker never assigns automatically and routers rarely use.
`docker-compose.yml` passes it in as `PROJECT_SUBNET`. The scripts:

- keep a project's existing `172.16.x` network, so its range never changes;
- otherwise start at a slot based on `PROJECT_NAME` and skip any range already used by another Docker network or by the current Wi-Fi, LAN or VPN;
- print the chosen range: `Docker project: my-project (network 172.16.129.0/24)`.

Projects created before this change still have their old network. Run `0stop.sh` and
then `1startDocker.sh` once to move them to `172.16.x`. If you run `docker compose`
directly without the scripts, the network falls back to `172.16.250.0/24`.

**Optional: protect every Docker project on the machine**

To keep Docker away from `192.168.x` for all projects, including ones that don't use
these scripts, tell Docker to give out small `172.x` networks instead (this restarts
Docker and briefly stops running containers):

```shell
echo '{ "default-address-pools": [ { "base": "172.17.0.0/12", "size": 24 } ] }' | sudo tee /etc/docker/daemon.json
sudo systemctl restart docker
```

This replaces any existing `/etc/docker/daemon.json`. If you already have one, add the
`default-address-pools` key to it by hand.

### Runbook for AI assistants: network clash diagnosis and fix

Follow these steps in order when a user reports slow uploads, broken internet or
dropouts on a machine running these projects. Run the commands yourself and report
what you find. Commands marked **(user)** need `sudo`, so ask the user to run them.

**Step 1: confirm the symptom is a clash, not Wi-Fi or the ISP**

```shell
# A. Does the default gateway resolve to this machine? "local ..." = clash confirmed.
ip route get "$(ip route | awk '/default/{print $3; exit}')"

# B. Which interfaces hold addresses inside the LAN's range? Any br-* or docker0 here = clash.
ip -br addr | grep -E '^(br-|docker0)'
ip -4 route | grep -v '^default'          # compare bridge ranges with the Wi-Fi/LAN range

# C. Measure upload vs download (ICMP ping is often blocked on office networks; use HTTPS).
head -c 5000000 /dev/urandom > /tmp/up.bin
curl -s -o /dev/null -w 'upload %{speed_upload} B/s\n' -X POST --data-binary @/tmp/up.bin https://speed.cloudflare.com/__up
curl -s -o /dev/null -w 'download %{speed_download} B/s\n' 'https://speed.cloudflare.com/__down?bytes=25000000'

# D. Rule out the radio: a signal better than about -70 dBm and a tx bitrate above about 50 MBit/s are fine.
iw dev "$(ip route | awk '/default/{print $5; exit}')" link
```

If A says `local`, or a bridge range overlaps the LAN range, continue below. If not,
the problem is somewhere else: Wi-Fi, ISP, VPN (`tailscale status`), or MTU.

**Step 2: find the Docker network that causes it**

```shell
docker network ls --format '{{.Name}}' | while read n; do
  echo "$n $(docker network inspect "$n" --format '{{range .IPAM.Config}}{{.Subnet}} {{end}}{{.Created}}')"
done
# For the clashing network: which containers and which project folder?
docker ps -a --filter network=<network> \
  --format '{{.Names}}\t{{.Status}}\t{{.Label "com.docker.compose.project.working_dir"}}'
```

- `docker ps` only shows running containers. It never shows networks, so a clashing network can exist with nothing listed.
- Check `docker context ls`: the scripts expect the system daemon (`/var/run/docker.sock`), not Podman or rootless Docker.
- If the clashing network was created on another network (home or office), that is expected. Docker only checks for clashes when it creates a network.

**Step 3: remove the clash, keeping data**

- If the project is unused or a throwaway test, and the user confirms, remove it: `docker rm <stopped containers>` and then `docker network rm <network>`.
- If the project is in use, run `./dockerLinux/0stop.sh` in its folder and then `./dockerLinux/1startDocker.sh`. That recreates the network in `172.16.x`. Tell the user first, because the site goes down for a few seconds.
- Networks no container uses: `docker network prune`. It never touches containers, images or volumes.
- **Never** use `docker compose down -v`, `docker volume prune` or `docker system prune --volumes` for this. They delete project databases.
- Do not restart Docker (`systemctl restart docker`) without asking. It stops every running project.

**Step 4: verify**

Repeat step 1 A and C. The gateway must no longer say `local`, and upload speed should
be back in line with download.

**Step 5: prevent it from coming back**

1. Check that the project's `dockerLinux/docker-common.sh` has `docker_pick_subnet`, and that its `docker-compose.yml` has the `networks: default: ipam: ... ${PROJECT_SUBNET...}` block. If either is missing, copy both from SetupCase Core. The rest of these two files is shared template code, so `diff` them against SetupCase Core before overwriting.
2. Check for projects still on old ranges, outside `172.16.x`: the list from step 2. Each one should be moved with `0stop.sh` and then `1startDocker.sh` when the user is ready. A `192.168.x` network is the high-risk one, even if it doesn't clash on today's network.
3. **(user)** Optional, protects every Docker project on the machine: the `daemon.json` command under "Optional: protect every Docker project on the machine" above. Check first whether `/etc/docker/daemon.json` exists (`cat /etc/docker/daemon.json`), and merge the setting into it rather than overwriting.
4. The `dockerWSL`, `dockerWIN` and `dockerIDE` variants do not have `docker_pick_subnet`. A WSL or Docker Desktop machine can hit the same clash. Fix it there by adding the same `networks:` block with a fixed `172.16.x.0/24` subnet unique to that project.

**What this looks like**

| Check | Clash | Healthy |
|---|---|---|
| `ip route get <gateway>` | `local 192.168.0.1 dev lo table local` | `192.168.0.1 dev wlp... src 192.168.0.x` |
| `ip -br addr` | `br-xxxx ... 192.168.0.1/20` | bridges only in `172.x` |
| Upload (5 MB) | about 1 Mbit/s, about 30 s or more | similar to download, about 1 s |
| Script output | `Docker project: name` (no network shown = old scripts) | `Docker project: name (network 172.16.x.0/24)` |
