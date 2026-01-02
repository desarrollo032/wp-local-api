# Análisis de Errores de Build - wp-feature-api

## Resumen Ejecutivo

Se han identificado **5 problemas críticos** en la configuración de Webpack y TypeScript que pueden causar errores de build.

---

## 🔴 PROBLEMA 1: Ruta Incorrecta en tsconfig.json Raíz

**Archivo:** `/workspaces/wp-feature-api/tsconfig.json`  
**Línea:** 7  
**Gravedad:** CRÍTICA

### Problema
```json
{
  "references": [
    { "path": "./demo/wp-feature-api-agent" }  // ❌ INCORRECTO
  ]
}
```

### Causa Raíz
La referencia apunta a `demo/wp-feature-api-agent` pero el paquete está en `packages/demo-agent`.

### Corrección
```json
{
  "references": [
    { "path": "./packages/demo-agent" }  // ✅ CORRECTO
  ]
}
```

---

## 🔴 PROBLEMA 2: Falta `entry` en demo-agent webpack.config.js

**Archivo:** `/workspaces/wp-feature-api/packages/demo-agent/webpack.config.js`  
**Gravedad:** CRÍTICA

### Problema
Falta la configuración de `entry` para el punto de entrada `src/index.tsx`.

### Corrección
Añadir al inicio del objeto `module.exports`:

```javascript
module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.tsx',  // ✅ AÑADIR
	},
	// ... resto de la configuración
};
```

### Archivo completo corregido:

```javascript
/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
/**
 * External dependencies
 */
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.tsx',
	},
	output: {
		...defaultConfig.output,
		filename: '[name].js',
		path: __dirname + '/build',
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve?.alias,
			'@automattic/wp-feature-api': path.resolve(
				__dirname,
				'../client/src'
			),
		},
	},
};
```

---

## 🟡 PROBLEMA 3: Inconsistencia en Alias de Webpack

**Archivos afectados:**
- `/workspaces/wp-feature-api/webpack.config.js` (línea 32-33)
- `/workspaces/wp-feature-api/packages/client-features/webpack.config.js` (línea 28-29)

### Problema
El alias de `@automattic/wp-feature-api` apunta a rutas diferentes:

| Archivo | Alias |
|---------|-------|
| webpack.config.js raíz | `packages/client/src` |
| client-features/webpack.config.js | `../client` |
| demo-agent/webpack.config.js | `../client/src` |

### Corrección
Unificar todos los alias para que apunten a `../client/src`:

**En `packages/client-features/webpack.config.js`:**
```javascript
'@automattic/wp-feature-api': path.resolve(
	__dirname,
	'../client/src'  // Cambiar de '../client' a '../client/src'
),
```

---

## 🟡 PROBLEMA 4: Falta `output` en demo-agent webpack.config.js

**Archivo:** `/workspaces/wp-feature-agent/packages/demo-agent/webpack.config.js`  
**Gravedad:** MEDIA

### Problema
No se define explícitamente la configuración de `output`, lo que puede causar que Webpack use valores por defecto incorrectos.

### Corrección
Añadir configuración de output:

```javascript
output: {
	...defaultConfig.output,
	filename: '[name].js',
	path: __dirname + '/build',
},
```

---

## 🟢 PROBLEMA 5: Mejores Prácticas de Mantenimiento

### 5.1 Usar `import type` en client-features

**Archivo:** `packages/client-features/src/index.ts`

**Problema actual:**
```typescript
import { registerFeature } from '@automattic/wp-feature-api';
```

**Corrección:**
```typescript
import type { registerFeature } from '@automattic/wp-feature-api';
// O si es un valor:
import { registerFeature } from '@automattic/wp-feature-api';
```

### 5.2 Añadir configuración de externals en demo-agent

Para consistencia con otros paquetes, añadir:

```javascript
externals: {
	...defaultConfig.externals,
	'@automattic/wp-feature-api': 'wp.features',
	'react': 'wp.react',
	'react-dom': 'wp.reactDom',
},
```

---

## 📋 Checklist de Correcciones

- [ ] Corregir referencia en `tsconfig.json` raíz
- [ ] Añadir `entry` en `packages/demo-agent/webpack.config.js`
- [ ] Añadir `output` en `packages/demo-agent/webpack.config.js`
- [ ] Unificar alias de `@automattic/wp-feature-api`
- [ ] Verificar que todos los builds funcionan después de los cambios

---

## 🧪 Comandos para Verificar

```bash
# Limpiar builds anteriores
npm run clean

# Build individual
npm run build:client
npm run build:client-features
npm run build:demo-agent

# Build completo
npm run build
```

---

## 📚 Notas Adicionales

1. **Orden de compilación:** El cliente debe compilarse primero porque `client-features` y `demo-agent` dependen de él.

2. **TypeScript composite:** El uso de `composite: true` en los tsconfig de los paquetes requiere que las referencias estén correctamente configuradas.

3. **wp-scripts:** Todos los paquetes usan `wp-scripts` que internamente usa Webpack 5 con configuración predefinida.

