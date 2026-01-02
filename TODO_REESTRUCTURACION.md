# TODO - Reestructuración del Proyecto

## Paso 1: Actualizar package.json raíz
- [ ] Centralizar React, ReactDOM, @wordpress/* en raíz
- [ ] Configurar workspaces correctamente
- [ ] Simplificar scripts de build

## Paso 2: Simplificar packages/client/package.json
- [ ] Eliminar dependencias duplicadas
- [ ] Usar peerDependencies solo
- [ ] Mantener solo scripts esenciales

## Paso 3: Simplificar packages/client-features/package.json
- [ ] Eliminar dependencias duplicadas
- [ ] Usar peerDependencies solo
- [ ] Mantener solo scripts esenciales

## Paso 4: Reorganizar estructura de carpetas
- [ ] Mover demo/wp-feature-api-agent → packages/demo-agent
- [ ] Eliminar demo/ original
- [ ] Eliminar release/ (redundante)
- [ ] Eliminar src/ (vacío/redundante)

## Paso 5: Actualizar configuraciones
- [ ] Actualizar webpack.config.js raíz
- [ ] Actualizar webpack.config.js de packages
- [ ] Actualizar tsconfig.json de cada package

## Paso 6: Verificar imports en código fuente
- [ ] Verificar imports en packages/client/src/
- [ ] Verificar imports en packages/client-features/src/
- [ ] Verificar imports en packages/demo-agent/src/

## Paso 7: Test de build
- [ ] Ejecutar npm install
- [ ] Ejecutar npm run build
- [ ] Verificar que no haya errores

## Estado: EN PROGRESO

