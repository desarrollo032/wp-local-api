# Plan de Corrección de TypeScript y Dependencies para wp-feature-api/client

## Problemas identificados:

1. **Conflicto de peerDependencies**: `react-autosize-textarea@7.1.0` (dependencia de `@wordpress/block-editor`) tiene peerDependencies de React 16
2. **Errores de TypeScript**:
   - `StoreDescriptor` en `@wordpress/data` >= 10.x tiene una estructura de tipos diferente
   - Funciones como `apiFetch`, `dispatch`, `select` pueden necesitar correcciones
   - Hay varios `@ts-expect-error` que indican problemas de tipos

## Solución Paso a Paso:

### Paso 1: Actualizar `packages/client/package.json` ✅
- [x] Añadir `overrides` para forzar React 18 en `react-autosize-textarea`
- [x] Verificar versiones consistentes de `@wordpress/*`

### Paso 2: Corregir `packages/client/src/store/store.types.ts` ✅
- [x] Crear tipos correctos para `StoreDescriptor` compatible con WP 15+

### Paso 3: Corregir `packages/client/src/store/index.ts` ✅
- [x] Usar tipos correctos para `createReduxStore`
- [x] Añadir type assertions necesarios

### Paso 4: Corregir `packages/client/src/store/constants.ts` ✅
- [x] Eliminar importación problemática de `@wordpress/data/types`

### Paso 5: Corregir `packages/client/src/api.ts` ✅
- [x] Actualizar uso de `StoreDescriptor`
- [x] Corregir tipos de `dispatch` y `select`

### Paso 6: Corregir `packages/client/src/command-integration/use-featured-comments.tsx` ✅
- [x] Corregir tipos de `useRegistry`

### Paso 7: Corregir `packages/client/src/command-integration/input-modal.tsx` ✅
- [x] Eliminar `@ts-ignore` y usar tipos correctos

### Paso 8: Corregir `packages/client/src/types.ts` ✅
- [x] Eliminar importación de `StoreDescriptor`

## Archivos modificados:
1. `packages/client/package.json`
2. `packages/client/src/store/store.types.ts`
3. `packages/client/src/store/index.ts`
4. `packages/client/src/store/constants.ts`
5. `packages/client/src/api.ts`
6. `packages/client/src/command-integration/use-featured-comments.tsx`
7. `packages/client/src/command-integration/input-modal.tsx`
8. `packages/client/src/types.ts`

## Próximos pasos para el usuario:
1. Ejecutar `npm install` en `packages/client`
2. Ejecutar `npm run build` para verificar que no hay errores de compilación
3. Si hay errores residuales, pueden necesitar más ajustes de tipos

