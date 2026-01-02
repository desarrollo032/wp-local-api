This repository will be deprecated as the [Abilities API](https://github.com/WordPress/abilities-api) AI Building Block for WordPress continues releasing stable versions, and becomes a core API in WordPress 6.9.

We encourage all users to migrate to Abilities API. Future work, including new features and fixes, will happen there. This repository will remain available in archived form for historical reference.

# WordPress Feature API

El repositorio original será archivado; esta copia contiene la documentación traducida.

El WordPress Feature API es un sistema para exponer la funcionalidad de WordPress de forma estandarizada y descubrible, tanto para uso en el servidor como en el cliente. Está diseñado para facilitar el acceso a la funcionalidad de WordPress desde sistemas de IA (en especial LLMs) y desarrolladores mediante un registro unificado de recursos y herramientas.

## Características clave

- **Registro unificado:** Registro central de features accesible desde cliente y servidor.
- **Formato estandarizado:** utiliza la especificación MCP para el registro.
- **Reutiliza funcionalidad existente:** endpoints REST y otras funciones de WordPress se exponen como features, lo que mejora su descubribilidad por LLMs.
- **Filtrable:** Las features se pueden filtrar, categorizar y buscar para ofrecer coincidencias más precisas.
- **Extensible:** Fácil registrar nuevas features desde plugins y temas.

## Estructura del proyecto

Este proyecto está organizado como un monorepo usando `npm workspaces`:

- **`packages/client`**: SDK cliente principal (`@automattic/wp-feature-api`). Proporciona la API (`registerFeature`, `executeFeature`, tipo `Feature`) para interactuar con el registro de features en el frontend y gestiona el store de datos.
- **`packages/client-features`**: Biblioteca con implementaciones estándar de features cliente (ej.: inserción de bloques, navegación). Depende del SDK cliente y lo usa el plugin principal para registrar features core.
- **`demo/wp-feature-api-agent`**: Plugin demo de WordPress que muestra cómo usar la Feature API, registrar features y usar un agente AI en TypeScript.
- **`src/`**: Entrada JavaScript principal (`src/index.js`) del plugin core. Inicializa el SDK cliente y registra las features cliente core.
- **`wp-feature-api.php`** y **`includes/`**: Lógica PHP core de la Feature API, incluyendo el registry, endpoints REST y definiciones server-side. Esto se exporta también como paquete Composer.

## MCP

La Feature API está fuertemente inspirada en la [especificación MCP](https://spec.modelcontextprotocol.io/specification/2025-03-26/), pero adaptada a las necesidades de WordPress. Aunque WordPress actúa como servidor y cliente, la Feature API permite que las features sean consumidas en ambos contextos.

Nota: esto no implementa un servidor MCP ni la capa de transporte. No obstante, el registro de features puede ser expuesto por un servidor MCP (por ejemplo, el plugin `wordpress-mcp`).

Las features no están limitadas al consumo por LLMs; pueden usarse en cualquier parte de WordPress como una API primitiva para funcionalidad genérica.

## Filtrado

Un aspecto importante es la habilidad de filtrar features manual o automáticamente. Para agentes LLM, la calidad de las herramientas que coincidan con la intención del usuario es crítica; la Feature API ofrece mecanismos para seleccionar las herramientas adecuadas.

Filtrado por:

- Propiedades de la feature.
- Búsqueda por palabras clave (nombre, descripción, ID).
- Categorías.
- Callback booleano `is_eligible`.
- Coincidencia de contexto (cuando se dispone de contexto previo y se buscan features que lo aprovechen).

## Comenzando

### Desarrollo

#### Instalación

1. Clona el repositorio.
2. Ejecuta `npm run setup` para instalar dependencias (PHP y JavaScript).

#### Construir

Ejecuta `npm run build` en la raíz. Este comando compilará los paquetes JavaScript (`client`, `client-features`, `demo`) y el script principal (`src/index.js`).

### Usar WordPress Feature API en tu plugin vía Composer

Los desarrolladores de plugins deberían incluir la Feature API usando Composer. La API gestionará conflictos de versiones cuando varios plugins la incluyan.

#### 1. Añadir dependencia Composer

Manualmente en `composer.json`:

```json
{
  "require": {
    "automattic/wp-feature-api": "^0.1.8" // Make sure to use the latest version
  }
}
```

Con el comando `composer`:

```bash
composer require automattic/wp-feature-api:"^0.1.8"
```

#### 2. Cargar la Feature API en tu plugin

Para cargar la Feature API de forma segura:

```php
// Plugin bootstrap code
function my_plugin_init() {
    // Just include the main plugin file - it automatically registers itself with the version manager
    require_once __DIR__ . '/vendor/automattic/wp-feature-api/wp-feature-api.php';

    // Register our features once we know API is initialized
    add_action( 'wp_feature_api_init', 'my_plugin_register_features' );
}

// hook into plugins_loaded - the Feature API will resolve which version to use
add_action( 'plugins_loaded', 'my_plugin_init' );

/**
 * Register features provided by this plugin
 */
function my_plugin_register_features() {
    // Register your features here
    wp_register_feature( array(
        'id' = 'my-plugin/example-feature',
        'name' => 'Example Feature',
        'description' => 'An example feature from my plugin',
        'callback' => 'my_plugin_example_feature_callback',
        'type' => 'tool',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'example_param' => array(
                    'type' => 'string',
                    'description' => 'An example parameter',
                ),
            ),
        ),
    ) );
}
```

### Ejecutar el demo

1. Asegúrate de que las dependencias estén instaladas y el código compilado.
2. Usa `@wordpress/env` (u otro entorno local) para arrancar WordPress: `npm run wp-env start`.
3. Activa el plugin "WordPress Feature API".
4. El plugin demo (`wp-feature-api-agent`) se cargará automáticamente si `WP_FEATURE_API_LOAD_DEMO` está definido.
5. Ve a la página "WP Feature Agent Demo" en Ajustes para configurar la clave OpenAI.
6. Recarga y prueba la interfaz de chat del agente AI.

## Contribuir

¡Contribuciones bienvenidas! Consulta `CONTRIBUTING.md` para detalles.
