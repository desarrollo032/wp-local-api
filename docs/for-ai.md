# 📘 Documentación de la API de Funcionalidades de WordPress

> **Referencia técnica comprimida para desarrolladores e inteligencia artificial**
> 
> Copia y pega este documento en cualquier chat de IA o compártelo con un desarrollador. Es una referencia única y comprimida que explica la función del plugin, cómo se integran sus componentes y cómo extenderlo.

---

## 1. Título y Descripción General

# WordPress Feature API: Exponiendo Capacidades del Sitio para Desarrolladores e IA

Este documento proporciona documentación técnica completa para el plugin WordPress Feature API. Su propósito es guiar a los desarrolladores sobre cómo entender, usar y extender la Feature API para registrar e interactuar con funcionalidades de WordPress de manera estandarizada y descubrible, especialmente para el consumo por agentes de IA y otros sistemas programáticos.

---

## 2. Resumen Ejecutivo

La **WordPress Feature API** proporciona una forma estandarizada de registrar y descubrir unidades distintas de funcionalidad dentro de un sitio WordPress. Estas unidades, llamadas **"Features" (Funcionalidades)**, representan ya sea acciones de recuperación de datos (**Recursos**) o acciones de modificación/creación de datos (**Herramientas**).

Acts acts como un registro central, accesible tanto desde PHP (lado del servidor) como desde JavaScript (lado del cliente), facilitando que el núcleo de WordPress, plugins, temas y sistemas externos (como agentes de IA) entiendan e interactúen con las capacidades disponibles en un sitio específico.

### ✨ Componentes Principales

| Componente | Descripción |
|------------|-------------|
| **Registro en Servidor** | `wp_register_feature()` para registrar funcionalidades PHP |
| **Registro en Cliente** | `@automattic/wp-feature-api` para registrar funcionalidades JS |
| **Endpoints REST** | `/wp/v2/features` para descubrimiento y ejecución |
| **Sistema Flexible** | Definición de entradas, salidas, permisos y elegibilidad |

---

## 3. Visión General de la Arquitectura

La Feature API emplea un patrón de registro con componentes distintos en el lado del servidor y del cliente, unificados a través de una capa de transporte REST API.

### 🏗️ Arquitectura General

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENTE (JavaScript)                           │
│                  (React UI, Agente de IA, Editor de Bloques)                │
└─────────────────────────────────────┬───────────────────────────────────────┘
                                      │ usa API del cliente
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                    @automattic/wp-feature-api                               │
│                 (Almacén de Datos, Funciones API)                           │
└──────────────────┬──────────────────────────┬───────────────────────────────┘
                   │                          │
                   │ sincroniza               │ descubrimiento/ejecución
                   ▼                          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        WP REST API                                          │
│                   (/wp/v2/features)                                         │
└──────────────────┬──────────────────────────┬───────────────────────────────┘
                   │                          │
                   │ manejo                   │ registro
                   ▼                          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                    WP_REST_Feature_Controller                               │
│                 (Maneja Peticiones/Respuestas REST)                         │
└──────────────────┬──────────────────────────┬───────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         Servidor (PHP)                                      │
│                   (Plugin/Tema/Núcleo de WP)                                │
└─────────────────────────────────────┬───────────────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                       WP_Feature_Registry                                   │
│                    (Gestor Central de Features)                             │
└──────────────────┬──────────────────────────┬───────────────────────────────┘
                                      │
                                      ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                     WP_Feature_Repository                                   │
│                    (Almacenamiento: Memoria/BD)                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Componentes del Lado del Servidor (PHP)

### 📦 Paquetes Principales

```php
// Componentes del servidor
├── WP_Feature_Registry        // Singleton central - Gestiona todas las features
├── WP_Feature                 // Representa una feature individual
├── WP_Feature_Repository_Interface  // Define cómo se almacenan las features
├── WP_Feature_Query           // Clase para filtrar y buscar features
├── WP_REST_Feature_Controller // Expone features vía REST API
└── wp_register_feature()      // Función global para registro fácil
```

### 🔧 WP_Feature_Registry

El **registro central singleton**. Accedido mediante `wp_feature_registry()`. Gestiona el almacenamiento y recuperación de features.

### 📝 WP_Feature

Representa una **única feature** (Recurso o Herramienta). Creada internamente al usar `wp_register_feature`. Proporciona métodos como:

- `call($context)` → Ejecuta la feature
- `is_eligible()` → Verifica elegibilidad
- `get_input_schema()` → Obtiene esquema de entrada

### ⚡ Función Principal: wp_register_feature()

```php
<?php
// En functions.php o tu plugin

// 1. Definir el callback
function miplugin_obtener_eslogan() {
    return get_bloginfo( 'description' );
}

// 2. Registrar la feature en init
add_action( 'init', function() {
    if ( ! function_exists( 'wp_register_feature' ) ) {
        return; // Feature API no está activa
    }

    wp_register_feature( array(
        'id'          => 'miplugin/eslogan-sitio',
        'name'        => __( 'Obtener Eslogan del Sitio', 'mi-plugin' ),
        'description' => __( 'Recupera el eslogan (descripción) del sitio.', 'mi-plugin' ),
        'type'        => \WP_Feature::TYPE_RESOURCE,
        'callback'    => 'miplugin_obtener_eslogan',
        'permission_callback' => '__return_true',
        'categories'  => array( 'mi-plugin', 'info-sitio' ),
    ) );
} );

// 3. Usar la feature posteriormente
function miplugin_mostrar_eslogan() {
    $feature = wp_find_feature( 'recurso-miplugin/eslogan-sitio' );

    if ( $feature && $feature->is_eligible() ) {
        $eslogan = $feature->call();
        if ( ! is_wp_error( $eslogan ) ) {
            echo 'Eslogan del Sitio: ' . esc_html( $eslogan );
        }
    }
}
```

---

## 5. Componentes del Lado del Cliente (JavaScript)

### 📦 Paquete: @automattic/wp-feature-api

```javascript
// Componentes del cliente
├── store (@wordpress/data)    // Gestiona estado de features
├── registerFeature()          // Registra una feature
├── executeFeature()           // Ejecuta una feature
└── getRegisteredFeatures()    // Obtiene todas las features
```

### 💻 Ejemplo Completo en Cliente

```javascript
// En tu código del lado del cliente (ej: script del editor de bloques)
import { registerFeature, executeFeature, getRegisteredFeatures } from '@automattic/wp-feature-api';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';

// 1. Definir y registrar una feature del lado del cliente
const guardarPostFeature = {
  id: 'mi-editor-guardar/post',
  name: 'Guardar Post Actual',
  description: 'Dispara la acción de guardado para el post que se está editando.',
  type: 'tool',
  location: 'client', // ¡Importante!
  categories: ['mi-editor', 'acciones-post'],
  callback: () => {
    try {
      dispatch(editorStore).savePost();
      return { success: true };
    } catch (error) {
      console.error('Error al guardar post:', error);
      return { success: false, error: error.message };
    }
  },
  output_schema: {
    type: 'object',
    properties: { 
      success: { type: 'boolean' }, 
      error: { type: 'string' } 
    },
    required: ['success']
  }
};

registerFeature(guardarPostFeature);

// 2. Usar una feature
async function actualizarYGuardar(nuevoTitulo) {
  try {
    await executeFeature('editor/establecer-titulo', { title: nuevoTitulo });
    console.log('Título actualizado.');
    
    const resultadoGuardado = await executeFeature('mi-editor-guardar/post', {});
    console.log('Resultado del guardado:', resultadoGuardado);
  } catch (error) {
    console.error('Error durante actualización:', error);
  }

  const todasFeatures = await getRegisteredFeatures();
  console.log('Features Disponibles:', todasFeatures);
}
```

---

## 6. API de Transporte (REST)

### 🌐 Endpoints Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/wp/v2/features` | Lista todas las features (descubrimiento) |
| `GET` | `/wp/v2/features/{feature-id}` | Obtiene detalles de una feature específica |
| `POST/GET` | `/wp/v2/features/{feature-id}/run` | Ejecuta una feature |

### 📋 Ejemplo de Petición REST

```bash
# Listar todas las features
curl https://tusitio.com/wp-json/wp/v2/features

# Obtener detalles de una feature específica
curl https://tusitio.com/wp-json/wp/v2/features/recurso-miplugin/eslogan-sitio

# Ejecutar una feature (POST)
curl -X POST https://tusitio.com/wp-json/wp/v2/features/recurso-miplugin/eslogan-sitio/run \
  -H "Content-Type: application/json" \
  -d '{}'
```

---

## 7. Puntos de Extensión

### 🛠️ Registrando Features del Lado del Servidor (PHP)

```php
<?php
/**
 * Plugin Name: Mis Features Personalizadas
 * Description: Registra features personalizadas con la WP Feature API.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Callback para obtener una opción específica.
 */
function mis_features_obtener_opcion_callback( $context ) {
    if ( ! isset( $context['nombre_opcion'] ) ) {
        return new WP_Error( 'falta_nombre_opcion', 
            __( 'El nombre de la opción es requerido.', 'mis-features' ) );
    }

    $nombre_opcion = sanitize_key( $context['nombre_opcion'] );
    $valor = get_option( $nombre_opcion );

    if ( false === $valor ) {
        $todas_opciones = wp_load_alloptions();
        if ( ! array_key_exists( $nombre_opcion, $todas_opciones ) ) {
            return new WP_Error( 'opcion_no_encontrada', 
                sprintf( __( 'Opción "%s" no encontrada.', 'mis-features' ), $nombre_opcion ),
                array( 'status' => 404 ) );
        }
    }

    return $valor;
}

/**
 * Callback para actualizar una opción específica.
 */
function mis_features_actualizar_opcion_callback( $context ) {
    if ( ! isset( $context['nombre_opcion'] ) || ! isset( $context['valor_opcion'] ) ) {
        return new WP_Error( 'faltan_parametros', 
            __( 'Se requieren nombre_opcion y valor_opcion.', 'mis-features' ) );
    }

    $nombre_opcion = sanitize_key( $context['nombre_opcion'] );
    $valor_opcion = sanitize_text_field( $context['valor_opcion'] );

    $exito = update_option( $nombre_opcion, $valor_opcion );

    if ( ! $exito ) {
        return new WP_Error( 'actualizacion_fallida', 
            sprintf( __( 'Error al actualizar opción "%s".', 'mis-features' ), $nombre_opcion ) );
    }

    return true;
}

/**
 * Registra las features personalizadas.
 */
function mis_features_registrar() {
    if ( ! function_exists( 'wp_register_feature' ) || ! class_exists( '\WP_Feature' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>El plugin Mis Features requiere la Feature API de WordPress.</p></div>';
        } );
        return;
    }

    // --- Feature: Obtener Opción (Recurso) ---
    wp_register_feature( array(
        'id'          => 'mis-features/obtener-opcion',
        'name'        => __( 'Obtener Opción de WordPress', 'mis-features' ),
        'description' => __( 'Recupera el valor de una opción específica de WordPress.', 'mis-features' ),
        'type'        => \WP_Feature::TYPE_RESOURCE,
        'callback'    => 'mis_features_obtener_opcion_callback',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'nombre_opcion' => array(
                    'type' => 'string',
                    'description' => __( 'El nombre de la opción a recuperar.', 'mis-features' ),
                    'required' => true,
                ),
            ),
            'required' => ['nombre_opcion'],
        ),
        'output_schema' => array(
            'type' => array('string', 'integer', 'boolean', 'array', 'object', 'null'),
            'description' => __( 'El valor de la opción solicitada.', 'mis-features' ),
        ),
        'categories'  => array( 'mis-features', 'opciones', 'configuracion-sitio' ),
    ) );

    // --- Feature: Actualizar Opción (Herramienta) ---
    wp_register_feature( array(
        'id'          => 'mis-features/actualizar-opcion',
        'name'        => __( 'Actualizar Opción de WordPress', 'mis-features' ),
        'description' => __( 'Actualiza el valor de una opción específica de WordPress.', 'mis-features' ),
        'type'        => \WP_Feature::TYPE_TOOL,
        'callback'    => 'mis_features_actualizar_opcion_callback',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'nombre_opcion' => array(
                    'type' => 'string',
                    'description' => __( 'El nombre de la opción a actualizar.', 'mis-features' ),
                    'required' => true,
                ),
                'valor_opcion' => array(
                    'type' => array('string', 'integer', 'boolean', 'null'),
                    'description' => __( 'El nuevo valor para la opción.', 'mis-features' ),
                    'required' => true,
                ),
            ),
            'required' => ['nombre_opcion', 'valor_opcion'],
        ),
        'output_schema' => array(
            'type' => 'boolean',
            'description' => __( 'True si la opción se actualizó correctamente.', 'mis-features' ),
        ),
        'categories'  => array( 'mis-features', 'opciones', 'configuracion-sitio' ),
    ) );
}
add_action( 'init', 'mis_features_registrar', 20 );
```

### 📊 Parámetros y Opciones de Registro

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | string | Identificador único con namespace (ej: `mi-plugin/mi-feature`) |
| `name` | string | Nombre legible y traducible |
| `description` | string | Explicación detallada para desarrolladores e IA |
| `type` | string | `TYPE_RESOURCE` (lectura) o `TYPE_TOOL` (escritura) |
| `callback` | callable | Función PHP a ejecutar |
| `permission_callback` | callable | Determina si el usuario puede ejecutar |
| `is_eligible` | callable | Verifica si la feature está disponible |
| `input_schema` | array | Definición JSON Schema de entrada |
| `output_schema` | array | Definición JSON Schema de salida |
| `categories` | string[] | Array de categorías para organización |
| `rest_alias` | string | Alias para endpoint REST existente |

---

## 8. Ejemplos Avanzados

### 🛒 Ejemplo: Generador de Datos WooCommerce

```php
/**
 * Requiere: WordPress, WooCommerce, Feature API, WooCommerce Smooth Generator
 */

// --- Permisos y Elegibilidad ---
function wcgs_verificar_permisos() {
    return current_user_can( 'install_plugins' ) || current_user_can( 'manage_woocommerce' );
}

function wcgs_verificar_elegibilidad() {
    return function_exists( 'WC' ) && class_exists( '\WC\SmoothGenerator\Generator\Product' );
}

// --- Adaptadores de Callback ---
function wcgs_generar_producto( array $context ) {
    if ( ! class_exists( '\WC\SmoothGenerator\Generator\Product' ) ) {
        return new WP_Error( 'wcgs_falta_generador', 'Clase generador no encontrada.' );
    }
    $cantidad = $context['cantidad'] ?? 10;
    $args = wp_array_slice_assoc( $context, array( 'tipo', 'usar-terminos-existentes' ) );
    return \WC\SmoothGenerator\Generator\Product::batch( (int) $cantidad, $args );
}

function wcgs_generar_pedido( array $context ) {
    if ( ! class_exists( '\WC\SmoothGenerator\Generator\Order' ) ) {
        return new WP_Error( 'wcgs_falta_generador', 'Clase generador no encontrada.' );
    }
    $cantidad = $context['cantidad'] ?? 10;
    $args = wp_array_slice_assoc( $context, array( 'fecha-inicio', 'fecha-fin', 'estado' ) );
    return \WC\SmoothGenerator\Generator\Order::batch( (int) $cantidad, $args );
}

/**
 * Registro principal.
 */
function wc_smooth_generator_registrar_features() {
    if ( ! function_exists( 'wp_register_feature' ) ) return;

    $schema_ids = array(
        'type' => 'array',
        'items' => array( 'type' => 'integer' ),
        'description' => __( 'Array con los IDs de los items generados.', 'wc-smooth-generator' ),
    );

    // --- Generador de Productos ---
    wp_register_feature( array(
        'id' => 'wc-smooth-generator/generar-productos',
        'name' => __( 'Generar Productos WooCommerce', 'wc-smooth-generator' ),
        'description' => __( 'Genera productos WooCommerce (simple/variable).', 'wc-smooth-generator' ),
        'type' => \WP_Feature::TYPE_TOOL,
        'callback' => 'wcgs_generar_producto',
        'permission_callback' => 'wcgs_verificar_permisos',
        'is_eligible' => 'wcgs_verificar_elegibilidad',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'cantidad' => array( 'type' => 'integer', 'description' => 'Número de productos.', 'default' => 10, 'minimum' => 1 ),
                'tipo' => array( 'type' => 'string', 'enum' => array( 'simple', 'variable' ) ),
                'usar-terminos-existentes' => array( 'type' => 'boolean', 'default' => false ),
            ),
            'required' => ['cantidad']
        ),
        'output_schema' => $schema_ids,
        'categories' => array( 'wc-smooth-generator', 'generacion-datos', 'woocommerce' ),
    ) );

    // --- Generador de Pedidos ---
    wp_register_feature( array(
        'id' => 'wc-smooth-generator/generar-pedidos',
        'name' => __( 'Generar Pedidos WooCommerce', 'wc-smooth-generator' ),
        'description' => __( 'Genera pedidos WooCommerce.', 'wc-smooth-generator' ),
        'type' => \WP_Feature::TYPE_TOOL,
        'callback' => 'wcgs_generar_pedido',
        'permission_callback' => 'wcgs_verificar_permisos',
        'is_eligible' => 'wcgs_verificar_elegibilidad',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'cantidad' => array( 'type' => 'integer', 'default' => 10, 'minimum' => 1 ),
                'fecha-inicio' => array( 'type' => 'string', 'format' => 'date' ),
                'fecha-fin' => array( 'type' => 'string', 'format' => 'date' ),
                'estado' => array( 'type' => 'string', 'enum' => array( 'completado', 'procesando', 'en-espera' ) ),
            ),
            'required' => ['cantidad']
        ),
        'output_schema' => $schema_ids,
        'categories' => array( 'wc-smooth-generator', 'generacion-datos', 'woocommerce' ),
    ) );
}
add_action( 'init', 'wc_smooth_generator_registrar_features', 20 );
```

---

## 9. Referencia Rápida de la API

### 📌 Tareas Comunes en PHP

#### Registrar una Feature del Servidor
```php
function mi_api_registrar_feature_titulo() {
    if ( ! function_exists('wp_register_feature') ) return;

    wp_register_feature( array(
        'id' => 'mi-api/obtener-titulo-sitio',
        'name' => __( 'Obtener Título del Sitio', 'mi-texto-dominio' ),
        'description' => __( 'Retorna el título actual del sitio WordPress.', 'mi-texto-dominio' ),
        'type' => \WP_Feature::TYPE_RESOURCE,
        'callback' => 'get_bloginfo',
        'permission_callback' => '__return_true',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'name' => array( 'type' => 'string', 'default' => 'name' )
            )
        ),
        'output_schema' => array( 'type' => 'string' ),
        'categories' => array( 'mi-api', 'info-sitio' ),
    ) );
}
add_action( 'init', 'mi_api_registrar_feature_titulo', 20 );
```

#### Encontrar y Llamar una Feature
```php
function mi_api_usar_feature() {
    if ( ! function_exists('wp_find_feature') ) return;

    // ID completo incluye prefijo de tipo
    $feature = wp_find_feature( 'recurso-mi-api/obtener-titulo-sitio' );

    if ( $feature && $feature->is_eligible() ) {
        $titulo_sitio = $feature->call();
        if ( ! is_wp_error( $titulo_sitio ) ) {
            error_log( 'Título desde Feature API: ' . $titulo_sitio );
        }
    }

    // Ejemplo con contexto
    $feature_opcion = wp_find_feature('recurso-mi-features/obtener-opcion');
    if($feature_opcion) {
        $email_admin = $feature_opcion->call( ['nombre_opcion' => 'admin_email'] );
    }
}
```

#### Consultar Features
```php
function mi_api_buscar_features() {
    if ( ! function_exists('wp_get_features') ) return;

    // Obtener todas las features en categoría 'mi-api'
    $mis_features = wp_get_features( array(
        'categories' => array( 'mi-api' ),
    ) );

    // Obtener todas las features de tipo 'tool'
    $todas_herramientas = wp_get_features( array(
        'type' => array( \WP_Feature::TYPE_TOOL ),
    ) );
}
```

### 📌 Tareas Comunes en JavaScript

#### Registrar Feature del Cliente
```javascript
import { registerFeature } from '@automattic/wp-feature-api';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

function registrarMiFeature() {
  if (typeof registerFeature !== 'function') return;

  const miFeature = {
    id: 'mi-cliente/mostrar-aviso',
    name: 'Mostrar Aviso Informativo',
    description: 'Muestra un aviso en el área de administración.',
    type: 'tool',
    location: 'client',
    callback: (args) => {
      try {
        dispatch(noticesStore).createNotice('info', args.message || 'Mensaje por defecto', {
          isDismissible: true,
        });
        return { success: true };
      } catch (error) {
        return { success: false, error: error.message };
      }
    },
    input_schema: {
      type: 'object',
      properties: {
        message: { type: 'string', description: 'El mensaje a mostrar.' },
      },
      required: ['message'],
    },
    output_schema: {
      type: 'object',
      properties: {
        success: { type: 'boolean' },
        error: { type: 'string' },
      },
      required: ['success'],
    },
    categories: ['mi-cliente', 'ui', 'avisos'],
  };

  registerFeature(miFeature);
}
registrarMiFeature();
```

#### Ejecutar Cualquier Feature
```javascript
import { executeFeature } from '@automattic/wp-feature-api';

async function usarFeatures() {
  try {
    // Ejecutar feature del servidor
    const tituloSitio = await executeFeature('recurso-mi-api/obtener-titulo-sitio', {});
    console.log('Título:', tituloSitio);

    // Ejecutar feature del cliente
    const resultadoAviso = await executeFeature('mi-cliente/mostrar-aviso', {
      message: 'Hola desde executeFeature!',
    });
    console.log('Resultado:', resultadoAviso);

  } catch (error) {
    console.error('Error:', error);
    if(error?.message) console.error('Mensaje:', error.message);
  }
}
```

---

## 10. Configuración y Ajustes

### ⚙️ Configuración del Plugin

El plugin core de Feature API tiene configuración mínima directa.

#### Cargar Demo Plugin

```php
// En wp-config.php
define( 'WP_FEATURE_API_LOAD_DEMO', true );
```

#### Personalizar Repositorio

```php
// Usar repositorio personalizado (base de datos)
add_filter( 'wp_feature_repository', function( $repositorio_defecto ) {
    include_once( plugin_dir_path( __FILE__ ) . 'class-mi-repositorio-db.php' );
    return new Mi_Repositorio_DB_Personalizado();
} );
```

#### Adaptadores de Esquema

```php
// Personalizar adaptación de esquemas
add_filter( 'wp_feature_input_schema_adapter', function( $adaptador ) {
    include_once( plugin_dir_path( __FILE__ ) . 'class-mi-adaptador.php' );
    return new Mi_Adaptador_Personalizado();
} );
```

---

## 11. Guías de Integración

### ✅ Mejores Prácticas

| Práctica | Descripción |
|----------|-------------|
| **Namespace IDs** | Usa prefijos únicos (ej: `mi-plugin/feature`) |
| **Hook init** | Registra en `init` con prioridad > 10 |
| **Verificar Existencia** | Comprueba `function_exists()` antes de llamar |
| **Descripciones Claras** | Explica propósito, entradas y salidas |
| **Definir Esquemas** | Usa JSON Schema para entradas y salidas |
| **Implementar Permisos** | Usa `permission_callback` apropiadamente |
| **Verificar Elegibilidad** | Implementa `is_eligible` cuando sea necesario |
| **Categorizar** | Usa `categories` para organizar |

### 🔒 Seguridad

```php
// ✅ CORRECTO: Verificar permisos
wp_register_feature( array(
    'permission_callback' => function() {
        return current_user_can( 'manage_options' );
    }
) );

// ❌ EVITAR: Acceso público sin control
'permission_callback' => '__return_true' // Solo para features genuinamente públicas
```

---

## 12. Solución de Problemas

### ❓ Problemas Comunes y Soluciones

| Problema | Causa | Solución |
|----------|-------|----------|
| `wp_register_feature()` no existe | Feature API no activa | Verificar que el plugin esté instalado y activado |
| Feature no aparece en `/wp/v2/features` | Error de registro | Verificar `is_eligible`, ID correcto, y hook `init` |
| Error de permisos | Falta capacidad | Verificar `permission_callback` y capacidades del usuario |
| Error de validación | Esquema no coincide | Revisar `input_schema` y datos proporcionados |
| Feature JS no funciona | `location: 'client'` faltante | Asegurar que la feature del cliente tenga `location: 'client'` |
| REST devuelve esquema inesperado | Adaptador de esquema | Revisar filtros `wp_feature_*_schema_adapter` |

### 🐛 Depuración

```php
// Habilitar modo debug
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Verificar features registradas
function depurar_features() {
    if ( ! function_exists('wp_get_features') ) return;
    
    $features = wp_get_features();
    error_log( 'Total features: ' . count( $features ) );
    
    foreach ( $features as $feature ) {
        error_log( sprintf( 
            'Feature: %s | Elegible: %s', 
            $feature->get_id(), 
            $feature->is_eligible() ? 'Sí' : 'No' 
        ) );
    }
}
add_action( 'init', 'depurar_features' );
```

---

## 📚 Recursos Adicionales

- **Documentación General:** [README.md](README.md)
- **Diseño del Plugin:** [DESIGN.md](DESIGN.md)
- **RFC Propuestas:** [RFC.md](RFC.md)
- **Contribuciones:** [CONTRIBUTING.md](../CONTRIBUTING.md)
- **Proceso de Release:** [12.proceso-lanzamiento.md](12.proceso-lanzamiento.md)

---

*Documento generado automáticamente. Para actualizaciones, consulta el repositorio oficial de WordPress Feature API.*

