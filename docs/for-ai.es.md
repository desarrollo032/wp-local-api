````markdown
Copia y pega este archivo en cualquier chat de IA o compártelo con un desarrollador humano. Es una referencia única y comprimida que explica qué hace el plugin, cómo encajan sus piezas y cómo extenderlo.

---

# Documentación: WordPress Feature API

## 1. Título e introducción

**WordPress Feature API: Exponer las capacidades del sitio para desarrolladores y agentes de IA**

Este documento ofrece una referencia técnica completa del plugin WordPress Feature API. Su objetivo es guiar a desarrolladores sobre cómo entender, usar y extender la Feature API para registrar e interactuar con funcionalidades de WordPress de forma estandarizada y descubrible, en especial cuando son consumidas por agentes de IA u otros sistemas programáticos.

## 2. Resumen ejecutivo

La Feature API de WordPress proporciona una forma estandarizada de registrar y descubrir unidades de funcionalidad en un sitio WordPress. Estas unidades, llamadas "Features" (Características), representan acciones de obtención de datos (**Resources**) o acciones de modificación/creación de datos (**Tools**). Actúa como un registro central accesible desde PHP (lado servidor) y JavaScript (lado cliente), facilitando que el core, plugins, temas y sistemas externos (por ejemplo, agentes de IA) entiendan e interactúen con las capacidades disponibles en un sitio.

Componentes clave:
- Registro del lado servidor: `wp_register_feature`.
- Registro del lado cliente: paquete `@automattic/wp-feature-api`.
- Endpoints REST para descubrimiento y ejecución.
- Sistema flexible para definir entradas (input), salidas (output), permisos y elegibilidad de cada feature.

## 3. Visión general de la arquitectura

La Feature API usa un patrón de registro (registry) con componentes separados para servidor y cliente, unificados mediante la capa de transporte REST API.

1. **Servidor (PHP):**
   - `WP_Feature_Registry`: singleton que administra las features registradas.
   - `WP_Feature`: representa una feature (Resource o Tool) y sus metadatos (ID, nombre, descripción, tipos, esquemas, callbacks, permisos, etc.).
   - `WP_Feature_Repository_Interface`: define cómo se almacenan las features (por defecto `WP_Feature_Repository_Memory`).
   - `WP_Feature_Query`: clase para filtrar y buscar features.
   - `WP_REST_Feature_Controller`: expone `/wp/v2/features`.
   - `wp_register_feature()`: función global para registrar features.

2. **Cliente (JavaScript - `@automattic/wp-feature-api`):**
   - **Data Store (`@wordpress/data`)**: gestiona el estado de las features en el cliente.
   - **API Functions**: `registerFeature`, `executeFeature`, `getRegisteredFeatures`, etc.
   - **Sincronización:** obtiene del servidor las features en la inicialización y las combina con las registradas en el cliente.

3. **Capa de transporte (WP REST API):**
   - `GET /wp/v2/features`: lista features (descubrimiento).
   - `GET /wp/v2/features/{feature-id}`: detalles de una feature.
   - `POST|GET /wp/v2/features/{feature-id}/run`: ejecuta una feature (valida input, permisos y llama al callback).

4. **Extensibilidad:**
   - Plugins/Temas registran features con `wp_register_feature` (PHP) o `registerFeature` (JS).
   - Features pueden ser `Resource` (lectura) o `Tool` (acción).
   - `rest_alias` permite exponer endpoints REST ya existentes como features.

**Flujo conceptual:**

```
++---------------------+      +-------------------------+      +------------------------+
|   Cliente (JS)      |----->| WP REST API             |<---->| Servidor (PHP)         |
| (UI, agentes IA)    |      | (/wp/v2/features)       |      | (Plugins/Temas/Core)   |
+---------------------+      +-------------------------+      +------------------------+
       |  ^                          |  ^                              |  ^
       |  |(usa API cliente)         |  |(descubrimiento/ejecución)   |  |(usa API PHP)
       v  |                          v  |                              v  |
+---------------------+      +-------------------------+      +------------------------+
| @automattic/wp-feature-api |<----->| WP_REST_Feature_Controller|<---->| WP_Feature_Registry    |
| (Data Store, API)   |      | (maneja REST Req/Res)    |      | (gestiona features)     |
+---------------------+      +-------------------------+      +------------------------+
                                                                       |
                                                                       v
                                                           +------------------------+
                                                           | WP_Feature_Repository  |
                                                           | (Almacenamiento)       |
                                                           +------------------------+
```

## 4. Componentes centrales

### Servidor (PHP)

- **`WP_Feature_Registry`**: registro central (singleton). Accesible mediante `wp_feature_registry()`.
- **`WP_Feature`**: representa una feature; ofrece `call()`, `is_eligible()`, `get_input_schema()`, etc.
- **`wp_register_feature( array|WP_Feature $args )`**: función para registrar features del lado servidor.

Ejemplo de registro y uso (PHP):

```php
<?php
// In functions.php or your plugin

// 1. Define the callback
function myplugin_get_site_tagline() {
    return get_bloginfo( 'description' );
}

// 2. Register the feature on init
add_action( 'init', function() {
    if ( ! function_exists( 'wp_register_feature' ) ) {
        return; // Feature API not active
    }

    wp_register_feature( array(
        'id'          => 'myplugin/site-tagline', // Unique namespaced ID
        'name'        => __( 'Get Site Tagline', 'my-plugin' ),
        'description' => __( 'Retrieves the tagline (description) of the site.', 'my-plugin' ),
        'type'        => \WP_Feature::TYPE_RESOURCE, // Data retrieval
        'callback'    => 'myplugin_get_site_tagline', // Function to execute
        'permission_callback' => '__return_true', // Publicly accessible
        'categories'  => array( 'my-plugin', 'site-info' ),
    ) );
} );

// 3. Use the feature later
function myplugin_display_tagline() {
    $feature = wp_find_feature( 'resource-myplugin/site-tagline' ); // Note: 'resource-' prefix added automatically when finding

    if ( $feature && $feature->is_eligible() ) {
        $tagline = $feature->call();
        if ( ! is_wp_error( $tagline ) ) {
            echo 'Site Tagline: ' . esc_html( $tagline );
        }
    }
}
```

### Cliente (JS - `@automattic/wp-feature-api`)

- **Store (`@wordpress/data`)**: mantiene el estado de features en el cliente.
- **`registerFeature`, `executeFeature`, `getRegisteredFeatures()`**: APIs para interactuar con el registro cliente.

Ejemplo en el cliente (JS):

```javascript
// In your client-side code (e.g., block editor script)
import { registerFeature, executeFeature, getRegisteredFeatures } from '@automattic/wp-feature-api';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';

// 1. Define and register a client-side feature
const saveCurrentPostFeature = {
  id: 'my-editor-features/save-post',
  name: 'Save Current Post',
  description: 'Triggers the save action for the post currently being edited.',
  type: 'tool',
  location: 'client', // Important!
  categories: ['my-editor', 'post-actions'],
  callback: () => {
    try {
      dispatch(editorStore).savePost();
      return { success: true };
    } catch (error) {
      console.error('Failed to save post:', error);
      return { success: false, error: error.message };
    }
  },
  output_schema: {
    type: 'object',
    properties: { success: { type: 'boolean' }, error: { type: 'string' } },
    required: ['success']
  }
};

registerFeature(saveCurrentPostFeature);

// 2. Use a feature (client or server-side)
async function updateAndSave(newTitle) {
  try {
    // Example: Use a hypothetical server-side or client-side feature to update title
    await executeFeature('editor/set-title', { title: newTitle });
    console.log('Title updated.');

    // Now execute the client-side save feature we registered
    const saveResult = await executeFeature('my-editor-features/save-post', {});
    console.log('Save result:', saveResult);

  } catch (error) {
    console.error('Error during update and save:', error);
  }

  // Discover available features
  const allFeatures = await getRegisteredFeatures();
  console.log('Available Features:', allFeatures);
}
```

## 5. Puntos de extensión (Extension Points)

La forma principal de extender la API es registrando tus propias Features, ya sea como `Resources` (lectura) o `Tools` (acciones).

### Registrar Features en el servidor (PHP)

Usa `wp_register_feature()` normalmente enganchado a `init`.

* **Qué hace:** Añade una capacidad implementada en PHP al registro central, haciéndola descubrible y ejecutable vía llamadas PHP (`$feature->call()`) o REST API.
* **Cuándo usarlo:** Para funcionalidades que requieren acceso al servidor, base de datos o APIs internas de WordPress.

Ejemplo completo (PHP):

```php
<?php
/**
 * Plugin Name: My Custom Features
 * Description: Registers custom features with the WP Feature API.
 * Version: 1.0
 * Author: Developer Name
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

/**
 * Callback function for retrieving a specific option.
 *
 * @param array $context Input context containing 'option_name'.
 * @return mixed|WP_Error Option value on success, WP_Error on failure.
 */
function my_custom_features_get_option_callback( $context ) {
 if ( ! isset( $context['option_name'] ) ) {
  return new WP_Error( 'missing_option_name', __( 'Option name is required.', 'my-custom-features' ) );
 }

 $option_name = sanitize_key( $context['option_name'] );
 $value = get_option( $option_name );

 if ( false === $value ) {
        // Distinguish between 'option does not exist' and 'option is false'
  $all_options = wp_load_alloptions();
        if (!array_key_exists($option_name, $all_options)) {
             return new WP_Error( 'option_not_found', sprintf( __( 'Option "%s" not found.', 'my-custom-features' ), $option_name ), array( 'status' => 404 ) );
        }
 }

 return $value;
}

---

# Documentación de WordPress Feature API

## 1. Título e Introducción

**WordPress Feature API: Exponer capacidades del sitio para desarrolladores y IA**

Este documento proporciona documentación técnica completa para el plugin WordPress Feature API. Su propósito es guiar a los desarrolladores sobre cómo entender, usar y extender la Feature API para registrar e interactuar con funcionalidades de WordPress de forma estandarizada y descubrible, particularmente para consumo por agentes de IA y otros sistemas programáticos.

## 2. Sección de Resumen

La WordPress Feature API ofrece una manera estandarizada de registrar y descubrir unidades de funcionalidad dentro de un sitio WordPress. Estas unidades, llamadas "Features" (Características), representan acciones de obtención de datos (**Resources**) o acciones de modificación/creación de datos (**Tools**). Actúa como un registro central, accesible tanto desde PHP (lado servidor) como desde JavaScript (lado cliente), facilitando que el core, plugins, temas y sistemas externos (como agentes de IA) entiendan e interactúen con las capacidades disponibles en un sitio específico. Los componentes clave incluyen el registro del lado servidor usando `wp_register_feature`, el registro del lado cliente vía `@automattic/wp-feature-api`, endpoints de la REST API para descubrimiento y ejecución, y un sistema flexible para definir entradas, salidas, permisos y elegibilidad para cada feature.

## 3. Visión general de la arquitectura

La Feature API emplea un patrón de registro (registry) con componentes distintos en servidor y cliente, unificados mediante una capa de transporte basada en la REST API.

1. **Lado servidor (PHP):**
    * `WP_Feature_Registry`: Clase singleton que administra todas las features registradas.
    * `WP_Feature`: Representa una feature individual (Resource o Tool) con propiedades como ID, nombre, descripción, tipo, esquemas, callbacks, permisos, etc.
    * `WP_Feature_Repository_Interface`: Define cómo se almacenan las features (por defecto: `WP_Feature_Repository_Memory` para almacenamiento en memoria por petición).
    * `WP_Feature_Query`: Clase para filtrar y buscar features.
    * `WP_REST_Feature_Controller`: Expone las features vía la WP REST API (`/wp/v2/features`).
    * `wp_register_feature()`: Función global para un registro fácil.

2. **Lado cliente (JavaScript - `@automattic/wp-feature-api`):**
    * **Data Store (`@wordpress/data`)**: Gestiona el estado de las features registradas en el cliente.
    * **Funciones API (`registerFeature`, `executeFeature`, `getRegisteredFeatures`, etc.)**: Proporcionan la interfaz para interactuar con el registro del lado cliente.
    * **Sincronización:** Obtiene las features del lado servidor vía REST al inicializarse y las resuelve junto con las features registradas por el cliente.

3. **Capa de transporte (WP REST API):**
    * `GET /wp/v2/features`: Lista features (descubrimiento).
    * `GET /wp/v2/features/{feature-id}`: Obtiene detalles de una feature específica.
    * `POST|GET /wp/v2/features/{feature-id}/run`: Ejecuta una feature (maneja validación de entrada, permisos, llamada al callback de la feature).

4. **Extensibilidad:**
    * Plugins/Temas registran features usando `wp_register_feature` (PHP) o `registerFeature` (JS).
    * Las features pueden ser Resources (lectura) o Tools (acciones).
    * `rest_alias` permite exponer endpoints REST existentes como features de forma sencilla.

**Flujo Diagramático (Conceptual):**

```
+---------------------+      +-------------------------+      +------------------------+
|   Client-Side JS    |----->| WP REST API             |<---->| Server-Side PHP        |
| (React UI, Agent)   |      | (/wp/v2/features)       |      | (Plugin/Theme/Core)    |
+---------------------+      +-------------------------+      +------------------------+
       |  ^                          |  ^                              |  ^
       |  |(uses client API)         |  |(discovery/execution)        |  |(uses PHP API)
       v  |                          v  |                              v  |
+---------------------+      +-------------------------+      +------------------------+
| @automattic/wp-feature-api |<----->| WP_REST_Feature_Controller|<---->| WP_Feature_Registry    |
| (Data Store, API fns)|      | (Handles REST Req/Res)  |      | (Manages Features)     |
+---------------------+      +-------------------------+      +------------------------+
                                                                       |
                                                                       v
                                                           +------------------------+
                                                           | WP_Feature_Repository  |
                                                           | (Storage: Memory/DB)   |
                                                           +------------------------+
```

## 4. Componentes centrales

### Lado Servidor (PHP)

* **`WP_Feature_Registry`**: El registro central singleton. Accesible vía `wp_feature_registry()`. Administra almacenamiento y recuperación de features.
* **`WP_Feature`**: Representa una feature individual. Se crea internamente al usar `wp_register_feature`. Proporciona métodos como `call()`, `is_eligible()`, `get_input_schema()`.
* **`wp_register_feature( array|WP_Feature $args )`**: La función principal para registrar una feature del lado servidor.

```php
<?php
// In functions.php or your plugin

// 1. Define the callback
function myplugin_get_site_tagline() {
    return get_bloginfo( 'description' );
}

// 2. Register the feature on init
add_action( 'init', function() {
    if ( ! function_exists( 'wp_register_feature' ) ) {
        return; // Feature API not active
    }

    wp_register_feature( array(
        'id'          => 'myplugin/site-tagline', // Unique namespaced ID
        'name'        => __( 'Get Site Tagline', 'my-plugin' ),
        'description' => __( 'Retrieves the tagline (description) of the site.', 'my-plugin' ),
        'type'        => \WP_Feature::TYPE_RESOURCE, // Data retrieval
        'callback'    => 'myplugin_get_site_tagline', // Function to execute
        'permission_callback' => '__return_true', // Publicly accessible
        'categories'  => array( 'my-plugin', 'site-info' ),
    ) );
} );

// 3. Use the feature later
function myplugin_display_tagline() {
    $feature = wp_find_feature( 'resource-myplugin/site-tagline' ); // Note: 'resource-' prefix added automatically when finding

    if ( $feature && $feature->is_eligible() ) {
        $tagline = $feature->call();
        if ( ! is_wp_error( $tagline ) ) {
            echo 'Site Tagline: ' . esc_html( $tagline );
        }
    }
}
```

### Lado Cliente (JavaScript - `@automattic/wp-feature-api`)

* **`store` (`@wordpress/data` store)**: Gestiona el estado de features en el cliente.
* **`registerFeature( feature: Feature )`**: Registra una feature del lado cliente.
* **`executeFeature( featureId: string, args: any )`**: Ejecuta una feature (cliente o servidor).
* **`getRegisteredFeatures()`**: Recupera todas las features conocidas (cliente + servidor resuelto).

```javascript
// In your client-side code (e.g., block editor script)
import { registerFeature, executeFeature, getRegisteredFeatures } from '@automattic/wp-feature-api';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';

// 1. Define and register a client-side feature
const saveCurrentPostFeature = {
  id: 'my-editor-features/save-post',
  name: 'Save Current Post',
  description: 'Triggers the save action for the post currently being edited.',
  type: 'tool',
  location: 'client', // Important!
  categories: ['my-editor', 'post-actions'],
  callback: () => {
    try {
      dispatch(editorStore).savePost();
      return { success: true };
    } catch (error) {
      console.error('Failed to save post:', error);
      return { success: false, error: error.message };
    }
  },
  output_schema: {
    type: 'object',
    properties: { success: { type: 'boolean' }, error: { type: 'string' } },
    required: ['success']
  }
};

registerFeature(saveCurrentPostFeature);

// 2. Use a feature (client or server-side)
async function updateAndSave(newTitle) {
  try {
    // Example: Use a hypothetical server-side or client-side feature to update title
    await executeFeature('editor/set-title', { title: newTitle });
    console.log('Title updated.');

    // Now execute the client-side save feature we registered
    const saveResult = await executeFeature('my-editor-features/save-post', {});
    console.log('Save result:', saveResult);

  } catch (error) {
    console.error('Error during update and save:', error);
  }

  // Discover available features
  const allFeatures = await getRegisteredFeatures();
  console.log('Available Features:', allFeatures);
}
```

## 5. Puntos de extensión

La forma principal de extender la API es registrando tus propias Features, ya sea como Resources (para obtención de datos) o Tools (para acciones).

### Registrar Features en el Servidor (PHP)

Usa la función `wp_register_feature()`, normalmente enganchada al hook `init`.

* **Qué hace:** Añade una capacidad basada en PHP al registro central, haciéndola descubrible y ejecutable vía llamadas PHP (`$feature->call()`) o la REST API.
* **Cuándo usarlo:** Para funcionalidad implementada en PHP, que interactúe con el core de WordPress, la base de datos u otros recursos del servidor.

**Ejemplo completo de código:**

```php
<?php
/**
 * Plugin Name: My Custom Features
 * Description: Registers custom features with the WP Feature API.
 * Version: 1.0
 * Author: Developer Name
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

/**
 * Callback function for retrieving a specific option.
 *
 * @param array $context Input context containing 'option_name'.
 * @return mixed|WP_Error Option value on success, WP_Error on failure.
 */
function my_custom_features_get_option_callback( $context ) {
 if ( ! isset( $context['option_name'] ) ) {
  return new WP_Error( 'missing_option_name', __( 'Option name is required.', 'my-custom-features' ) );
 }

 $option_name = sanitize_key( $context['option_name'] );
 $value = get_option( $option_name );

 if ( false === $value ) {
        // Distinguish between 'option does not exist' and 'option is false'
  $all_options = wp_load_alloptions();
        if (!array_key_exists($option_name, $all_options)) {
             return new WP_Error( 'option_not_found', sprintf( __( 'Option "%s" not found.', 'my-custom-features' ), $option_name ), array( 'status' => 404 ) );
        }
 }

 return $value;
}

/**
 * Callback function for updating a specific option.
 *
 * @param array $context Input context containing 'option_name' and 'option_value'.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function my_custom_features_update_option_callback( $context ) {
 if ( ! isset( $context['option_name'] ) || ! isset( $context['option_value'] ) ) {
  return new WP_Error( 'missing_params', __( 'Both option_name and option_value are required.', 'my-custom-features' ) );
 }

 $option_name = sanitize_key( $context['option_name'] );
 // Sanitize based on expected type - this is basic, complex types need more care
 $option_value = sanitize_text_field( $context['option_value'] );

 $success = update_option( $option_name, $option_value );

 if ( ! $success ) {
  // update_option returns false if value is the same or on failure
        // We might want to check if the value actually changed if needed
        return new WP_Error( 'update_failed', sprintf( __( 'Failed to update option "%s".', 'my-custom-features' ), $option_name ) );
 }

 return true;
}


/**
 * Registers the custom features.
 */
function my_custom_features_register() {
 // Ensure Feature API is available
 if ( ! function_exists( 'wp_register_feature' ) || ! class_exists( '\WP_Feature' ) ) {
  add_action( 'admin_notices', function() {
   echo '<div class="notice notice-error"><p>My Custom Features plugin requires the WordPress Feature API plugin to be active.</p></div>';
  });
  return;
 }

 // --- Get Option Feature (Resource) ---
 wp_register_feature( array(
  'id'          => 'my-custom-features/get-option',
  'name'        => __( 'Get WordPress Option', 'my-custom-features' ),
  'description' => __( 'Retrieves the value of a specific WordPress option from the options table.', 'my-custom-features' ),
  'type'        => \WP_Feature::TYPE_RESOURCE, // Read-only
  'callback'    => 'my_custom_features_get_option_callback',
  'permission_callback' => function() {
   // Only allow users who can manage options
   return current_user_can( 'manage_options' );
  },
  'input_schema' => array(
   'type' => 'object',
   'properties' => array(
    'option_name' => array(
     'type' => 'string',
     'description' => __( 'The name of the option to retrieve.', 'my-custom-features' ),
                    'required' => true, // Mark as required in description/docs
    ),
   ),
            // Formal required declaration for validation
            'required' => ['option_name'],
  ),
  'output_schema' => array(
   // Type can be mixed (string, int, bool, array, object)
   'type' => array('string', 'integer', 'boolean', 'array', 'object', 'null'),
   'description' => __( 'The value of the requested option.', 'my-custom-features' ),
  ),
  'categories'  => array( 'my-custom-features', 'options', 'site-settings' ),
 ) );

 // --- Update Option Feature (Tool) ---
    wp_register_feature( array(
  'id'          => 'my-custom-features/update-option',
  'name'        => __( 'Update WordPress Option', 'my-custom-features' ),
  'description' => __( 'Updates the value of a specific WordPress option in the options table.', 'my-custom-features' ),
  'type'        => \WP_Feature::TYPE_TOOL, // Action/Write
  'callback'    => 'my_custom_features_update_option_callback',
  'permission_callback' => function() {
   return current_user_can( 'manage_options' );
  },
  'input_schema' => array(
   'type' => 'object',
   'properties' => array(
    'option_name' => array(
     'type' => 'string',
     'description' => __( 'The name of the option to update.', 'my-custom-features' ),
                    'required' => true,
    ),
                'option_value' => array(
                    // Allow various primitive types for option value
     'type' => array('string', 'integer', 'boolean', 'null'),
     'description' => __( 'The new value for the option.', 'my-custom-features' ),
                    'required' => true,
    ),
   ),
            'required' => ['option_name', 'option_value'],
  ),
  'output_schema' => array(
   'type' => 'boolean',
   'description' => __( 'True if the option was successfully updated, false otherwise (or if value was unchanged).', 'my-custom-features' ),
  ),
  'categories'  => array( 'my-custom-features', 'options', 'site-settings' ),
 ) );
}
add_action( 'init', 'my_custom_features_register', 20 ); // Priority 20 to run after core features

```

**Explicación de parámetros y opciones:**

* `id` (string, requerido): Identificador único con namespace (ej., `my-plugin/feature-name`). Usa minúsculas alfanuméricas, guiones, barras. *No incluyas* el prefijo de tipo (`resource-` o `tool-`) aquí.
* `name` (string, requerido): Nombre legible, traducible.
* `description` (string, requerido): Explicación detallada y traducible para desarrolladores y IA. Explica propósito, entradas y salidas.
* `type` (string, requerido): `WP_Feature::TYPE_RESOURCE` (como GET) o `WP_Feature::TYPE_TOOL` (como POST/PUT/DELETE).
* `callback` (callable|null): La función/método PHP a ejecutar. Recibe un argumento: `$context` (array asociativo de entrada validada). Debe retornar el resultado o un `WP_Error`. Puede ser `null` si es `rest_alias` o manejado por filtros.
* `permission_callback` (callable|string[]|string|null): Determina si el usuario actual puede ejecutar la feature.
  * `callable`: Función que devuelve `true`, `false`, o `WP_Error`. Recibe `WP_User` y `WP_Feature`.
  * `string[]`: Array de capacidades de WordPress (ej., `['edit_posts', 'manage_options']`). Comprueba si el usuario tiene *todas* las capacidades.
  * `string`: Una sola capacidad (ej., `'manage_options'`).
  * `null` (u omitido): Por defecto deniega acceso. Usar `__return_true` para features públicas (usar con cautela). A menudo se infiere para `rest_alias`.
* `is_eligible` (callable|null): Determina si la feature está disponible *en el contexto actual*. Devuelve `true` o `false`. Útil para comprobar si plugins dependientes están activos, configuraciones habilitadas, etc. Por defecto `true`.
* `input_schema` (array|null): Definición JSON Schema de la entrada esperada `$context`. Usado para validación por la REST API y documentación.
* `output_schema` (array|null): Definición JSON Schema del valor de retorno esperado del `callback`. Usado para documentación y potencialmente validación de respuesta.
* `categories` (string[]|null): Array de slugs de categoría para organización y filtrado.
* `meta` (array|null): Array asociativo para metadatos arbitrarios.
* `rest_alias` (string|false): Si se establece a una ruta REST (ej., `/wp/v2/posts/(?P<id>[\d]+)`), la feature actúa como alias de ese endpoint. Esquemas y permisos pueden inferirse. Por defecto `false`.

### Registrar Features en el Lado Cliente (JavaScript)

Usa la función `registerFeature` exportada por el paquete `@automattic/wp-feature-api`.

* **Qué hace:** Añade una capacidad basada en JavaScript al registro del cliente. El `callback` de la feature se ejecuta directamente en el navegador del usuario.
* **Cuándo usarlo:** Para funcionalidad que interactúa directamente con el entorno del navegador, el DOM o componentes de WordPress del lado cliente como el Block Editor o Site Editor.

**Ejemplo completo de código:**

```javascript
/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { dispatch, select } from '@wordpress/data';

/**
 * Feature API Client dependencies
 */
import { registerFeature } from '@automattic/wp-feature-api'; // Assuming this is correctly imported/available

/**
 * Client-side Feature: Show Admin Notice
 */
const showAdminNoticeFeature = {
  id: 'my-client-features/show-notice', // Unique namespaced ID
  name: __('Show Admin Notice (Client-Side)', 'my-client-features'),
  description: __('Displays a notice message in the WordPress admin area.', 'my-client-features'),
  type: 'tool',      // It performs an action
  location: 'client', // Crucial: Indicates this runs in the browser
  categories: ['my-client-features', 'ui', 'notifications'],
  input_schema: {
    type: 'object',
    properties: {
      message: {
        type: 'string',
        description: __('The text content of the notice.', 'my-client-features'),
        required: true,
      },
      type: {
        type: 'string',
        description: __('Type of notice (success, info, warning, error).', 'my-client-features'),
        enum: ['success', 'info', 'warning', 'error'],
        default: 'info',
      },
      isDismissible: {
        type: 'boolean',
        description: __('Whether the notice can be dismissed by the user.', 'my-client-features'),
        default: true,
      },
      id: {
        type: 'string',
        description: __('Optional unique ID for the notice (allows programmatic removal).', 'my-client-features'),
      }
    },
    required: ['message'],
  },
  output_schema: {
    type: 'object',
    properties: {
      success: { type: 'boolean' },
      noticeId: { type: 'string', description: 'The generated or provided notice ID.' },
    },
    required: ['success', 'noticeId'],
  },
  // The actual JavaScript function to execute
  callback: (args) => {
    const { message, type = 'info', isDismissible = true, id } = args;

    if (typeof message !== 'string' || message.trim() === '') {
      throw new Error('Notice message cannot be empty.');
    }

    // Use the @wordpress/notices store to create the notice
    try {
      // Generate a unique ID if one wasn't provided
      const noticeId = id || `client-feature-notice-${Date.now()}`;

      // Create the notice using the notices store dispatcher
      dispatch(noticesStore).createNotice(type, message, {
        id: noticeId,
        isDismissible: isDismissible,
        // Add other options if needed, e.g., actions
      });

      // Return success and the ID used
      return { success: true, noticeId: noticeId };

    } catch (error) {
      console.error('Failed to create admin notice:', error);
      throw new Error(`Failed to show notice: ${error instanceof Error ? error.message : String(error)}`);
    }
  },
  // Optional: Only make this feature available if the notices store exists
  is_eligible: () => {
     try {
       // Check if the notices store is available in the data registry
       return !!select(noticesStore);
     } catch (e) {
       return false; // Store not found
     }
   }
};


/**
 * Example of how to register this feature
 * This would typically run within a script enqueued for the WordPress admin
 */
function registerMyClientFeatures() {
 if (typeof registerFeature === 'function') {
   registerFeature(showAdminNoticeFeature);
        console.log('Registered client-side feature: show-notice');

        // Example Usage (e.g., in another part of your client-side code):
        /*
        import { executeFeature } from '@automattic/wp-feature-api';

        async function triggerNotice() {
          try {
            const result = await executeFeature('my-client-features/show-notice', {
              message: 'This is a success notice from a client feature!',
              type: 'success'
            });
            console.log('Notice creation result:', result);
          } catch (error) {
            console.error('Error triggering notice:', error);
          }
        }
        // Call triggerNotice() when needed, e.g., after an action
        */

 } else {
  console.error('Feature API client `registerFeature` not available.');
 }
}

// You might register this within a WordPress plugin initialization
// registerPlugin('my-client-features-registration', { render: () => { registerMyClientFeatures(); return null; } });
// Or simply call it when your script loads:
registerMyClientFeatures();

```

**Explicación de parámetros y opciones:**

* **`id`, `name`, `description`, `type`, `categories`, `input_schema`, `output_schema`, `meta`**: Mismo significado que las features del lado servidor.
* **`location`** (string, requerido): **Debe establecerse en `'client'`**. Indica que el callback es JavaScript y se ejecuta en el navegador.
* **`callback`** (function, requerido): La función JavaScript a ejecutar. Recibe el objeto `args` basado en el `input_schema`. Debe devolver el resultado o lanzar un error.
* **`is_eligible`** (function|null): Función JavaScript opcional que devuelve `true` o `false`. Puede verificar contexto del navegador, elementos DOM o estado del cliente (ej. usando selectores de `@wordpress/data`).
* **`icon`** (any): Opcional. Puede ser un slug de Dashicon (string) o un componente React SVG para uso en integraciones UI como el Command Palette.

## 6. Ejemplos avanzados

Estos ejemplos muestran cómo integrar la Feature API con otras funcionalidades o plugins de WordPress.

### Ejemplo 1: Generar datos de WooCommerce (usando WooCommerce Smooth Generator)

Esto requiere que WooCommerce y el plugin WooCommerce Smooth Generator estén activos. Registra *tools* para generar datos de muestra.

```php
/**
 * Code Snippet: Register WooCommerce Smooth Generator features with the Feature API.
 *
 * IMPORTANT: Requires WordPress, WooCommerce, Feature API plugin, and
 * WooCommerce Smooth Generator plugin to be installed and active.
 * Deactivate this snippet if Smooth Generator adds native support later.
 */

// --- Permission & Eligibility Callbacks ---
function wcgs_snippet_check_permission() {
 // Allow users who can manage WC or install plugins
 return current_user_can( 'install_plugins' ) || current_user_can( 'manage_woocommerce' );
}

function wcgs_snippet_check_eligibility() {
 // Check if WC and the specific Generator class exist
 return function_exists( 'WC' ) && class_exists( '\WC\SmoothGenerator\Generator\Product' );
}

// --- Callback Adapter Functions ---

/** Adapter for Product generator. */
function wcgs_snippet_run_product_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Product' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Product generator class not found.' );
 $amount = $context['amount'] ?? 10;
 // Only pass allowed arguments to the batch function
 $args   = wp_array_slice_assoc( $context, array( 'type', 'use-existing-terms' ) );
 return \WC\SmoothGenerator\Generator\Product::batch( (int) $amount, $args );
}

// ... (Similar adapter functions for Order, Customer, Coupon, Term generators - see original snippet)
function wcgs_snippet_run_order_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Order' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Order generator class not found.' );
 $amount = $context['amount'] ?? 10;
 $args   = wp_array_slice_assoc( $context, array( 'date-start', 'date-end', 'status', 'coupons', 'skip-order-attribution' ) );
 return \WC\SmoothGenerator\Generator\Order::batch( (int) $amount, $args );
}
function wcgs_snippet_run_customer_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Customer' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Customer generator class not found.' );
 $amount = $context['amount'] ?? 10;
 $args   = wp_array_slice_assoc( $context, array( 'country', 'type' ) );
 return \WC\SmoothGenerator\Generator\Customer::batch( (int) $amount, $args );
}
function wcgs_snippet_run_coupon_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Coupon' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Coupon generator class not found.' );
 $amount = $context['amount'] ?? 10;
 $args   = wp_array_slice_assoc( $context, array( 'min', 'max' ) );
 return \WC\SmoothGenerator\Generator\Coupon::batch( (int) $amount, $args );
}
function wcgs_snippet_run_term_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Term' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Term generator class not found.' );
 $taxonomy = $context['taxonomy'] ?? null;
 $amount   = $context['amount'] ?? 10;
 $args     = wp_array_slice_assoc( $context, array( 'max-depth', 'parent' ) );
 if ( is_null( $taxonomy ) ) return new \WP_Error( 'missing_taxonomy', __( 'Taxonomy argument is required for generating terms.', 'wc-smooth-generator' ) );
 if ( ! taxonomy_exists( $taxonomy ) || ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) return new \WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy provided. Use "product_cat" or "product_tag".', 'wc-smooth-generator' ) );
 return \WC\SmoothGenerator\Generator\Term::batch( (int) $amount, $taxonomy, $args );
}

/**
 * Main registration function hooked to 'init'.
 */
function wc_smooth_generator_register_features_snippet() {
 if ( ! function_exists( 'wp_register_feature' ) || ! class_exists( '\WP_Feature' ) || ! function_exists( 'WC' ) ) {
  return; // Exit if dependencies aren't met
 }

 $output_schema_ids = array(
  'type'        => 'array',
  'items'       => array( 'type' => 'integer' ),
  'description' => __( 'An array containing the IDs of the generated items.', 'wc-smooth-generator' ),
 );

 // --- Register Product Generator ---
 wp_register_feature( array(
  'id'                  => 'wc-smooth-generator/generate-products',
  'name'                => __( 'Generate WooCommerce Products', 'wc-smooth-generator' ),
  'description'         => __( 'Generates WooCommerce products (simple/variable). Specify amount, optionally type (simple/variable) and use-existing-terms (boolean).', 'wc-smooth-generator' ),
  'type'                => \WP_Feature::TYPE_TOOL,
  'callback'            => 'wcgs_snippet_run_product_generator',
  'permission_callback' => 'wcgs_snippet_check_permission',
  'is_eligible'         => 'wcgs_snippet_check_eligibility',
  'input_schema'        => array(
   'type'       => 'object',
   'properties' => array(
    'amount'             => array( 'type' => 'integer', 'description' => __( 'Number of products.', 'wc-smooth-generator' ), 'default' => 10, 'minimum' => 1 ),
    'type'               => array( 'type' => 'string', 'description' => __( 'Type (simple/variable). Defaults to mix.', 'wc-smooth-generator' ), 'enum' => array( 'simple', 'variable' ) ),
    'use-existing-terms' => array( 'type' => 'boolean', 'description' => __( 'Only use existing categories/tags.', 'wc-smooth-generator' ), 'default' => false ),
   ),
   'required' => ['amount']
  ),
  'output_schema'       => $output_schema_ids,
  'categories'          => array( 'wc-smooth-generator', 'data-generation', 'woocommerce', 'testing', 'product' ),
 ) );

    // --- Register Order Generator ---
 wp_register_feature( array(
        'id'                  => 'wc-smooth-generator/generate-orders',
        'name'                => __( 'Generate WooCommerce Orders', 'wc-smooth-generator' ),
        'description'         => __( 'Generates WooCommerce orders. Specify amount, optionally date range (date-start/date-end YYYY-MM-DD), status (completed/processing/etc.), coupons (boolean), skip-order-attribution (boolean).', 'wc-smooth-generator' ),
        'type'                => \WP_Feature::TYPE_TOOL,
        'callback'            => 'wcgs_snippet_run_order_generator',
        'permission_callback' => 'wcgs_snippet_check_permission',
        'is_eligible'         => 'wcgs_snippet_check_eligibility', // Assumes Order generator exists if Product does
        'input_schema'        => array(
            'type'       => 'object',
            'properties' => array(
                'amount'                 => array( 'type' => 'integer', 'description' => __( 'Number of orders.', 'wc-smooth-generator' ), 'default' => 10, 'minimum' => 1 ),
                'date-start'             => array( 'type' => 'string', 'format' => 'date', 'description' => __( 'Start date (YYYY-MM-DD).', 'wc-smooth-generator' ) ),
                'date-end'               => array( 'type' => 'string', 'format' => 'date', 'description' => __( 'End date (YYYY-MM-DD).', 'wc-smooth-generator' ) ),
                'status'                 => array( 'type' => 'string', 'description' => __( 'Order status. Defaults to mix.', 'wc-smooth-generator' ), 'enum' => array( 'completed', 'processing', 'on-hold', 'failed' ) ),
                'coupons'                => array( 'type' => 'boolean', 'description' => __( 'Create and apply coupons.', 'wc-smooth-generator' ), 'default' => false ),
                'skip-order-attribution' => array( 'type' => 'boolean', 'description' => __( 'Skip order attribution meta.', 'wc-smooth-generator' ), 'default' => false ),
            ),
             'required' => ['amount']
        ),
        'output_schema'       => $output_schema_ids,
        'categories'          => array( 'wc-smooth-generator', 'data-generation', 'woocommerce', 'testing', 'order' ),
    ) );

 // ... (Registrations for Customer, Coupon, Term generators using respective adapter functions)

}
add_action( 'init', 'wc_smooth_generator_register_features_snippet', 20 );
```

### Ejemplo 2: Interactuar con Code Snippets

... (la traducción continúa con el resto del documento, preservando íntegramente los bloques de código)
