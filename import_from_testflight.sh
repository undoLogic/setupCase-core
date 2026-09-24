#!/usr/bin/env bash
# Import factory files (CodeBlocks, build scripts, docs) from testFlight into the current branch (normally main).
# Files are OVERWRITTEN with the source branch version (no merge). To protect edits made directly on this
# branch, the import stops if any file here has content that never existed on the source branch.
# Only updates the working tree - nothing is staged or committed. Review with `git diff`, then stage/commit yourself.
# sourceFiles/ is never imported: main is the template and must not contain a built app.
#
# Usage: ./import_from_testflight.sh [source-branch]   (default: testFlight)

set -euo pipefail

SOURCE_BRANCH="${1:-testFlight}"

# Factory paths to import - add more here as needed (never sourceFiles)
PATHS=(
    codeBlocks
    init-web
    docs
    AGENTS.md
)

cd "$(dirname "$0")"

if [ ! -d codeBlocks ]; then
    echo "ERROR - codeBlocks/ not found. Is this SetupCase Core?"
    exit 1
fi

if ! git rev-parse --verify --quiet "$SOURCE_BRANCH" >/dev/null; then
    echo "ERROR - branch '$SOURCE_BRANCH' not found locally (try: git fetch origin $SOURCE_BRANCH:$SOURCE_BRANCH)"
    exit 1
fi

CURRENT_BRANCH="$(git branch --show-current)"
CURRENT_BRANCH="${CURRENT_BRANCH:-HEAD}" # detached HEAD
if [ "$CURRENT_BRANCH" = "$SOURCE_BRANCH" ]; then
    echo "ERROR - already on '$SOURCE_BRANCH'. Switch to the target branch (e.g. main) first."
    exit 1
fi

for path in "${PATHS[@]}"; do
    case "$path" in
        sourceFiles|sourceFiles/*)
            echo "ERROR - sourceFiles must never be imported"
            exit 1
            ;;
    esac
done

# Don't overwrite uncommitted work in the paths being imported
if [ -n "$(git status --porcelain -- "${PATHS[@]}")" ]; then
    echo "ERROR - uncommitted changes in: ${PATHS[*]}"
    git status --short -- "${PATHS[@]}"
    echo "Commit or stash them first."
    exit 1
fi

# Stop if this branch has its own edits that the import would overwrite or delete:
# a differing file is safe only if its current content came from the source branch at some point
LOCAL_EDITS=()
while IFS= read -r file; do
    [ -z "$file" ] && continue
    blob="$(git rev-parse -q --verify "HEAD:$file" || true)"
    [ -z "$blob" ] && continue # only on the source branch - will be added
    # every version of this file ever committed on the source branch
    history="$(git log "$SOURCE_BRANCH" --format= --raw --no-abbrev -- "$file" | awk '{print $4}')"
    if ! grep -qx "$blob" <<< "$history"; then
        LOCAL_EDITS+=("$file")
    fi
done < <(git diff --name-only HEAD "$SOURCE_BRANCH" -- "${PATHS[@]}")

if [ "${#LOCAL_EDITS[@]}" -gt 0 ]; then
    echo "ERROR - these files were changed on $CURRENT_BRANCH and would be overwritten or deleted:"
    printf '  %s\n' "${LOCAL_EDITS[@]}"
    echo "Copy those changes into $SOURCE_BRANCH first (then re-run), or compare with:"
    echo "  git diff $SOURCE_BRANCH HEAD -- <file>"
    exit 1
fi

echo "Importing from $SOURCE_BRANCH ($(git log -1 --format='%h %ad %s' --date=short "$SOURCE_BRANCH"))"
echo "into $CURRENT_BRANCH: ${PATHS[*]}"
echo ""

# Worktree only (no staging); also removes files deleted on the source branch
git restore --source="$SOURCE_BRANCH" --worktree -- "${PATHS[@]}"

echo "Changes (not staged):"
git status --short -- "${PATHS[@]}"
echo ""
echo "Review with: git diff -- ${PATHS[*]}"
echo "Then stage and commit yourself."
