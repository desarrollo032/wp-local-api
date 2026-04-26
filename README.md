# WordPress Feature API

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![React](https://img.shields.io/badge/React-18%2B-blue)
![TypeScript](https://img.shields.io/badge/TypeScript-4%2B-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue)

Un sistema para exponer la funcionalidad del servidor y del lado del cliente en WordPress para su uso en LLMs y sistemas agenciales.

## 📝 Visión General

WordPress Feature API es un plugin que permite a los desarrolladores de WordPress exponer de forma segura y sencilla la funcionalidad del backend y del frontend a sistemas de inteligencia artificial, como los modelos de lenguaje grandes (LLMs). Esto abre un mundo de posibilidades para la creación de experiencias de usuario más inteligentes y dinámicas en los sitios de WordPress.

## ✨ Características

- **API Extensible:** Define fácilmente nuevos "features" para exponer la funcionalidad de tu plugin o tema.
- **Seguridad:** El sistema de permisos y el control de acceso garantizan que solo los usuarios autorizados puedan acceder a los features.
- **Integración con el Cliente:** Un paquete de cliente para interactuar con la API desde el frontend de tu sitio.
- **Soporte para Agentes:** Incluye un agente de demostración que muestra cómo utilizar la API en un sistema de IA conversacional.

## 🚀 Tecnologías Utilizadas

- **Backend:**
    - PHP
- **Frontend:**
    - React
    - TypeScript
    - SASS
    - Webpack

## 🏁 Cómo Empezar

### Prerrequisitos

- WordPress 6.0 o superior
- PHP 7.4 o superior

### Instalación

1. Descarga la última versión del plugin desde la página de [releases](https://github.com/Automattic/wp-feature-api/releases).
2. Sube el archivo `.zip` a tu sitio de WordPress a través del menú `Plugins > Añadir nuevo`.
3. Activa el plugin.

### Uso

Una vez activado, puedes empezar a definir tus propios "features" en tu tema o en otro plugin. Consulta la [documentación para desarrolladores](https://github.com/Automattic/wp-feature-api/tree/main/docs) para obtener más información.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor, lee la [guía de contribución](./.github/CONTRIBUTING.md) para empezar.

## 📄 Licencia

Este proyecto está licenciado bajo la licencia GPL-2.0-or-later. Consulta el archivo [LICENSE](./LICENSE) para más detalles.