# TODO - Integración wordpress-mcp con Chat de IA

## Estado General
- **Inicio:** Completado
- **Objetivo:** Integrar detección y uso del plugin wordpress-mcp

---

## Fase 1: Backend PHP - Detección de MCP ✅ COMPLETADO

### 1.1 Añadir endpoints REST para detección MCP
- [x] `demo/wp-feature-api-agent/includes/class-wp-ai-api-proxy.php`
  - [x] Añadir endpoint `/mcp/status` para verificar si MCP está activo
  - [x] Añadir endpoint `/mcp/tools` para obtener herramientas disponibles
  - [x] Implementar método `mcp_status_check()`
  - [x] Implementar método `mcp_tools_list()`

---

## Fase 2: Frontend TypeScript - Proveedor MCP ✅ COMPLETADO

### 2.1 Crear proveedor MCP
- [x] `demo/wp-feature-api-agent/src/agent/mcp-tool-provider.ts`
  - [x] Crear interfaz `McpToolProvider`
  - [x] Implementar método `getTools()` que consulta endpoint REST
  - [x] Manejar errores cuando MCP no está disponible

### 2.2 Integrar con ConversationProvider
- [x] `demo/wp-feature-api-agent/src/context/ConversationProvider.tsx`
  - [x] Añadir estado `mcpStatus`
  - [x] Añadir verificación MCP en useEffect
  - [x] Registrar `McpToolProvider` junto con `WpFeatureToolProvider`

---

## Fase 3: Frontend TypeScript - UI ✅ COMPLETADO

### 3.1 Crear indicador visual
- [x] `demo/wp-feature-api-agent/src/components/McpStatusIndicator.tsx`
  - [x] Crear componente con icono de estado
  - [x] Añadir tooltip con información
  - [x] Implementar colores según estado (verde/gris)

### 3.2 Integrar en ChatApp
- [x] `demo/wp-feature-api-agent/src/components/ChatApp.tsx`
  - [x] Importar y añadir `McpStatusIndicator`
  - [x] Posicionar en el header del chat

### 3.3 Añadir estilos
- [x] `demo/wp-feature-api-agent/src/style.scss`
  - [x] Estilos para el indicador MCP
  - [x] Animaciones y transiciones

---

## Fase 4: System Prompt ✅ COMPLETADO

### 4.1 Actualizar prompt del sistema
- [x] `demo/wp-feature-api-agent/src/agent/system-prompt.ts`
  - [x] Añadir sección sobre capacidades MCP
  - [x] Incluir lógica condicional según estado MCP
  - [x] Documentar herramientas disponibles cuando MCP está activo

---

## Fase 5: Documentación 📋 PENDIENTE

### 5.1 Actualizar documentación existente
- [ ] `docs/10.protocolo-mcp.md`
  - [ ] Añadir referencia a integración con chat
  - [ ] Documentar endpoint de detección
  - [ ] Añadir guía de configuración

### 5.2 Actualizar README del demo
- [ ] `demo/wp-feature-api-agent/README.md`
  - [ ] Documentar funcionalidad MCP
  - [ ] Añadir screenshots del indicador
  - [ ] Incluir sección de troubleshooting

---

## Fase 6: Construcción y Pruebas 📋 PENDIENTE

### 6.1 Construcción
- [ ] Ejecutar `npm run build`
- [ ] Verificar que no hay errores de TypeScript
- [ ] Verificar que el build se genera correctamente

### 6.2 Pruebas
- [ ] Probar en entorno WordPress local
- [ ] Verificar detección de MCP cuando está activo
- [ ] Verificar comportamiento cuando MCP NO está activo
- [ ] Probar ejecución de herramientas MCP

---

## Archivos Creados

| Archivo | Estado |
|---------|--------|
| `PLAN_MCP_INTEGRATION.md` | ✅ Completado |
| `demo/wp-feature-api-agent/src/agent/mcp-tool-provider.ts` | ✅ Completado |
| `demo/wp-feature-api-agent/src/components/McpStatusIndicator.tsx` | ✅ Completado |

## Archivos Modificados

| Archivo | Estado |
|---------|--------|
| `demo/wp-feature-api-agent/includes/class-wp-ai-api-proxy.php` | ✅ Completado |
| `demo/wp-feature-api-agent/src/context/ConversationProvider.tsx` | ✅ Completado |
| `demo/wp-feature-api-agent/src/agent/system-prompt.ts` | ✅ Completado |
| `demo/wp-feature-api-agent/src/components/ChatApp.tsx` | ✅ Completado |
| `demo/wp-feature-api-agent/src/style.scss` | ✅ Completado |

---

## Próximos Pasos

1. **Construir el proyecto** - Verificar que no hay errores
2. **Probar en entorno local** - Verificar funcionamiento
3. **Actualizar documentación** - Completar docs
4. **Crear release** - Generar nueva versión del plugin

