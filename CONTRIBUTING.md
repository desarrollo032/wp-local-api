# Contribuir a la API de Funcionalidades de WP

> ¡Bienvenido! Gracias por tu interés en contribuir a la API de Funcionalidades de WordPress. Esta guía te ayudará a comenzar.

---

## 🏗️ Construir el Proyecto

### 1. Clonar el repositorio

```bash
git clone https://github.com/Automattic/wp-feature-api.git
cd wp-feature-api
```

### 2. Instalar dependencias

Este proyecto usa espacios de trabajo npm. Las dependencias se instalan desde el directorio raíz.

#### Dependencias de Node.js:

```bash
npm install
```

#### Dependencias de PHP:

```bash
composer install
```

### 3. Construir el proyecto

Para construir los activos de JavaScript y CSS del plugin y sus paquetes:

```bash
npm run build
```

---

## 🚀 Proceso de Lanzamiento

Lanzar una nueva versión involucra varios pasos:

### Paso 1: Preparar la rama principal

Asegúrate de que la rama `trunk` esté estable y que todos los cambios para el lanzamiento estén fusionados.

### Paso 2: Crear una rama para el lanzamiento

Crea una nueva rama para el lanzamiento (ej. `release/vX.Y.Z`):

```bash
git checkout -b release/vX.Y.Z
```

### Paso 3: Actualizar números de versión

Actualiza manualmente el número de versión en los siguientes archivos a la nueva versión (ej. `1.2.3`):

| Archivo | Qué actualizar |
|---------|----------------|
| [`package.json`](package.json) | Campo `version` |
| [`wp-feature-api.php`](wp-feature-api.php) | Comentario de cabecera del plugin Y constante PHP |

#### Actualizar package.json:

```json
{
  "name": "wp-feature-api",
  "version": "NUEVA_VERSION_AQUI",
  // ...
}
```

#### Actualizar wp-feature-api.php:

```php
/**
 * ...
 * Version: NUEVA_VERSION_AQUI
 * ...
 */
```

```php
define( 'WP_FEATURE_API_VERSION', 'NUEVA_VERSION_AQUI' );
```

### Paso 4: Confirmar el cambio de versión

Confirma estos cambios de versión con un mensaje como `Update version to X.Y.Z`:

```bash
git add package.json wp-feature-api.php
git commit -m "Update version to X.Y.Z"
```

### Paso 5: Subir cambios

Después de confirmar el cambio de versión, sube los cambios al repositorio remoto:

```bash
git push origin release/vX.Y.Z
```

### Paso 6: Crear Pull Request

Abre un Pull Request desde tu rama `release/vX.Y.Z` hacia la rama `trunk`.

### Paso 7: Fusionar Pull Request

Una vez que el Pull Request sea aprobado, fusiónalo en la rama `trunk`.

### Paso 8: Crear y Publicar Release en GitHub

Una vez que el Pull Request de actualización de versión esté fusionado en `trunk`:

1. Ve a la página de "Releases" en la interfaz de GitHub.
2. Haz clic en el botón "Draft a new release".
3. En el desplegable "Choose a tag", escribe tu nueva etiqueta de versión (ej. `v1.2.3`). GitHub ofrecerá "Create new tag: vX.Y.Z on publish". Selecciona esto.
4. Asegúrate de que el "Target" sea la rama `trunk`.
5. Ingresa un "Release title" (ej. `Version 1.2.3` o `v1.2.3`).
6. Escribe una descripción para el lanzamiento. Puedes listar los cambios principales o usar la función de notas de lanzamiento auto-generadas de GitHub si está disponible/configurada.
7. Haz clic en "Publish release".

> Esta acción creará la nueva etiqueta desde `trunk` y activará el flujo de trabajo de GitHub Actions definido en [`.github/workflows/release.yml`](.github/workflows/release.yml:1).

### Paso 9: Verificar el lanzamiento

Después de que el flujo de trabajo de GitHub Actions termine, verifica la página de "Releases" en GitHub para asegurar que todo se completó correctamente.

---

## 📋 Checklist de Lanzamiento

| Paso | Tarea | Estado |
|------|-------|--------|
| 1 | Rama `trunk` estable y actualizada | ⬜ |
| 2 | Rama de release creada | ⬜ |
| 3 | Versiones actualizadas en archivos | ⬜ |
| 4 | Cambio de versión confirmado | ⬜ |
| 5 | Cambios subidos a remoto | ⬜ |
| 6 | Pull Request creado | ⬜ |
| 7 | Pull Request fusionado | ⬜ |
| 8 | Release de GitHub publicado | ⬜ |
| 9 | Lanzamiento verificado | ⬜ |

---

## 🤝 Guías de Contribución

- ✅ Sigue los estándares de código del proyecto
- ✅ Escribe pruebas para nuevas funcionalidades
- ✅ Actualiza la documentación cuando sea necesario
- ✅ Usa mensajes de commit claros y descriptivos
- ✅ Haz PRs con cambios pequeños y enfocados

- ❌ No mezcles refactorización con nuevas funcionalidades
- ❌ No introduzcas cambios ruptivos sin discutirlo primero

---

## 📧 ¿Tienes dudas?

Si tienes preguntas sobre el proceso de contribución, no dudes en abrir un [issue](https://github.com/Automattic/wordpress-feature-api/issues) en GitHub.

¡Gracias por contribuir! 🎉

