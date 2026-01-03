# Proceso de Release Automatizado - WordPress Feature API

## 📋 Resumen

Este documento describe el proceso para crear un release del plugin WordPress Feature API.

El sistema ahora está completamente automatizado mediante GitHub Actions:
1. Al hacer push de un tag con formato `v*`, se ejecutará el workflow
2. Se construirán los assets de JavaScript
3. Se generarán los ZIPs de los plugins
4. Se validará la estructura de WordPress
5. Se creará automáticamente el GitHub Release con los ZIPs adjuntos

---

## 🚀 Pasos para Crear un Release

### 1. Actualizar la versión en los archivos

```bash
# Actualizar wp-feature-api.php
# Cambiar: Version: X.X.X
# Cambiar: $wp_feature_api_version = 'X.X.X'

# Actualizar package.json (root)
# Cambiar: "version": "X.X.X"

# Actualizar packages/*/package.json según corresponda
```

### 2. Actualizar el CHANGELOG.md

Agregar la nueva sección de versión al inicio del archivo:

```markdown
## [X.X.X] - YYYY-MM-DD

### Nuevas funcionalidades
- Descripción...

### Bug fixes
- Descripción...

### Cambios
- Descripción...
```

### 3. Hacer commit de los cambios

```bash
git add -A
git commit -m "Prepare release vX.X.X"
```

### 4. Crear el tag y hacer push

```bash
# Crear tag semántico
git tag vX.X.X

# Push de cambios y tags
git push origin main --tags
```

### 5. Esperar la ejecución del workflow

1. Ve a https://github.com/desarrollo032/wp-feature-api/actions
2. Observa la ejecución del workflow "Release Plugin"
3. Cuando termine, el release estará disponible en:
   https://github.com/desarrollo032/wp-feature-api/releases

---

## 📦 Archivos Generados

El workflow genera dos archivos ZIP:

| Archivo | Plugin | Descripción |
|---------|--------|-------------|
| `wp-feature-api.zip` | WordPress Feature API | Plugin principal con SDK del cliente |
| `wp-feature-api-agent.zip` | WP Feature API Agent | Plugin demo con agente de IA |

---

## ✅ Validaciones Automáticas

El sistema valida que los ZIPs cumplan con las reglas de WordPress:

- [x] Carpeta raíz con el slug del plugin
- [x] Archivo principal `.php` en la raíz
- [x] Headers requeridos (Plugin Name, Version, Author, etc.)
- [x] Protección `ABSPATH` en el archivo principal
- [x] No incluye `node_modules`
- [x] No incluye archivos de configuración (webpack, tsconfig, etc.)
- [x] No incluye archivos de Git

---

## 🔧 Troubleshooting

### El workflow no se ejecuta
- Verifica que el tag comience con `v` (ej: `v0.1.10`)
- Verifica que el tag haya sido pusheado a GitHub

### Error en el build
- Verifica que `npm ci` funcione localmente
- Verifica que `npm run build` funcione localmente

### Error en validación
- Verifica que el CHANGELOG tenga el formato correcto
- Verifica que la versión en PHP coincida con el tag

---

## 📝 Ejemplo Completo para v0.1.10

```bash
# 1. Actualizar versión en wp-feature-api.php
# Cambiar Version: 0.1.10 y $wp_feature_api_version = '0.1.10'

# 2. Actualizar package.json
# Cambiar "version": "0.1.10"

# 3. Actualizar packages/*/package.json según corresponda

# 4. Actualizar CHANGELOG.md

# 5. Commit
git add -A
git commit -m "Prepare release v0.1.10"

# 6. Tag y push
git tag v0.1.10
git push origin main --tags

# 7. Verificar en GitHub Actions
# Ir a: https://github.com/desarrollo032/wp-feature-api/actions

# 8. Verificar release
# Ir a: https://github.com/desarrollo032/wp-feature-api/releases/tag/v0.1.10
```

---

## 📂 Archivos del Sistema

| Archivo | Descripción |
|---------|-------------|
| `.github/workflows/release.yml` | Workflow principal de GitHub Actions |
| `scripts/create-plugins-zip.js` | Script Node.js para generar ZIPs |
| `scripts/validate-plugin.js` | Script de validación de estructura |
| `package.json` | Scripts de build: `npm run build:plugins` |

---

## 🔗 Enlaces Rápidos

- **GitHub Actions**: https://github.com/desarrollo032/wp-feature-api/actions
- **Releases**: https://github.com/desarrollo032/wp-feature-api/releases
- **Tags**: https://github.com/desarrollo032/wp-feature-api/tags

---

## 📌 Notas

- El workflow usa `actions/create-release@v1` que está deprecated
- Para producción, considerar migrar a `softprops/action-gh-release` o API REST
- Los artifacts se eliminan después de 5 días para ahorrar espacio

