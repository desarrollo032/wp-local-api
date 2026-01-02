#!/usr/bin/env bash
set -euo pipefail

# Script local para replicar la creación de assets de release
ROOT="$PWD"
RELEASE_DIR="$ROOT/release"

rm -rf "$RELEASE_DIR"
mkdir -p "$RELEASE_DIR/wp-feature-api"
mkdir -p "$RELEASE_DIR/wp-feature-api-agent"

# Rsync para plugin principal
rsync -av \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='tests' \
  --exclude='src' \
  --exclude='vendor' \
  --exclude='.gitignore' \
  --exclude='.gitattributes' \
  --exclude='composer.lock' \
  --exclude='package-lock.json' \
  --exclude='tsconfig*.json' \
  --exclude='webpack.config.js' \
  --exclude='phpcs.xml*' \
  --exclude='phpunit*' \
  --exclude='*.map' \
  --exclude='.eslintrc*' \
  --exclude='.editorconfig' \
  --exclude='CONTRIBUTING.md' \
  --exclude='DESIGN.md' \
  --exclude='RFC.md' \
  --exclude='README.md' \
  --exclude='build/' \
  --exclude='packages/*/build*' \
  --exclude='packages/*/node_modules' \
  --exclude='packages/*/tsconfig*.json' \
  --exclude='packages/*/webpack.config.js' \
  . "$RELEASE_DIR/wp-feature-api/"

# Rsync para agent (demo)
if [ -d demo/wp-feature-api-agent ]; then
  rsync -av \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='src' \
    --exclude='build' \
    --exclude='*.map' \
    --exclude='.gitignore' \
    demo/wp-feature-api-agent/ "$RELEASE_DIR/wp-feature-api-agent/"
fi

pushd "$RELEASE_DIR" >/dev/null
zip -r wp-feature-api.zip wp-feature-api >/dev/null
zip -r wp-feature-api-agent.zip wp-feature-api-agent >/dev/null || true

sha256sum wp-feature-api.zip > wp-feature-api.zip.sha256
if [ -f wp-feature-api-agent.zip ]; then
  sha256sum wp-feature-api-agent.zip > wp-feature-api-agent.zip.sha256
fi

echo "Assets generados en: $RELEASE_DIR"
ls -lah "$RELEASE_DIR"

popd >/dev/null

echo "Hecho."
