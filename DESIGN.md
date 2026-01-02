# Diseño: API de Funcionalidades de WordPress

> Este documento describe el diseño de la API de Funcionalidades de WordPress en relación con el [RFC](RFC.md).

---

## 📋 Visión General

El objetivo principal de la API de Funcionalidades es registrar fácilmente funcionalidades (en forma de `recursos` y `herramientas`) de manera que sean accesibles para IA y desarrolladores de WordPress.

Proponemos un registro accesible tanto en el cliente como en el servidor que utiliza la API WP REST para recuperar y resolver funcionalidades.

---

## 🏗️ Estructura de una Funcionalidad

```ts
type FeatureLocationTuple = ['client'] | ['server'] | ['client', 'server'];

type WP_Feature = {
	id: string; // ID con namespace
	name: string; // Nombre legible para humanos, usado para contexto de IA y etiquetas UI
	description: string; // Descripción de la funcionalidad, usada para contexto de IA
	type: 'resource' | 'tool'; // Tipo de funcionalidad
	meta?: any; // Metadatos adicionales
	categories?: string[]; // Categorías de la funcionalidad, usadas para agrupar y filtrar
	input_schema?: any; // Esquema para la entrada de la funcionalidad
	output_schema?: any; // Esquema para la salida de la funcionalidad, útil para salidas estructuradas
	callback?: (context: any) => {}; // Callback para la funcionalidad
	permissions?:
		| string
		| string[]
		| ((user: WP_User, feature: WP_Feature) => boolean); // Permisos requeridos para usar la funcionalidad
	filter?: (feature: WP_Feature) => boolean; // Filtro para determinar si la funcionalidad está disponible
	_location: FeatureLocationTuple; // Ubicación de la funcionalidad: [client], [server] o [client, server]
};
```

---

## 🖥️ Registro del Servidor

### `WP_Feature_Registry`

El registro para el servidor se realiza a través de un singleton `WP_Feature_Registry`.

#### `WP_Feature_Registry::get_instance()`

Devuelve la instancia singleton del registro.

#### `WP_Feature_Registry::register(WP_Feature $feature)`

Registra una funcionalidad y persiste sus metadatos en el repositorio.

#### `WP_Feature_Registry::unregister(string|WP_Feature $feature)`

Elimina el registro de una funcionalidad por su ID y la elimina del repositorio.

#### `WP_Feature_Registry::find(string $feature_id)`

Recupera una funcionalidad conocida por su ID.

#### `WP_Feature_Registry::get(?WP_Feature_Query $query = null)`

Consulta el registro de funcionalidades. Esto nos permite recuperar y filtrar funcionalidades basadas en los parámetros de consulta.

Un parámetro nulo devuelve todas las funcionalidades desde la caché.

Esto también puede reenviar opciones de paginación para la respuesta REST.

#### `WP_Feature_Registry::use_repository(WP_Feature_Repository_Interface $repository)`

Establece el repositorio a usar para el registro. Puede ser una tabla personalizada, reutilización de `posts`, o cualquier otro repositorio. Es la implementación la que determina cómo se almacenan y recuperan las funcionalidades.

Los datos de `WP_Feature` deben ser serializables para almacenamiento en el repositorio.

El repositorio debe ser compatible con los mecanismos de caché estándar de WordPress para mejor rendimiento.

Esto hace que consultar funcionalidades sea más fácil y eficiente que solo en la memoria del registro.

### Funciones Globales

Se deben proporcionar funciones globales para facilitar el trabajo con el registro.

Ej. `wp_register_feature(WP_Feature|array $feature)`

---

## 🌐 Registro del Cliente

El registro del cliente es un módulo `features` en el objeto global `wp`. El registro es un almacén `@wordpress/data`. Comparte la misma API que el registro del servidor. Sin embargo, en lugar de consultar `WP_Feature_Registry` (y la base de datos `WP_Feature_Repository`), consulta la API REST como intermediario hacia el registro del servidor.

### `wp.features.register(feature: WP_Feature)`

Registra una funcionalidad. Estas siempre se registran con una `_location` de `client`. Se almacenan en el almacén `wp.features`.

### `wp.features.find(feature_id: string): Promise<WP_Feature|WP_Error>`

Recupera una funcionalidad por su ID. Hay un proceso de resolución que ocurre aquí. Se hace una consulta a la API REST para recuperar cualquier funcionalidad registrada en el servidor. Estas solo existen si tienen `callback`s definidos. Las funcionalidades se marcan con ubicaciones `client` y/o `server` dependiendo del registro donde se encuentren.

> **Nota:** Cuando se ejecuta una funcionalidad `['client', 'server']`, primero se ejecutará en el servidor y su respuesta se devolverá y ejecutará como contexto para la funcionalidad del cliente.

### `wp.features.get(query: WP_Feature_Query): Promise<WP_Feature[]|WP_Error>`

Consulta el registro. Igual que `find` pero devuelve una colección de funcionalidades coincidentes según la consulta.

### [...] La misma firma que `WP_Feature_Registry`

---

## 📡 Capa de Transporte: API WP REST

La capa de transporte para nuestras funcionalidades de cliente y servidor es la API WP REST. Confiamos tanto como sea posible en las características de la API REST siempre que sea posible. Esto incluye: validación de parámetros/esquema, autenticación, formatos de respuesta, filtros y hooks para "middleware", sanitización, etc.

Una `WP_Feature` registrada con un `callback` será devuelta por la ruta REST de funcionalidades de WP. Sin callback es un `404` y se puede asumir que solo se encuentra en el registro del cliente.

La funcionalidad reenvía su `input_schema` y `permissions` para ser validado por las capacidades integradas de la API REST.

La funcionalidad reenvía su `output_schema` a la API REST para validación de respuesta a través del argumento `schema` y `rest_ensure_response`.

### Texto vs Stream

Las funcionalidades pueden ejecutarse como respuesta de texto o como stream. Por lo tanto, se registran dos endpoints para cada funcionalidad del servidor, uno para la respuesta de texto y otro para el stream SSE.

### Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/wp-json/wp/v2/features` | Consultar funcionalidades |
| `GET` | `/wp-json/wp/v2/features/{feature_id}` | Obtener datos de funcionalidad |
| `POST` | `/wp-json/wp/v2/features/{feature_id}` | Ejecutar funcionalidad |
| `POST` | `/wp-json/wp/v2/features/{feature_id}/stream` | Ejecutar funcionalidad como stream |

#### Payload para Ejecutar Funcionalidad:

```ts
{
  metadata: {
    client_features: WP_Feature[];
  },
  context: any;
}
```

#### Errores

Los errores estándar de la API REST de WP deben surfacedeerse en la respuesta de la funcionalidad.

---

## 📚 Más Detalles

Las siguientes estructuras de datos se aplican tanto al cliente como al servidor ya que compartirán una API muy similar. Por simplicidad, aquí solo discutiremos la API del servidor en PHP.

### `WP_Feature_Query`

Esta clase se usa para consultar el registro. Algunas de sus propiedades, como `categories`, `location`, `type`, etc., se usan para consultar el repositorio. Otras, especialmente los callbacks, se usan para filtrar aún más los resultados después de recuperarlos.

#### `WP_Feature_Query::query(WP_Feature_Search_Query $search)`

Este método se usa para consultar por búsqueda de palabras clave o semánticamente si hay embeddings disponibles.

### `WP_Feature`

Este es el objeto principal para las funcionalidades, como se describió anteriormente como el tipo `WP_Feature`. Además de sus propiedades, su método principal es un método `run` que ejecuta la funcionalidad dado un contexto.

#### `WP_Feature::run(array $context)`

Este método se usa para ejecutar la funcionalidad. Acepta un parámetro `context`, que se valida contra el `input_schema` de la funcionalidad. El contexto luego se pasa al `callback` de la funcionalidad para su ejecución. Antes de devolver el resultado, la salida se valida contra el `output_schema` de la funcionalidad y se devuelve un `WP_Error` si no valida.

---

## 🔮 Consideraciones Futuras

### Seguimiento del Registro del Cliente en el Servidor

Deberíamos considerar si el registro del cliente debería estar registrado centralmente en el servidor. Esto registraría solo las propiedades estáticas. Esto puede ayudar a mantener el JavaScript del cliente más pequeño y eficiente, y evita tener que pasar funcionalidades del cliente por la red al servidor, ya que están disponibles allí.

Con esto, también podemos explorar una forma unificada de registrar funcionalidades a través de un archivo como `features.json` que puede definir todos los datos estáticos de funcionalidades tanto para funcionalidades del cliente como del servidor. Puede tener dos tipos de referencias para los callbacks:

```json
{
  ...
  "client_callback": "path/to/js/callback.js", // se espera una función exportada que se puede importar dinámicamente
  "server_callback": "path/to/php/callback.php" // se espera una clase WP_Feature_Callback
}
```

### Limitación del Tamaño del Registro del Cliente

Esta API de Funcionalidades puede crecer mucho, por lo que deberíamos considerar algunos mecanismos para limitar el tamaño del registro en el cliente. Algunos pensamientos iniciales son:

- Registrar callbacks solo en el cliente, ya que las propiedades estáticas de las funcionalidades ya están registradas en el servidor
- Carga diferida de funcionalidades bajo demanda

### Embeddings de Funcionalidades para Búsqueda Semántica

Una vez que llegue el soporte de embeddings y vectores en los entornos de WordPress, deberíamos aprovechar esto para una mejor recuperación de funcionalidades relevantes. Por eso proponemos un repositorio de base de datos para los metadatos de las funcionalidades.

### Agrupación de Funcionalidades

Las funcionalidades pueden agruparse en un "conjunto de funcionalidades". Esto puede usarse para agrupar funcionalidades que están relacionadas de alguna manera y comparten configuración común.

```php
// Agrupar funcionalidades
wp_register_feature_group("woocommerce", [
    "id" => "woocommerce",
    "name" => "WooCommerce",
    "description" => "Gestionar herramientas y recursos de WooCommerce.",
    "permissions" => ["manage_woocommerce"],
    "categories" => ["woocommerce", "products"],
    "features" => [
      "woocommerce/product/report",
      "woocommerce/product/update_price",
    ],
]);
```

Esto también proporciona más disponibilidad para delimitar funcionalidades:

```tsx
const wooFeatures = wp.features.get({
	group: 'woocommerce',
});
```

### Versionado / Desaprobación de Funcionalidades

Si las funcionalidades son abundantes y se iteran sobre ellas, puede valer la pena introducir versiones de la misma funcionalidad:

```php
// Registro de funcionalidades con versión
wp_register_feature("woocommerce/product/report", [
    "id" => "woocommerce/product/report",
    "version" => "2.0.0", // Versión actual
    "deprecated_version" => "3.0.0", // Opcional: versión cuando esto será eliminado
    "since_version" => "1.0.0", // Cuándo se introdujo esta funcionalidad
    "alternatives" => ["woocommerce/product/analytics"], // Funcionalidades de reemplazo sugeridas
    "deprecated_message" => "Usa woocommerce/product/analytics en su lugar para características de informe mejoradas",

    // Soporte para múltiples versiones de la misma funcionalidad
    "versions" => [
        "1.0.0" => [
            "input_schema" => [
                "productId" => ["type" => "string"],
            ],
            "output_schema" => [
                "sales" => ["type" => "number"],
            ],
            "callback" => function($input) {
                // Implementación legacy
            }
        ],
        "2.0.0" => [
            "input_schema" => [
                "productId" => ["type" => "string"],
                "dateRange" => ["type" => "string", "enum" => ["day", "week", "month"]]
            ],
            "output_schema" => [
                "sales" => ["type" => "number"],
                "trends" => ["type" => "object"]
            ],
            "callback" => function($input) {
                // Implementación actual
            }
        ]
    ]
]);
```

Usar y manejar versionado:

```tsx
// Solicitar versión específica
const feature = wp.features.find('woocommerce/product/report', {
	version: '1.0.0', // Recurre a la última si no se encuentra
});

// Verificar si la funcionalidad está deprecada
if (feature.isDeprecated()) {
	console.warn(
		`Feature ${feature.id} is deprecated. ${feature.deprecated_message}`
	);
}

// Obtener alternativas sugeridas
const alternatives = feature.getAlternatives();
```

Notificar o silenciar desaprobación:

```php
// Notificar cuando se usa una funcionalidad deprecada
add_action('wp_feature_deprecated_run', function(
    string $feature_id,
    string $version_used,
    string $deprecated_version,
    array $alternatives
) {
    _deprecated_function(
        sprintf('Feature: %s (v%s)', $feature_id, $version_used),
        $deprecated_version,
        sprintf('Use one of: %s', implode(', ', $alternatives))
    );
});

// Filtro para modificar comportamiento de desaprobación
add_filter('wp_feature_handle_deprecated', function(
    bool $should_run,
    string $version_used,
    WP_Feature $feature,
) {
    // Opcionalmente prevenir que funcionalidades deprecadas se ejecuten
    if ($feature->is("some_feature") && $version_used < '2.0.0') {
        return false;
    }
    return $should_run;
}, 10, 3);
```

Añadir información de versión a las cabeceras de respuesta REST:

```php
// Añadir información de versión a las cabeceras de respuesta REST
add_filter('wp_feature_rest_response', function($response, $feature) {
    $response->header('X-WP-Feature-Version', $feature->version);
    if ($feature->isDeprecated()) {
        $response->header('X-WP-Feature-Deprecated', 'true');
        $response->header('X-WP-Feature-Alternatives', implode(',', $feature->alternatives));
    }
    return $response;
}, 10, 2);
```

---

## 🔗 Alias de WP REST

La API REST ya comparte mucha de la misma funcionalidad que estamos tratando de exponer en la API de Funcionalidades. Podemos aprovechar esto en cierta medida para evitar duplicar funcionalidad definiendo alias.

Si una funcionalidad se registra con un `rest_alias` que corresponde a una ruta REST, entonces la funcionalidad usará cualquier propiedad de la ruta REST como sus propias propiedades, como el callback, args, esquema y permisos.

```php
wp_register_feature("posts", [
    "id" => "posts",
    "is_rest_alias" => true,
    "description" => "Consultar y obtener objetos de entradas de WordPress.",
	"type" => "resource",
]);
```

---

## 📊 Resumen de la Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                     API de Funcionalidades de WordPress           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐      ┌─────────────────┐                   │
│  │   Cliente (JS)  │      │   Servidor (PHP) │                   │
│  │                 │      │                  │                   │
│  │ wp.features.*   │◄────►│ WP_Feature_      │                   │
│  │ - register()    │ REST │ Registry         │                   │
│  │ - find()        │ API  │ - register()     │                   │
│  │ - get()         │      │ - find()         │                   │
│  │                 │      │ - get()          │                   │
│  └─────────────────┘      └─────────────────┘                   │
│           │                        │                            │
│           │    ┌───────────────────┘                            │
│           │    │                                                │
│           ▼    ▼                                                │
│    ┌─────────────────────────────────────────────┐              │
│    │           WP_Feature_Repository              │              │
│    │    (Almacenamiento: BD, Posts, Caché)       │              │
│    └─────────────────────────────────────────────┘              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

