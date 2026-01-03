/* eslint-disable no-console */
/**
 * Script de validación de plugins WordPress
 */

const fs = require( 'fs' );
const path = require( 'path' );
const crypto = require( 'crypto' );
// eslint-disable-next-line no-unused-vars
const { execSync } = require( 'child_process' );

/**
 * Validar encabezado del archivo principal
 *
 * @param {string} filePath Ruta del archivo
 * @return {Object} Resultado
 */
function validateMainHeader( filePath ) {
	const content = fs.readFileSync( filePath, 'utf8' );
	const errors = [];

	if ( ! content.includes( 'Plugin Name:' ) ) {
		errors.push( 'Falta "Plugin Name"' );
	}
	if ( ! content.includes( 'Version:' ) ) {
		errors.push( 'Falta "Version"' );
	}
	if ( ! content.includes( 'ABSPATH' ) ) {
		console.log( '   ⚠️  Advertencia: No se detectó chequeo de ABSPATH' );
	}

	return {
		valid: errors.length === 0,
		errors,
	};
}

/**
 * Validar estructura de archivos requeridos
 *
 * @param {string}   pluginDir     Directorio del plugin
 * @param {string[]} requiredFiles Archivos requeridos
 * @return {Object} Resultado
 */
function validateRequiredFiles( pluginDir, requiredFiles = [] ) {
	const missing = [];

	for ( const file of requiredFiles ) {
		if ( ! fs.existsSync( path.join( pluginDir, file ) ) ) {
			missing.push( file );
		}
	}

	if ( missing.length > 0 ) {
		console.log( '   ❌ Archivos faltantes:' );
		missing.forEach( ( f ) => console.log( `      - ${ f }` ) );
	}

	return {
		valid: missing.length === 0,
		missing,
	};
}

/**
 * Calcular hash SHA256 de un archivo
 *
 * @param {string} filePath Ruta del archivo
 * @return {string} Hash
 */
// eslint-disable-next-line no-unused-vars
function calculateSHA256( filePath ) {
	const fileBuffer = fs.readFileSync( filePath );
	const hashSum = crypto.createHash( 'sha256' );
	hashSum.update( fileBuffer );
	return hashSum.digest( 'hex' );
}

/**
 * Validar estructura general
 *
 * @param {string} pluginDir Directorio del plugin
 * @return {boolean} Validez
 */
function validateFileStructure( pluginDir ) {
	// Validar que no haya archivos de desarrollo
	const devFiles = [
		'.git',
		'.gitignore',
		'node_modules',
		'webpack.config.js',
		'tsconfig.json',
		'.eslintrc',
		'.prettierrc',
	];

	const foundDevFiles = [];

	// Función recursiva para buscar archivos
	function scan( dir ) {
		const entries = fs.readdirSync( dir, { withFileTypes: true } );
		for ( const entry of entries ) {
			if ( devFiles.includes( entry.name ) ) {
				foundDevFiles.push( path.join( dir, entry.name ) );
			} else if ( entry.isDirectory() ) {
				scan( path.join( dir, entry.name ) );
			}
		}
	}

	scan( pluginDir );

	if ( foundDevFiles.length > 0 ) {
		console.log(
			'   ⚠️  Advertencia: Se encontraron archivos de desarrollo:'
		);
		foundDevFiles.forEach( ( f ) =>
			console.log( `      - ${ path.relative( pluginDir, f ) }` )
		);
		// No fallamos por esto, pero avisamos
	}

	return true;
}

/**
 * Generar reporte
 *
 * @param {string} pluginDir Directorio del plugin
 * @param {Object} results   Resultados
 * @return {boolean} Resultado final
 */
function generateReport( pluginDir, results ) {
	console.log( '\n📊 Reporte de Validación' );
	console.log( '-'.repeat( 30 ) );

	let isValid = true;

	if ( results.header && ! results.header.valid ) {
		isValid = false;
		console.log( '❌ Error en encabezados:' );
		results.header.errors.forEach( ( e ) => console.log( `   - ${ e }` ) );
	} else {
		console.log( '✅ Encabezados correctos' );
	}

	if ( results.required && ! results.required.valid ) {
		isValid = false;
		console.log( '❌ Archivos requeridos faltantes' );
	} else {
		console.log( '✅ Archivos requeridos presentes' );
	}

	if ( results.structure ) {
		console.log( '✅ Estructura verificada' );
	}

	return isValid;
}

/**
 * Función principal
 *
 * @param {string} pluginDir Directorio del plugin a validar
 */
function main( pluginDir ) {
	if ( ! pluginDir ) {
		console.error( '❌ Error: Debes especificar el directorio del plugin' );
		process.exit( 1 );
	}

	if ( ! fs.existsSync( pluginDir ) ) {
		console.error( `❌ Error: El directorio no existe: ${ pluginDir }` );
		process.exit( 1 );
	}

	console.log( `🔍 Validando plugin en: ${ pluginDir }` );

	// Buscar archivo principal
	const mainFile = fs
		.readdirSync( pluginDir )
		.find( ( f ) => f.endsWith( '.php' ) );

	if ( ! mainFile ) {
		console.error( '❌ Error: No se encontró archivo PHP principal' );
		process.exit( 1 );
	}

	const mainFilePath = path.join( pluginDir, mainFile );

	const results = {
		header: validateMainHeader( mainFilePath ),
		required: validateRequiredFiles( pluginDir, [ mainFile ] ),
		structure: validateFileStructure( pluginDir ),
	};

	const isValid = generateReport( pluginDir, results );

	console.log(
		'\n' + ( isValid ? '✅ VALIDACIÓN EXITOSA' : '❌ VALIDACIÓN FALLIDA' )
	);

	process.exit( isValid ? 0 : 1 );
}

// Ejecutar si se llama directamente
if ( require.main === module ) {
	const dir = process.argv[ 2 ];
	main( dir );
}

module.exports = { main, validateMainHeader, validateRequiredFiles };
