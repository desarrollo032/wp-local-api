#!/bin/bash
cd "$(dirname "$0")/.."
npm run build --prefix admin
rm -rf wp-mcp-control.zip
zip -r wp-mcp-control.zip wp-mcp-control \
  -x "wp-mcp-control/admin/node_modules/*" \
     "wp-mcp-control/admin/src/*" \
     "wp-mcp-control/admin/*.log" \
     "wp-mcp-control/package*" "*.git*"
echo "✅ wp-mcp-control.zip created (WordPress-ready)"
