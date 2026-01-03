#!/usr/bin/env bash
###############################################################################
# VERIFY PLUGINS SCRIPT - wp-feature-api
#
# Verifica que los ZIPs generados sean válidos como plugins WordPress
#
# Uso: ./scripts/verify-plugins.sh
###############################################################################

set -euo pipefail

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

###############################################################################
# Verificar que existe un archivo en el ZIP
###############################################################################
verify_file_in_zip() {
    local zip_file="$1"
    local file_path="$2"
    local description="$3"
    
    local file_list
    file_list=$(tar -tzf "$zip_file")
    
    if echo "$file_list" | grep -q "$file_path"; then
        log_success "$description encontrado"
        return 0
    else
        log_error "$description NO encontrado"
        return 1
    fi
}

###############################################################################
# Verificar headers PHP en un ZIP
###############################################################################
verify_php_headers() {
    local zip_file="$1"
    local php_file="$2"
    local plugin_name="$3"
    
    log_info "Verificando headers PHP en $plugin_name..."
    
    # Extraer el archivo PHP y verificar headers (ahora está en una subcarpeta)
    local php_content
    php_content=$(tar -xzf "$zip_file" -O "$plugin_name/$php_file" 2>/dev/null || echo "")
    
    if [ -z "$php_content" ]; then
        log_error "No se pudo extraer $plugin_name/$php_file"
        return 1
    fi
    
    # Verificar headers requeridos
    if ! echo "$php_content" | grep -q "Plugin Name:"; then
        log_error "Falta header 'Plugin Name'"
        return 1
    fi
    
    if ! echo "$php_content" | grep -q "Version:"; then
        log_error "Falta header 'Version'"
        return 1
    fi
    
    if ! echo "$php_content" | grep -q "ABSPATH"; then
        log_error "Falta protección ABSPATH"
        return 1
    fi
    
    log_success "Headers PHP válidos"
    return 0
}

###############################################################################
# Verificar estructura de plugin WordPress
###############################################################################
verify_plugin_structure() {
    local zip_file="$1"
    local plugin_name="$2"
    local php_file="$3"
    
    log_info "Verificando estructura de $plugin_name..."
    
    local errors=0
    
    # Verificar archivo PHP principal
    if ! verify_file_in_zip "$zip_file" "$plugin_name/$php_file" "Archivo PHP principal"; then
        errors=$((errors + 1))
    fi
    
    # Verificar headers PHP
    if ! verify_php_headers "$zip_file" "$php_file" "$plugin_name"; then
        errors=$((errors + 1))
    fi
    
    # Verificar directorio build
    if ! verify_file_in_zip "$zip_file" "$plugin_name/build/index.js" "Build JavaScript"; then
        errors=$((errors + 1))
    fi
    
    # Verificar asset file
    if ! verify_file_in_zip "$zip_file" "$plugin_name/build/index.asset.php" "Asset file"; then
        errors=$((errors + 1))
    fi
    
    if [ $errors -eq 0 ]; then
        log_success "$plugin_name: Estructura válida"
        return 0
    else
        log_error "$plugin_name: $errors errores encontrados"
        return 1
    fi
}

###############################################################################
# Verificar checksums
###############################################################################
verify_checksums() {
    log_info "Verificando checksums SHA256..."
    
    local errors=0
    
    for zip_file in "$DIST_DIR"/*.zip; do
        if [ -f "$zip_file" ]; then
            local sha_file="${zip_file}.sha256"
            if [ -f "$sha_file" ]; then
                if sha256sum -c "$sha_file" > /dev/null 2>&1; then
                    log_success "$(basename "$sha_file") válido"
                else
                    log_error "Checksum inválido: $(basename "$zip_file")"
                    errors=$((errors + 1))
                fi
            else
                log_error "Falta checksum: $(basename "$zip_file")"
                errors=$((errors + 1))
            fi
        fi
    done
    
    return $errors
}

###############################################################################
# Función principal
###############################################################################
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════════════════╗"
    echo "║                    VERIFICACIÓN DE PLUGINS                                ║"
    echo "╚═══════════════════════════════════════════════════════════════════════════╝"
    echo ""
    
    local total_errors=0
    
    # Verificar que existan los ZIPs
    if [ ! -d "$DIST_DIR" ]; then
        log_error "Directorio dist no encontrado. Ejecuta primero: npm run package"
        exit 1
    fi
    
    # Verificar wp-feature-api.zip
    echo ""
    log_info "=== VERIFICANDO wp-feature-api.zip ==="
    if verify_plugin_structure "$DIST_DIR/wp-feature-api.zip" "wp-feature-api" "wp-feature-api.php"; then
        log_success "wp-feature-api.zip: ✅ VÁLIDO"
    else
        log_error "wp-feature-api.zip: ❌ INVÁLIDO"
        total_errors=$((total_errors + 1))
    fi
    
    # Verificar wp-feature-api-agent.zip
    echo ""
    log_info "=== VERIFICANDO wp-feature-api-agent.zip ==="
    if verify_plugin_structure "$DIST_DIR/wp-feature-api-agent.zip" "wp-feature-api-agent" "wp-feature-api-agent.php"; then
        log_success "wp-feature-api-agent.zip: ✅ VÁLIDO"
    else
        log_error "wp-feature-api-agent.zip: ❌ INVÁLIDO"
        total_errors=$((total_errors + 1))
    fi
    
    # Verificar wp-feature-api-demo.zip
    echo ""
    log_info "=== VERIFICANDO wp-feature-api-demo.zip ==="
    if verify_plugin_structure "$DIST_DIR/wp-feature-api-demo.zip" "wp-feature-api-demo" "wp-feature-api-demo.php"; then
        log_success "wp-feature-api-demo.zip: ✅ VÁLIDO"
    else
        log_error "wp-feature-api-demo.zip: ❌ INVÁLIDO"
        total_errors=$((total_errors + 1))
    fi
    
    # Verificar checksums
    echo ""
    log_info "=== VERIFICANDO CHECKSUMS ==="
    if ! verify_checksums; then
        total_errors=$((total_errors + 1))
    fi
    
    # Resumen final
    echo ""
    echo "==============================================================================="
    if [ $total_errors -eq 0 ]; then
        log_success "VERIFICACIÓN COMPLETADA - TODOS LOS PLUGINS SON VÁLIDOS"
        echo ""
        echo "📦 Los 3 ZIPs están listos para instalación en WordPress:"
        echo "   • wp-feature-api.zip - Plugin principal"
        echo "   • wp-feature-api-agent.zip - Client features"
        echo "   • wp-feature-api-demo.zip - Demo agent"
        echo ""
        echo "🔐 Checksums SHA256 verificados correctamente"
    else
        log_error "VERIFICACIÓN FALLÓ - $total_errors ERRORES ENCONTRADOS"
        exit 1
    fi
    echo "==============================================================================="
    echo ""
}

main "$@"