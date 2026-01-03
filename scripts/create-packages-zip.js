#!/usr/bin/env node
/**
 * Script para generar los ZIPs de los paquetes del proyecto
 *
 * Uso: node scripts/create-packages-zip.js
 *
 * Genera:
 * - wp-feature-api.zip (paquete client)
 * - wp-feature-api-agent.zip (paquete client-features)
 * - wp-feature-api-demo.zip (paquete demo-agent)
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { execSync } = require( 'child_process' );

// Rutas
const ROOT = path.resolve( __dirname, '..' );
const DIST = path.resolve( ROOT, 'dist' );

// Patrones a excluir
const EXCLUDE_PATTERNS = [
	/node_modules/,
	/\.git/,
	/\.gitignore/,
	/\.eslintrc/,
	/\.prettierrc/,
	/\.DS_Store/,
	/\.map$/,
	/\.tsbuildinfo$/,
	/tsconfig\.json$/,
	/webpack\.config\.js$/,
	/composer\.lock$/,
	/package-lock\.json$/,
];

/**
 * Verificar que el archivo existe
 */
function fileExists( filePath ) {
	return fs.existsSync( filePath );
}

/**
 * Crear directorio si no existe
 */
function ensureDir( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		fs.mkdirSync( dir, { recursive: true } );
	}
}

/**
 * Leer versión del package.json
 */
function getVersion( packageJsonPath ) {
	if ( fileExists( packageJsonPath ) ) {
		const content = fs.readFileSync( packageJsonPath, 'utf8' );
		const pkg = JSON.parse( content );
		return pkg.version || '0.0.0';
	}
	return '0.0.0';
}

/**
 * Verificar si un path debe ser excluido
 */
function shouldExclude( filePath ) {
	const relativePath = path.relative( ROOT, filePath );

	for ( const pattern of EXCLUDE_PATTERNS ) {
		if ( pattern.test( relativePath ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Copiar directorio recursivamente
 */
function copyDirRecursive( src, dest ) {
	if ( ! fileExists( src ) ) {
		console.log( `      ⚠️  No existe: ${ src }` );
		return;
	}

	ensureDir( dest );

	const entries = fs.readdirSync( src, { withFileTypes: true } );

	for ( const entry of entries ) {
		const srcPath = path.join( src, entry.name );
		const destPath = path.join( dest, entry.name );
		const relPath = path.relative( ROOT, srcPath );

		if ( shouldExclude( srcPath ) ) {
			continue;
		}

		if ( entry.isDirectory() ) {
			copyDirRecursive( srcPath, destPath );
		} else {
			fs.copyFileSync( srcPath, destPath );
		}
	}
}

/**
 * Copiar archivo individual
 */
function copySingleFile( src, dest ) {
	const srcPath = path.resolve( ROOT, src );
	const destPath = path.resolve( dest );

	if ( fileExists( srcPath ) ) {
		ensureDir( path.dirname( destPath ) );
		fs.copyFileSync( srcPath, destPath );
		return true;
	}
	return false;
}

/**
 * Generar checksum SHA256
 */
function generateChecksum( filePath ) {
	try {
		const content = fs.readFileSync( filePath );
		const hash = require( 'crypto' )
			.createHash( 'sha256' )
			.update( content )
			.digest( 'hex' );
		return hash;
	} catch ( error ) {
		console.error( `❌ Error generando checksum: ${ error.message }` );
		return null;
	}
}

/**
 * Generar archivo ZIP usando zip
 */
function createZip( sourceDir, zipName ) {
	const zipPath = path.resolve( DIST, zipName );

	console.log( `      📦 Generando ${ zipName }...` );

	try {
		execSync(
			`cd "${ DIST }" && zip -r "${ zipName }" "${ path.basename( sourceDir ) }"`,
			{
				stdio: 'inherit',
				cwd: DIST,
			}
		);
		console.log( `      ✅ ZIP creado: ${ zipName }` );
		return true;
	} catch ( e ) {
		// Fallback a tar+gzip
		try {
			const tgzName = zipName.replace( '.zip', '.tgz' );
			const tgzPath = path.resolve( DIST, tgzName );
			execSync(
				`cd "${ DIST }" && tar -czf "${ tgzName }" "${ path.basename( sourceDir ) }"`,
				{
					stdio: 'inherit',
				}
			);
			// Rename to .zip for consistency
			if ( fileExists( tgzPath ) ) {
				fs.renameSync( tgzPath, zipPath );
			}
			console.log( `      ✅ Archivo creado: ${ zipName } (tar+gzip)` );
			return true;
		} catch ( tarError ) {
			console.error( `      ❌ Error creando archivo: ${ tarError.message }` );
			return false;
		}
	}
}

/**
 * Construir un paquete
 */
function buildPackage( packageKey, pkgConfig ) {
	const tempDir = path.resolve( DIST, pkgConfig.name );

	console.log( `\n🔨 Construyendo: ${ pkgConfig.name }` );
	console.log( '─'.repeat( 40 ) );

	// Limpiar directorio temporal anterior si existe
	if ( fs.existsSync( tempDir ) ) {
		fs.rmSync( tempDir, { recursive: true, force: true } );
	}
	ensureDir( tempDir );

	// Copiar archivos
	console.log( '\n📁 Copiando archivos:' );

	for ( const item of pkgConfig.include ) {
		const srcPath = path.resolve( ROOT, item.src );

		if ( ! fileExists( srcPath ) ) {
			console.log( `      ⚠️  No encontrado: ${ item.src }` );
			continue;
		}

		if ( fs.statSync( srcPath ).isDirectory() ) {
			const destPath = path.resolve( tempDir, item.dest );
			console.log( `   📂 ${ item.src }/ → ${ item.dest }/` );
			copyDirRecursive( srcPath, destPath );
		} else {
			const destPath = path.resolve( tempDir, item.dest );
			console.log( `   📄 ${ path.basename( item.src ) } → ${ item.dest }` );
			copySingleFile( item.src, destPath );
		}
	}

	// Listar contenido del temp dir
	console.log( '\n   📋 Contenido del paquete:' );
	const listContents = ( dir, indent = '      ' ) => {
		const entries = fs.readdirSync( dir, { withFileTypes: true } );
		entries.forEach( ( entry, idx ) => {
			const isLast = idx === entries.length - 1;
			const connector = isLast ? '└── ' : '├── ';
			console.log( `${ indent }${ connector }${ entry.name }` );
			if ( entry.isDirectory() ) {
				const newIndent = indent + ( isLast ? '    ' : '│   ' );
				listContents( path.join( dir, entry.name ), newIndent );
			}
		} );
	};
	listContents( tempDir );

	// Generar ZIP
	const zipName = `${ pkgConfig.name }.zip`;
	console.log( `\n📦 Generando ZIP...` );

	if ( ! createZip( tempDir, zipName ) ) {
		return null;
	}

	// Generar checksum
	const zipPath = path.resolve( DIST, zipName );
	if ( fileExists( zipPath ) ) {
		const checksum = generateChecksum( zipPath );

		if ( checksum ) {
			const checksumPath = zipPath + '.sha256';
			fs.writeFileSync( checksumPath, checksum + '\n' );
			console.log( `   ✅ Checksum: ${ zipName }.sha256` );
		}

		// Limpiar directorio temporal después de generar el ZIP
		fs.rmSync( tempDir, { recursive: true, force: true } );
		console.log( `   🧹 Temp limpio` );

		return {
			name: pkgConfig.name,
			zip: zipName,
			checksum: checksum,
			size: fs.statSync( zipPath ).size,
		};
	}

	return null;
}

/**
 * Mostrar ayuda de verificación
 */
function showVerificationHelp() {
	console.log( '\n' + '='.repeat( 60 ) );
	console.log( '📋 COMANDOS DE VERIFICACIÓN' );
	console.log( '='.repeat( 60 ) );

	console.log( '\n1. Verificar estructura del ZIP:' );
	console.log( '   unzip -l dist/wp-feature-api.zip' );
	console.log( '   unzip -l dist/wp-feature-api-agent.zip' );
	console.log( '   unzip -l dist/wp-feature-api-demo.zip' );

	console.log( '\n2. Verificar checksums:' );
	console.log( '   sha256sum -c dist/wp-feature-api.zip.sha256' );
	console.log( '   sha256sum -c dist/wp-feature-api-agent.zip.sha256' );
	console.log( '   sha256sum -c dist/wp-feature-api-demo.zip.sha256' );

	console.log( '\n3. Extraer y verificar:' );
	console.log( '   cd dist && unzip wp-feature-api.zip -d wp-feature-api-test' );
	console.log( '   cd wp-feature-api-test && ls -la' );

	console.log( '\n4. Verificar con WP-CLI (si WordPress está instalado):' );
	console.log( '   wp plugin install ./wp-feature-api.zip --force' );
	console.log( '   wp plugin activate wp-feature-api' );
	console.log( '   wp plugin list --name=wp-feature-api' );

	console.log( '\n5. Verificar checksums con openssl:' );
	console.log( '   openssl sha256 dist/wp-feature-api.zip' );
}

/**
 * Función principal
 */
async function main() {
	console.log( '╔═══════════════════════════════════════════════════════════╗' );
	console.log( '║          GENERADOR DE PAQUETES ZIP - wp-feature-api      ║' );
	console.log( '╚═══════════════════════════════════════════════════════════╝\n' );

	// Limpiar y crear directorio dist primero
	console.log( '🧹 Limpiando directorio dist...' );
	if ( fs.existsSync( DIST ) ) {
		fs.rmSync( DIST, { recursive: true, force: true } );
	}
	ensureDir( DIST );
	console.log( `   ✅ Directorio: ${ DIST }` );

	// Verificar builds primero
	console.log( '\n🔍 Verificando builds...' );
	const buildPaths = [
		{ path: path.resolve( ROOT, 'packages/client/build' ), name: 'client' },
		{ path: path.resolve( ROOT, 'packages/client-features/build' ), name: 'client-features' },
		{ path: path.resolve( ROOT, 'packages/demo-agent/build' ), name: 'demo-agent' },
	];

	let needsBuild = false;
	for ( const bp of buildPaths ) {
		if ( ! fileExists( bp.path ) ) {
			console.log( `   ⚠️  Build de ${ bp.name } no encontrado` );
			needsBuild = true;
		} else {
			console.log( `   ✅ Build de ${ bp.name } encontrado` );
		}
	}

	// Ejecutar build si es necesario
	if ( needsBuild ) {
		console.log( '\n🔨 Ejecutando build...' );
		try {
			execSync( 'npm run build', {
				stdio: 'inherit',
				cwd: ROOT,
			} );
			console.log( '✅ Build completado\n' );
		} catch ( error ) {
			console.error( '❌ Error en build:', error.message );
			process.exit( 1 );
		}
	} else {
		console.log( '\n⏭️  Build existente, saltando...\n' );
	}

	// Construir paquetes
	console.log( '═'.repeat( 60 ) );
	console.log( '📦 CONSTRUYENDO PAQUETES' );
	console.log( '═'.repeat( 60 ) );

	const PACKAGES = {
		'wp-feature-api': {
			name: 'wp-feature-api',
			include: [
				{ src: 'wp-feature-api.php', dest: 'wp-feature-api.php' },
				{ src: 'includes', dest: 'includes' },
				{ src: 'packages/client/src', dest: 'src' },
				{ src: 'packages/client/build', dest: 'build' },
				{ src: 'packages/client/build-types', dest: 'build-types' },
				{ src: 'packages/client/package.json', dest: 'package.json' },
				{ src: 'packages/client/README.md', dest: 'README.md' },
			],
		},
		'wp-feature-api-agent': {
			name: 'wp-feature-api-agent',
			include: [
				{ src: 'packages/client-features/src', dest: 'src' },
				{ src: 'packages/client-features/build', dest: 'build' },
				{ src: 'packages/client-features/package.json', dest: 'package.json' },
				{ src: 'packages/client-features/README.md', dest: 'README.md' },
			],
		},
		'wp-feature-api-demo': {
			name: 'wp-feature-api-demo',
			include: [
				{ src: 'packages/demo-agent/wp-feature-api-agent.php', dest: 'wp-feature-api-demo.php' },
				{ src: 'packages/demo-agent/includes', dest: 'includes' },
				{ src: 'packages/demo-agent/src', dest: 'src' },
				{ src: 'packages/demo-agent/build', dest: 'build' },
				{ src: 'packages/demo-agent/package.json', dest: 'package.json' },
				{ src: 'packages/demo-agent/README.md', dest: 'README.md' },
			],
		},
	};

	const results = [];

	for ( const [ key, pkg ] of Object.entries( PACKAGES ) ) {
		const result = buildPackage( key, pkg );
		if ( result ) {
			results.push( result );
		}
	}

	// Resumen final
	console.log( '\n' + '='.repeat( 60 ) );
	console.log( '✅ BUILD COMPLETADO' );
	console.log( '='.repeat( 60 ) );

	console.log( '\n📁 Archivos generados en dist/:' );
	const files = fs.readdirSync( DIST );
	files.sort().forEach( ( file ) => {
		const filePath = path.resolve( DIST, file );
		const stats = fs.statSync( filePath );
		const size = stats.isFile() ? `${ ( stats.size / 1024 ).toFixed( 2 ) } KB` : '[DIR]';
		console.log( `   📄 ${ file } (${ size })` );
	} );

	console.log( '\n📋 Resumen:' );
	results.forEach( ( r ) => {
		console.log(
			`   • ${ r.name }: ${ r.zip } (${ ( r.size / 1024 ).toFixed( 2 ) } KB)`
		);
	} );

	showVerificationHelp();
}

main().catch( ( error ) => {
	console.error( '\n❌ Error fatal:', error.message );
	process.exit( 1 );
} );

