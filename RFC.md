# RFC: API de Funcionalidades de WordPress

> **Resumen de la Propuesta:** La API de Funcionalidades de WordPress es un sistema propuesto para exponer funcionalidad del lado del servidor y del cliente en WordPress para uso en, pero no exclusivo de, LLMs con el fin de mejorar los sistemas de WordPress agénticos.

---

## 📝 Resumen

La **API de Funcionalidades de WordPress** es un sistema propuesto para exponer funcionalidad del lado del servidor y del cliente en WordPress para uso en, pero no exclusivo de, LLMs con el fin de mejorar los sistemas agénticos de WordPress. Se centra en la detectabilidad y ejecución tanto en el servidor como en el cliente. En su núcleo, es un registro muy accesible de funcionalidades definidas de WordPress en forma de recursos y herramientas.

Proponemos aprovechar la API WP REST existente para potenciar la funcionalidad subyacente de la API de Funcionalidades y proporcionar un módulo JavaScript `wp.features` para uso del lado del cliente.

---

## ❌ Lo Que NO Es

Esto **no** es un intento de introducir características de IA y LLMs en WordPress. Eso es una discusión separada, aunque buena y necesaria. Sin embargo, el consumidor previsto de la API de Funcionalidades **sí** son los LLMs y sistemas agénticos que se construirán en WordPress. Como tal, no está restringido solo para uso por LLMs, y ofrece una forma estándar y distribuida de exponer la funcionalidad de WP en todo WordPress.

---

## ⚠️ Problema

Los LLMs carecen de una forma formal de interactuar con WordPress, ya sea que se llamen desde el cliente o el servidor. Este problema ha sido abordado por estándares emergentes como [MCP](https://modelcontextprotocol.io/) que hacen que el software potenciado por LLM del cliente sea consciente de recursos y herramientas externos.

En el caso de WordPress, tenemos control de extremo a extremo del servidor y el cliente, por lo tanto, esto es un medio para facilitar la exposición de la funcionalidad de WP a través de él, inspirado en conceptos de MCP para alinearse con el ecosistema de IA más amplio.

Hay mucho potencial en funcionalidades compartidas para WordPress que son bastante simples y fáciles de implementar:

| Funcionalidad | Descripción |
|---------------|-------------|
| 🔧 Actualizar opciones del sitio | Modificar configuraciones globales |
| 🎨 Personalizar estilos | Cambiar la apariencia del tema |
| 🔍 Buscar entradas | Consultar contenido del sitio |
| ✏️ Actualizar una entrada | Modificar contenido existente |
| ... | Y muchas más |

La lista es interminable. Pero todas son fáciles de definir y útiles para los usuarios de WordPress, siendo la IA uno de ellos. Dadas algunas propiedades clave, como descripciones y esquemas estructurados para describir la funcionalidad, la IA es bastante capaz de usarlas al igual que lo haría un desarrollador humano en WordPress.

### Preguntas Clave

Esto plantea tres preguntas principales que este RFC pretende abordar:

| # | Pregunta | Descripción |
|---|----------|-------------|
| 1 | **Detectabilidad** | ¿Cómo hacemos que las funcionalidades sean detectables en el cliente y servidor? |
| 2 | **Ejecución** | ¿Cómo hacemos que las funcionalidades sean ejecutables en el cliente y servidor? |
| 3 | **Facilidad de uso** | ¿Cómo hacemos esto lo más fácil posible para los desarrolladores? |

---

## ✨ Características Principales

| Característica | Descripción |
|----------------|-------------|
| 🔄 **Registro Centralizado** | Registro centralizado de funcionalidades accesible por cliente y servidor, consolidado bajo una única API |
| 🧩 **Registro Fácil** | Fácil de registrar funcionalidades en cualquier lugar de WordPress |
| 🎯 **Scoped y Filtrable** | Delimitables y filtrables para enfocarse en las funcionalidades más relevantes para la llamada específica del LLM |
| 🔗 **Composable** | Las funcionalidades pueden componerse para crear flujos de trabajo más complejos |

---

## 💡 Casos de Uso

Veamos algunos casos de uso comunes para abordar cómo podemos usar la API de Funcionalidades para resolverlos. Dado que somos agnósticos a cómo llamamos a un LLM, usaré `vercel/ai` como ejemplo para el cliente, y un hipotético SDK de IA del lado del servidor `ai()`.

---

## 🤖 LLMs del Lado del Cliente

Imagina un modelo de IA operando en WordPress:

```tsx
import { generateText } from 'ai';
import { openai } from '@ai-sdk/openai';

const userMessage = 'I want to update my post';

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	system: 'You are a friendly WordPress assistant!',
	prompt: userMessage,
} );
```

¿Cómo hacemos que este modelo sea consciente de todas las funcionalidades de WordPress y las use?

### Registrar Funcionalidades

```tsx
import { generateText } from 'ai';
import { openai } from '@ai-sdk/openai';

// Registrar funcionalidades globalmente
wp.features.register( 'editor/go_to_post', {
	type: 'tool', // o resource, prestado de MCP
	description: 'Navega a una entrada cuando está en el editor de WordPress.',
	input_schema: z.object( { postId: z.string() } ),
	callback: async ( input ) => {
		document.location.href = `post.php?post=${ input.postId }&action=edit`;
	},
} );

// Obtiene todas las funcionalidades del registro
const features = wp.features.get();

// Reformatea nuestras funcionalidades como herramientas para el LLM
const toolsFromFeatures = function ( features ) {
	return features.map( ( { id, name, description, input_schema } ) => ( {
		id,
		name,
		description,
		parameters: input_schema,
		execute: async ( input ) => {
			return await wp.features.run( id, input );
		},
	} ) );
};

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	tools: toolsFromFeatures( features ),
	system: `You are a friendly WordPress assistant! You must not make up tool parameters, always ask the user for the information needed, or use other tools to get the information needed.`,
	prompt: 'I want to update my post',
} );
```

### Recursos vs Herramientas

Como ves, estamos tomando prestado la terminología de MCP para el `type` de funcionalidad:

| Tipo | Comportamiento | Analogía |
|------|----------------|----------|
| **Herramienta (tool)** | Accionable, tiene efectos | Similar a POST requests |
| **Recurso (resource)** | Pasivo, proporciona contexto | Similar a GET requests |

Los recursos se usan para proporcionar más contexto. Por esta razón, los recursos a menudo se registran del lado del servidor, porque pueden exponer datos sobre la API REST.

---

## 🔍 Obtener ID de Entrada

Necesitaremos un ID de entrada para el ejemplo anterior. Podemos preguntarle al usuario, o podemos proporcionar otra herramienta, proporcionada a través de un Recurso de Funcionalidad, que se puede usar para obtener el ID de entrada.

### Registrar Recurso de Búsqueda

```php
wp_register_feature('core/posts/search', [
    'name' => 'Buscar Entradas',
    'description' => 'Busca entradas por título, contenido o slug.',
    'type' => 'resource',
    'input_schema' => [
        'query' => [
          'type' => 'string',
          'description' => 'Una consulta simple para buscar entradas por título, contenido o slug. Cuanto más simple y corta sea, mejor para tener un acierto.',
        ],
    ],
    'output_schema' => [
        'posts' => [
          'type' => 'array',
          'items' => ['type' => 'object', 'properties' => [
            'id' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
          ]],
        ],
    ],
]);
```

Ahora la IA debería responder al usuario preguntando qué entrada y condensar eso en una consulta simple para buscar en el recurso de entradas, y pasarlo a la herramienta `editor/go_to_post` con el ID de entrada obtenido.

---

## 🛒 Ejemplo con WooCommerce

Veamos cómo registrar funcionalidades específicas de plugins, como WooCommerce:

### Registrar Funcionalidades de Producto

```php
wp_register_feature('woocommerce/product/report', [
    'name' => 'Informe de Producto WooCommerce',
    'description' => 'Obtiene un informe de producto de WooCommerce por ID.',
    'type' => 'resource',
    'input_schema' => [
        'productId' => ['type' => 'string'],
    ],
    'output_schema' => [
        'name' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'attributes' => ['type' => 'array'],
        'sales_by_month' => [
          'type' => 'array',
          'items' => ['type' => 'object', 'properties' => [
            'month' => ['type' => 'string'],
            'sales' => ['type' => 'number'],
          ]],
        ],
    ],
    'callback' => function (array $input) {
        $product = get_post($input['productId']);
        $sales = get_post_meta($product->ID, '_total_sales', true);
        $sales_by_month = get_post_meta($product->ID, '_sales_by_month', true);

        return [
            'name' => $product->post_title,
            'description' => $product->post_content,
            'sales' => $sales,
            'sales_by_month' => $sales_by_month,
        ];
    },
]);

wp_register_feature('woocommerce/products', [
    'name' => 'Productos WooCommerce',
    'description' => 'Obtiene una lista de productos de WooCommerce.',
    'type' => 'resource',
    'output_schema' => [
        'products' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id' => ['type' => 'string'],
              'name' => ['type' => 'string'],
              'description' => ['type' => 'string'],
              'price' => ['type' => 'number'],
            ],
          ],
        ],
    ],
]);
```

### Usar la Funcionalidad desde el Cliente

Ahora que hemos registrado este recurso, el cliente puede usarlo:

```tsx
const feature = wp.features.find( 'woocommerce/product/report' );
const report = await feature.run(
	{
		productId: '123',
	},
	// opciones
	{
		stream: false,
	}
);
```

Esto llama al endpoint de la API REST que ha sido registrado por la funcionalidad. Los parámetros de solicitud se validan contra el esquema de entrada de la funcionalidad y el resultado se valida contra el esquema de salida antes de ser devuelto como respuesta.

---

## 💬 Mensaje Enriquecido con IA

Ahora podemos registrar una funcionalidad del cliente para mostrar un mensaje enriquecido al usuario que renderiza un informe de producto de WooCommerce.

```tsx
wp.features.register( 'woocommerce/rich_message/report', {
	id: 'woocommerce/rich_message/report',
	name: 'Informe de Mensaje Enriquecido WooCommerce',
	type: 'tool',
	description:
		'Muestra un mensaje enriquecido al usuario que renderiza un informe de producto de WooCommerce.',
	input_schema: z.object( {
		userMessage: z.string(),
		productId: z.string(),
	} ),
	output_schema: z.object( {
		message: z.string(),
		report: z.object( {
			name: z.string(),
			description: z.string(),
			total: z.number(),
			monthly: z.array(
				z.object( {
					month: z.string(),
					amount: z.number(),
				} )
			),
		} ),
	} ),
	callback: generateRichMessageReport,
} );

async function generateRichMessageReport( context, feature ) {
	const { userMessage, productId } = context;
	const { output_schema } = feature;

	const productReportResource = wp.features.find(
		'woocommerce/product/report'
	);
	const report = await productReportResource.run( { productId } );

	// Usar el LLM para generar un mensaje enriquecido que cumpla con el esquema de salida de esta funcionalidad
	const { text } = await generateText( {
		model: openai( 'gpt-4o' ),
		prompt: `You are a friendly WordPress assistant! Generate a rich message to the user that renders a WooCommerce product report and a response to the user's message.

    User message: ${ userMessage }

    Product report: ${ JSON.stringify( report ) }`,
		schema: output_schema,
	} );

	return text;
}
```

---

## 🖥️ LLMs del Lado del Servidor

Hasta ahora hemos estado considerando IA del lado del cliente para demostrar cómo se pueden usar con funcionalidades del servidor y cliente. Sin embargo, en muchos casos querremos llamar a nuestra IA desde el servidor para no exponer demasiado en el cliente, como nuestras claves de API y prompts.

Para hacer esto, podemos definir una funcionalidad impulsada por IA del lado del servidor como punto de entrada para ser llamada directamente desde el cliente.

### Llamar desde el Cliente

```tsx
const feature = wp.features.find( 'woocommerce/product/report' );
const report = await feature.run( { productId: '123' } );
```

### Registrar como Funcionalidad del Servidor

```php
wp_register_feature("woocommerce/rich_message/report", [
  "name" => "Informe de Mensaje Enriquecido WooCommerce",
  "description" => "Muestra un mensaje enriquecido al usuario que renderiza un informe de producto de WooCommerce.",
  "type" => "tool",
  "input_schema" => array(
    "userMessage" => array(
      "type" => "string",
    ),
    "productId" => array(
      "type" => "string",
    ),
  ),
  "output_schema" => array(
    "message" => array(
      "type" => "string",
    ),
    "report" => array(
      "type" => "object",
      "properties" => array(
        "name" => array(
          "type" => "string",
        ),
        "description" => array(
          "type" => "string",
        ),
        "total" => array(
          "type" => "number",
        ),
        "monthly" => array(
          "type" => "array",
          "items" => array(
            "type" => "object",
            "properties" => array(
              "month" => array(
                "type" => "string",
              ),
              "amount" => array(
                "type" => "number",
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  "callback" => function (array $context, WP_Feature $feature) {
    $report_resource = wp_get_feature("woocommerce/product/report", array('type' => 'resource'));
    $report = $report_resource->call($context);

    // Ayudante de Prompt hipotético
    $prompt = Prompt::find('woocommerce/rich_message/report')->set_context([
      'userMessage' => $context['userMessage'],
      'productReport' => $report,
    ]);

    return ai()->generate_text()->prompt($prompt)->response()->to_array();
  },
]);
```

---

## 🎯 Alcance de Funcionalidades

Podemos imaginar que se registran muchas funcionalidades, por lo que siempre pasar tantas a nuestro LLM no sería factible. Necesitamos buenas formas de filtrar a las funcionalidades más relevantes para nuestras necesidades.

---

### 📁 Categorías de Funcionalidades

Al registrar una funcionalidad, proporciona una categoría para ella. Esto puede especificarse para la recuperación:

```php
wp_register_feature("woocommerce/product/report", [
  "name" => "Informe de Producto WooCommerce",
  "description" => "Obtiene un informe de producto de WooCommerce por ID.",
  "type" => "resource",
  "categories" => ["woocommerce", "reporting"],
]);
```

```tsx
const features = wp.features.get( { categories: [ 'woocommerce' ] } );
```

---

### 🔍 Filtros

Para situaciones donde la disponibilidad de la funcionalidad se determina dinámicamente según el estado de la solicitud, proporciona un callback que devuelve un booleano para filtrar la funcionalidad:

```php
wp_register_feature("woocommerce/product/report", [
  "id" => "woocommerce/product/report",
  "name" => "Informe de Producto WooCommerce",
  "description" => "Obtiene un informe de producto de WooCommerce por ID.",
  "type" => "resource",
  "filter" => function (WP_Feature $_feature) {
    return is_woocommerce();
  },
]);
```

O para el cliente, filtrar por estado del cliente:

```tsx
wp.features.register( 'core/blocks/edit_color', {
	type: 'tool',
	description:
		'Edita el color de un bloque para el fondo o el texto.',
	filter: () => {
		const selectedBlockClientId =
			select( blockEditorStore ).getSelectedBlockClientId();

		if ( ! selectedBlockClientId ) {
			return false;
		}

		const selectedBlock = select( blockEditorStore ).getBlock(
			selectedBlockClientId
		);
		const blockType = select( 'core/blocks' ).getBlockType(
			selectedBlock.name
		);

		const hasBackgroundColorSetting =
			blockType?.supports?.color?.background || false;
		const hasTextColorSetting = blockType?.supports?.color?.text || false;

		return hasBackgroundColorSetting || hasTextColorSetting;
	},
} );
```

Ahora obtenemos filtrado fuera de la caja cuando recuperamos funcionalidades:

```tsx
const features = wp.features.get( {
	// true por defecto, así que esto no es necesario para que el filtrado se aplique.
	filter: true,
} );
```

---

### 🔗 Coincidencia de Esquema

También podemos hacer coincidir funcionalidades basadas en el esquema de entrada o salida, lo cual es útil cuando ya hemos construido algún contexto y queremos funcionalidades que coincidan con lo que tenemos disponible.

```php
wp_register_feature("bigsky/blocks/edit_color", [
  "name" => "Editar Color del Bloque",
  "description" => "Edita el color de un bloque para el fondo o el texto.",
  "type" => "feature",
  "input_schema" => array(
    "color" => array(
      "type" => "string",
    ),
    "blockId" => array(
      "type" => "number",
    ),
  ),
  "output_schema" => array(
    "block" => array(
      ...
    ),
  ),
  ...
]);
```

Esto devolverá solo las funcionalidades que coincidan con el contexto proporcionado del cliente, en este caso funcionalidades que tienen una propiedad blockId.

```tsx
const ctx = {
	message: 'I want to edit the color of the block',
	blockId: 123,
};

const features = wp.features.get( {
	context: { infer: ctx, strict: false },
} );
```

---

### 🔎 Consulta de Funcionalidades

Es posible que queramos filtrar nuestras funcionalidades basadas en la consulta del usuario. Para esto, podemos hacer dos tipos de búsqueda:

| Tipo | Descripción |
|------|-------------|
| 🔤 **Búsqueda por palabras clave** | Coincidencia exacta de términos |
| 🧠 **Búsqueda semántica** | Coincidencia por significado (requiere embeddings) |

> **Nota:** Las funcionalidades registradas deben tener un repositorio correspondiente para sus metadatos, como la base de datos, posiblemente reutilizando la tabla `posts`. Esto es para ayudar en la consulta cuando hay muchas funcionalidades registradas, o búsqueda semántica cuando esa característica eventualmente llegue a WordPress.

#### Búsqueda Semántica

```tsx
const userMessage = 'I want to edit the color of the block';

const { embedding } = await embed( {
	model: openai.embedding( 'text-embedding-3-small' ),
	value: userMessage,
} );

const features = wp.features.get( {
	query: { semantic: { embedding } },
} );

const { text } = await generateText( {
	model: openai( 'gpt-4o' ),
	tools: toolsFromFeatures( features ),
	prompt: userMessage,
} );
```

O de forma más sencilla:

```tsx
const userMessage = 'I want to edit the color of the block';

const features = wp.features.get( {
	query: { semantic: { text: userMessage } },
} );
```

---

## 🔄 Conciencia Cliente/Servidor

Dado que el registro de funcionalidades puede ocurrir en el servidor, cliente o ambos, surge la pregunta de cómo podemos hacer que ambos registros sean conscientes del otro.

### Servidor → Cliente

Cada vez que se llama a `wp.features.get`, obtiene la lista de funcionalidades del servidor sobre REST. Esto puede filtrarse según los criterios del usuario.

### Cliente → Servidor

Para funcionalidades registradas solo del cliente, puedes simplemente compartir las funcionalidades del cliente como contexto con tu llamada:

```tsx
const features = wp.features.get( { location: 'client' } );
const feature = wp.features.find( 'bigsky/assistant_router' );
const result = await feature.run( {
	features: features.map( ( { id, description } ) => ( {
		id,
		description,
	} ) ),
	message: 'I want to edit the color of the block',
} );
```

O automáticamente con `shareFeatures`:

```tsx
const tool = await feature.run(
	{
		message: 'I want to edit the color of the block',
	},
	{
		shareFeatures: true,
	}
);
```

---

## 🎛️ Integración con APIs Existentes de WordPress (Command Palette)

Ya existen algunas funcionalidades muy útiles en WordPress que se integran bien con este sistema, una de ellas siendo la paleta de comandos del editor.

### Registrar Funcionalidades desde Comandos

```js
const command = {
	id: 'custom-command/clear-content',
	name: 'Clear Content',
	label: __( 'Clear all content' ),
	icon: 'trash',
	callback: ( { close } ) => {
		if ( confirm( 'Are you sure you want to clear all content?' ) ) {
			wp.data.dispatch( 'core/block-editor' ).resetBlocks( [] );
			createInfoNotice( 'Content cleared!', { type: 'snackbar' } );
		}
		close();
	},
};
const cmdSchema = z
	.object( {
		close: z.function().optional(),
		open: z.function().optional(),
		isOpen: z.function().optional(),
		search: z.function().optional(),
		history: z.function().optional(),
	} )
	.optional();

useCommand( command );

registerFeature( command.id, {
	name: command.name,
	type: 'tool',
	description: 'Clears all content from the editor.',
	category: [ 'editor', 'command-palette' ],
	input_schema: cmdSchema,
	filter: () => {
		return window.wp.editor !== undefined;
	},
	callback: async ( props, _feature ) => {
		return command.callback( props );
	},
} );
```

---

## 🔐 Permisos

Por supuesto, necesitaremos asegurarnos de que las funcionalidades sean permisivas. Esto solo se aplicaría a las funcionalidades del lado del servidor, ya que las funcionalidades del lado del cliente ya están limitadas por el contexto del cliente.

Para funcionalidades del lado del servidor, hay una propiedad `permissions` que puede usarse para especificar las funcionalidades disponibles para el usuario autenticado.

### Permisos por Capacidades

```php
wp_register_feature("woocommerce/product/report", [
  "name" => "Informe de Producto WooCommerce",
  "description" => "Obtiene un informe de producto de WooCommerce por ID.",
  "type" => "resource",
  "permissions" => ["manage_woocommerce"],
]);
```

Pasar un array por defecto verifica las capacidades del usuario actual, o una cadena para verificar su rol.

### Permisos Personalizados

```php
wp_register_feature("woocommerce/product/report", [
  "name" => "Informe de Producto WooCommerce",
  "description" => "Obtiene un informe de producto de WooCommerce por ID.",
  "permissions" => function (WP_User $user, WP_Feature $_feature) {
    return $user->has_cap("manage_woocommerce");
  },
]);
```

O mediante un hook de filtro:

```php
add_filter('feature_woocommerce_product_report_user_can', function(WP_User $user, WP_Feature $feature) {
  return $user->has_cap("manage_woocommerce");
}, 10, 3);
```

---

## ⚖️ Reglas de Separación Cliente/Servidor

Las funcionalidades del servidor y cliente se mantienen separadas pero se consolidan cuando se ejecutan:

| Regla | Descripción |
|-------|-------------|
| 📦 Funcionalidades del servidor | Mantenidas bajo una colección `server` |
| 📦 Funcionalidades del cliente | Mantenidas bajo una colección `client` |
| 🔄 Sin callback en servidor | Consideradas del cliente, esperarán que se registre una funcionalidad del cliente |
| ⚡ Ambos tienen callback | El servidor se llama primero, luego el cliente con el resultado del servidor |
| 🎯 Solo callback del cliente | Llamado exclusivamente desde el cliente |

---

## 🛠️ Personalización

Para integrarse con funcionalidades y personalizar su comportamiento, estarían disponibles varios hooks y filtros. Gran parte de la funcionalidad proviene de la API REST, por lo que podemos envolver muchos de los filtros/hooks REST actuales para el sistema de funcionalidades.

### Filtros de Ejemplo

```php
// Filtro global para entrada de funcionalidad antes de ejecución
apply_filters('feature_pre_run', array $context, WP_Feature $feature);
apply_filters("feature_{$feature_id}_pre_run", array $context, WP_Feature $feature);
```

### "Middleware" de Funcionalidades usando Hooks

```php
// Middleware de rate limiting para funcionalidades intensivas en recursos
add_filter('feature_core_update_post_pre_run', function($context, WP_Feature $feature) {
  $transient_key = "feature_rate_limit_{$feature->id}";
  $rate_limit = get_transient($transient_key);

  if ($rate_limit && $rate_limit >= 100) { // 100 solicitudes máximo
    return new WP_Error(
      "rate_limit_exceeded",
      "Rate limit exceeded for this feature",
      ["status" => 429]
    );
  }

  set_transient(
    $transient_key,
    ($rate_limit ? $rate_limit + 1 : 1),
    HOUR_IN_SECONDS
  );

  return $context;
}, 10, 2);

// Middleware de logging
add_action('feature_post_run', function(WP_Feature $feature, $output, $context) {
  error_log(sprintf(
    "Feature %s executed with context %s and output %s",
    $feature->id,
    wp_json_encode($context),
    wp_json_encode($output)
  ));
}, 10, 4);
```

---

## 📊 Resumen de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                    API de Funcionalidades de WordPress                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   ┌────────────────────┐          ┌────────────────────┐            │
│   │   Cliente (JS)     │          │   Servidor (PHP)   │            │
│   │                    │          │                    │            │
│   │  wp.features.*     │◄────┬───►│ WP_Feature_Registry│            │
│   │  - register()      │ REST│    │  - register()      │            │
│   │  - find()          │ API │    │  - find()          │            │
│   │  - get()           │     │    │  - get()           │            │
│   │  - run()           │     │    │  - run()           │            │
│   └────────────────────┘     │    └────────────────────┘            │
│                              │                                      │
│                              │    ┌────────────────────┐            │
│                              └───►│ WP_Feature_Query   │            │
│                                   │ Búsqueda/Filtering │            │
│                                   └────────────────────┘            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ✅ Conclusión

La API de Funcionalidades de WordPress proporciona:

1. **Detectabilidad** - Registro centralizado accesible desde cliente y servidor
2. **Ejecutabilidad** - API unificada para ejecutar funcionalidades en cualquier lugar
3. **Facilidad de uso** - API simple y bien documentada para desarrolladores

Este enfoque permite que tanto humanos como sistemas de IA interactúen con WordPress de manera estandarizada y eficiente.

