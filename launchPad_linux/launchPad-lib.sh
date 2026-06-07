#!/usr/bin/env bash

set -o errexit
set -o nounset
set -o pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/config.json"
CI_STATUS_FILE="$SCRIPT_DIR/../.ci_status.json"

require_command() {
    local command_name="$1"

    if ! command -v "$command_name" >/dev/null 2>&1; then
        printf 'ERROR: Required command not found: %s\n' "$command_name" >&2
        exit 1
    fi
}

require_config() {
    if [[ ! -f "$CONFIG_FILE" ]]; then
        printf 'ERROR: Config file not found: %s\n' "$CONFIG_FILE" >&2
        exit 1
    fi

    if ! jq empty "$CONFIG_FILE" >/dev/null 2>&1; then
        printf 'ERROR: Invalid JSON in config file: %s\n' "$CONFIG_FILE" >&2
        exit 1
    fi
}

config_value() {
    local environment="$1"
    local key="$2"

    jq --raw-output --arg environment "$environment" --arg key "$key" '
        if (.environments[$environment] | type) != "object" then
            error("Environment not found: " + $environment)
        elif (.environments[$environment] | has($key) | not) then
            error("Config key not found: " + $environment + "." + $key)
        else
            .environments[$environment][$key]
        end
    ' "$CONFIG_FILE"
}

ci_value() {
    local key="$1"

    jq --raw-output --arg key "$key" '.[$key] // ""' "$CI_STATUS_FILE"
}

ci_status_is_valid() {
    jq empty "$CI_STATUS_FILE" >/dev/null 2>&1
}

show_ci_status() {
    if [[ ! -f "$CI_STATUS_FILE" ]]; then
        printf '.ci_status.json not found: %s\n' "$CI_STATUS_FILE"
        return
    fi

    if ! ci_status_is_valid; then
        printf '.ci_status.json is invalid JSON\n'
        return
    fi

    printf 'status: %s\n' "$(ci_value status)"
    printf 'commit: %s\n' "$(ci_value commit)"
    printf 'timestamp: %s\n' "$(ci_value timestamp)"
}

shell_quote() {
    printf '%q' "$1"
}

open_browser() {
    local url="$1"

    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$url" >/dev/null 2>&1 &
    elif command -v gio >/dev/null 2>&1; then
        gio open "$url" >/dev/null 2>&1 &
    else
        printf 'Open in browser: %s\n' "$url"
    fi
}
