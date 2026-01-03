# Proceso de Release Automatizado

Este documento describe el proceso de release automatizado para el plugin WordPress Feature API.

## Flujo de Release

### 📋 Resumen del Workflow

El workflow `.github/workflows/release.yml` automatiza completamente el proceso de release:

1. **Trigger**: Se ejecuta automáticamente al hacer push de un tag con formato `v*`
2. **Build**: Instala dependencias, compila assets JS/Webpack
3. **Empaquetado**: Genera los ZIPs de los plugins
4. **Validación**: Verifica estructura correcta (carpeta raíz, archivo PHP, sin archivos de desarrollo)
5. **Release**: Crea el release en GitHub con los ZIPs adjuntos

---

## 🚀 Comandos para Crear un Release

### Paso 1: Actualizar versión en los archivos

```bash
# Editar version en package.json (minor/patch según corresponda)
npm version patch  # o minor, o major

# Esto crea automáticamente un commit y un tag
```

### Paso 2: Hacer push del tag

```bash
# Push de tags a GitHub
git push origin --tags
```

### Alternativa: Crear tag manualmente

```bash
# Crear tag anotado
git tag -a v0.1.11 -m "Release v0.1.11"

# Hacer push del tag
git push origin v0.1.11
```

---

## 📦 Archivos Generados

El workflow genera dos archivos ZIP:

### 1. wp-feature-api.zip
- **Propósito**: Plugin principal de la API
- **Carpeta raíz**: `wp-feature-api/`
- **Archivo principal**: `wp-feature-api.php`
- **Incluye**:
  - `includes/` - Classes principales
  - `build/client/` - SDK cliente compilado
  - `build/client-features/` - Componentes Blocks

### 2. wp-feature-api-agent.zip
- **Propósito**: Plugin demo con agente AI
- **Carpeta raíz**: `wp-feature-api-agent/`
- **Archivo principal**: `wp-feature-api-agent.php`
- **Incluye**:
  - `includes/` - Clases del proxy AI
  - `build/` - Assets JS compilados
  - `vendor/@automattic/wp-feature-api/` - SDK como dependencia

---

## ✅ Validaciones Realizadas

El workflow verifica:

1. **Estructura ZIP**:
   - ✅ Carpeta raíz con nombre correcto
   - ✅ Archivo `.php` principal en la raíz
   - ✅ No incluye `node_modules/`
   - ✅ No incluye archivos de configuración (`webpack.config.js`, `tsconfig.json`, etc.)
   - ✅ No incluye archivos `.map` ni `.d.ts`

2. **Headers del Plugin**:
   - ✅ `Plugin Name:` presente
   - ✅ `Version:` presente
   - ✅ Protección `ABSPATH` verificada

---

## 🔧 Configuración de GitHub Actions

### Permisos Requeridos

El workflow requiere los siguientes permisos en Settings > Actions > General:

```yaml
permissions:
  contents: write
  packages: read
```

### Secrets Opcionales

No se requieren secrets adicionales. El workflow usa:
- `GITHUB_TOKEN`: Automático para Actions
- `NODE_AUTH_TOKEN`: Si se usan paquetes privados de npm

---

## 📝 CHANGELOG

El release usa automáticamente el CHANGELOG.md. Formato esperado:

```markdown
## [0.1.11] - 2025-01-15

### Nuevas funcionalidades
- Feature A añadida
- Feature B mejorada

### Bug fixes
- Corrección en X
```

El script extrae automáticamente la sección entre `## [VERSION]` y el siguiente `## [`].

---

## 🐛 Solución de Problemas

### El workflow no se ejecuta

1. Verificar que el tag comience con `v`: `v0.1.11`
2. Verificar permisos: Settings > Actions > General > Workflow permissions
3. Revisar que no sea un fork anónimo (en forks, los tags también disparan)

### Error en validación

```bash
# Probar localmente
npm ci
npm run build
npm run build:plugins
unzip -l dist/wp-feature-api.zip | grep -E "(node_modules|webpack)"
```

### Error al crear release

Verificar que `softprops/action-gh-release@v2` esté disponible y los permisos de escritura.

---

## 📊 États del Workflow

| Job | Descripción | Estado |
|-----|-------------|--------|
| `build` | Compila y genera ZIPs | ✅ Requerido |
| `validate` | Verifica estructura | ✅ Requerido |
| `release` | Crea GitHub Release | ✅ Final |
| `notify` | Mensaje de éxito | Opcional |

---

## 🔄 Actualizar Dependencias

Antes de un release mayor:

```bash
# Actualizar todos los paquetes
npm update

# Actualizar paquetes de WordPress específicamente
npm run packages-update

# Verificar que todo compila
npm run build
```

---

## 📦 Notas de Deployment

Los ZIPs generados son **100% instalables en WordPress**:

1. Descargar desde la página de Releases
2. Ir a Plugins > Añadir nuevo > Subir plugin
3. Instalar y activar

No requieren pasos adicionales de build.

