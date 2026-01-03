#!/usr/bin/env bash
###############################################################################
# Script de Build y Empaquetado - wp-feature-api
#
# Este script:
# 1. Ejecuta el build de todos los paquetes
# 2. Genera los ZIPs separados por paquete
# 3. Genera checksums SHA256
#
# Uso: ./scripts/build-packages.sh [--skip-build]
###############################################################################

set -euo pipefail

# Colores para输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Rutas
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"

# Opciones
SKIP_BUILD=false
for arg in "$@"; do
    case $arg in
        --skip-build)
            SKIP_BUILD=true
            shift
            ;;
    esac
done

# Funciones de logging
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

###############################################################################
# Verificar dependencias
###############################################################################
check_dependencies() {
    log_info "Verificando dependencias..."

    local missing_deps=()

    # Verificar Node.js
    if ! command -v node &> /dev/null; then
        missing_deps+=( "node" )
    fi

    # Verificar npm
    if ! command -v npm &> /dev/null; then
        missing_deps+=( "npm" )
    fi

    # Verificar zip (opcional, se usará tar como fallback)
    if ! command -v zip &> /dev/null; then
        log_warn "zip no encontrado, se usará tar+gzip como fallback"
    fi

    # Verificar sha256sum
    if ! command -v sha256sum &> /dev/null; then
        # Fallback para macOS
        if command -v shasum &> /dev/null; then
            log_info "Usando shasum (macOS) como替代 de sha256sum"
        else
            missing_deps+=( "sha256sum o shasum" )
        fi
    fi

    if [ ${#missing_deps[@]} -ne 0 ]; then
        log_error "Dependencias faltantes: ${missing_deps[*]}"
        exit 1
    fi

    log_success "Dependencias verificadas"
}

###############################################################################
# Obtener versión desde package.json
###############################################################################
get_version() {
    local pkg_path="$1"
    if [ -f "$pkg_path" ]; then
        node -e "console.log(require('$pkg_path').version || '0.0.0')"
    else
        echo "0.0.0"
    fi
}

###############################################################################
# Ejecutar build
###############################################################################
run_build() {
    if [ "$SKIP_BUILD" = true ]; then
        log_info "Build saltado (--skip-build)"
        return 0
    fi

    log_info "Ejecutando build..."
    cd "$ROOT_DIR"

    if npm run build; then
        log_success "Build completado"
        return 0
    else
        log_error "Error en build"
        return 1
    fi
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
# Crear estructura para un paquete
###############################################################################
create_package_structure() {
    local package_name="$1"
    local temp_dir="$DIST_DIR/.temp.$package_name"

    rm -rf "$temp_dir"
    mkdir -p "$temp_dir"

    echo "$temp_dir"
}

###############################################################################
# Generar ZIP usando el método disponible
###############################################################################
create_zip() {
    local source_dir="$1"
    local zip_name="$2"
    local zip_path="$DIST_DIR/$zip_name"

    log_info "Generando $zip_name..."

    # Cambiar al directorio padre del source para evitar rutas largas
    local parent_dir="$(dirname "$source_dir")"
    local dir_name="$(basename "$source_dir")"

    if command -v zip &> /dev/null; then
        cd "$parent_dir"
        if zip -r "$zip_path" "$dir_name" > /dev/null 2>&1; then
            log_success "ZIP creado: $zip_name"
            return 0
        else
            log_warn "Error con zip, intentando con tar+gzip..."
        fi
    fi

    # Fallback a tar+gzip
    local tgz_name="${zip_name%.zip}.tgz"
    local tgz_path="$DIST_DIR/$tgz_name"

    cd "$parent_dir"
    if tar -czf "$tgz_path" "$dir_name"; then
        log_success "TAR.GZ creado: $tgz_name"
        mv "$tgz_path" "$zip_path"
        return 0
    else
        log_error "Error al crear archivo"
        return 1
    fi
}

###############################################################################
# Generar checksum SHA256
###############################################################################
generate_checksum() {
    local zip_path="$1"
    local checksum_path="${zip_path}.sha256"

    log_info "Generando checksum para $(basename "$zip_path")..."

    if command -v sha256sum &> /dev/null; then
        sha256sum "$zip_path" > "$checksum_path"
    elif command -v shasum &> /dev/null; then
        shasum -a 256 "$zip_path" > "$checksum_path"
    else
        log_error "No se puede generar checksum"
        return 1
    fi

    log_success "Checksum generado: $(basename "$checksum_path")"
}

###############################################################################
# Copiar archivos con exclusión
###############################################################################
copy_with_exclude() {
    local src="$1"
    local dest="$2"
    local exclude_patterns="${3:-}"

    mkdir -p "$dest"

    # Usar rsync si está disponible, sino cp
    if command -v rsync &> /dev/null; then
        local exclude_args=""
        for pattern in $exclude_patterns; do
            exclude_args="$exclude_args --exclude=$pattern"
        done

        rsync -a $exclude_args "$src/" "$dest/"
    else
        # Copiado básico con find
        find "$src" -type f | while read -r file; do
            local should_copy=true
            local rel_path="${file#$src/}"

            for pattern in $exclude_patterns; do
                if [[ "$rel_path" == $pattern ]]; then
                    should_copy=false
                    break
                fi
            done

            if [ "$should_copy" = true ]; then
                local dest_file="$dest/$rel_path"
                mkdir -p "$(dirname "$dest_file")"
                cp "$file" "$dest_file"
            fi
        done
    fi
}

###############################################################################
# Construir paquete wp-feature-api (client)
###############################################################################
build_wp_feature_api() {
    log_info "Construyendo wp-feature-api..."

    local pkg_name="wp-feature-api"
    local temp_dir=$(create_package_structure "$pkg_name")

    # Copiar estructura
    cp -r "$ROOT_DIR/wp-feature-api.php" "$temp_dir/"
    cp -r "$ROOT_DIR/includes" "$temp_dir/"
    cp -r "$ROOT_DIR/packages/client/src" "$temp_dir/src"
    cp -r "$ROOT_DIR/packages/client/build" "$temp_dir/build"
    cp -r "$ROOT_DIR/packages/client/build-types" "$temp_dir/build-types"
    cp "$ROOT_DIR/packages/client/package.json" "$temp_dir/"
    cp "$ROOT_DIR/packages/client/README.md" "$temp_dir/"

    # Excluir archivos de desarrollo
    find "$temp_dir" -name "*.map" -delete
    find "$temp_dir" -name ".gitignore" -delete
    find "$temp_dir" -name "tsconfig.json" -delete
    find "$temp_dir" -name "webpack.config.js" -delete

    # Generar ZIP
    local zip_name="${pkg_name}.zip"
    if create_zip "$temp_dir" "$zip_name"; then
        generate_checksum "$DIST_DIR/$zip_name"
    fi

    # Limpiar temp
    rm -rf "$temp_dir"
}

###############################################################################
# Construir paquete wp-feature-api-agent (client-features)
###############################################################################
build_wp_feature_api_agent() {
    log_info "Construyendo wp-feature-api-agent..."

    local pkg_name="wp-feature-api-agent"
    local temp_dir=$(create_package_structure "$pkg_name")

    # Copiar estructura
    cp -r "$ROOT_DIR/packages/client-features/src" "$temp_dir/src"
    cp -r "$ROOT_DIR/packages/client-features/build" "$temp_dir/build"
    cp "$ROOT_DIR/packages/client-features/package.json" "$temp_dir/"
    cp "$ROOT_DIR/packages/client-features/README.md" "$temp_dir/"

    # Excluir archivos de desarrollo
    find "$temp_dir" -name "*.map" -delete
    find "$temp_dir" -name ".gitignore" -delete
    find "$temp_dir" -name "tsconfig.json" -delete
    find "$temp_dir" -name "webpack.config.js" -delete

    # Generar ZIP
    local zip_name="${pkg_name}.zip"
    if create_zip "$temp_dir" "$zip_name"; then
        generate_checksum "$DIST_DIR/$zip_name"
    fi

    # Limpiar temp
    rm -rf "$temp_dir"
}

###############################################################################
# Construir paquete wp-feature-api-demo (demo-agent)
###############################################################################
build_wp_feature_api_demo() {
    log_info "Construyendo wp-feature-api-demo..."

    local pkg_name="wp-feature-api-demo"
    local temp_dir=$(create_package_structure "$pkg_name")

    # Copiar estructura
    cp "$ROOT_DIR/packages/demo-agent/wp-feature-api-agent.php" "$temp_dir/wp-feature-api-demo.php"
    cp -r "$ROOT_DIR/packages/demo-agent/includes" "$temp_dir/includes"
    cp -r "$ROOT_DIR/packages/demo-agent/src" "$temp_dir/src"
    cp -r "$ROOT_DIR/packages/demo-agent/build" "$temp_dir/build"
    cp "$ROOT_DIR/packages/demo-agent/package.json" "$temp_dir/"
    cp "$ROOT_DIR/packages/demo-agent/README.md" "$temp_dir/"

    # Excluir archivos de desarrollo
    find "$temp_dir" -name "*.map" -delete
    find "$temp_dir" -name ".gitignore" -delete
    find "$temp_dir" -name "tsconfig.json" -delete
    find "$temp_dir" -name "webpack.config.js" -delete
    find "$temp_dir" -name ".eslintrc*" -delete

    # Generar ZIP
    local zip_name="${pkg_name}.zip"
    if create_zip "$temp_dir" "$zip_name"; then
        generate_checksum "$DIST_DIR/$zip_name"
    fi

    # Limpiar temp
    rm -rf "$temp_dir"
}

###############################################################################
# Mostrar estructura de dist
###############################################################################
show_dist_structure() {
    echo ""
    log_info "Estructura de dist/:"
    echo ""

    if command -v tree &> /dev/null; then
        tree -h "$DIST_DIR"
    else
        find "$DIST_DIR" -type f -exec ls -lh {} \; 2>/dev/null | awk '{print "   " $9 " (" $5 ")"}'
    fi
}

###############################################################################
# Mostrar ayuda de verificación
###############################################################################
show_verification_help() {
    echo ""
    echo "==============================================================================="
    echo "📋 COMANDOS DE VERIFICACIÓN"
    echo "==============================================================================="
    echo ""
    echo "1. Verificar estructura del ZIP:"
    echo "   unzip -l dist/wp-feature-api.zip"
    echo "   unzip -l dist/wp-feature-api-agent.zip"
    echo "   unzip -l dist/wp-feature-api-demo.zip"
    echo ""
    echo "2. Verificar checksums:"
    echo "   sha256sum -c dist/wp-feature-api.zip.sha256"
    echo "   sha256sum -c dist/wp-feature-api-agent.zip.sha256"
    echo "   sha256sum -c dist/wp-feature-api-demo.zip.sha256"
    echo ""
    echo "3. Extraer y verificar contenido:"
    echo "   cd dist && unzip wp-feature-api.zip -d wp-feature-api-test"
    echo "   cd wp-feature-api-test && ls -la"
    echo ""
    echo "4. Verificar checksums con openssl:"
    echo "   openssl sha256 dist/wp-feature-api.zip"
    echo ""
    echo "5. Verificar con WP-CLI (si WordPress está instalado):"
    echo "   wp plugin install ./dist/wp-feature-api.zip --force"
    echo "   wp plugin activate wp-feature-api"
    echo "   wp plugin list --name=wp-feature-api"
    echo ""
}

###############################################################################
# Mostrar resumen
###############################################################################
show_summary() {
    echo ""
    echo "==============================================================================="
    echo "✅ BUILD COMPLETADO"
    echo "==============================================================================="
    echo ""
    echo "📁 Directorio: $DIST_DIR"
    echo ""
    echo "📦 Archivos generados:"
    echo ""

    local total_size=0
    for file in "$DIST_DIR"/*.zip; do
        if [ -f "$file" ]; then
            local size=$(stat -c%s "$file" 2>/dev/null || stat -f%z "$file" 2>/dev/null)
            local size_kb=$((size / 1024))
            echo "   📄 $(basename "$file") (${size_kb} KB)"
            total_size=$((total_size + size))
        fi
    done

    for file in "$DIST_DIR"/*.sha256; do
        if [ -f "$file" ]; then
            echo "   🔐 $(basename "$file")"
        fi
    done

    echo ""
    echo "📊 Total: $((total_size / 1024)) KB"

    show_verification_help
}

###############################################################################
# Función principal
###############################################################################
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════════════════╗"
    echo "║          GENERADOR DE PAQUETES ZIP - wp-feature-api                      ║"
    echo "╚═══════════════════════════════════════════════════════════════════════════╝"
    echo ""

    # Verificar dependencias
    check_dependencies

    # Ejecutar build
    run_build || exit 1

    # Limpiar dist
    clean_dist

    # Construir paquetes
    echo ""
    echo "==============================================================================="
    echo "📦 CONSTRUYENDO PAQUETES"
    echo "==============================================================================="

    build_wp_feature_api
    build_wp_feature_api_agent
    build_wp_feature_api_demo

    # Mostrar estructura y resumen
    show_dist_structure
    show_summary
}

# Ejecutar función principal
main "$@"

