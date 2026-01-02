# Plan de Integración: wordpress-mcp con el Chat de IA

## Objetivo
Integrar el plugin `wordpress-mcp` (https://github.com/desarrollo032/wordpress-mcp) en el chat de IA para permitir:
- **Detección automática** si el plugin está instalado y activo
- **Ejecución de acciones** a través de MCP cuando está disponible
- **Modo de solo sugerencia** cuando NO está activo

---

## Información Recopilada

### Archivos Relevantes del Proyecto
| Archivo | Propósito |
|---------|-----------|
| `demo/wp-feature-api-agent/src/context/ConversationProvider.tsx` | Provider del contexto del chat |
| `demo/wp-feature-api-agent/src/agent/wp-feature-tool-provider.ts` | Proveedor de herramientas desde WP Feature API |
| `demo/wp-feature-api-agent/src/agent/tool-executor.ts` | Ejecutor central de herramientas |
| `demo/wp-feature-api-agent/src/agent/orchestrator.ts` | Orquestador del agente IA |
| `demo/wp-feature-api-agent/src/agent/system-prompt.ts` | Prompt del sistema |
| `demo/wp-feature-api-agent/includes/class-wp-ai-api-proxy.php` | Proxy REST para APIs de IA |

### Arquitectura Actual del Chat
1. **ConversationProvider** inicializa el tool executor y obtiene herramientas del WP Feature API
2. **WP Feature Tool Provider** convierte features en herramientas para el agente
3. **Tool Executor** ejecuta las herramientas cuando el LLM las solicita
4. **Agent Orchestrator** procesa queries y maneja tool calls

---

## Plan de Implementación

### Fase 1: Backend - Detección de MCP
**Archivos a modificar:**
1. `demo/wp-feature-api-agent/includes/class-wp-ai-api-proxy.php`
2. `demo/wp-feature-api-agent/includes/class-wp-feature-register.php`

**Cambios:**
- Añadir endpoint REST `/wp/v2/ai-api-proxy/v1/mcp/status` para detectar si MCP está activo
- Añadir endpoint REST `/wp/v2/ai-api-proxy/v1/mcp/tools` para obtener herramientas MCP
- Añadir función helper para verificar si el plugin está activo
- Registrar nuevas features para información del sitio MCP

### Fase 2: Frontend - Integración MCP
**Archivos a modificar:**
1. `demo/wp-feature-api-agent/src/agent/mcp-tool-provider.ts` (NUEVO)
2. `demo/wp-feature-api-agent/src/context/ConversationProvider.tsx`
3. `demo/wp-feature-api-agent/src/agent/system-prompt.ts`
4. `demo/wp-feature-api-agent/src/hooks/useConversation.ts`

**Cambios:**
- Crear `McpToolProvider` que consulta las herramientas MCP del servidor
- Detectar estado MCP y pasarlo al contexto
- Modificar el provider principal para combinar WP Features + MCP Tools
- Actualizar el system prompt para informar al agente sobre capacidades MCP
- Añadir indicador visual en el chat mostrando estado MCP

### Fase 3: Componente UI - Indicador MCP
**Archivos a modificar:**
1. `demo/wp-feature-api-agent/src/components/McpStatusIndicator.tsx` (NUEVO)
2. `demo/wp-feature-api-agent/src/components/ChatApp.tsx`
3. `demo/wp-feature-api-agent/src/style.scss`

**Cambios:**
- Crear componente visual para mostrar estado MCP (activo/inactivo)
- Mostrar indicador verde cuando MCP está activo
- Mostrar indicador gris cuando está inactivo
- Añadir tooltip con información de MCP

### Fase 4: Actualización de Documentación
**Archivos a modificar:**
1. `docs/10.protocolo-mcp.md`
2. `demo/wp-feature-api-agent/README.md`

**Cambios:**
- Actualizar documentación sobre integración con desarrollo032/wordpress-mcp
- Añadir guía de configuración
- Documentar las capacidades del sistema

---

## Archivos Nuevos a Crear

| Archivo | Descripción |
|---------|-------------|
| `demo/wp-feature-api-agent/src/agent/mcp-tool-provider.ts` | Provider para herramientas MCP |
| `demo/wp-feature-api-agent/src/components/McpStatusIndicator.tsx` | Componente visual de estado MCP |
| `demo/wp-feature-api-agent/includes/class-wp-mcp-status.php` | Backend para detección MCP |

---

## Cambios en Archivos Existentes

### 1. class-wp-ai-api-proxy.php
```php
// Añadir después de register_rest_routes():
register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/mcp/status',
    array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'mcp_status_check' ),
        'permission_callback' => array( $this, 'check_permissions' ),
    )
);

register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/mcp/tools',
    array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array( $this, 'mcp_tools_list' ),
        'permission_callback' => array( $this, 'check_permissions' ),
    )
);

// Nuevos métodos:
public function mcp_status_check( WP_REST_Request $request ) { ... }
public function mcp_tools_list( WP_REST_Request $request ) { ... }
```

### 2. ConversationProvider.tsx
```typescript
// Añadir estado MCP:
const [ mcpStatus, setMcpStatus ] = useState<{
    isActive: boolean;
    toolsCount: number;
    version?: string;
}>({ isActive: false, toolsCount: 0 });

// Añadir verificación MCP en useEffect:
useEffect( () => {
    // ... código existente ...
    
    // Verificar estado MCP
    (async () => {
        try {
            const resp = await apiFetch({ 
                path: '/wp/v2/ai-api-proxy/v1/mcp/status' 
            });
            setMcpStatus(resp);
        } catch (e) {
            setMcpStatus({ isActive: false, toolsCount: 0 });
        }
    })();
}, [] );
```

### 3. system-prompt.ts
```typescript
// Añadir información sobre MCP al prompt:
const mcpSection = mcpStatus.isActive 
    ? `\n\n## MCP Tools Available (${mcpStatus.toolsCount} tools)
You can execute automatic actions on WordPress using MCP tools.`
    : '\n\n## MCP Status
MCP is NOT active. You can suggest actions but cannot execute them automatically.';

// Añadir al final del prompt existente
```

---

## Dependencias a Instalar
Ninguna adicional. Usaremos las APIs existentes de WordPress.

---

## Pasos de Implementación

### Paso 1: Backend PHP
- [ ] Añadir endpoints REST para detección MCP
- [ ] Añadir helper para verificar estado del plugin
- [ ] Registrar features relacionadas con MCP

### Paso 2: Frontend TypeScript - Proveedor MCP
- [ ] Crear `McpToolProvider`
- [ ] Implementar detección automática
- [ ] Integrar con `ConversationProvider`

### Paso 3: Frontend TypeScript - UI
- [ ] Crear componente `McpStatusIndicator`
- [ ] Integrar en `ChatApp`
- [ ] Añadir estilos

### Paso 4: System Prompt
- [ ] Actualizar prompt para informar sobre capacidades MCP
- [ ] Añadir lógica condicional según estado

### Paso 5: Documentación
- [ ] Actualizar docs/10.protocolo-mcp.md
- [ ] Actualizar README del demo

### Paso 6: Construcción y Pruebas
- [ ] Ejecutar `npm run build`
- [ ] Verificar que no hay errores de Typescript
- [ ] Probar en entorno WordPress

---

## Notas Técnicas

### Detección del Plugin wordpress-mcp
El plugin desarrollo032/wordpress-mcp debería exponer un endpoint o filtro que permita:
1. Verificar si está activo
2. Obtener lista de herramientas disponibles
3. Ejecutar herramientas remotamente

### Formato MCP esperado
```json
{
  "tools": [
    {
      "name": "tool-name",
      "description": "Description",
      "input_schema": { ... }
    }
  ]
}
```

### Fallback
Si wordpress-mcp no está disponible, el sistema funcionará igual pero:
- El indicador mostrará estado "inactivo"
- El system prompt informará al agente que no puede ejecutar acciones
- Las herramientas del WP Feature API seguirán disponibles

---

## Referencias
- Plugin wordpress-mcp: https://github.com/desarrollo032/wordpress-mcp
- Documentación MCP existente: docs/10.protocolo-mcp.md
- Repositorio Automattic: https://github.com/Automattic/wordpress-mcp

