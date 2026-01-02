#!/usr/bin/env bash
set -euo pipefail

# Usage: scripts/update-release-notes.sh [CUR_TAG] [PREV_TAG]
# If CUR_TAG is not provided, uses the latest tag. If PREV_TAG not provided,
# the script selects the most recent previous tag by creation date.

CUR_TAG=${1:-$(git describe --tags --abbrev=0 2>/dev/null || echo "")}
if [ -z "$CUR_TAG" ]; then
  echo "Error: no current tag found. Provide CUR_TAG as first argument." >&2
  exit 1
fi

if [ -n "${2:-}" ]; then
  PREV_TAG="$2"
else
  PREV_TAG=$(git tag --sort=-creatordate | grep -v "^$CUR_TAG$" | head -n1 || echo "")
fi

if [ -n "$PREV_TAG" ]; then
  CHANGELOG=$(git log "$PREV_TAG".."$CUR_TAG" --pretty=format:'* %s by @%an in #%h' --reverse)
else
  CHANGELOG=$(git log --pretty=format:'* %s by @%an in #%h' --since="30 days ago" --reverse)
fi

[ -z "$CHANGELOG" ] && CHANGELOG="* No changes from previous version"

REPO=$(git config --get remote.origin.url | sed -E 's#.*github.com[:/](.*)\.git#\1#')

BODY_FILE=$(mktemp)
cat > "$BODY_FILE" <<EOF
## Changelog

$CHANGELOG

---

**Full changelog:** https://github.com/$REPO/compare/${PREV_TAG:-HEAD}...$CUR_TAG
EOF

echo "Updating release notes for $CUR_TAG (previous: ${PREV_TAG:-none})"
gh release edit "$CUR_TAG" --notes-file "$BODY_FILE"
rm -f "$BODY_FILE"

echo "Release notes updated for $CUR_TAG"
