# Plan de Implementación

## Objetivo
Habilitar la configuración de API tokens (OpenAI y OpenRouter) y el chat de IA inmediatamente después de instalar el plugin, sin necesidad de configuración manual.

## Problema Identificado
- El plugin demo (`wp-feature-api-agent/`) contiene toda la funcionalidad necesaria
- Pero NO se cargaba automáticamente - requería definir `WP_FEATURE_API_LOAD_DEMO` en wp-config.php

## Solución Implementada ✅

### 1. Modificar `wp-feature-api.php`
- ~~Cambiar `WP_FEATURE_API_LOAD_DEMO` de `false` a `true` por defecto~~
- ~~Esto cargará automáticamente el plugin demo con:~~
  - ~~Página de configuración de API tokens~~
  - ~~Chat de IA en el footer del admin~~

### 2. Archivos Modificados
1. `wp-feature-api.php` - Cambiado `WP_FEATURE_API_LOAD_DEMO` a `true`
2. `release/wp-feature-api/wp-feature-api.php` - Cambiado `WP_FEATURE_API_LOAD_DEMO` a `true`

## Estado de Tareas
- [x] Analizar estructura del proyecto
- [x] Identificar el problema
- [x] Crear plan de implementación
- [x] Modificar wp-feature-api.php para cargar demo por defecto
- [x] Modificar release/wp-feature-api/wp-feature-api.php
- [ ] Verificar que el chat se muestra correctamente
- [ ] Probar la funcionalidad

## Después de Instalar el Plugin
1. Activar el plugin "WordPress Feature API"
2. Ir a "Configuración" → "WP Feature Agent Demo" para configurar API keys
3. El chat de IA aparecerá en el footer del admin

## Desactivar el Demo (Opcional)
Si no quieres el chat y configuración automática, añade esto en wp-config.php:
```php
define( 'WP_FEATURE_API_LOAD_DEMO', false );
```

