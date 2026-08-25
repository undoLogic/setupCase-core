#!/usr/bin/env bash

set -o errexit
set -o nounset
set -o pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "$SCRIPT_DIR/.." && pwd)"
source "$SCRIPT_DIR/launchPad-lib.sh"

OUTPUT_DIR="$PROJECT_DIR/.idea"
TEMPLATE_DIR="$SCRIPT_DIR/templates/phpstorm"

usage() {
    printf 'Usage: %s [--output-dir PATH]\n' "$(basename "$0")"
}

xml_escape() {
    jq --raw-input --raw-output '@html'
}

backup_file() {
    local file_path="$1"
    local timestamp
    local backup_path

    if [[ ! -f "$file_path" ]]; then
        return
    fi

    timestamp="$(date '+%Y%m%d-%H%M%S%N')"
    backup_path="$file_path.$timestamp.bak"
    cp -- "$file_path" "$backup_path"
    printf 'Backed up: %s\n' "$backup_path"
}

render_template() {
    local template_path="$1"
    local token="$2"
    local replacement="$3"
    local line

    while IFS= read -r line || [[ -n "$line" ]]; do
        if [[ "$line" == "$token" ]]; then
            printf '%s\n' "$replacement"
        else
            printf '%s\n' "$line"
        fi
    done < "$template_path"
}

while (($# > 0)); do
    case "$1" in
        --output-dir)
            if (($# < 2)); then
                printf 'ERROR: --output-dir requires a path.\n' >&2
                exit 1
            fi
            OUTPUT_DIR="$2"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            printf 'ERROR: Unknown option: %s\n' "$1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

require_command jq
require_config

web_servers_template="$TEMPLATE_DIR/webServers.xml"
deployment_template="$TEMPLATE_DIR/deployment.xml"

for template in "$web_servers_template" "$deployment_template"; do
    if [[ ! -f "$template" ]]; then
        printf 'ERROR: Template file not found: %s\n' "$template" >&2
        exit 1
    fi
done

web_servers=""
deployments=""
environment_count=0

while IFS= read -r environment; do
    user="$(jq --raw-output --arg environment "$environment" \
        '.environments[$environment].USER // ""' "$CONFIG_FILE")"
    host="$(jq --raw-output --arg environment "$environment" \
        '.environments[$environment].SFTP_HOST // .environments[$environment].URL // ""' "$CONFIG_FILE")"
    absolute_path="$(jq --raw-output --arg environment "$environment" \
        '.environments[$environment].ABSOLUTE_PATH // ""' "$CONFIG_FILE")"

    if [[ -z "$user" || -z "$host" || -z "$absolute_path" ]]; then
        printf 'WARNING: Skipping incomplete environment: %s\n' "$environment" >&2
        continue
    fi

    if [[ "$absolute_path" != */www/* ]]; then
        printf "WARNING: Skipping environment '%s': ABSOLUTE_PATH must contain '/www/'.\n" \
            "$environment" >&2
        continue
    fi

    server_name="${environment^^}"
    root_folder="${absolute_path%%/www/*}"
    deploy_path="/www/${absolute_path#*/www/}"

    escaped_name="$(printf '%s' "$server_name" | xml_escape)"
    escaped_user="$(printf '%s' "$user" | xml_escape)"
    escaped_host="$(printf '%s' "$host" | xml_escape)"
    escaped_root="$(printf '%s' "$root_folder" | xml_escape)"
    escaped_deploy="$(printf '%s' "$deploy_path" | xml_escape)"

    web_servers+="$(cat <<EOF
      <webServer name="$escaped_name">
        <fileTransfer rootFolder="$escaped_root" accessType="SFTP" host="$escaped_host" port="22" username="$escaped_user" authAgent="true" />
      </webServer>
EOF
)"
    web_servers+=$'\n'

    deployments+="$(cat <<EOF
      <paths name="$escaped_name">
        <serverdata>
          <mappings>
            <mapping deploy="$escaped_deploy" local="\$PROJECT_DIR\$/sourceFiles" web="/" />
          </mappings>
        </serverdata>
      </paths>
EOF
)"
    deployments+=$'\n'

    printf 'Prepared %s (%s@%s)\n' "$server_name" "$user" "$host"
    ((environment_count += 1))
done < <(jq --raw-output '.environments | keys_unsorted[]' "$CONFIG_FILE")

if ((environment_count == 0)); then
    printf 'ERROR: No complete environments were available for PhpStorm deployment.\n' >&2
    exit 1
fi

web_servers="${web_servers%$'\n'}"
deployments="${deployments%$'\n'}"

mkdir -p -- "$OUTPUT_DIR"
web_servers_path="$OUTPUT_DIR/webServers.xml"
deployment_path="$OUTPUT_DIR/deployment.xml"

backup_file "$web_servers_path"
backup_file "$deployment_path"
render_template "$web_servers_template" "{{WEB_SERVERS}}" "$web_servers" > "$web_servers_path"
render_template "$deployment_template" "{{DEPLOYMENTS}}" "$deployments" > "$deployment_path"

printf '\nGenerated:\n'
printf '  %s\n' "$web_servers_path"
printf '  %s\n' "$deployment_path"
