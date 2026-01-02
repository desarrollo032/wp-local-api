# WordPress Feature API Demo

Este plugin de demostración muestra cómo usar la **WordPress Feature API**, incluyendo el registro de funcionalidades y la implementación de WP Features como herramientas en un Agente de IA basado en TypeScript.

---

## ✨ Características

- 🤖 **Agente de IA**: Implementación de un agente inteligente conversacional
- 🔧 **Registro de Features**: Aprende a registrar funcionalidades personalizadas
- 🔌 **Integración TypeScript**: Tipado completo y moderno para mayor seguridad
- 🖥️ **Features del Servidor**: Acceso a funcionalidades del lado del servidor
- 🌐 **Features del Cliente**: Acceso a funcionalidades del lado del cliente
- 📝 **Consultas Dinámicas**: Haz preguntas sobre tu sitio WordPress en tiempo real
- 🎯 **Herramientas WP**: Usa las funcionalidades de WordPress como herramientas de IA
- 🔑 **Multiples Proveedores**: Soporte para OpenAI y OpenRouter
- 💬 **Interfaz de Chat**: Chat interactivo con el agente de IA

---

## 🚀 Uso

1. **Esta demostración está incluída** en el repositorio principal `wp-feature-api`. Sigue las instrucciones de instalación en el [README principal](../../README.md) y ejecuta los siguientes comandos desde la raíz del repositorio `wp-feature-api`:
   ```bash
   npm install
   npm run build
   ```

2. **Activa el plugin**: Asegúrate de que el plugin principal "WordPress Feature API" esté activado en tu entorno WordPress.

3. **Carga automática**: La demostración se cargará automáticamente (controlado por `WP_FEATURE_API_LOAD_DEMO` en el archivo principal del plugin).

4. **Configura tu API Key**: Navega a **"Ajustes" → "WP Feature Agent Demo"** en el panel de administración de WordPress para configurar tu clave de API de OpenAI u OpenRouter.

5. **Interactúa con el Agente**: Refresca la página y accede a la interfaz de chat del Agente de IA. ¡Haz preguntas sobre tu sitio WordPress y sus funcionalidades!

---

## 🔑 Configuración de API

El agente soporta los siguientes proveedores de IA:

### OpenAI
- Requiere una clave de API de OpenAI
- Compatible con modelos como GPT-4, GPT-3.5-Turbo, etc.

### OpenRouter
- Alternativa a OpenAI con múltiples modelos disponibles
- Requiere una clave de API de OpenRouter
- Ofrece acceso a modelos de varios proveedores

---

## 🎯 ¿Qué puede hacer el Agente?

El agente de IA puede:

- 📊 **Consultar información del sitio**: Obtener datos sobre configuraciones, usuarios, publicaciones, páginas y más
- 🔍 **Explorar funcionalidades**: Descubrir qué features están disponibles en tu instalación
- 🛠️ **Ejecutar operaciones**: Realizar operaciones en WordPress a través de las features registradas
- 💡 **Responder preguntas**: Proporcionar información sobre el estado de tu sitio
- 📦 **Gestionar contenido**: Ayudarte a crear y organizar contenido
- 🎨 **Acceder a patrones y bloques**: Consultar y utilizar patrones de Gutenberg y bloques disponibles
- 🌐 **Navegación del sitio**: Obtener información sobre la estructura de navegación

---

## 📋 Requisitos

- WordPress instalado y configurado
- Plugin "WordPress Feature API" activado
- Clave de API de OpenAI u OpenRouter
- Node.js y npm para la compilación

---

## 📖 Más Información

Consulta la [documentación completa](../../docs/) para aprender más sobre la WordPress Feature API y sus capacidades.
