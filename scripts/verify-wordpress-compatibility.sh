#!/usr/bin/env bash
###############################################################################
# VERIFY WORDPRESS COMPATIBILITY - wp-feature-api
#
# Verifica que los ZIPs sean compatibles con WordPress
#
# Uso: ./scripts/verify-wordpress-compatibility.sh
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
# Verificar estructura de plugin WordPress
###############################################################################
verify_wordpress_structure() {
    local zip_file="$1"
    local plugin_name="$2"
    
    log_info "Verificando estructura WordPress de $plugin_name..."
    
    local errors=0
    
    # Verificar que el plugin está en una carpeta con su nombre
    if ! tar -tzf "$zip_file" | grep -q "^$plugin_name/$"; then
        log_error "Plugin no está en carpeta '$plugin_name/'"
        errors=$((errors + 1))
    else
        log_success "Plugin en carpeta correcta: $plugin_name/"
    fi
    
    # Verificar archivo PHP principal
    if ! tar -tzf "$zip_file" | grep -q "^$plugin_name/$plugin_name\.php$"; then
        log_error "Archivo PHP principal no encontrado: $plugin_name.php"
        errors=$((errors + 1))
    else
        log_success "Archivo PHP principal encontrado"
    fi
    
    # Verificar que NO hay archivos de desarrollo
    local dev_files
    dev_files=$(tar -tzf "$zip_file" | grep -E "(phpcs|phpunit|\.dist|composer\.json|\.git|node_modules|\.env)" || true)
    
    if [ -n "$dev_files" ]; then
        log_error "Archivos de desarrollo encontrados:"
        echo "$dev_files" | while read -r file; do
            echo "   ❌ $file"
        done
        errors=$((errors + 1))
    else
        log_success "Sin archivos de desarrollo"
    fi
    
    # Verificar que hay directorio build
    if ! tar -tzf "$zip_file" | grep -q "^$plugin_name/build/"; then
        log_error "Directorio build no encontrado"
        errors=$((errors + 1))
    else
        log_success "Directorio build encontrado"
    fi
    
    return $errors
}

###############################################################################
# Verificar headers de plugin WordPress
###############################################################################
verify_wordpress_headers() {
    local zip_file="$1"
    local plugin_name="$2"
    
    log_info "Verificando headers WordPress de $plugin_name..."
    
    local php_content
    php_content=$(tar -xzf "$zip_file" -O "$plugin_name/$plugin_name.php" 2>/dev/null || echo "")
    
    if [ -z "$php_content" ]; then
        log_error "No se pudo leer el archivo PHP principal"
        return 1
    fi
    
    local errors=0
    
    # Headers requeridos por WordPress
    local required_headers=("Plugin Name:" "Version:" "Description:" "Author:")
    
    for header in "${required_headers[@]}"; do
        if echo "$php_content" | grep -q "$header"; then
            log_success "Header encontrado: $header"
        else
            log_error "Header faltante: $header"
            errors=$((errors + 1))
        fi
    done
    
    # Verificar protección ABSPATH
    if echo "$php_content" | grep -q "ABSPATH"; then
        log_success "Protección ABSPATH encontrada"
    else
        log_warn "Advertencia: Falta protección ABSPATH"
    fi
    
    return $errors
}

###############################################################################
# Función principal
###############################################################################
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════════════════╗"
    echo "║              VERIFICACIÓN DE COMPATIBILIDAD WORDPRESS                     ║"
    echo "╚═══════════════════════════════════════════════════════════════════════════╝"
    echo ""
    
    local total_errors=0
    
    # Verificar que existan los ZIPs
    if [ ! -d "$DIST_DIR" ]; then
        log_error "Directorio dist no encontrado. Ejecuta primero: npm run package"
        exit 1
    fi
    
    local plugins=("wp-feature-api" "wp-feature-api-agent" "wp-feature-api-demo")
    
    for plugin in "${plugins[@]}"; do
        local zip_file="$DIST_DIR/$plugin.zip"
        
        if [ ! -f "$zip_file" ]; then
            log_error "ZIP no encontrado: $plugin.zip"
            total_errors=$((total_errors + 1))
            continue
        fi
        
        echo ""
        log_info "=== VERIFICANDO $plugin.zip ==="
        
        # Verificar estructura
        if ! verify_wordpress_structure "$zip_file" "$plugin"; then
            total_errors=$((total_errors + 1))
        fi
        
        # Verificar headers
        if ! verify_wordpress_headers "$zip_file" "$plugin"; then
            total_errors=$((total_errors + 1))
        fi
        
        if [ $? -eq 0 ]; then
            log_success "$plugin.zip: ✅ COMPATIBLE CON WORDPRESS"
        else
            log_error "$plugin.zip: ❌ NO COMPATIBLE"
        fi
    done
    
    # Resumen final
    echo ""
    echo "==============================================================================="
    if [ $total_errors -eq 0 ]; then
        log_success "TODOS LOS PLUGINS SON COMPATIBLES CON WORDPRESS"
        echo ""
        echo "🎉 Los ZIPs están listos para instalar en WordPress sin errores"
        echo ""
        echo "📋 Próximos pasos:"
        echo "   1. Subir ZIPs a WordPress Admin → Plugins → Añadir nuevo → Subir plugin"
        echo "   2. Activar plugins según necesites"
        echo "   3. Configurar según documentación"
    else
        log_error "SE ENCONTRARON $total_errors ERRORES DE COMPATIBILIDAD"
        echo ""
        echo "🔧 Ejecuta 'npm run package' para regenerar los ZIPs"
        exit 1
    fi
    echo "==============================================================================="
    echo ""
}

main "$@"