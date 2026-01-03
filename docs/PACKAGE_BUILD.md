# Package Build Guide

This document describes how to build and package the wp-feature-api project.

## Quick Start

### Build and Package (Recommended)

```bash
# Build all packages and generate ZIPs
npm run build:packages

# Or with clean
npm run build:packages:clean

# Using the bash script directly
./scripts/build-packages.sh
```

### Manual Build

```bash
# 1. Build the project
npm run build

# 2. Generate packages
node scripts/create-packages-zip.js
```

## Output Structure

After running the build, the `dist/` directory will contain:

```
dist/
├── wp-feature-api.zip              # Core client SDK
├── wp-feature-api.zip.sha256       # SHA256 checksum
├── wp-feature-api-agent.zip        # Client features plugin
├── wp-feature-api-agent.zip.sha256 # SHA256 checksum
├── wp-feature-api-demo.zip         # Demo agent plugin
└── wp-feature-api-demo.zip.sha256  # SHA256 checksum
```

## Package Contents

### wp-feature-api.zip
- `wp-feature-api.php` - Main plugin file
- `includes/` - PHP includes
- `src/` - TypeScript source code
- `build/` - Compiled JavaScript assets
- `build-types/` - TypeScript type definitions
- `package.json` - Package metadata
- `README.md` - Documentation

### wp-feature-api-agent.zip
- `src/` - TypeScript source code
- `build/` - Compiled JavaScript assets
- `package.json` - Package metadata
- `README.md` - Documentation

### wp-feature-api-demo.zip
- `wp-feature-api-demo.php` - Main plugin file
- `includes/` - PHP includes
- `src/` - TypeScript source code
- `build/` - Compiled JavaScript assets
- `package.json` - Package metadata
- `README.md` - Documentation

## Verification Commands

### Verify ZIP Structure
```bash
unzip -l dist/wp-feature-api.zip
unzip -l dist/wp-feature-api-agent.zip
unzip -l dist/wp-feature-api-demo.zip
```

### Verify Checksums
```bash
# Using sha256sum (Linux)
sha256sum -c dist/wp-feature-api.zip.sha256
sha256sum -c dist/wp-feature-api-agent.zip.sha256
sha256sum -c dist/wp-feature-api-demo.zip.sha256

# Using shasum (macOS)
shasum -a 256 -c dist/wp-feature-api.zip.sha256
shasum -a 256 -c dist/wp-feature-api-agent.zip.sha256
shasum -a 256 -c dist/wp-feature-api-demo.zip.sha256

# Using openssl
openssl sha256 dist/wp-feature-api.zip
```

### Extract and Inspect
```bash
cd dist
unzip wp-feature-api.zip -d wp-feature-api-test
cd wp-feature-api-test
ls -la
tree
```

## WP-CLI Commands (WordPress Installation Required)

### Install and Activate Plugins
```bash
# Install wp-feature-api
wp plugin install ./dist/wp-feature-api.zip --force

# Install wp-feature-api-agent
wp plugin install ./dist/wp-feature-api-agent.zip --force

# Install wp-feature-api-demo
wp plugin install ./dist/wp-feature-api-demo.zip --force

# Activate plugins
wp plugin activate wp-feature-api
wp plugin activate wp-feature-api-agent
wp plugin activate wp-feature-api-demo

# List plugins
wp plugin list --name=wp-feature-api
wp plugin list --name=wp-feature-api-agent
wp plugin list --name=wp-feature-api-demo
```

### Verify Plugin Functionality
```bash
# Check plugin status
wp plugin status wp-feature-api

# Verify plugin is active
wp plugin is-active wp-feature-api

# Check plugin files
wp plugin get wp-feature-api --field=status

# Test REST API endpoints
wp rest-api get /wp/v2/posts --per_page=1
```

### Debug and Troubleshooting
```bash
# Enable debug mode
wp config set WP_DEBUG true --raw

# Check error log
wp shell "ini_set('display_errors', 1); error_reporting(E_ALL);"

# Verify JavaScript is loaded
wp eval "echo plugins_url('/build/index.js', __FILE__);"
```

## CI/CD Pipeline

The project includes a GitHub Actions workflow (`.github/workflows/release.yml`) that:

1. **Builds** all packages on every push to main or tag
2. **Packages** the plugins into ZIPs with checksums
3. **Creates** GitHub releases automatically on version tags
4. **Verifies** the release artifacts

### Triggering a Release

**Automatic (Tag Push):**
```bash
git tag v0.1.12
git push origin v0.1.12
```

**Manual (Workflow Dispatch):**
1. Go to GitHub Actions → Release & Package
2. Click "Run workflow"
3. Enter version number
4. Click "Run workflow"

### Nightly Builds

Scheduled to run daily at 00:00 UTC:
```yaml
on:
  schedule:
    - cron: '0 0 * * *'
```

## NPM Scripts

| Script | Description |
|--------|-------------|
| `npm run build` | Build all packages (client, client-features, demo-agent) |
| `npm run build:packages` | Build all packages and generate ZIPs |
| `npm run build:packages:clean` | Clean dist and rebuild packages |
| `npm run build:client` | Build only the client package |
| `npm run build:client-features` | Build only the client-features package |
| `npm run build:demo-agent` | Build only the demo-agent package |

## Troubleshooting

### Build Fails
```bash
# Clear node_modules and reinstall
rm -rf node_modules packages/*/node_modules
npm install
npm run build
```

### ZIP Generation Fails
```bash
# Check if zip is installed
which zip

# Install zip on Ubuntu/Debian
sudo apt-get install zip

# Install zip on macOS
brew install zip
```

### Checksum Verification Fails
```bash
# Verify the file wasn't corrupted
openssl sha256 dist/wp-feature-api.zip

# Compare with the checksum file
cat dist/wp-feature-api.zip.sha256
```

## Version Management

Package versions are read from:
- Root: `package.json` → `version`
- Client: `packages/client/package.json` → `version`
- Client-Features: `packages/client-features/package.json` → `version`
- Demo-Agent: `packages/demo-agent/package.json` → `version`

Ensure versions are updated before building releases.

