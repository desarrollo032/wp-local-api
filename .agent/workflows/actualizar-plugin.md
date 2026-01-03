---
description: Cómo actualizar el plugin en WordPress
---

# Actualizar Plugin WP Feature API - AI Agent Proxy

Sigue estos pasos para actualizar el plugin y ver los cambios de diseño:

## 1. En WordPress Admin

1. Ve a **Plugins** → **Plugins instalados**
2. Busca **WP Feature API - AI Agent Proxy**
3. Haz clic en **Desactivar**
4. Haz clic en **Eliminar**
5. Confirma la eliminación

## 2. Instalar la nueva versión

1. Ve a **Plugins** → **Añadir nuevo**
2. Haz clic en **Subir plugin**
3. Selecciona el archivo: `d:\Proyectos\wp-feature-api\dist\wp-feature-api-agent.zip`
4. Haz clic en **Instalar ahora**
5. Haz clic en **Activar plugin**

## 3. Limpiar caché del navegador

Presiona **Ctrl + Shift + R** (o **Ctrl + F5**) para hacer una recarga forzada y limpiar el caché del CSS.

## 4. Verificar los cambios

1. Ve a cualquier página del admin de WordPress
2. El chat flotante debería aparecer en la esquina inferior derecha con el nuevo diseño futurista:
   - Fondo oscuro con gradientes
   - Animaciones suaves
   - Bordes con efecto glow
   - Diseño moderno y premium

## Notas

- Si no ves los cambios, verifica la consola del navegador (F12) para errores
- Asegúrate de que el archivo CSS se está cargando: `build/style-index.css`
- El plugin solo es visible para usuarios con permisos de `manage_options`
