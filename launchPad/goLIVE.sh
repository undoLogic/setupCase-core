#!/usr/bin/env bash

set -o errexit
set -o nounset
set -o pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/launchPad-lib.sh"

require_command git
require_command jq
require_command ssh
require_config

branch="$(git -C "$SCRIPT_DIR/.." rev-parse --abbrev-ref HEAD)"
deploy_commit="$(git -C "$SCRIPT_DIR/.." rev-parse HEAD)"
ci_gate_enabled="$(jq --raw-output '.environments.LIVE.CI_GATE_ENABLED // false' "$CONFIG_FILE")"
printf 'Current GIT branch: %s\n' "$branch"

if [[ "$ci_gate_enabled" == "true" ]]; then
    if [[ ! -f "$CI_STATUS_FILE" ]]; then
        printf 'ERROR: Deployment blocked: CI gate is enabled but .ci_status.json was not found.\n' >&2
        exit 1
    fi

    if ! ci_status_is_valid; then
        printf 'ERROR: .ci_status.json is invalid JSON\n' >&2
        exit 1
    fi

    ci_state="$(ci_value status)"
    ci_commit="$(ci_value commit)"
    ci_timestamp="$(ci_value timestamp)"

    printf '\n--- CI Gate ---\n'
    printf 'status: %s\n' "$ci_state"
    printf 'commit: %s\n' "$ci_commit"
    printf 'timestamp: %s\n' "$ci_timestamp"
    printf 'deploy commit: %s\n' "$deploy_commit"

    if [[ "$ci_state" != "success" ]]; then
        printf "ERROR: Deployment blocked: CI status must be 'success'.\n" >&2
        exit 1
    fi

    if [[ -z "$ci_commit" || "$ci_commit" != "$deploy_commit" ]]; then
        printf 'ERROR: Deployment blocked: CI commit does not match deploy commit.\n' >&2
        exit 1
    fi

    printf 'CI gate passed for this commit.\n'
else
    printf 'CI gate is not enabled; skipping CI status check.\n'
fi

user="$(config_value pending USER)"
url="$(config_value LIVE URL)"
pending_path="$(config_value pending ABSOLUTE_PATH)"
live_path="$(config_value LIVE ABSOLUTE_PATH)"
post_commands="$(config_value LIVE POST_COMMANDS)"

source_path="$(shell_quote "$pending_path/$branch/sourceFiles/.")"
target_path="$(shell_quote "$live_path/.")"
quoted_live_path="$(shell_quote "$live_path")"
remote_command="rsync -av --no-perms --omit-dir-times --fake-super $source_path $target_path &&
cd $quoted_live_path &&
$post_commands"

printf '\n%s\n' "$remote_command"
printf 'ssh %s@%s\n' "$user" "$url"

ssh -o StrictHostKeyChecking=accept-new "$user@$url" "$remote_command"
open_browser "https://$url"
