
# WordPress Feature API

> Sistema para exponer funcionalidades de WordPress de manera estandarizada para uso en LLMs y sistemas de IA.

[![Versión](https://img.shields.io/badge/Version-0.1.11-blue)](https://github.com/Automattic/wordpress-feature-api)
[![GPLv2 License](https://img.shields.io/badge/License-GPL%20v2-green)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blueviolet)](https://wordpress.org/)

---

## 📋 Descripción

La **WordPress Feature API** es un sistema para exponer la funcionalidad de WordPress de manera estandarizada y detectable, tanto para uso del lado del servidor como del cliente. Está diseñado para hacer que la funcionalidad de WordPress sea accesible para sistemas de IA (particularmente LLMs) y desarrolladores a través de un registro unificado de recursos y herramientas.

El sistema permite que WordPress ejecute funcionalidades por sí mismo tanto en el backend como en el frontend, proporcionando una API genérica para funcionalidad reutilizable.

---

## ✨ Características Principales

| Característica | Descripción |
|----------------|-------------|
| 🔄 **Registro Unificado** | Registro centralizado de funcionalidades accesible desde cliente y servidor |
| 📋 **Formato Estandarizado** | Utiliza la especificación MCP para el registro de funcionalidades |
| ♻️ **Reutiliza Funcionalidad Existente** | Las funcionalidades de WordPress existentes como endpoints REST son reutilizadas |
| 🔍 **Filtrable** | Las funcionalidades pueden filtrarse, categorizarse y buscarse |
| 🧩 **Extensible** | Fácil de registrar nuevas funcionalidades desde plugins y temas |
| 🤖 **Integración IA** | Proxy para OpenRouter con modelos gratuitos priorizados |
| 💬 **Chat Interface** | Interfaz de chat integrada para interacción con IA |

---

## 📁 Estructura del Proyecto

Este proyecto está estructurado como un monorepo usando espacios de trabajo npm:

```
wp-feature-api/
├── packages/
│   ├── client/              # SDK del cliente (@automattic/wp-feature-api)
│   ├── client-features/     # Funcionalidades estándar del cliente
│   └── demo-agent/          # Plugin AI Agent con chat y proxy
├── src/                     # JavaScript del plugin principal
├── includes/                # PHP del plugin principal
├── dist/                    # Plugins empaquetados (generados)
├── scripts/                 # Scripts de build y release
└── wp-feature-api.php      # Archivo principal del plugin
```

### Paquetes Disponibles

| Paquete | Descripción | Uso |
|---------|-------------|-----|
| **wp-feature-api** | Plugin principal de WordPress | Registro de funcionalidades, API REST |
| **wp-feature-api-agent** | Proxy AI + Chat Interface | OpenRouter integration, chat UI, MCP support |

---

## 🚀 Instalación

### Opción 1: Desde Releases (Recomendado)

1. Descargar los ZIPs desde [GitHub Releases](https://github.com/Automattic/wordpress-feature-api/releases/latest):
   - `wp-feature-api.zip` - Plugin principal
   - `wp-feature-api-agent.zip` - AI Agent con chat

2. Instalar en WordPress:
   - Ir a **Plugins → Añadir nuevo → Subir plugin**
   - Subir e instalar ambos ZIPs
   - Activar los plugins

### Opción 2: Desarrollo

```bash
# Clonar repositorio
git clone https://github.com/Automattic/wordpress-feature-api.git
cd wordpress-feature-api

# Instalar dependencias
npm install

# Build y generar ZIPs
npm run build

# Los ZIPs estarán en dist/
ls dist/*.zip
```

---

## ⚙️ Configuración

### 1. Plugin Principal (wp-feature-api)
- Se activa automáticamente
- No requiere configuración adicional
- Proporciona la API base para registrar funcionalidades

### 2. AI Agent (wp-feature-api-agent)

1. **Configurar OpenRouter:**
   - Ir a **Ajustes → WP Feature Agent Demo**
   - Seleccionar **"OpenRouter"** como proveedor
   - Introducir tu **OpenRouter API Key** ([obtener aquí](https://openrouter.ai/))
   - Guardar configuración

2. **Verificar funcionamiento:**
   - El chat aparecerá automáticamente en el admin de WordPress
   - Los modelos gratuitos se priorizan automáticamente
   - Compatible con WordPress MCP si está instalado

---

## 💬 Usar el Chat

1. **Acceder:** El chat aparece en la esquina inferior derecha del admin de WordPress
2. **Seleccionar modelo:** Elegir un modelo gratuito (marcado con "FREE")
3. **Chatear:** Hacer preguntas sobre WordPress, contenido, configuración, etc.
4. **Funcionalidades:** El AI puede ejecutar acciones si WordPress MCP está instalado

### Modelos Gratuitos Soportados

- `microsoft/phi-3-mini-128k-instruct:free`
- `microsoft/phi-3-medium-128k-instruct:free`
- `huggingfaceh4/zephyr-7b-beta:free`
- `openchat/openchat-7b:free`
- `gryphe/mythomist-7b:free`
- Y más...

---

## 🔧 Desarrollo

### Scripts Disponibles

```bash
# Build completo (compila + genera ZIPs)
npm run build

# Build individual
npm run build:client
npm run build:client-features
npm run build:demo-agent

# Solo generar ZIPs
npm run package

# Verificar plugins
npm run verify

# Limpiar builds
npm run clean
```

### Registrar Funcionalidades Personalizadas

```php
<?php
// En tu plugin
add_action( 'wp_feature_api_init', 'mi_plugin_registrar_funcionalidades' );

function mi_plugin_registrar_funcionalidades() {
    wp_register_feature( array(
        'id' => 'mi-plugin/ejemplo',
        'name' => 'Ejemplo de Funcionalidad',
        'description' => 'Una funcionalidad de ejemplo',
        'callback' => 'mi_plugin_callback',
        'type' => 'tool',
        'categories' => array( 'content', 'management' ),
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'titulo' => array(
                    'type' => 'string',
                    'description' => 'Título del contenido',
                ),
            ),
        ),
    ) );
}

function mi_plugin_callback( $input ) {
    // Lógica de la funcionalidad
    return array(
        'success' => true,
        'data' => 'Resultado de la funcionalidad'
    );
}
```

---

## 🔗 API Endpoints

### Funcionalidades
- `GET /wp/v2/features` - Listar funcionalidades
- `POST /wp/v2/features/{id}/execute` - Ejecutar funcionalidad

### AI Proxy
- `GET /wp/v2/ai-api-proxy/v1/healthcheck` - Estado del proxy
- `GET /wp/v2/ai-api-proxy/v1/models` - Modelos disponibles
- `POST /wp/v2/ai-api-proxy/v1/chat/completions` - Chat completions

### MCP Integration
- `GET /wp/v2/ai-api-proxy/v1/mcp/status` - Estado de MCP
- `GET /wp/v2/ai-api-proxy/v1/mcp/tools` - Herramientas MCP
- `POST /wp/v2/ai-api-proxy/v1/mcp/call` - Ejecutar herramienta MCP

---

## 🔍 Compatibilidad

### WordPress MCP Plugin
Compatible con [WordPress MCP](https://github.com/Automattic/wordpress-mcp) v0.2.5+:
- Detección automática del plugin
- Ejecución de herramientas MCP desde el chat
- Indicador de estado en la interfaz

### Requisitos
- **WordPress:** 6.0+
- **PHP:** 7.4+
- **Node.js:** 18+ (solo para desarrollo)

---

## 🐛 Debugging

### Si el chat no aparece:

1. **Verificar permisos:** El usuario debe tener capacidad `manage_options`
2. **Console del navegador:** Buscar errores JavaScript
3. **Logs de WordPress:** Activar `WP_DEBUG` para ver logs detallados
4. **Verificar assets:** Asegurar que los archivos build existen

### Logs útiles:
```javascript
// En console del navegador
"WP Feature API Agent: Chat container added to DOM"
"WP Feature API Agent: Initializing chat interface"
```

---

## 📚 Documentación

| Recurso | Descripción |
|---------|-------------|
| [Introducción](docs/1.introduccion.md) | Conceptos básicos |
| [Comenzando](docs/2.comenzando.md) | Guía de inicio |
| [Registrar Funcionalidades](docs/3.registrar-funcionalidades.md) | Crear funcionalidades |
| [Usar Funcionalidades](docs/4.usar-funcionalidades.md) | Consumir funcionalidades |
| [REST API](docs/5.puntos-finales-rest.md) | Endpoints disponibles |
| [Protocolo MCP](docs/10.protocolo-mcp.md) | Integración MCP |

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Por favor:

1. Fork el repositorio
2. Crear una rama para tu feature
3. Hacer commit de los cambios
4. Crear un Pull Request

Ver [CONTRIBUTING.md](CONTRIBUTING.md) para más detalles.

---

## 📄 Licencia

Este proyecto está licenciado bajo GPL v2 o posterior. Ver [LICENSE](LICENSE) para más detalles.

---

## 🔗 Enlaces

| Recurso | URL |
|---------|-----|
| **GitHub** | [wordpress-feature-api](https://github.com/Automattic/wordpress-feature-api) |
| **Releases** | [GitHub Releases](https://github.com/Automattic/wordpress-feature-api/releases) |
| **NPM** | [@automattic/wp-feature-api](https://www.npmjs.com/package/@automattic/wp-feature-api) |
| **WordPress MCP** | [wordpress-mcp](https://github.com/Automattic/wordpress-mcp) |
| **OpenRouter** | [openrouter.ai](https://openrouter.ai/) |

---

⭐ *Si este proyecto te es útil, ¡considera darle una estrella en GitHub!*