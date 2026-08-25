#!/usr/bin/env bash

set -o errexit
set -o nounset
set -o pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/launchPad-lib.sh"

environment="${1:-dev}"

require_command git
require_command jq
require_command ssh
require_config

branch="$(git -C "$SCRIPT_DIR/.." rev-parse --abbrev-ref HEAD)"
printf 'Current GIT branch: %s\n' "$branch"

user="$(config_value "$environment" USER)"
url="$(config_value "$environment" URL)"
git_address="$(config_value "$environment" GIT_ADDRESS)"
remote_path="$(config_value "$environment" ABSOLUTE_PATH)"
post_commands="$(config_value "$environment" POST_COMMANDS)"
use_pat="$(config_value "$environment" USE_PAT)"

quoted_path="$(shell_quote "$remote_path")"
quoted_branch="$(shell_quote "$branch")"
clone_path="$(shell_quote "$remote_path/$branch")"

if [[ "$use_pat" == "true" ]]; then
    if [[ -z "${GITHUB_PAT:-}" ]]; then
        printf '\nERROR: GITHUB_PAT is not set on your machine.\n\n' >&2
        printf 'Set it for the current shell with:\n\n' >&2
        printf "export GITHUB_PAT='ghp_xxxxxxxxxxxxxx'\n\n" >&2
        printf 'Add that export to ~/.bashrc to persist it, then open a new shell.\n' >&2
        exit 1
    fi

    clone_url="https://$GITHUB_PAT@$git_address"
else
    clone_url="$git_address"
fi

quoted_clone_url="$(shell_quote "$clone_url")"
remote_command="cd $quoted_path &&
rm -rf -- * &&
rm -rf -- $quoted_branch &&
git clone $quoted_clone_url --branch $quoted_branch --single-branch $clone_path &&
rsync -a --info=progress2,stats --no-perms --omit-dir-times --fake-super $clone_path/sourceFiles/ . &&
$post_commands"

printf '\n--- Environment: %s ---\n\n' "$environment"
if [[ "$use_pat" == "true" ]]; then
    printf '%s\n' "${remote_command//$GITHUB_PAT/<GITHUB_PAT>}"
else
    printf '%s\n' "$remote_command"
fi
echo " ================================== "
echo "ssh $user@$url" "$remote_command"
ssh "$user@$url" "$remote_command"
open_browser "https://$url"

if [[ "$environment" == "dev" || "$environment" == "pending" ]]; then
    printf '\n--- CI Status ---\n'
    show_ci_status
fi
