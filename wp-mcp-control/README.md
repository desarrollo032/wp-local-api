# WP MCP Control

Plugin WordPress unificado que expone **control total** del sitio como servidor MCP (Model Context Protocol). LLMs se conectan y ejecutan acciones autorizadas.

## 🚀 Instalación

1. Descargar `wp-mcp-control.zip`
2. Subir a `/wp-content/plugins/`
3. Activar
4. Admin → **MCP Control** → Configurar tokens/permisos

## 🔑 Tokens & Permisos

**Página principal**: Matriz granular (leer/crear/editar/eliminar por categoría).

| Categoría | Tools |
|-----------|-------|
| Contenido | posts, pages, media, blocks... |
| Config | plugins, themes, settings |
| Usuarios | users, roles |
| Datos | meta, options (db readonly) |

## 🔌 Endpoints MCP

```
GET  /wp-json/mcp/v1/tools    # Descubrir tools permitidas
POST /wp-json/mcp/v1/call     # {"tool": "posts-create", "arguments": {...}}

Header: Authorization: Bearer {token}
```

**Posts ejemplo**:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"tool":"posts-create","arguments":{"title":"Test","content":"Hello","status":"publish"}}' \
  https://yoursite/wp-json/mcp/v1/call
```

## 🛠️ Desarrollo

```
cd wp-mcp-control/admin
npm install && npm run build
```

**Añadir tool**: `includes/tools/class-tool-NUEVA.php` → auto-registrada.

## 📋 Tools Implementadas (20+)

- ✅ **Posts**: CRUD, duplicate, schedule
- ⏳ **Media/Users/Plugins**... (extender clases)

## 🔒 Seguridad

- Tokens hasheados (`wp_hash`)
- Rate limit 60/min/token
- Sanitización/esquemas JSON
- Logs auditables
- Nonces WP standard

## 📦 Empaquetar

```bash
cd wp-mcp-control && scripts/package.sh
```

**Licencia**: GPL-2.0-or-later
