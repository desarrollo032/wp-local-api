#!/usr/bin/env bash
set -euo pipefail

# Usage: scripts/create-release.sh <VERSION> [PREV_TAG]
# Example: scripts/create-release.sh v0.1.9 v0.1.8

VERSION=${1:?Provide version, e.g. v0.1.9}
PREV_TAG=${2:-}

echo "Creating release for $VERSION"

# 1. Build release assets
if [ -x scripts/make-local-release.sh ]; then
  echo "Running packaging script..."
  ./scripts/make-local-release.sh
else
  echo "Error: scripts/make-local-release.sh not found or not executable" >&2
  exit 1
fi

# 2. Create release if not exists (draft), else upload assets
if gh release view "$VERSION" >/dev/null 2>&1; then
  echo "Release $VERSION exists — uploading assets"
  gh release upload "$VERSION" release/*.zip release/*.sha256 --clobber || true
else
  echo "Creating draft release $VERSION"
  gh release create "$VERSION" --title "$VERSION" --notes "Preparing release $VERSION" --draft
  gh release upload "$VERSION" release/*.zip release/*.sha256 || true
fi

# 3. Update release notes using update-release-notes script if available
if [ -x scripts/update-release-notes.sh ]; then
  echo "Updating release notes"
  if [ -n "$PREV_TAG" ]; then
    ./scripts/update-release-notes.sh "$VERSION" "$PREV_TAG"
  else
    ./scripts/update-release-notes.sh "$VERSION"
  fi
else
  echo "Note: scripts/update-release-notes.sh not found — skipping notes update"
fi

# 4. Publish release (remove draft)
echo "Publishing release $VERSION"
gh release edit "$VERSION" --draft=false || true

echo "Release $VERSION complete. Check GitHub Releases for assets and notes."
