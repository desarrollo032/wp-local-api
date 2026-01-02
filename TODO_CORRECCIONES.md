# Plan de Corrección de TypeScript y Dependencies para wp-feature-api/client

## Problemas identificados:

1. **Conflicto de peerDependencies**: `react-autosize-textarea@7.1.0` (dependencia de `@wordpress/block-editor`) tiene peerDependencies de React 16
2. **Errores de TypeScript**:
   - `StoreDescriptor` en `@wordpress/data` >= 10.x tiene una estructura de tipos diferente
   - Funciones como `apiFetch`, `dispatch`, `select` pueden necesitar correcciones
   - Hay varios `@ts-expect-error` que indican problemas de tipos

## Solución Paso a Paso:

### Paso 1: Actualizar `packages/client/package.json`
- [ ] Añadir `overrides` para forzar React 18 en `react-autosize-textarea`
- [ ] Verificar versiones consistentes de `@wordpress/*`

### Paso 2: Corregir `packages/client/src/store/store.types.ts`
- [ ] Crear tipos correctos para `StoreDescriptor` compatible con WP 15+

### Paso 3: Corregir `packages/client/src/store/index.ts`
- [ ] Usar tipos correctos para `createReduxStore`
- [ ] Añadir type assertions necesarios

### Paso 4: Corregir `packages/client/src/api.ts`
- [ ] Actualizar uso de `StoreDescriptor`
- [ ] Corregir tipos de `dispatch` y `select`

### Paso 5: Corregir `packages/client/src/command-integration/use-featured-comments.tsx`
- [ ] Corregir tipos de `useRegistry`

### Paso 6: Corregir `packages/client/src/command-integration/input-modal.tsx`
- [ ] Eliminar `@ts-ignore` y usar tipos correctos

## Archivos a modificar:
1. `packages/client/package.json`
2. `packages/client/src/store/store.types.ts`
3. `packages/client/src/store/index.ts`
4. `packages/client/src/api.ts`
5. `packages/client/src/command-integration/use-featured-comments.tsx`
6. `packages/client/src/command-integration/input-modal.tsx`

