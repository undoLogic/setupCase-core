# LaunchPAD

Bash launchers for preparing development and pending environments, then
promoting pending files to live.

## Requirements

- Bash
- Git
- jq
- SSH
- `rsync` on the remote server
- `xdg-open` or `gio` locally to open the deployed site automatically

## Setup
Modify the config.json file with your environment details. (For SECURITY we do NOT store any secrets here)
"URL": "",
- The public URL of the server.

"USER": "",
- The username to of the server

"GIT_ADDRESS": "",
- HTTPS (NO KEYS): github.com/undoLogic/projectName.git
- SSH-KEYS: git@github.com:undoLogic/projectname.git
  NOTE: Multiple projects on one server requires git@PROJECT.github.com:undoLogic/projectName.git

"USE_PAT": true,
- If true will use the GitHub Personal Access Token to checkout the repository.
  NORMALLY when using SSH keys you do NOT use PAT

"ABSOLUTE_PATH": "",
- The absolute path to the project on the server.

"COPY_SRC_TO_ROOT": true,
- Profiles files use the branch name eg main, if 'true' it will copy all the files to the root of the project, so you can access normally (without the branch name in the path)

"POST_COMMANDS": "composer install"
- Which commands to run after the project is cloned.

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

## Create PhpStorm deployment servers

Generate the project deployment configuration from `config.json`:

```bash
./launchPad_linux/8-phpstorm-init.sh
```

This creates `.idea/webServers.xml` and `.idea/deployment.xml`. Existing files
are backed up before replacement. Each environment requires `USER`,
`ABSOLUTE_PATH`, and either `SFTP_HOST` or `URL`; incomplete environments are
skipped.

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






### GITHUB_HOST - Configure multiple projects on the same server

Github does NOT allow (for security) to add mutliple SSH-KEYS to the same server. In order to setup multiple projects on the same server you need to create separate github hostnames to reference each project.
- First create the new private/public file which will be used for this github project and we are specifying the "-f ..." so we won't overright our original key pairs
```
cd ~/.ssh
ssh-keygen -t ed25519 -C "you@email.com" -f id_ed25519_projectName1
chmod 600 id_ed25519_projectName1*

cat id_ed25519_projectName1.pub
```

- Now create or edit your ssh config file
```
  nano ~/.ssh/config
```
- Add your new public (ends with .PUB) you created above into the ssh config file
- Ensure the GITHUB_HOST matches the Host line (Hostname is ALWAYS github.com)
- project1 can be any name to represent your project

```
Host project1.github.com
        Hostname github.com
        IdentityFile ~/.ssh/id_ed25519_projectName1
Host project2.github.com
        Hostname github.com
        IdentityFile ~/.ssh/id_ed25519_projectName2
```

NOTE: You do NOT need to manually clone, but just so you understand how this works, and if you wanted to manually clone
Launch will automatically do this for you after you change 'GITHUB_HOST' in the launch/settings.json

```shell
git clone git@project1.github.com:OWNER/repo-project1.git
# OR
git clone git@project2.github.com:OWNER/repo-project2.git
```