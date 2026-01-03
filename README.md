
# API de Funcionalidades de WordPress
# WordPress Feature API - Client Features (@wp-feature-api/client-features)

> ⚠️ **Este repositorio será reemplazado** por la [API de Funcionalidades](https://github.com/WordPress/abilities-api) a medida que se lancen versiones estables y se convierta en una API central de WordPress 6.9.
This package provides a library of standard, reusable client-side features for the WordPress Feature API.

[![Versión](https://img.shields.io/badge/Version-0.1.10-blue)](https://github.com/Automattic/wordpress-feature-api)
[![GPLv2 License](https://img.shields.io/badge/License-GPL%20v2-green)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-6.9+-blueviolet)](https://wordpress.org/)
- Contains the actual implementation logic for common client-side actions within the WordPress frontend (e.g., interacting with the block editor, navigation).
- Uses the main `@wp-feature-api/client` package (for the `Feature`, `registerFeature`, etc).
- The main plugin's initialization script (`src/index.js`) imports and calls `registerCoreFeatures` to make these standard features available when the plugin loads.

---
## Usage

## Descripción
This package is primarily intended for internal use within the `wp-feature-api` project. Third-party plugins typically **do not** need to depend on or interact with this package directly.

La **API de Funcionalidades de WordPress** es un sistema para exponer la funcionalidad de WordPress de una manera estandarizada y detectable, tanto para uso del lado del servidor como del cliente. Está diseñado para hacer que la funcionalidad de WordPress sea accesible para sistemas de IA (particularmente LLMs) y desarrolladores a través de un registro unificado de recursos y herramientas.
Instead, third-party plugins should depend on `@wp-feature-api/client` to register their *own* custom features. The features provided here are registered automatically by the main `wp-feature-api` plugin, and can be used as examples for how to implement your own custom features.

El sistema permite que WordPress ejecute funcionalidades por sí mismo tanto en el backend como en el frontend, proporcionando una primitiva API genérica para funcionalidad genérica.

---

## ✨ Características Principales

| Característica | Descripción |
|----------------|-------------|
| 🔄 **Registro Unificado** | Registro centralizado de funcionalidades accesible desde cliente y servidor |
| 📋 **Formato Estandarizado** | Utiliza la especificación MCP para el registro de funcionalidades |
| ♻️ **Reutiliza Funcionalidad Existente** | Las funcionalidades de WordPress existentes como endpoints REST son reutilizadas, haciéndolas más detectables y fáciles de usar por LLMs |
| 🔍 **Filtrable** | Las funcionalidades pueden filtrarse, categorizarse y buscarse para una coincidencia más precisa |
| 🧩 **Extensible** | Fácil de registrar nuevas funcionalidades desde plugins y temas |

---

## 📁 Estructura del Proyecto

Este proyecto está estructurado como un monorepo usando espacios de trabajo npm:

| Directorio/Paquete | Descripción |
|--------------------|-------------|
| `packages/client` | SDK del lado del cliente (`@automattic/wp-feature-api`). Proporciona la API (`registerFeature`, `executeFeature`, `Feature`) para interactuar con el registro de funcionalidades en el frontend |
| `packages/client-features` | Biblioteca que contiene implementaciones de funcionalidades estándar del lado del cliente (ej. inserción de bloques, navegación) |
| `demo/wp-feature-api-agent` | Plugin demo de WordPress que muestra cómo usar la API de Funcionalidades, incluyendo registro de funcionalidades e implementación como herramientas en un Agente de IA |
| `src/` | Punto de entrada principal de JavaScript (`src/index.js`) para el plugin principal de WordPress |
| `wp-feature-api.php` & `includes/` | Lógica PHP central para la API de Funcionalidades, incluyendo el registro, endpoints REST y definiciones de funcionalidades del servidor |

```
wp-feature-api/
├── packages/
│   ├── client/              # SDK del cliente
│   └── client-features/     # Funcionalidades del cliente
├── demo/
│   └── wp-feature-api-agent/ # Plugin demo
├── src/                      # JavaScript del plugin
├── includes/                 # PHP del plugin
└── wp-feature-api.php       # Archivo principal del plugin
```

---

## 🔗 MCP

Este sistema se basa fuertemente en la [Especificación MCP](https://spec.modelcontextprotocol.io/specification/2025-03-26/), aunque está adaptado a las necesidades de WordPress. Dado que WordPress es por naturaleza tanto el servidor como el cliente, la API de Funcionalidades está diseñada para usarse en ambos contextos y aprovechar la funcionalidad existente de WordPress.

Las funcionalidades pueden aparecer en un servidor MCP real consumido por un cliente MCP externo. La principal diferencia es que las funcionalidades son compatibles entre el servidor y el cliente, permitiendo que WordPress ejecute funcionalidades por sí mismo tanto en el backend como en el frontend.

> **Nota:** Esto no implementa el servidor MCP ni la capa de transporte. Sin embargo, el registro de funcionalidades puede ser usado por un servidor MCP como el plugin [wordpress-mcp](https://github.com/Automattic/wordpress-mcp) de Automattic.

Las funcionalidades no están limitadas al consumo por LLMs y pueden usarse en todo WordPress directamente como una primitiva API para funcionalidad genérica. De ahí el nombre más genérico de "API de Funcionalidades" en lugar de "API de MCP".

---

## 🔎 Filtrado

Un aspecto importante de la API de Funcionalidades es su capacidad para filtrar funcionalidades manualmente y automáticamente. Dado que el éxito de un agente LLM dependerá de la calidad de las herramientas que coincidan con la intención del usuario o el contexto actual dentro de WordPress, la API de Funcionalidades proporciona varios mecanismos para asegurar que las herramientas correctas estén disponibles en el momento correcto.

El filtrado se puede realizar mediante:

| Método | Descripción |
|--------|-------------|
| **Consultar propiedades de funcionalidades** | Filtrar por atributos específicos como tipo, nombre, etc. |
| **Búsqueda por palabras clave** | Buscar en nombre, descripción e ID |
| **Categorías** | Agrupar y filtrar por categorías temáticas |
| **Callback `is_eligible`** | Verificación programática de elegibilidad |
| **Coincidencia de contexto** | Encontrar funcionalidades que pueden cumplirse usando el contexto disponible |

---

## 🚀 Primeros Pasos

### Desarrollo

#### Instalación

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/desarrollo032/wp-feature-api.git
   cd wordpress-feature-api
   ```

2. Ejecutar `npm run setup` para instalar todas las dependencias (tanto PHP como JavaScript):
   ```bash
   npm run setup
   ```

#### Construcción

Ejecutar `npm run build` desde el directorio raíz. Este comando construirá todos los paquetes de JavaScript (`client`, `client-features`, `demo`) y el script principal del plugin (`src/index.js`):

```bash
npm run build
```

---

## 📦 Usar la API de Funcionalidades en tu Plugin via Composer

Los desarrolladores de plugins deben incluir la API de Funcionalidades de WordPress en sus plugins usando Composer. La API de Funcionalidades manejará automáticamente los conflictos de versiones cuando múltiples plugins la incluyan.

#### 1. Añadir como dependencia de Composer

Añadir manualmente a tu archivo `composer.json`:

```json
{
  "require": {
    "automattic/wp-feature-api": "^0.1.10"
  }
}
```

O usando el comando `composer` en la terminal:

```bash
composer require automattic/wp-feature-api:"^0.1.10"
```

#### 2. Cargar la API de Funcionalidades en tu plugin

Para cargar la API de Funcionalidades de forma segura:

```php
<?php
// Código de inicio del plugin
function mi_plugin_iniciar() {
    // Solo incluye el archivo principal del plugin - se registra automáticamente con el gestor de versiones
    require_once __DIR__ . '/vendor/automattic/wp-feature-api/wp-feature-api.php';

    // Registrar nuestras funcionalidades una vez que sepamos que la API está inicializada
    add_action( 'wp_feature_api_init', 'mi_plugin_registrar_funcionalidades' );
}

// Conectar a plugins_loaded - La API de Funcionalidades resolverá qué versión usar
add_action( 'plugins_loaded', 'mi_plugin_iniciar' );

/**
 * Registrar funcionalidades proporcionadas por este plugin
 */
function mi_plugin_registrar_funcionalidades() {
    // Registra tus funcionalidades aquí
    wp_register_feature( array(
        'id' => 'mi-plugin/ejemplo-funcionalidad',
        'name' => 'Ejemplo de Funcionalidad',
        'description' => 'Una funcionalidad de ejemplo de mi plugin',
        'callback' => 'mi_plugin_ejemplo_funcionalidad_callback',
        'type' => 'tool',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'parametro_ejemplo' => array(
                    'type' => 'string',
                    'description' => 'Un parámetro de ejemplo',
                ),
            ),
        ),
    ) );
}
```

---

## 💡 Ejecutar la Demo

1. Asegúrate de que las dependencias estén instaladas y el código esté construido (ver arriba).
2. Usa `@wordpress-env` (o tu entorno local de WordPress preferido como Studio) para iniciar WordPress:
   ```bash
   npm run wp-env start
   ```
3. Activa el plugin "WordPress Feature API".
4. El plugin demo (`wp-feature-api-agent`) debería cargarse automáticamente (controlado por la constante `WP_FEATURE_API_LOAD_DEMO` en `wp-feature-api.php`). Deberías ver un aviso de administrador confirmando esto.
5. Navega a la página "WP Feature Agent Demo" añadida bajo el menú de Configuración en el administrador de WordPress para configurar tu clave de API de OpenAI.
6. Actualiza y verás la interfaz de chat del Agente de IA.
7. Haz preguntas al Agente de IA sobre tu sitio de WordPress y funcionalidades. Tiene acceso tanto a funcionalidades del lado del servidor como del cliente.

---

## 🤝 Contribuir

¡Bienvenidas las contribuciones! Por favor, consulta nuestro [CONTRIBUTING.md](CONTRIBUTING.md) para detalles sobre cómo contribuir a este proyecto.

---

## 📚 Recursos Adicionales

| Recurso | Enlace |
|---------|--------|
| Repositorio en GitHub | [wordpress-feature-api](https://github.com/Automattic/wordpress-feature-api) |
| Paquete NPM | [@automattic/wp-feature-api](https://www.npmjs.com/package/@automattic/wp-feature-api) |
| Packagist | [automattic/wp-feature-api](https://packagist.org/packages/automattic/wp-feature-api) |
| Plugin MCP | [wordpress-mcp](https://github.com/Automattic/wordpress-mcp) |
| Documentación en Español | [/docs/README.md](/docs/README.md) |

---

⭐ *Si este proyecto te es útil, considera darle una estrella en GitHub!*
## Build

This package is built using `@wordpress/scripts`. Run `npm run build` from the monorepo root.