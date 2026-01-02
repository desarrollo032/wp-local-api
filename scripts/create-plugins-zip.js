/**
 * Script de build para generar los plugins ZIP de WordPress
 * 
 * Este script:
 * 1. Compila los assets con Webpack
 * 2. Copia los archivos necesarios a carpetas de distribución
 * 3. Genera los archivos ZIP
 * 
 * Uso: node scripts/create-plugins-zip.js
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Rutas
const ROOT = path.resolve(__dirname, '..');
const DIST = path.resolve(ROOT, 'dist');
const BUILD_CLIENT = path.resolve(ROOT, 'packages/client/build');
const BUILD_CLIENT_FEATURES = path.resolve(ROOT, 'packages/client-features/build');
const BUILD_DEMO_AGENT = path.resolve(ROOT, 'packages/demo-agent/build');

// Archivos del plugin principal (wp-feature-api)
const PLUGIN_MAIN_FILES = [
    'wp-feature-api.php',
    'composer.json',
    'composer.lock',
];

const PLUGIN_MAIN_DIRS = [
    'includes',
    'build',  // Se copiará después del build
];

// Archivos del plugin demo (wp-feature-api-agent)
const PLUGIN_DEMO_FILES = [
    'wp-feature-api-agent.php',
];

const PLUGIN_DEMO_DIRS = [
    'includes',
    'build',  // Se copiará después del build
];

/**
 * Crear directorio si no existe
 */
function ensureDir(dir) {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
        console.log(`📁 Creado: ${path.relative(ROOT, dir)}`);
    }
}

/**
 * Copiar archivo
 */
function copyFile(src, dest) {
    fs.copyFileSync(src, dest);
    console.log(`   📄 Copiado: ${path.relative(ROOT, src)} → ${path.relative(ROOT, dest)}`);
}

/**
 * Copiar directorio recursivamente
 */
function copyDir(src, dest) {
    ensureDir(dest);
    const entries = fs.readdirSync(src, { withFileTypes: true });
    
    for (const entry of entries) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);
        
        if (entry.isDirectory()) {
            copyDir(srcPath, destPath);
        } else {
            copyFile(srcPath, destPath);
        }
    }
}

/**
 * Construir el plugin wp-feature-api
 */
function buildPluginMain() {
    console.log('\n🔨 Construyendo plugin principal: wp-feature-api');
    
    const pluginDir = path.resolve(DIST, 'wp-feature-api');
    ensureDir(pluginDir);
    
    // Copiar archivos PHP
    console.log('\n📋 Copiando archivos PHP:');
    for (const file of PLUGIN_MAIN_FILES) {
        const src = path.resolve(ROOT, file);
        const dest = path.resolve(pluginDir, file);
        if (fs.existsSync(src)) {
            copyFile(src, dest);
        } else {
            console.warn(`   ⚠️ No encontrado: ${file}`);
        }
    }
    
    // Copiar directorios
    console.log('\n📋 Copiando directorios:');
    for (const dir of PLUGIN_MAIN_DIRS) {
        const src = path.resolve(ROOT, dir);
        const dest = path.resolve(pluginDir, dir);
        if (fs.existsSync(src)) {
            copyDir(src, dest);
        } else if (dir === 'build') {
            // El directorio build se crea después del build de webpack
            console.warn(`   ⚠️ Directorio no encontrado (se creará después del build): ${dir}`);
        }
    }
    
    // Copiar el SDK del cliente al directorio build del plugin
    console.log('\n📋 Copiando SDK del cliente:');
    const clientBuildDest = path.resolve(pluginDir, 'build', 'client');
    ensureDir(clientBuildDest);
    
    if (fs.existsSync(BUILD_CLIENT)) {
        copyDir(BUILD_CLIENT, clientBuildDest);
    } else {
        console.warn(`   ⚠️ Build del cliente no encontrado: ${BUILD_CLIENT}`);
    }
    
    // Copiar client-features al directorio build del plugin
    console.log('\n📋 Copiando client-features:');
    const clientFeaturesDest = path.resolve(pluginDir, 'build', 'client-features');
    ensureDir(clientFeaturesDest);
    
    if (fs.existsSync(BUILD_CLIENT_FEATURES)) {
        copyDir(BUILD_CLIENT_FEATURES, clientFeaturesDest);
    } else {
        console.warn(`   ⚠️ Build de client-features no encontrado: ${BUILD_CLIENT_FEATURES}`);
    }
    
    return pluginDir;
}

/**
 * Construir el plugin wp-feature-api-agent
 */
function buildPluginDemo() {
    console.log('\n🔨 Construyendo plugin demo: wp-feature-api-agent');
    
    const pluginDir = path.resolve(DIST, 'wp-feature-api-agent');
    ensureDir(pluginDir);
    
    // Copiar archivos PHP
    console.log('\n📋 Copiando archivos PHP:');
    for (const file of PLUGIN_DEMO_FILES) {
        const src = path.resolve(ROOT, 'packages/demo-agent', file);
        const dest = path.resolve(pluginDir, file);
        if (fs.existsSync(src)) {
            copyFile(src, dest);
        } else {
            console.warn(`   ⚠️ No encontrado: ${file}`);
        }
    }
    
    // Copiar directorios
    console.log('\n📋 Copiando directorios:');
    for (const dir of PLUGIN_DEMO_DIRS) {
        const src = path.resolve(ROOT, 'packages/demo-agent', dir);
        const dest = path.resolve(pluginDir, dir);
        if (fs.existsSync(src)) {
            copyDir(src, dest);
        } else {
            console.warn(`   ⚠️ No encontrado: ${dir}`);
        }
    }
    
    // Copiar el SDK del cliente como dependencia
    console.log('\n📋 Copiando SDK del cliente como dependencia:');
    const clientDest = path.resolve(pluginDir, 'vendor', '@automattic', 'wp-feature-api');
    ensureDir(clientDest);
    
    if (fs.existsSync(BUILD_CLIENT)) {
        copyDir(BUILD_CLIENT, clientDest);
    }
    
    return pluginDir;
}

/**
 * Generar archivo ZIP
 */
function createZip(dir, zipName) {
    console.log(`\n📦 Generando ${zipName}...`);
    
    const cwd = process.cwd();
    const dirName = path.basename(dir);
    const zipPath = path.resolve(DIST, zipName);
    
    try {
        // Usar zip de Linux
        execSync(`cd "${DIST}" && zip -r "${zipName}" "${dirName}"`, { 
            stdio: 'inherit',
            cwd: DIST 
        });
        console.log(`✅ Creado: ${zipName}`);
    } catch (error) {
        // Si zip no está disponible, usar método alternativo
        console.warn('⚠️ zip no disponible, tacto con tar+gzip...');
        try {
            execSync(`cd "${DIST}" && tar -czf "${zipName.replace('.zip', '.tgz')}" "${dirName}"`, {
                stdio: 'inherit'
            });
            console.log(`✅ Creado: ${zipName.replace('.zip', '.tgz')}`);
        } catch (e) {
            console.error('❌ No se pudo crear el archivo comprimido');
            throw e;
        }
    }
}

/**
 * Función principal
 */
async function main() {
    console.log('🚀 Iniciando build de plugins WordPress\n');
    console.log('=' .repeat(50));
    
    // Verificar que existen los builds
    console.log('\n🔍 Verificando builds existentes...');
    
    if (!fs.existsSync(BUILD_CLIENT)) {
        console.log('⚠️ Build del cliente no encontrado. Ejecutando npm run build:client...');
        execSync('npm run build:client', { stdio: 'inherit', cwd: ROOT });
    }
    
    if (!fs.existsSync(BUILD_DEMO_AGENT)) {
        console.log('⚠️ Build del demo-agent no encontrado. Ejecutando npm run build:demo-agent...');
        execSync('npm run build:demo-agent', { stdio: 'inherit', cwd: ROOT });
    }
    
    // Limpiar directorio dist
    console.log('\n🧹 Limpiando directorio dist...');
    if (fs.existsSync(DIST)) {
        fs.rmSync(DIST, { recursive: true, force: true });
    }
    ensureDir(DIST);
    
    // Construir plugins
    const pluginMainDir = buildPluginMain();
    const pluginDemoDir = buildPluginDemo();
    
    // Generar ZIPs
    console.log('\n' + '='.repeat(50));
    console.log('📦 Generando archivos ZIP...');
    
    createZip(pluginMainDir, 'wp-feature-api.zip');
    createZip(pluginDemoDir, 'wp-feature-api-agent.zip');
    
    console.log('\n' + '='.repeat(50));
    console.log('✅ Build completado exitosamente!');
    console.log(`📁 Archivos en: ${DIST}`);
    console.log('\nArchivos generados:');
    const files = fs.readdirSync(DIST);
    for (const file of files) {
        const filePath = path.resolve(DIST, file);
        const size = fs.statSync(filePath).size;
        console.log(`   - ${file} (${(size / 1024).toFixed(2)} KB)`);
    }
}

main().catch(console.error);

