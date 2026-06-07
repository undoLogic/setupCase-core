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

## Scripts

Run the scripts from anywhere in the repository:

```shell
./dockerWSL/1buildDocker.sh
./dockerWSL/1startDocker.sh
./dockerWSL/1reStartDocker.sh
./dockerWSL/0stop.sh
./dockerWSL/2loginDockerContainer.sh
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
