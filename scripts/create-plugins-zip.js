/* eslint-disable no-console */
/**
 * Script de build para generar los plugins ZIP de WordPress
 *
 * Este script:
 * 1. Compila los assets con Webpack
 * 2. Valida la estructura de los plugins
 * 3. Copia los archivos necesarios (excluyendo desarrollo)
 * 4. Genera los archivos ZIP
 *
 * Uso: node scripts/create-plugins-zip.js
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { execSync } = require( 'child_process' );

// Rutas
const ROOT = path.resolve( __dirname, '..' );
const DIST = path.resolve( ROOT, 'dist' );
const BUILD_CLIENT = path.resolve( ROOT, 'packages/client/build' );
const BUILD_CLIENT_FEATURES = path.resolve(
	ROOT,
	'packages/client-features/build'
);
const BUILD_DEMO_AGENT = path.resolve( ROOT, 'packages/demo-agent/build' );

// Patrones a excluir (archivos de desarrollo)
const EXCLUDE_PATTERNS = [
	/node_modules/,
	/\.git/,
	/\.gitignore/,
	/\.eslint/,
	/\.prettier/,
	/\.vscode/,
	/tsconfig/,
	/webpack\.config\.js/,
	/composer\.lock/,
	/package-lock\.json/,
	/\.DS_Store/,
	/Thumbs\.db/,
];

// Extensiones de archivos a excluir
const EXCLUDE_EXTENSIONS = [ '.map', '.d.ts', '.tsbuildinfo' ];

/**
 * Crear directorio si no existe
 *
 * @param {string} dir Directorio a crear
 */
function ensureDir( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		fs.mkdirSync( dir, { recursive: true } );
		console.log( `📁 Creado: ${ path.relative( ROOT, dir ) }` );
	}
}

/**
 * Verificar si un archivo debe ser excluido
 *
 * @param {string} filePath Ruta del archivo
 * @return {boolean} Si debe ser excluido
 */
function shouldExclude( filePath ) {
	const relativePath = path.relative( ROOT, filePath );

	for ( const pattern of EXCLUDE_PATTERNS ) {
		if ( pattern.test( relativePath ) ) {
			return true;
		}
	}

	const ext = path.extname( filePath );
	if ( EXCLUDE_EXTENSIONS.includes( ext ) ) {
		return true;
	}

	return false;
}

/**
 * Copiar archivo
 *
 * @param {string} src  Origen
 * @param {string} dest Destino
 */
function copyFile( src, dest ) {
	fs.copyFileSync( src, dest );
	console.log( `   📄 ${ path.basename( src ) }` );
}

/**
 * Copiar directorio recursivamente
 *
 * @param {string} src  Origen
 * @param {string} dest Destino
 */
function copyDir( src, dest ) {
	ensureDir( dest );
	const entries = fs.readdirSync( src, { withFileTypes: true } );

	for ( const entry of entries ) {
		const srcPath = path.join( src, entry.name );
		const destPath = path.join( dest, entry.name );

		if ( entry.isDirectory() ) {
			copyDir( srcPath, destPath );
		} else if ( ! shouldExclude( srcPath ) ) {
			copyFile( srcPath, destPath );
		}
	}
}

/**
 * Construir el plugin wp-feature-api
 *
 * @return {string} Ruta del plugin
 */
function buildPluginMain() {
	console.log( '\n🔨 Construyendo: wp-feature-api' );
	console.log( '-'.repeat( 40 ) );

	const pluginDir = path.resolve( DIST, 'wp-feature-api' );
	ensureDir( pluginDir );

	// Copiar archivo principal del plugin
	console.log( '\n📄 Archivos PHP:' );
	copyFile(
		path.resolve( ROOT, 'wp-feature-api.php' ),
		path.resolve( pluginDir, 'wp-feature-api.php' )
	);

	// Copiar includes
	console.log( '\n📁 includes/:' );
	copyDir(
		path.resolve( ROOT, 'includes' ),
		path.resolve( pluginDir, 'includes' )
	);

	// Copiar build del cliente (SDK)
	console.log( '\n📁 build/client/:' );
	if ( fs.existsSync( BUILD_CLIENT ) ) {
		const clientDest = path.resolve( pluginDir, 'build', 'client' );
		ensureDir( clientDest );
		copyDir( BUILD_CLIENT, clientDest );
	}

	// Copiar build de client-features
	console.log( '\n📁 build/client-features/:' );
	if ( fs.existsSync( BUILD_CLIENT_FEATURES ) ) {
		const featuresDest = path.resolve(
			pluginDir,
			'build',
			'client-features'
		);
		ensureDir( featuresDest );
		copyDir( BUILD_CLIENT_FEATURES, featuresDest );
	}

	return pluginDir;
}

/**
 * Construir el plugin wp-feature-api-agent
 *
 * @return {string} Ruta del plugin
 */
function buildPluginDemo() {
	console.log( '\n🔨 Construyendo: wp-feature-api-agent' );
	console.log( '-'.repeat( 40 ) );

	const pluginDir = path.resolve( DIST, 'wp-feature-api-agent' );
	ensureDir( pluginDir );

	// Copiar archivo principal del plugin
	console.log( '\n📄 Archivos PHP:' );
	copyFile(
		path.resolve( ROOT, 'packages/demo-agent/wp-feature-api-agent.php' ),
		path.resolve( pluginDir, 'wp-feature-api-agent.php' )
	);

	// Copiar includes
	console.log( '\n📁 includes/:' );
	copyDir(
		path.resolve( ROOT, 'packages/demo-agent/includes' ),
		path.resolve( pluginDir, 'includes' )
	);

	// Copiar build (JS y CSS)
	console.log( '\n📁 build/:' );
	if ( fs.existsSync( BUILD_DEMO_AGENT ) ) {
		copyDir( BUILD_DEMO_AGENT, path.resolve( pluginDir, 'build' ) );
	}

	// Copiar SDK del cliente como dependencia
	console.log( '\n📁 vendor/@automattic/wp-feature-api/:' );
	const vendorDir = path.resolve(
		pluginDir,
		'vendor',
		'@automattic',
		'wp-feature-api'
	);
	ensureDir( vendorDir );
	if ( fs.existsSync( BUILD_CLIENT ) ) {
		copyDir( BUILD_CLIENT, vendorDir );
	}

	return pluginDir;
}

/**
 * Validar estructura del plugin WordPress
 *
 * @param {string} pluginDir Directorio del plugin
 * @return {boolean} Resultado
 */
function validatePlugin( pluginDir ) {
	console.log( `\n🔍 Validando: ${ path.basename( pluginDir ) }` );

	const errors = [];

	// Verificar archivo principal
	const mainPhp = fs
		.readdirSync( pluginDir )
		.find( ( f ) => f.endsWith( '.php' ) );
	if ( ! mainPhp ) {
		errors.push( 'Archivo PHP principal no encontrado' );
	} else {
		// Verificar headers requeridos de WordPress
		const content = fs.readFileSync(
			path.join( pluginDir, mainPhp ),
			'utf8'
		);
		const requiredHeaders = [ 'Plugin Name:', 'Version:' ];
		for ( const header of requiredHeaders ) {
			if ( ! content.includes( header ) ) {
				errors.push( `Header requerido faltante: ${ header }` );
			}
		}
		// Verificar protección ABSPATH
		if ( ! content.includes( 'ABSPATH' ) ) {
			console.log( '   ⚠️  Advertencia: No tiene protección ABSPATH' );
		}
	}

	if ( errors.length > 0 ) {
		console.log( '   ❌ Errores:' );
		errors.forEach( ( e ) => console.log( `      - ${ e }` ) );
		return false;
	}

	console.log( '   ✅ Validación exitosa' );
	return true;
}

/**
 * Generar archivo ZIP
 *
 * @param {string} pluginDir Directorio del plugin
 * @param {string} zipName   Nombre del ZIP
 * @return {boolean} Resultado
 */
function createZip( pluginDir, zipName ) {
	console.log( `\n📦 Generando ${ zipName }...` );

	const dirName = path.basename( pluginDir );

	try {
		execSync( `cd "${ DIST }" && zip -r "${ zipName }" "${ dirName }"`, {
			stdio: 'inherit',
			cwd: DIST,
		} );
		console.log( `   ✅ Creado: ${ zipName }` );
		return true;
	} catch ( error ) {
		console.log( '   ℹ️  zip no disponible, usando tar+gzip...' );
		try {
			const tgzName = zipName.replace( '.zip', '.tgz' );
			execSync(
				`cd "${ DIST }" && tar -czf "${ tgzName }" "${ dirName }"`,
				{
					stdio: 'inherit',
				}
			);
			console.log( `   ✅ Creado: ${ tgzName }` );
			return true;
		} catch ( e ) {
			console.error( `   ❌ Error al crear archivo: ${ e.message }` );
			return false;
		}
	}
}

/**
 * Mostrar contenido del ZIP
 *
 * @param {string} zipName Nombre del ZIP
 */
function showZipContents( zipName ) {
	const zipPath = path.resolve( DIST, zipName );
	if ( fs.existsSync( zipPath ) ) {
		const stats = fs.statSync( zipPath );
		console.log(
			`   📄 ${ zipName } (${ ( stats.size / 1024 ).toFixed( 2 ) } KB)`
		);
	}
}

/**
 * Listar estructura de archivos
 *
 * @param {string} dir    Directorio
 * @param {string} prefix Prefijo para visualización
 */
function listFiles( dir, prefix = '' ) {
	const entries = fs.readdirSync( dir, { withFileTypes: true } );
	entries.forEach( ( entry, index ) => {
		const isLast = index === entries.length - 1;
		const connector = isLast ? '└── ' : '├── ';
		console.log( `${ prefix }${ connector }${ entry.name }` );
		if ( entry.isDirectory() ) {
			const newPrefix = prefix + ( isLast ? '    ' : '│   ' );
			listFiles( path.join( dir, entry.name ), newPrefix );
		}
	} );
}

/**
 * Función principal
 */
async function main() {
	console.log( '🚀 Build de Plugins WordPress' );
	console.log( '='.repeat( 50 ) );

	// Verificar builds
	console.log( '\n🔍 Verificando builds de webpack...' );
	let needsBuild = false;

	if ( ! fs.existsSync( BUILD_CLIENT ) ) {
		console.log( '   ⚠️ Build del cliente no encontrado' );
		needsBuild = true;
	}
	if ( ! fs.existsSync( BUILD_DEMO_AGENT ) ) {
		console.log( '   ⚠️ Build del demo-agent no encontrado' );
		needsBuild = true;
	}

	if ( needsBuild ) {
		console.log( '\n📦 Ejecutando builds de webpack...' );
		try {
			execSync( 'npm run build', { stdio: 'inherit', cwd: ROOT } );
		} catch ( e ) {
			console.error( '❌ Error en build de webpack' );
			process.exit( 1 );
		}
	}

	// Limpiar dist
	console.log( '\n🧹 Limpiando directorio dist...' );
	if ( fs.existsSync( DIST ) ) {
		fs.rmSync( DIST, { recursive: true, force: true } );
	}
	ensureDir( DIST );

	// Construir plugins
	console.log( '\n' + '='.repeat( 50 ) );
	const pluginMainDir = buildPluginMain();
	const pluginDemoDir = buildPluginDemo();

	// Validar plugins
	console.log( '\n' + '='.repeat( 50 ) );
	console.log( '🔍 VALIDACIÓN DE PLUGINS' );
	console.log( '='.repeat( 50 ) );

	const mainValid = validatePlugin( pluginMainDir );
	const demoValid = validatePlugin( pluginDemoDir );

	if ( ! mainValid || ! demoValid ) {
		console.error( '\n❌ Error: La validación falló' );
		process.exit( 1 );
	}

	// Generar ZIPs
	console.log( '\n' + '='.repeat( 50 ) );
	console.log( '📦 GENERANDO ARCHIVOS ZIP' );
	console.log( '='.repeat( 50 ) );

	const mainZipOk = createZip( pluginMainDir, 'wp-feature-api.zip' );
	const demoZipOk = createZip( pluginDemoDir, 'wp-feature-api-agent.zip' );

	if ( ! mainZipOk || ! demoZipOk ) {
		console.error( '\n❌ Error: No se pudieron crear los ZIPs' );
		process.exit( 1 );
	}

	// Resumen
	console.log( '\n' + '='.repeat( 50 ) );
	console.log( '✅ BUILD COMPLETADO' );
	console.log( '='.repeat( 50 ) );
	console.log( `📁 Directorio: ${ DIST }` );
	console.log( '\nArchivos generados:' );
	showZipContents( 'wp-feature-api.zip' );
	showZipContents( 'wp-feature-api-agent.zip' );

	// Listar estructura
	console.log( '\n📂 Estructura de plugins:' );

	console.log( '\nwp-feature-api/' );
	listFiles( pluginMainDir, '  ' );

	console.log( '\nwp-feature-api-agent/' );
	listFiles( pluginDemoDir, '  ' );
}

main().catch( console.error );
