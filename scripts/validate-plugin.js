/**
 * Script de validación de estructura de plugins WordPress
 * 
 * Verifica que los plugins cumplan con las reglas de WordPress:
 * - Archivo principal con header correcto
 * - No hay archivos de desarrollo (node_modules, .git, etc.)
 * - Estructura de carpetas válida
 * 
 * Uso: node scripts/validate-plugin.js [ruta_plugin]
 */

const fs = require('fs');
const path = require('path');

const REQUIRED_HEADERS = [
    'Plugin Name',
    'Version',
];

const FORBIDDEN_PATTERNS = [
    /node_modules/,
    /\.git/,
    /\.gitignore/,
    /composer\.lock/,
    /package-lock\.json/,
    /\.eslint/,
    /\.prettier/,
    /\.vscode/,
    /tsconfig/,
    /webpack\.config\.js/,
    /\.DS_Store/,
    /Thumbs\.db/,
];

const REQUIRED_FILES = [
    'wp-feature-api.php',
];

const VALID_PLUGIN_HEADERS = [
    'Plugin Name',
    'Version',
    'Author',
    'License',
    'Text Domain',
];

/**
 * Verificar header del archivo principal
 */
function validateMainHeader(filePath) {
    console.log(`\n📄 Validando header del plugin: ${path.basename(filePath)}`);
    
    const content = fs.readFileSync(filePath, 'utf8');
    const errors = [];
    const warnings = [];
    
    // Verificar headers requeridos
    for (const header of REQUIRED_HEADERS) {
        const regex = new RegExp(`${header}:`, 'i');
        if (!regex.test(content)) {
            errors.push(`Header requerido faltante: ${header}`);
        }
    }
    
    // Verificar exit si ABSPATH no está definido
    if (!content.includes('ABSPATH')) {
        warnings.push('No se encontró verificación de ABSPATH');
    }
    
    return { valid: errors.length === 0, errors, warnings };
}

/**
 * Verificar estructura de archivos
 */
function validateFileStructure(pluginDir) {
    console.log(`\n📁 Validando estructura: ${path.basename(pluginDir)}`);
    
    const errors = [];
    const warnings = [];
    const foundFiles = [];
    
    function scanDir(dir, baseDir = dir) {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        
        for (const entry of entries) {
            const fullPath = path.join(dir, entry.name);
            const relativePath = path.relative(baseDir, fullPath);
            
            if (entry.isDirectory()) {
                // Verificar si el nombre del directorio es válido
                if (entry.name.startsWith('.') || entry.name.includes(' ')) {
                    warnings.push(`Nombre de directorio potencialmente problemático: ${relativePath}`);
                }
                scanDir(fullPath, baseDir);
            } else {
                foundFiles.push(relativePath);
                
                // Verificar patrones prohibidos
                for (const pattern of FORBIDDEN_PATTERNS) {
                    if (pattern.test(relativePath)) {
                        errors.push(`Archivo de desarrollo encontrado: ${relativePath}`);
                    }
                }
            }
        }
    }
    
    scanDir(pluginDir);
    
    return { 
        valid: errors.length === 0, 
        errors, 
        warnings,
        fileCount: foundFiles.length 
    };
}

/**
 * Verificar que existen archivos requeridos
 */
function validateRequiredFiles(pluginDir, requiredFiles) {
    console.log(`\n✅ Verificando archivos requeridos...`);
    
    const errors = [];
    
    for (const file of requiredFiles) {
        const filePath = path.join(pluginDir, file);
        if (!fs.existsSync(filePath)) {
            errors.push(`Archivo requerido faltante: ${file}`);
        }
    }
    
    return { valid: errors.length === 0, errors };
}

/**
 * Verificar estructura del ZIP (antes de comprimir)
 */
function validateZipStructure(pluginDir) {
    console.log(`\n📦 Validando estructura del plugin...`);
    
    const errors = [];
    const info = [];
    
    // El directorio debe tener el mismo nombre que el archivo principal
    const dirName = path.basename(pluginDir);
    
    // Verificar que el directorio no contenga espacios o caracteres especiales
    if (/[^a-z0-9\-_]/.test(dirName)) {
        errors.push(`Nombre del directorio contiene caracteres inválidos: ${dirName}`);
    }
    
    // Contar archivos
    let fileCount = 0;
    let dirCount = 0;
    
    function count(dir) {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        for (const entry of entries) {
            if (entry.isDirectory()) {
                dirCount++;
                count(path.join(dir, entry.name));
            } else {
                fileCount++;
            }
        }
    }
    
    count(pluginDir);
    
    info.push(`Archivos: ${fileCount}`);
    info.push(`Directorios: ${dirCount}`);
    
    // Verificar que hay archivos PHP
    const hasPhp = foundFiles => foundFiles.some(f => f.endsWith('.php'));
    if (!hasPhp([...foundFiles])) {
        errors.push('No se encontraron archivos PHP');
    }
    
    return { valid: errors.length === 0, errors, info };
}

/**
 * Generar reporte de validación
 */
function generateReport(pluginDir, results) {
    console.log('\n' + '='.repeat(60));
    console.log(`📋 REPORTE DE VALIDACIÓN: ${path.basename(pluginDir)}`);
    console.log('='.repeat(60));
    
    let allValid = true;
    
    // Header
    console.log('\n🔍 VALIDACIÓN DE HEADER:');
    if (results.header.valid) {
        console.log('   ✅ Header válido');
    } else {
        console.log('   ❌ Errores:');
        results.header.errors.forEach(e => console.log(`      - ${e}`));
        allValid = false;
    }
    results.header.warnings.forEach(w => console.log(`   ⚠️  ${w}`));
    
    // Archivos requeridos
    console.log('\n📄 ARCHIVOS REQUERIDOS:');
    if (results.required.valid) {
        console.log('   ✅ Todos los archivos requeridos presentes');
    } else {
        console.log('   ❌ Archivos faltantes:');
        results.required.errors.forEach(e => console.log(`      - ${e}`));
        allValid = false;
    }
    
    // Estructura
    console.log('\n📁 ESTRUCTURA DE ARCHIVOS:');
    if (results.structure.valid) {
        console.log(`   ✅ Estructura válida (${results.structure.fileCount} archivos)`);
    } else {
        console.log('   ❌ Problemas encontrados:');
        results.structure.errors.forEach(e => console.log(`      - ${e}`));
        allValid = false;
    }
    results.structure.warnings.forEach(w => console.log(`   ⚠️  ${w}`));
    
    console.log('\n' + '='.repeat(60));
    
    return allValid;
}

/**
 * Función principal
 */
function main() {
    const args = process.argv.slice(2);
    const pluginDir = args[0] || process.cwd();
    
    console.log('🔍 Validador de Plugins WordPress');
    console.log('='.repeat(60));
    console.log(`📂 Plugin: ${pluginDir}`);
    
    // Verificar que existe
    if (!fs.existsSync(pluginDir)) {
        console.error(`❌ Error: El directorio no existe: ${pluginDir}`);
        process.exit(1);
    }
    
    // Encontrar archivo principal
    const mainFile = fs.readdirSync(pluginDir).find(f => f.endsWith('.php'));
    
    if (!mainFile) {
        console.error('❌ Error: No se encontró archivo PHP principal');
        process.exit(1);
    }
    
    const mainFilePath = path.join(pluginDir, mainFile);
    
    // Ejecutar validaciones
    const results = {
        header: validateMainHeader(mainFilePath),
        required: validateRequiredFiles(pluginDir, REQUIRED_FILES),
        structure: validateFileStructure(pluginDir),
    };
    
    // Generar reporte
    const isValid = generateReport(pluginDir, results);
    
    console.log('\n' + (isValid ? '✅ VALIDACIÓN EXITOSA' : '❌ VALIDACIÓN FALLIDA'));
    
    process.exit(isValid ? 0 : 1);
}

module.exports = {
    validateMainHeader,
    validateFileStructure,
    validateRequiredFiles,
    validateZipStructure,
    generateReport,
};

main();
