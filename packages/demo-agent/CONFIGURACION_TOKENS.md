# 🔐 Configuración de Tokens: IA y WordPress MCP

**Fecha**: 2026-01-03  
**Estado**: ✅ Configuración Agregada

---

## 📋 **Resumen: Dos Tokens Diferentes**

Tu plugin necesita **DOS tokens distintos** para funcionar completamente:

| Token           | Propósito                       | Requerido Para                                                   |
| --------------- | ------------------------------- | ---------------------------------------------------------------- |
| **Token de IA** | Generar respuestas inteligentes | ✅ **Obligatorio** - Sin esto el chat no funciona                 |
| **Token MCP**   | Ejecutar acciones en WordPress  | ⚠️ **Opcional** - Solo si quieres que el chat modifique WordPress |

---

## 🤖 **TOKEN 1: API de IA (OpenRouter/OpenAI)**

### **Propósito**
Permite que el chat genere respuestas inteligentes usando modelos de lenguaje.

### **Opciones Disponibles**

#### **Opción A: OpenRouter (Recomendado - Gratis)**

**Ventajas**:
- ✅ Modelos gratuitos disponibles
- ✅ No requiere tarjeta de crédito
- ✅ Acceso a múltiples proveedores (OpenAI, Anthropic, Meta, etc.)

**Cómo Obtener**:
1. Ve a https://openrouter.ai/
2. Crea una cuenta (gratis)
3. Ve a tu dashboard → "Keys"
4. Haz clic en "Create Key"
5. Copia la key (empieza con `sk-or-v1-...`)

**Configuración en WordPress**:
1. Ve a **Agent Demo** en el menú de WordPress
2. En "AI API Settings":
   - **AI Provider**: Selecciona "OpenRouter"
   - **OpenRouter API Key**: Pega tu key
3. Guarda cambios

#### **Opción B: OpenAI**

**Ventajas**:
- ✅ Modelos de alta calidad (GPT-4, GPT-3.5)
- ✅ Documentación extensa

**Desventajas**:
- ❌ Requiere créditos prepagados
- ❌ Necesita tarjeta de crédito

**Cómo Obtener**:
1. Ve a https://platform.openai.com/api-keys
2. Inicia sesión o crea una cuenta
3. Haz clic en "Create new secret key"
4. Copia la key (empieza con `sk-...`)

**Configuración en WordPress**:
1. Ve a **Agent Demo** en el menú de WordPress
2. En "AI API Settings":
   - **AI Provider**: Selecciona "OpenAI"
   - **OpenAI API Key**: Pega tu key
3. Guarda cambios

---

## 🔧 **TOKEN 2: WordPress MCP (Opcional)**

### **Propósito**
Permite que el chat ejecute acciones en WordPress como:
- Crear/editar/eliminar posts y páginas
- Gestionar usuarios
- Modificar configuraciones
- Subir archivos
- Y mucho más...

### **¿Cuándo lo Necesitas?**
- ✅ **SÍ** - Si quieres que el chat pueda modificar tu sitio WordPress
- ❌ **NO** - Si solo quieres un chat conversacional sin acciones

### **Requisitos Previos**

1. **Instalar el plugin wordpress-mcp**:
   ```bash
   # Opción 1: Desde GitHub
   cd wp-content/plugins
   git clone https://github.com/Automattic/wordpress-mcp.git
   
   # Opción 2: Descargar ZIP
   # Ve a https://github.com/Automattic/wordpress-mcp
   # Descarga el ZIP y súbelo a WordPress
   ```

2. **Activar el plugin**:
   - Ve a WordPress Admin → Plugins
   - Busca "WordPress MCP"
   - Haz clic en "Activar"

### **Cómo Obtener el Token MCP**

El token MCP se genera desde el plugin wordpress-mcp:

1. **Accede a la configuración de wordpress-mcp**:
   - Ve a WordPress Admin
   - Busca el menú de "WordPress MCP" o "MCP Settings"

2. **Genera un token**:
   - Busca la sección "Authentication" o "API Tokens"
   - Haz clic en "Generate New Token"
   - Copia el token generado

3. **Configura en Agent Demo**:
   - Ve a **Agent Demo** en el menú de WordPress
   - En "WordPress MCP Settings":
     - **MCP Authentication Token**: Pega tu token
   - Guarda cambios

### **Verificación**

Después de configurar el token MCP:

1. El indicador de estado MCP en el chat debería mostrar "Connected"
2. El chat tendrá acceso a herramientas de WordPress (41 tools aprox.)
3. Podrás pedirle al chat que ejecute acciones como:
   - "Crea un nuevo post titulado 'Hola Mundo'"
   - "Lista todos los usuarios del sitio"
   - "Actualiza la descripción del sitio"

---

## 🎯 **Configuración Completa: Paso a Paso**

### **Paso 1: Configurar Token de IA (Obligatorio)**

1. Obtén tu API key de OpenRouter u OpenAI (ver arriba)
2. Ve a WordPress Admin → **Agent Demo**
3. En "AI API Settings":
   - Selecciona tu proveedor (OpenRouter u OpenAI)
   - Pega tu API key
4. Haz clic en "Guardar cambios"

### **Paso 2: Configurar Token MCP (Opcional)**

1. Instala y activa el plugin wordpress-mcp
2. Genera un token desde wordpress-mcp
3. Ve a WordPress Admin → **Agent Demo**
4. En "WordPress MCP Settings":
   - Pega tu token MCP
5. Haz clic en "Guardar cambios"

### **Paso 3: Verificar Funcionamiento**

1. Recarga el dashboard de WordPress
2. El chat flotante debería aparecer
3. Prueba con un mensaje simple: "Hola"
4. Deberías recibir una respuesta

---

## 🔍 **Solución de Problemas**

### **Problema: "No AI models available"**

**Causa**: Token de IA no configurado o inválido

**Solución**:
1. Ve a Agent Demo → AI API Settings
2. Verifica que el proveedor esté seleccionado
3. Verifica que la API key sea correcta
4. Guarda cambios y recarga

### **Problema: MCP Status muestra "Inactive"**

**Causa**: Plugin wordpress-mcp no instalado o token no configurado

**Solución**:
1. Verifica que wordpress-mcp esté instalado y activado
2. Ve a Agent Demo → WordPress MCP Settings
3. Configura el token MCP
4. Guarda cambios y recarga

### **Problema: "WordPress MCP plugin is not installed"**

**Causa**: El plugin wordpress-mcp no está instalado

**Solución**:
1. Instala wordpress-mcp desde https://github.com/Automattic/wordpress-mcp
2. Activa el plugin
3. Recarga la página de configuración

### **Problema: El campo MCP Token está deshabilitado**

**Causa**: wordpress-mcp no está activo

**Solución**:
1. Ve a Plugins
2. Activa "WordPress MCP"
3. Recarga la página de configuración
4. El campo debería habilitarse

---

## 📊 **Comparación de Funcionalidades**

| Funcionalidad           | Solo Token IA | Token IA + MCP |
| ----------------------- | ------------- | -------------- |
| Chat conversacional     | ✅             | ✅              |
| Responder preguntas     | ✅             | ✅              |
| Generar contenido       | ✅             | ✅              |
| Crear posts             | ❌             | ✅              |
| Editar páginas          | ❌             | ✅              |
| Gestionar usuarios      | ❌             | ✅              |
| Modificar configuración | ❌             | ✅              |
| Subir archivos          | ❌             | ✅              |

---

## 🔐 **Seguridad**

### **Almacenamiento de Tokens**
- ✅ Los tokens se almacenan en la base de datos de WordPress
- ✅ Los campos son de tipo `password` (ocultos en la UI)
- ✅ Solo usuarios con capacidad `manage_options` pueden verlos
- ✅ Se sanitizan antes de guardarse

### **Recomendaciones**
1. **No compartas tus tokens** con nadie
2. **Regenera tokens** si sospechas que fueron comprometidos
3. **Usa tokens con permisos mínimos** necesarios
4. **Revisa los logs** periódicamente para detectar uso inusual

---

## ✅ **Checklist de Configuración**

### **Configuración Mínima (Solo Chat)**
- [ ] Token de IA obtenido (OpenRouter u OpenAI)
- [ ] Token de IA configurado en Agent Demo
- [ ] Proveedor seleccionado correctamente
- [ ] Chat responde a mensajes

### **Configuración Completa (Chat + Acciones)**
- [ ] Token de IA configurado ✅
- [ ] Plugin wordpress-mcp instalado
- [ ] Plugin wordpress-mcp activado
- [ ] Token MCP generado
- [ ] Token MCP configurado en Agent Demo
- [ ] MCP Status muestra "Connected"
- [ ] Chat puede ejecutar acciones en WordPress

---

## 📝 **Resumen**

1. **Token de IA**: **OBLIGATORIO** - Sin esto el chat no funciona
2. **Token MCP**: **OPCIONAL** - Solo si quieres que el chat modifique WordPress

**Configuración Mínima**: Solo Token de IA → Chat conversacional  
**Configuración Completa**: Token de IA + Token MCP → Chat con superpoderes

---

## 🆘 **Soporte**

Si tienes problemas:

1. Verifica que ambos tokens estén configurados correctamente
2. Revisa la consola del navegador (F12) para errores
3. Verifica los logs de PHP en `wp-content/debug.log`
4. Prueba el endpoint de healthcheck:
   ```
   https://tu-sitio.com/wp-json/wp/v2/ai-api-proxy/v1/healthcheck
   ```

**Respuesta esperada**: `{"status":"OK"}` si el token de IA está configurado.
