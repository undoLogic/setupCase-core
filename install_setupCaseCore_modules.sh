#!/bin/bash

set -e


REPO_URL="https://github.com/undoLogic/setupCase-core.git"
TEMP_DIR="/tmp/setupCase-core-$$"

MODULES=(
    "codeBlocks"
    "init-web"
    "dockerWSL"
    "launchPad_win"
)

cleanup() {
    rm -rf "$TEMP_DIR"
}

trap cleanup EXIT

echo ""
echo "=========================================="
echo " setupCase Core Module Installer"
echo "=========================================="
echo ""
echo "Select a module to install:"
echo ""

for i in "${!MODULES[@]}"; do
    echo "$((i+1))) ${MODULES[$i]}"
done

echo ""
echo "A) ALL"
echo "Q) Quit"
echo ""

read -p "Choice: " CHOICE

if [[ "$CHOICE" =~ ^[Qq]$ ]]; then
    exit 0
fi

echo ""
echo "Downloading setupCase-core..."
git clone --depth=1 "$REPO_URL" "$TEMP_DIR" >/dev/null

install_module() {
    MODULE="$1"

    if [ ! -d "$TEMP_DIR/$MODULE" ]; then
        echo "Module not found: $MODULE"
        return 1
    fi

    echo ""
    echo "Installing $MODULE..."

    if [ -d "./$MODULE" ]; then
        echo "Updating existing directory..."
        rsync -av --delete "$TEMP_DIR/$MODULE/" "./$MODULE/"
    else
        cp -R "$TEMP_DIR/$MODULE" .
    fi

    echo "Done."
}

if [[ "$CHOICE" =~ ^[Aa]$ ]]; then

    for MODULE in "${MODULES[@]}"; do
        install_module "$MODULE"
    done

else

    INDEX=$((CHOICE-1))

    if [ "$INDEX" -lt 0 ] || [ "$INDEX" -ge "${#MODULES[@]}" ]; then
        echo "Invalid selection."
        exit 1
    fi

    install_module "${MODULES[$INDEX]}"

fi

echo ""
echo "=========================================="
echo "Completed"
echo "=========================================="