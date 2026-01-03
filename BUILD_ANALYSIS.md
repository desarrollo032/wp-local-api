# ANÁLISIS DE PROBLEMAS DETECTADOS

## 1. Scripts Duplicados e Inconsistentes

### Scripts en `scripts/`:
| Script | Propósito | Problema |
|--------|-----------|----------|
| `build-packages.sh` | Build + Package | Mezcla responsabilidades |
| `create-packages-zip.js` | Package | Duplica funcionalidad de build |
| `create-plugins-zip.js` | Build + Package | Duplica funcionalidad |
| `make-local-release.sh` | Release local | Usa estructura distinta (`demo/wp-feature-api-agent/`) |
| `create-release.sh` | GitHub Release | Depende de `make-local-release.sh` |
| `update-release-notes.sh` | Release notes | OK - mantener |
| `validate-plugin.js` | Validación | No se usa desde otros scripts |

### TOTAL: 7 scripts con funcionalidad superpuesta

## 2. Problema del ZIP del Demo

### Error: "No se ha podido descomprimir el paquete. No se encontraron plugins."

**Causas raíz:**
1. El script `create-plugins-zip.js` NO genera `wp-feature-api-demo.zip`
2. Solo genera 2 ZIPs: `wp-feature-api.zip` y `wp-feature-api-agent.zip`
3. `make-local-release.sh` espera `demo/wp-feature-api-agent/` que no existe

### Mapeo correcto de paquetes:
```
packages/client/           → wp-feature-api.zip (plugin principal)
packages/client-features/  → wp-feature-api-agent.zip  
packages/demo-agent/       → wp-feature-api-demo.zip (NUEVO)
```

## 3. Estructura PHP Inconsistente

### Archivos PHP principales:
- `wp-feature-api.php` → Header: "Plugin Name: WordPress Feature API"
- `packages/demo-agent/wp-feature-api-agent.php` → Header: "Plugin Name: WP Feature API - AI Agent Proxy"

**Problema:** El demo-agent usa `wp-feature-api-agent.php` pero debería renombrarse a `wp-feature-api-demo.php` para coincidir con el nombre del ZIP.

## 4. Fallos de Validación

### `create-plugins-zip.js`:
- ✅ Valida headers PHP
- ❌ No valida que exista build antes de empaquetar
- ❌ No verifica estructura correcta de plugin WordPress
- ❌ No falla explícitamente si falta el archivo PHP principal

## 5. Falta Generar SHA256

### Archivos que generan checksums:
- `build-packages.sh` → ✅ Genera SHA256
- `create-packages-zip.js` → ✅ Genera SHA256
- `create-plugins-zip.js` → ❌ NO Genera SHA256
- `make-local-release.sh` → ✅ Genera SHA256

## SOLUCIONES A IMPLEMENTAR

### 1. Unificar scripts:
- `build.sh` - Solo build (Webpack/TypeScript)
- `package.sh` - Solo package (ZIP + SHA256)
- Mantener `update-release-notes.sh` (no toca)
- Eliminar: `build-packages.sh`, `create-packages-zip.js`, `create-plugins-zip.js`, `make-local-release.sh`, `create-release.sh`, `validate-plugin.js`

### 2. Generar 3 ZIPs:
1. `wp-feature-api.zip` - Plugin principal
2. `wp-feature-api-agent.zip` - Client features
3. `wp-feature-api-demo.zip` - Demo agent (NUEVO)

### 3. Validaciones obligatorias:
- Verificar que existe build antes de empaquetar
- Verificar que el archivo PHP principal está en la raíz
- Fallar explícitamente si falta algo

### 4. Estructura correcta de cada ZIP:
```
wp-feature-api.zip/
├── wp-feature-api.php           (header correcto)
├── includes/
├── build/
└── package.json

wp-feature-api-agent.zip/
├── wp-feature-api-agent.php     (header correcto)
├── includes/
├── build/
└── package.json

wp-feature-api-demo.zip/
├── wp-feature-api-demo.php      (renombrado desde agent)
├── includes/
├── build/
└── package.json
```

## Archivos a eliminar (justificación):
1. `build-packages.sh` - Reemplazado por `build.sh` + `package.sh`
2. `create-packages-zip.js` - Reemplazado por `package.sh`
3. `create-plugins-zip.js` - Reemplazado por `package.sh`
4. `make-local-release.sh` - Usa estructura `demo/` que no existe
5. `create-release.sh` - Se simplificará a un script nuevo
6. `validate-plugin.js` - Integrado en `package.sh`

