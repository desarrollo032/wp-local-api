#!/usr/bin/env bash
###############################################################################
# RELEASE SCRIPT - wp-feature-api
#
# Crea releases de GitHub con los plugins ZIP
#
# Uso: ./scripts/release.sh <VERSION> [PREV_TAG]
# Ejemplo: ./scripts/release.sh v0.1.11 v0.1.10
###############################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"

VERSION=${1:?Error: Debe especificar versión, ej. v0.1.11}
PREV_TAG=${2:-}

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

###############################################################################
# Verificar que gh CLI esté instalado
###############################################################################
check_gh() {
    if ! command -v gh &> /dev/null; then
        log_error "GitHub CLI (gh) no está instalado"
        echo "Instálalo desde: https://cli.github.com/"
        exit 1
    fi
}

###############################################################################
# Verificar que los ZIPs existan
###############################################################################
verify_zips() {
    log_info "Verificando ZIPs en dist/..."
    
    local zips=(
        "wp-feature-api.zip"
        "wp-feature-api-agent.zip"
    )
    
    local missing=0
    for zip in "${zips[@]}"; do
        if [ ! -f "$DIST_DIR/$zip" ]; then
            log_error "Falta: $zip"
            missing=1
        else
            log_success "$zip encontrado"
        fi
    done
    
    if [ $missing -eq 1 ]; then
        echo ""
        log_error "Primero ejecuta: npm run package"
        exit 1
    fi
}

###############################################################################
# Verificar checksums
###############################################################################
verify_checksums() {
    log_info "Verificando checksums SHA256..."
    
    # Skip checksum verification on Windows (sha256sum not available)
    if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]] || command -v powershell.exe &> /dev/null; then
        log_info "Saltando verificación de checksums en Windows"
        return 0
    fi
    
    for zip in "$DIST_DIR"/*.zip; do
        if [ -f "$zip" ]; then
            local sha_file="${zip}.sha256"
            if [ -f "$sha_file" ]; then
                if sha256sum -c "$sha_file" > /dev/null 2>&1; then
                    log_success "$(basename "$sha_file") válido"
                else
                    log_error "Checksum inválido: $(basename "$zip")"
                    exit 1
                fi
            fi
        fi
    done
}

###############################################################################
# Generar notas de release
###############################################################################
generate_release_notes() {
    local notes_file="$DIST_DIR/release-notes.md"
    
    if [ -n "$PREV_TAG" ]; then
        CHANGELOG=$(git log "$PREV_TAG".."$VERSION" --pretty=format:'* %s' --reverse 2>/dev/null || echo "")
    else
        CHANGELOG=$(git log --pretty=format:'* %s' --since="30 days ago" --reverse 2>/dev/null || echo "")
    fi
    
    [ -z "$CHANGELOG" ] && CHANGELOG="* No changes from previous version"
    
    cat > "$notes_file" <<EOF
## 🚀 WordPress Feature API v0.1.11

### ✨ Nuevas Características
- **OpenRouter Integration:** Integración completa con OpenRouter API y modelos gratuitos
- **Chat Interface:** Interfaz de chat moderna integrada en WordPress admin
- **MCP Support:** Compatibilidad completa con WordPress MCP plugin v0.2.5+
- **Build Automation:** \`npm run build\` ahora compila y genera ZIPs automáticamente

### 🔧 Mejoras
- **Simplified Structure:** Reducido de 3 plugins a 2 (eliminado duplicado)
- **Enhanced Error Handling:** Manejo robusto de diferentes estructuras de respuesta API
- **Better Debugging:** Logs detallados para troubleshooting
- **Free Models Priority:** Priorización automática de modelos gratuitos
- **Optional Dependencies:** Funciona sin dependencia del plugin principal

### 🐛 Correcciones
- Fixed "Invalid response structure from API proxy" error con OpenRouter
- Fixed chat no aparecía al instalar plugins
- Improved asset handling y validación de estructura
- Better user permission checks

### 📦 Plugins Incluidos
- **wp-feature-api.zip** - Plugin principal con API de funcionalidades
- **wp-feature-api-agent.zip** - Proxy AI + Chat interface completo

### 🎯 Modelos Gratuitos Soportados
- microsoft/phi-3-mini-128k-instruct:free
- huggingfaceh4/zephyr-7b-beta:free
- openchat/openchat-7b:free
- Y 8 modelos gratuitos más...

---

$CHANGELOG

---

**Full changelog:** https://github.com/Automattic/wp-feature-api/compare/${PREV_TAG:-${VERSION}}...${VERSION}

## 📥 Instalación

1. Descargar los ZIPs desde esta release
2. Instalar en WordPress: **Plugins → Añadir nuevo → Subir plugin**
3. Activar ambos plugins
4. Configurar OpenRouter API key en **Ajustes → WP Feature Agent Demo**

## 🔗 Enlaces Útiles
- [Documentación](https://github.com/Automattic/wp-feature-api#readme)
- [OpenRouter API Keys](https://openrouter.ai/)
- [WordPress MCP Plugin](https://github.com/Automattic/wordpress-mcp)
EOF
    
    log_success "Notas generadas: release-notes.md"
}

###############################################################################
# Subir assets a GitHub Release
###############################################################################
upload_release() {
    log_info "Subiendo assets a GitHub..."
    
    # Verificar que el release exista
    if ! gh release view "$VERSION" >/dev/null 2>&1; then
        log_info "Creando release draft: $VERSION"
        gh release create "$VERSION" --title "$VERSION" --notes-file "$DIST_DIR/release-notes.md" --draft
    else
        log_info "Release $VERSION ya existe, actualizando..."
    fi
    
    # Subir ZIPs y checksums
    log_info "Subiendo archivos..."
    gh release upload "$VERSION" "$DIST_DIR"/*.zip "$DIST_DIR"/*.sha256 --clobber
    
    log_success "Assets subidos"
}

###############################################################################
# Publicar release
###############################################################################
publish_release() {
    log_info "Publicando release..."
    gh release edit "$VERSION" --draft=false
    log_success "Release publicado"
}

###############################################################################
# Función principal
###############################################################################
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════════════════╗"
    echo "║                    RELEASE - wp-feature-api                               ║"
    echo "╚═══════════════════════════════════════════════════════════════════════════╝"
    echo ""
    
    echo "📦 Versión: $VERSION"
    [ -n "$PREV_TAG" ] && echo "📦 Versión anterior: $PREV_TAG"
    echo ""
    
    check_gh
    verify_zips
    verify_checksums
    generate_release_notes
    upload_release
    publish_release
    
    echo ""
    echo "==============================================================================="
    log_success "RELEASE COMPLETADO"
    echo "==============================================================================="
    echo ""
    echo "📝 Revisa el release en: https://github.com/Automattic/wp-feature-api/releases/tag/$VERSION"
    echo ""
}

main "$@"

