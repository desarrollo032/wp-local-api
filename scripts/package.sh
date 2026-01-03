#!/usr/bin/env bash
###############################################################################
# PACKAGE SCRIPT - wp-feature-api
#
# Genera los ZIPs de plugins WordPress válidos
#
# Uso: ./scripts/package.sh [--skip-build-check]
###############################################################################

set -euo pipefail

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"

SKIP_BUILD_CHECK=false
for arg in "$@"; do
    case $arg in
        --skip-build-check)
            SKIP_BUILD_CHECK=true
            shift
            ;;
    esac
done

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }
log_header() { echo -e "${CYAN}📦 $1${NC}"; }

###############################################################################
# Verificar que el archivo PHP principal existe y tiene headers válidos
###############################################################################
validate_plugin_header() {
    local php_file="$1"
    local plugin_name="$2"
    
    if [ ! -f "$php_file" ]; then
        log_error "Archivo PHP principal no encontrado: $php_file"
        return 1
    fi
    
    local content
    content=$(cat "$php_file")
    
    # Verificar headers requeridos por WordPress
    if ! echo "$content" | grep -q "Plugin Name:"; then
        log_error "Falta header 'Plugin Name' en $php_file"
        return 1
    fi
    
    if ! echo "$content" | grep -q "Version:"; then
        log_error "Falta header 'Version' en $php_file"
        return 1
    fi
    
    # Verificar protección ABSPATH
    if ! echo "$content" | grep -q "ABSPATH"; then
        log_warn "Advertencia: Falta protección ABSPATH en $php_file"
    fi
    
    log_success "Header válido: $plugin_name"
    return 0
}

###############################################################################
# Verificar que los builds existan
###############################################################################
verify_builds() {
    if [ "$SKIP_BUILD_CHECK" = true ]; then
        log_info "Verificación de builds saltada (--skip-build-check)"
        return 0
    fi
    
    log_info "Verificando builds..."
    
    local missing=()
    
    if [ ! -d "$ROOT_DIR/packages/client/build" ]; then
        missing+=("client")
    fi
    if [ ! -d "$ROOT_DIR/packages/client-features/build" ]; then
        missing+=("client-features")
    fi
    if [ ! -d "$ROOT_DIR/packages/demo-agent/build" ]; then
        missing+=("demo-agent")
    fi
    
    if [ ${#missing[@]} -ne 0 ]; then
        log_error "Builds faltantes: ${missing[*]}"
        echo ""
        echo "Ejecuta primero: ./scripts/build.sh"
        return 1
    fi
    
    log_success "Todos los builds están presentes"
    return 0
}

###############################################################################
# Limpiar directorio dist
###############################################################################
clean_dist() {
    log_info "Limpiando directorio dist..."
    rm -rf "$DIST_DIR"
    mkdir -p "$DIST_DIR"
    log_success "Directorio dist preparado"
}

###############################################################################
# Copiar directorio excluyendo archivos de desarrollo
###############################################################################
copy_with_exclusions() {
    local src="$1"
    local dest="$2"
    
    mkdir -p "$dest"
    
    # Usar rsync con exclusiones
    if command -v rsync &> /dev/null; then
        rsync -a \
            --exclude='node_modules' \
            --exclude='.git' \
            --exclude='.gitignore' \
            --exclude='*.map' \
            --exclude='tsconfig*.json' \
            --exclude='webpack.config.js' \
            --exclude='.eslintrc*' \
            --exclude='.prettierrc*' \
            --exclude='*.lock' \
            --exclude='.DS_Store' \
            --exclude='Thumbs.db' \
            "$src/" "$dest/"
    else
        # Fallback: copia básica
        cp -r "$src"/* "$dest/" 2>/dev/null || true
        # Eliminar archivos no deseados
        find "$dest" -name "*.map" -delete
        find "$dest" -name "tsconfig*.json" -delete
        find "$dest" -name "webpack.config.js" -delete
        find "$dest" -name ".eslintrc*" -delete
    fi
}

###############################################################################
# Generar checksum SHA256
###############################################################################
generate_sha256() {
    local file="$1"
    local checksum_file="${file}.sha256"
    
    if command -v sha256sum &> /dev/null; then
        sha256sum "$file" > "$checksum_file"
    elif command -v shasum &> /dev/null; then
        shasum -a 256 "$file" > "$checksum_file"
    else
        log_warn "No se puede generar SHA256 (herramienta no disponible)"
        return 1
    fi
    
    log_success "SHA256: $(basename "$checksum_file")"
}

###############################################################################
# Crear archivo ZIP y carpeta de revisión
###############################################################################
create_zip() {
    local source_dir="$1"
    local zip_name="$2"
    local zip_path="$DIST_DIR/$zip_name"
    local plugin_name="${zip_name%.zip}"
    local review_dir="$DIST_DIR/$plugin_name"
    local temp_plugin_dir="$DIST_DIR/.temp-zip-$plugin_name"
    
    log_info "Generando $zip_name..."
    
    # Crear carpeta de revisión
    rm -rf "$review_dir"
    cp -r "$source_dir" "$review_dir"
    log_success "Carpeta de revisión: $plugin_name/"
    
    # Crear estructura temporal para ZIP (WordPress espera carpeta con nombre del plugin)
    rm -rf "$temp_plugin_dir"
    mkdir -p "$temp_plugin_dir"
    cp -r "$source_dir" "$temp_plugin_dir/$plugin_name"
    
    # Cambiar al directorio temporal para crear ZIP
    cd "$temp_plugin_dir"
    
    if command -v zip &> /dev/null; then
        if zip -rq "$zip_path" "$plugin_name"; then
            log_success "ZIP creado: $zip_name"
            cd "$ROOT_DIR"
            rm -rf "$temp_plugin_dir"
            return 0
        fi
    fi
    
    # Fallback a tar+gzip
    local tgz_name="${zip_name%.zip}.tgz"
    local tgz_path="$DIST_DIR/$tgz_name"
    
    if tar -czf "$tgz_path" "$plugin_name"; then
        mv "$tgz_path" "$zip_path"
        log_success "ZIP creado (tar+gzip): $zip_name"
        cd "$ROOT_DIR"
        rm -rf "$temp_plugin_dir"
        return 0
    fi
    
    log_error "Error al crear $zip_name"
    cd "$ROOT_DIR"
    rm -rf "$temp_plugin_dir"
    return 1
}

###############################################################################
# Construir wp-feature-api.zip
###############################################################################
build_wp_feature_api() {
    local pkg_name="wp-feature-api"
    local temp_dir="$DIST_DIR/.temp.$pkg_name"
    
    log_header "Construyendo: $pkg_name"
    
    # Limpiar y crear temp
    rm -rf "$temp_dir"
    mkdir -p "$temp_dir"
    
    # Validar archivo PHP principal
    if ! validate_plugin_header "$ROOT_DIR/wp-feature-api.php" "$pkg_name"; then
        return 1
    fi
    
    # Copiar archivos
    cp "$ROOT_DIR/wp-feature-api.php" "$temp_dir/"
    cp "$ROOT_DIR/package.json" "$temp_dir/" 2>/dev/null || true
    cp "$ROOT_DIR/README.md" "$temp_dir/" 2>/dev/null || true
    
    copy_with_exclusions "$ROOT_DIR/includes" "$temp_dir/includes"
    copy_with_exclusions "$ROOT_DIR/packages/client/build" "$temp_dir/build"
    copy_with_exclusions "$ROOT_DIR/packages/client/build-types" "$temp_dir/build-types" 2>/dev/null || true
    
    # Generar ZIP
    local zip_name="${pkg_name}.zip"
    if ! create_zip "$temp_dir" "$zip_name"; then
        return 1
    fi
    
    # Generar SHA256
    generate_sha256 "$DIST_DIR/$zip_name"
    
    # Limpiar temp
    rm -rf "$temp_dir"
    
    log_success "✅ $pkg_name completado"
    return 0
}

###############################################################################
# Construir wp-feature-api-agent.zip
###############################################################################
build_wp_feature_api_agent() {
    local pkg_name="wp-feature-api-agent"
    local temp_dir="$DIST_DIR/.temp.$pkg_name"
    
    log_header "Construyendo: $pkg_name"
    
    # Limpiar y crear temp
    rm -rf "$temp_dir"
    mkdir -p "$temp_dir"
    
    # Validar archivo PHP principal
    if ! validate_plugin_header "$ROOT_DIR/packages/demo-agent/wp-feature-api-agent.php" "$pkg_name"; then
        return 1
    fi
    
    # Copiar archivos
    cp "$ROOT_DIR/packages/demo-agent/wp-feature-api-agent.php" "$temp_dir/wp-feature-api-agent.php"
    cp "$ROOT_DIR/packages/demo-agent/package.json" "$temp_dir/package.json" 2>/dev/null || true
    cp "$ROOT_DIR/packages/client-features/README.md" "$temp_dir/README.md" 2>/dev/null || true
    
    copy_with_exclusions "$ROOT_DIR/packages/demo-agent/includes" "$temp_dir/includes"
    copy_with_exclusions "$ROOT_DIR/packages/demo-agent/build" "$temp_dir/build"
    copy_with_exclusions "$ROOT_DIR/packages/client-features/build" "$temp_dir/build-features"
    
    # Generar ZIP
    local zip_name="${pkg_name}.zip"
    if ! create_zip "$temp_dir" "$zip_name"; then
        return 1
    fi
    
    # Generar SHA256
    generate_sha256 "$DIST_DIR/$zip_name"
    
    # Limpiar temp
    rm -rf "$temp_dir"
    
    log_success "✅ $pkg_name completado"
    return 0
}

###############################################################################
# Mostrar estructura de dist
###############################################################################
show_dist_structure() {
    echo ""
    log_info "Estructura de dist/:"
    echo ""
    
    if command -v tree &> /dev/null; then
        tree -h "$DIST_DIR" -L 2
    else
        echo "📁 $DIST_DIR/"
        for item in "$DIST_DIR"/*; do
            if [ -f "$item" ]; then
                local size
                size=$(stat -c%s "$item" 2>/dev/null || stat -f%z "$item" 2>/dev/null)
                local size_kb=$((size / 1024))
                echo "   📄 $(basename "$item") (${size_kb} KB)"
            elif [ -d "$item" ]; then
                echo "   📁 $(basename "$item")/"
                find "$item" -maxdepth 1 -type f | head -5 | while read -r file; do
                    echo "      📄 $(basename "$file")"
                done
                local file_count
                file_count=$(find "$item" -type f | wc -l)
                if [ "$file_count" -gt 5 ]; then
                    echo "      ... y $((file_count - 5)) archivos más"
                fi
            fi
        done
    fi
}

###############################################################################
# Mostrar comandos de verificación
###############################################################################
show_verification_commands() {
    echo ""
    echo "==============================================================================="
    echo "📋 COMANDOS DE VERIFICACIÓN"
    echo "==============================================================================="
    echo ""
    echo "1. Verificar estructura del ZIP:"
    echo "   unzip -l dist/wp-feature-api.zip"
    echo "   unzip -l dist/wp-feature-api-agent.zip"
    echo ""
    echo "2. Revisar carpetas descomprimidas:"
    echo "   ls -la dist/wp-feature-api/"
    echo "   ls -la dist/wp-feature-api-agent/"
    echo ""
    echo "3. Verificar checksums:"
    echo "   sha256sum -c dist/wp-feature-api.zip.sha256"
    echo "   sha256sum -c dist/wp-feature-api-agent.zip.sha256"
    echo ""
    echo "4. Verificar archivos PHP principales:"
    echo "   head -20 dist/wp-feature-api/wp-feature-api.php"
    echo "   head -20 dist/wp-feature-api-agent/wp-feature-api-agent.php"
    echo ""
    echo "5. Verificar builds:"
    echo "   ls -la dist/wp-feature-api/build/"
    echo "   ls -la dist/wp-feature-api-agent/build/"
    echo ""
    echo "6. Verificar con WP-CLI (si WordPress está instalado):"
    echo "   wp plugin install ./dist/wp-feature-api.zip --force"
    echo "   wp plugin activate wp-feature-api"
    echo "   wp plugin list --name=wp-feature-api"
    echo ""
    echo "7. Desarrollo y testing:"
    echo "   # Copiar carpeta de revisión a WordPress"
    echo "   cp -r dist/wp-feature-api /path/to/wordpress/wp-content/plugins/"
    echo "   # Editar archivos directamente en dist/ para testing"
    echo "   code dist/wp-feature-api/"
    echo ""
}

###############################################################################
# Mostrar resumen final
###############################################################################
show_summary() {
    echo ""
    echo "==============================================================================="
    echo "✅ PACKAGE COMPLETADO"
    echo "==============================================================================="
    echo ""
    echo "📁 Directorio: $DIST_DIR"
    echo ""
    echo "📦 Archivos ZIP generados:"
    echo ""
    
    local total_size=0
    for file in "$DIST_DIR"/*.zip; do
        if [ -f "$file" ]; then
            local size
            size=$(stat -c%s "$file" 2>/dev/null || stat -f%z "$file" 2>/dev/null)
            local size_kb=$((size / 1024))
            echo "   📄 $(basename "$file") (${size_kb} KB)"
            total_size=$((total_size + size))
        fi
    done
    
    echo ""
    echo "📁 Carpetas de revisión generadas:"
    echo ""
    
    for dir in "$DIST_DIR"/wp-feature-api*; do
        if [ -d "$dir" ] && [[ ! "$dir" == *.zip* ]]; then
            local file_count
            file_count=$(find "$dir" -type f | wc -l)
            echo "   📁 $(basename "$dir")/ ($file_count archivos)"
        fi
    done
    
    echo ""
    echo "🔐 Checksums SHA256:"
    echo ""
    
    for file in "$DIST_DIR"/*.sha256; do
        if [ -f "$file" ]; then
            echo "   🔐 $(basename "$file")"
        fi
    done
    
    echo ""
    echo "📊 Total ZIPs: $((total_size / 1024)) KB"
    echo ""
}

###############################################################################
# Función principal
###############################################################################
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════════════════╗"
    echo "║              GENERADOR DE PLUGINS ZIP - wp-feature-api                    ║"
    echo "╚═══════════════════════════════════════════════════════════════════════════╝"
    echo ""
    
    # Verificar builds
    verify_builds || exit 1
    
    # Limpiar dist
    clean_dist
    
    # Construir paquetes
    echo ""
    echo "==============================================================================="
    echo "📦 CONSTRUYENDO PLUGINS"
    echo "==============================================================================="
    echo ""
    
    build_wp_feature_api || exit 1
    echo ""
    build_wp_feature_api_agent || exit 1
    
    # Mostrar resultados
    show_dist_structure
    show_summary
    show_verification_commands
}

main "$@"

