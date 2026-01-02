# Documentación de la API de Funcionalidades de WordPress (Español)

> **Documentación completa de la API de Funcionalidades de WordPress en español.**

---

## Acerca de Esta Documentación

Esta documentación proporciona una guía completa sobre la **API de Funcionalidades de WordPress**, un sistema diseñado para registrar, descubrir y ejecutar funcionalidad del lado del servidor y del cliente dentro de WordPress, destinado principalmente para uso por agentes de IA y otros sistemas programáticos.

Esta es la versión en **español** de la documentación. [Ver documentación en inglés](/docs/).

---

## Tabla de Contenidos

### Primeros Pasos

| Capítulo | Título | Descripción |
|----------|--------|-------------|
| 1 | **[Introducción y Descripción General](/docs/es/1.introduccion.md)** | Conceptos fundamentales, objetivos y relación con MCP. |
| 2 | **[Comenzando](/docs/es/2.comenzando.md)** | Opciones de instalación y primer ejemplo de uso. |

### Uso de la API

| Capítulo | Título | Descripción |
|----------|--------|-------------|
| 3 | **[Registrar Funcionalidades](/docs/es/3.registrar-funcionalidades.md)** | Guía completa para registrar funcionalidades con `wp_register_feature()`. |
| 4 | **[Usar Funcionalidades](/docs/es/4.usar-funcionalidades.md)** | Cómo descubrir y ejecutar funcionalidades registradas. |
| 5 | **[Puntos Finales REST](/docs/es/5.puntos-finales-rest.md)** | Referencia completa de los endpoints REST API. |
| 6 | **[Categorías](/docs/es/6.categorias.md)** | Organizar y agrupar funcionalidades relacionadas. |

### Temas Avanzados

| Capítulo | Título | Descripción |
|----------|--------|-------------|
| 7 | **[Funcionalidades del Lado del Cliente](/docs/es/7.funcionalidades-cliente.md)** | Registrar y usar funcionalidades JavaScript. |
| 8 | **[Temas Avanzados](/docs/es/8.temas-avanzados.md)** | Repositorios, adaptadores de esquema y composabilidad. |

### Extensión y Contribución

| Capítulo | Título | Descripción |
|----------|--------|-------------|
| 9 | **[Extender y Contribuir](/docs/es/9.extender-contribuir.md)** | Guía para desarrolladores de terceros. |
| 10 | **[Protocolo MCP](/docs/es/10.protocolo-mcp.md)** | Integración con el Model Context Protocol. |

### Recursos Adicionales

| Capítulo | Título | Descripción |
|----------|--------|-------------|
| 11 | **[Demostración](/docs/es/11.demo.md)** | Explora el plugin de demostración del agente de IA. |
| 12 | **[Proceso de Lanzamiento](/docs/es/12.proceso-lanzamiento.md)** | Cómo se distribuyen nuevas versiones. |

---

## Inicio Rápido

### Instalación con Composer

```json
{
  "require": {
    "automattic/wp-feature-api": "^0.1.8"
  }
}
```

### Registrar tu Primera Funcionalidad

```php
<?php
add_action( 'wp_feature_api_init', 'mi_plugin_registrar_funcionalidades' );

function mi_plugin_registrar_funcionalidades() {
    wp_register_feature( array(
        'id'          => 'miplugin/saludo',
        'name'        => __( 'Saludo Personalizado', 'mi-plugin' ),
        'description' => __( 'Devuelve un saludo personalizado.', 'mi-plugin' ),
        'type'        => WP_Feature::TYPE_RESOURCE,
        'callback'    => function() {
            return '¡Hola desde la API de Funcionalidades!';
        },
        'permission_callback' => '__return_true',
    ) );
}
```

---

## Conceptos Clave

### Tipos de Funcionalidades

| Tipo | Constante | Descripción |
|------|-----------|-------------|
| **Recurso** | `WP_Feature::TYPE_RESOURCE` | Para recuperar datos (solo lectura). |
| **Herramienta** | `WP_Feature::TYPE_TOOL` | Para realizar acciones o modificar datos. |

### Ubicación de Funcionalidades

| Ubicación | Descripción |
|-----------|-------------|
| **Servidor** | Funcionalidades PHP ejecutadas en el backend de WordPress. |
| **Cliente** | Funcionalidades JavaScript ejecutadas en el navegador del usuario. |

### Elementos de una Funcionalidad

```
┌─────────────────────────────────────────────────────┐
│                  WP_Feature                          │
├─────────────────────────────────────────────────────┤
│  id              │ Identificador único               │
│  name            │ Nombre legible                    │
│  description     │ Descripción detallada             │
│  type            │ resource | tool                   │
│  callback        │ Función PHP o JS a ejecutar       │
│  input_schema    │ Esquema de parámetros de entrada  │
│  output_schema   │ Esquema del valor de retorno      │
│  permission_callback │ Verificación de permisos     │
│  is_eligible     │ Verificación de disponibilidad    │
│  categories      │ Array de categorías               │
└─────────────────────────────────────────────────────┘
```

---

## Recursos para Desarrolladores

### Paquetes NPM

| Paquete | Descripción |
|---------|-------------|
| [`@automattic/wp-feature-api`](https://www.npmjs.com/package/@automattic/wp-feature-api) | SDK del lado del cliente. |

### Paquetes Composer

| Paquete | Descripción |
|---------|-------------|
| [`automattic/wp-feature-api`](https://packagist.org/packages/automattic/wp-feature-api) | API de Funcionalidades para plugins. |

### Plugins de Ejemplo

| Plugin | Descripción |
|--------|-------------|
| [`demo/wp-feature-api-agent/`](/demo/wp-feature-api-agent/) | Demostración de agente de IA. |

---

## Integración con Agentes de IA

La API de Funcionalidades está diseñada para ser consumida por agentes de IA:

1. **Registro** → Los plugins/temas registran funcionalidades.
2. **Descubrimiento** → Los agentes descubren capacidades disponibles.
3. **Ejecución** → Los agentes invocan funcionalidades para realizar acciones.
4. **Protocolos** → MCP Adapter permite integración con varios protocolos de IA.

### Adaptadores Disponibles

| Adaptador | Protocolo | Enlace |
|-----------|-----------|--------|
| wordpress-mcp | Model Context Protocol | [GitHub](https://github.com/Automattic/wordpress-mcp) |

---

## Contribuir

¿Deseas contribuir a la documentación en español?

1. Haz fork del repositorio.
2. Edita los archivos en `docs/es/`.
3. Envía un pull request.

### Convenciones de Traducción

| Término en Inglés | Término en Español |
|-------------------|-------------------|
| Feature | Funcionalidad |
| Resource | Recurso |
| Tool | Herramienta |
| Callback | Callback / Función de retorno |
| Schema | Esquema |
| Registry | Registro |
| Repository | Repositorio |
| Eligibility | Elegibilidad |
| Hook | Hook / Gancho |

---

## Licencia

Este proyecto está licenciado bajo la licencia GPL v2 o posterior.

---

## Enlaces Rápidos

| Recurso | Enlace |
|---------|--------|
| Repositorio Principal | [GitHub](https://github.com/Automattic/wordpress-feature-api) |
| Paquete NPM | [npmjs.com](https://www.npmjs.com/package/@automattic/wp-feature-api) |
| Packagist | [packagist.org](https://packagist.org/packages/automattic/wp-feature-api) |
| Issues | [GitHub Issues](https://github.com/Automattic/wordpress-feature-api/issues) |

---

> **¿Preguntas?** Revisa los capítulos individuales o abre un issue en GitHub.

