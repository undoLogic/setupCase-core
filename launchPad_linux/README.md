# LaunchPAD (Linux)

Bash launchers for preparing development and pending environments, then
promoting pending files to live.

## Requirements

- Bash
- Git
- jq
- SSH
- `rsync` on the remote server
- `xdg-open` or `gio` locally to open the deployed site automatically

## Usage

```bash
./launchPad_linux/1-prepare-dev.sh
./launchPad_linux/1-prepare-dev2.sh
./launchPad_linux/2-prepare-pending.sh
./launchPad_linux/3-go-LIVE.sh
```

You can also select an environment directly:

```bash
./launchPad_linux/prepare.sh pending
```

## CI Deployment Gate

The live deployment ignores `.ci_status.json` unless the gate is explicitly
enabled in `launchPad_linux/config.json`:

```json
"CI_GATE_ENABLED": true
```

When enabled, `3-go-LIVE.sh` requires a successful CI status for the exact
commit being deployed. A missing, invalid, failed, or stale status blocks the
deployment.

## GitHub Personal Access Token

For environments with `"USE_PAT": true`, set `GITHUB_PAT` locally:

```bash
export GITHUB_PAT='ghp_xxxxxxxxxxxxxx'
```

Add the export to `~/.bashrc` to persist it, then open a new shell. Create
tokens at <https://github.com/settings/tokens/new>.

## SSH Keys

For environments with `"USE_PAT": false`, the remote server must have an SSH
key that can clone the configured repository.

For multiple projects on one server, define separate GitHub host aliases in
`~/.ssh/config`:

```text
Host project1.github.com
    HostName github.com
    IdentityFile ~/.ssh/id_ed25519_projectName1
```

Then use that alias in `GIT_ADDRESS`:

```text
git@project1.github.com:OWNER/repository.git
```

The first SSH connection automatically records a new host key. SSH still
blocks the connection if a previously recorded host key changes.
