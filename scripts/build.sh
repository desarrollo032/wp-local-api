#!/usr/bin/env bash
###############################################################################
# BUILD SCRIPT - wp-feature-api
#
# Compila todos los paquetes TypeScript/Webpack
#
# Uso: ./scripts/build.sh [--skip-npm-install]
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

SKIP_NPM_INSTALL=false
for arg in "$@"; do
    case $arg in
        --skip-npm-install)
            SKIP_NPM_INSTALL=true
            shift
            ;;
    esac
done

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

###############################################################################
# Verificar dependencias
###############################################################################
check_dependencies() {
    log_info "Verificando dependencias..."
    
    if ! command -v node &> /dev/null; then
        log_error "Node.js no está instalado"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        log_error "npm no está instalado"
        exit 1
    fi
    
    log_success "Dependencias verificadas"
}

###############################################################################
# Instalar dependencias npm
###############################################################################
npm_install() {
    if [ "$SKIP_NPM_INSTALL" = true ]; then
        log_info "npm install saltado (--skip-npm-install)"
        return 0
    fi
    
    log_info "Instalando dependencias npm..."
    cd "$ROOT_DIR"
    
    if npm install; then
        log_success "Dependencias instaladas"
        return 0
    else
        log_error "Error instalando dependencias"
        return 1
    fi
}

###############################################################################
# Ejecutar builds de webpack
###############################################################################
run_builds() {
    log_info "Ejecutando builds..."
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
# Verificar que los builds existan
###############################################################################
verify_builds() {
    log_info "Verificando builds..."
    
    local missing_builds=()
    
    if [ ! -d "$ROOT_DIR/packages/client/build" ]; then
        missing_builds+=("client")
    fi
    
    if [ ! -d "$ROOT_DIR/packages/client-features/build" ]; then
        missing_builds+=("client-features")
    fi
    
    if [ ! -d "$ROOT_DIR/packages/demo-agent/build" ]; then
        missing_builds+=("demo-agent")
    fi
    
    if [ ${#missing_builds[@]} -ne 0 ]; then
        log_error "Builds faltantes: ${missing_builds[*]}"
        return 1
    fi
    
    log_success "Todos los builds están presentes"
    return 0
}

###############################################################################
# Función principal
###############################################################################
main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════════════╗"
    echo "║                    BUILD - wp-feature-api                         ║"
    echo "╚═══════════════════════════════════════════════════════════════════╝"
    echo ""
    
    check_dependencies
    npm_install || exit 1
    run_builds || exit 1
    verify_builds || exit 1
    
    echo ""
    echo "==============================================================================="
    log_success "BUILD COMPLETADO"
    echo "==============================================================================="
    echo ""
    echo "📁 Builds generados:"
    echo "   packages/client/build/"
    echo "   packages/client-features/build/"
    echo "   packages/demo-agent/build/"
    echo ""
    echo "📦 Listo para empaquetar con: ./scripts/package.sh"
    echo ""
}

main "$@"

