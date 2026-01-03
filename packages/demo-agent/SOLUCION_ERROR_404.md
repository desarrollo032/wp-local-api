# 🔧 SOLUCIÓN AL ERROR 404 - Configuración Completa

**Fecha**: 2026-01-03  
**Estado**: ✅ CORREGIDO

---

## 🔴 Problema Identificado

### Error en Consola:
```
POST https://pulsodigital.zeabur.app/wp-json/wp/v2/ai-api-proxy/v1/chat/completions 404 (Not Found)
```

### Causa Raíz:
1. **Ruta REST no registrada específicamente**: El frontend llamaba a `/chat/completions` pero solo existía un catch-all que no la capturaba correctamente
2. **Falta configuración de API Keys**: El plugin necesita API keys de OpenRouter u OpenAI para funcionar

---

## ✅ Correcciones Aplicadas

### 1. Ruta REST Específica Agregada

**Archivo**: `includes/class-wp-ai-api-proxy.php`

**Cambio Aplicado**:
```php
// Línea 145-156: Nueva ruta específica para /chat/completions
register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/chat/completions',
    array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'ai_api_chat_completions' ),
        'permission_callback' => array( $this, 'check_permissions' ),
    )
);
```

**Método Handler Agregado**:
```php
// Línea 307-320: Método que maneja las solicitudes de chat
public function ai_api_chat_completions( WP_REST_Request $request ) {
    // Create a new request with the api_path parameter set to 'chat/completions'
    $request->set_param( 'api_path', 'chat/completions' );
    
    // Delegate to the generic proxy method
    return $this->ai_api_proxy( $request );
}
```

**Beneficio**: La ruta `/wp/v2/ai-api-proxy/v1/chat/completions` ahora está registrada explícitamente y funcionará correctamente.

---

## 🔑 Configuración de API Keys (REQUERIDO)

### Paso 1: Acceder a la Página de Configuración

1. Ve al dashboard de WordPress
2. En el menú lateral, busca **"Agent Demo"** (icono de superhéroe)
3. Haz clic para abrir la página de configuración

### Paso 2: Seleccionar Proveedor de IA

Tienes dos opciones:

#### **Opción A: OpenRouter (Recomendado - Tiene modelos gratuitos)**

1. **Seleccionar Proveedor**: Elige "OpenRouter" en el dropdown
2. **Obtener API Key**:
   - Ve a https://openrouter.ai/
   - Crea una cuenta o inicia sesión
   - Ve a "Keys" en tu dashboard
   - Crea una nueva API key
   - Copia la key

3. **Configurar en WordPress**:
   - Pega la API key en el campo "OpenRouter API Key"
   - Deja "OpenRouter API Host" vacío (usa el valor por defecto)
   - Haz clic en "Guardar cambios"

**Ventajas de OpenRouter**:
- ✅ Tiene modelos gratuitos disponibles
- ✅ Acceso a múltiples modelos (OpenAI, Anthropic, Meta, etc.)
- ✅ No requiere tarjeta de crédito para modelos gratuitos

#### **Opción B: OpenAI**

1. **Seleccionar Proveedor**: Elige "OpenAI" en el dropdown
2. **Obtener API Key**:
   - Ve a https://platform.openai.com/api-keys
   - Inicia sesión o crea una cuenta
   - Crea una nueva API key
   - Copia la key

3. **Configurar en WordPress**:
   - Pega la API key en el campo "OpenAI API Key"
   - Haz clic en "Guardar cambios"

**Nota**: OpenAI requiere créditos prepagados.

---

## 🚀 Verificación de Funcionamiento

### 1. Verificar que la Ruta Está Registrada

Abre en tu navegador:
```
https://pulsodigital.zeabur.app/wp-json/wp/v2/ai-api-proxy/v1/
```

Deberías ver una lista de rutas disponibles incluyendo `/chat/completions`.

### 2. Probar el Chat

1. Recarga el dashboard de WordPress
2. El chat flotante debería aparecer en la esquina inferior derecha
3. Escribe un mensaje de prueba: "Hola"
4. Deberías recibir una respuesta del modelo de IA

### 3. Verificar en Consola del Navegador

Abre la consola del navegador (F12) y busca:

✅ **Mensajes Correctos**:
```
WP Feature API Agent: Initializing chat interface
WP Feature API Agent: Chat interface initialized successfully
Calling API Proxy with history: ...
Received response from API Proxy: ...
```

❌ **Errores a Evitar**:
```
404 (Not Found)  ← Ya no debería aparecer
No AI models available  ← Necesitas configurar API key
```

---

## 🔍 Solución de Problemas

### Problema: Sigue apareciendo 404

**Solución**:
1. Verifica que los cambios en PHP se hayan guardado
2. Ve a WordPress Admin → Configuración → Enlaces permanentes
3. Haz clic en "Guardar cambios" (esto regenera las rutas REST)
4. Recarga el dashboard

### Problema: "No AI models available"

**Solución**:
1. Verifica que hayas configurado la API key correctamente
2. Ve a "Agent Demo" en el menú de WordPress
3. Asegúrate de que el proveedor seleccionado tenga su API key configurada
4. Haz clic en "Guardar cambios"

### Problema: Error de autenticación de API

**Solución**:
1. Verifica que la API key sea válida
2. Para OpenRouter: Verifica que la key tenga permisos
3. Para OpenAI: Verifica que tengas créditos disponibles

### Problema: El chat no aparece

**Solución**:
1. Verifica que el plugin esté activado
2. Verifica que tu usuario tenga el rol de "Administrador"
3. Abre la consola del navegador (F12) y busca errores JavaScript
4. Limpia el cache del navegador (Ctrl+Shift+R)

---

## 📊 Archivos Modificados

| Archivo                     | Líneas  | Descripción                                 |
| --------------------------- | ------- | ------------------------------------------- |
| `class-wp-ai-api-proxy.php` | 145-156 | Ruta REST específica para /chat/completions |
| `class-wp-ai-api-proxy.php` | 307-320 | Método handler ai_api_chat_completions      |

---

## 🎯 Próximos Pasos

### Inmediatos:
1. ✅ Configurar API key (OpenRouter o OpenAI)
2. ✅ Probar el chat con un mensaje simple
3. ✅ Verificar que no haya errores en consola

### Opcionales:
1. **Configurar wordpress-mcp**: Si quieres que el chat pueda modificar contenido de WordPress
   - Instala el plugin: https://github.com/Automattic/wordpress-mcp
   - El chat detectará automáticamente las herramientas MCP disponibles

2. **Personalizar modelos**: En la configuración del chat, puedes seleccionar diferentes modelos de IA

---

## ✅ Checklist de Verificación

- [ ] Ruta `/chat/completions` registrada (código aplicado)
- [ ] API key configurada en "Agent Demo"
- [ ] Proveedor seleccionado (OpenRouter u OpenAI)
- [ ] Enlaces permanentes regenerados
- [ ] Chat aparece en el dashboard
- [ ] Mensaje de prueba enviado exitosamente
- [ ] Sin errores 404 en consola

---

## 📝 Notas Importantes

1. **Seguridad**: Las API keys se almacenan en la base de datos de WordPress de forma segura
2. **Permisos**: Solo usuarios con capacidad `manage_options` (administradores) pueden usar el chat
3. **Costos**: 
   - OpenRouter: Modelos gratuitos disponibles (sin costo)
   - OpenAI: Requiere créditos prepagados
4. **Cache**: Los modelos se cachean por 30 minutos para mejorar rendimiento

---

## 🆘 Soporte

Si sigues teniendo problemas:

1. **Verifica logs de PHP**: Busca errores en `wp-content/debug.log` (si `WP_DEBUG` está activo)
2. **Verifica consola del navegador**: Busca errores JavaScript
3. **Prueba el endpoint directamente**: 
   ```bash
   curl -X GET https://pulsodigital.zeabur.app/wp-json/wp/v2/ai-api-proxy/v1/healthcheck
   ```

**Estado Esperado**: `{"status":"OK"}` si la API key está configurada correctamente.
