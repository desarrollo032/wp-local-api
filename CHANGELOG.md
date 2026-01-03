# Historial de Cambios

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/spec/v2.0.0.html).

## [0.1.11] - 2025-01-03

### 🚀 Nuevas Características

- **OpenRouter Integration:** Integración completa con OpenRouter API con soporte para modelos gratuitos
- **Chat Interface:** Interfaz de chat integrada en el admin de WordPress con UI moderna
- **MCP Support:** Compatibilidad completa con WordPress MCP plugin v0.2.5+
- **Free Models Priority:** Priorización automática de modelos gratuitos de OpenRouter
- **Build Automation:** `npm run build` ahora compila y genera ZIPs automáticamente

### 🔧 Mejoras

- **Simplified Structure:** Reducido de 3 plugins a 2 (eliminado plugin duplicado)
- **Better Error Handling:** Manejo robusto de diferentes estructuras de respuesta API
- **Enhanced Debugging:** Logs detallados para troubleshooting del chat
- **Improved UI:** Indicadores "(FREE)" para modelos gratuitos en la interfaz
- **Optional Dependencies:** Plugin funciona sin dependencia del plugin principal
- **Security Enhancements:** Validación mejorada de inputs y sanitización

### 🐛 Correcciones

- **Fixed:** Error "Invalid response structure from API proxy" con OpenRouter
- **Fixed:** Chat no aparecía al instalar plugins (simplificado montaje JavaScript)
- **Fixed:** Dependencias opcionales para mejor compatibilidad
- **Fixed:** Manejo de assets mejorado con validación de estructura
- **Fixed:** Permisos de usuario verificados correctamente

### 📦 Estructura de Plugins

- **wp-feature-api.zip (34 KB):** Plugin principal con API de funcionalidades
- **wp-feature-api-agent.zip (61 KB):** Proxy AI + Chat interface completo

### 🔄 Cambios de API

- **New Endpoints:**
  - `GET /wp/v2/ai-api-proxy/v1/models` - Lista modelos disponibles
  - `GET /wp/v2/ai-api-proxy/v1/mcp/status` - Estado de MCP
  - `GET /wp/v2/ai-api-proxy/v1/mcp/tools` - Herramientas MCP disponibles
  - `POST /wp/v2/ai-api-proxy/v1/mcp/call` - Ejecutar herramientas MCP

### 🎯 Modelos Gratuitos Soportados

- `microsoft/phi-3-mini-128k-instruct:free`
- `microsoft/phi-3-medium-128k-instruct:free`
- `huggingfaceh4/zephyr-7b-beta:free`
- `openchat/openchat-7b:free`
- `gryphe/mythomist-7b:free`
- `undi95/toppy-m-7b:free`
- `nousresearch/nous-capybara-7b:free`
- `mistralai/mistral-7b-instruct:free`
- `google/gemma-7b-it:free`
- `meta-llama/llama-3-8b-instruct:free`
- `qwen/qwen-2-7b-instruct:free`

### 📚 Documentación

- **Updated:** README.md completamente reescrito para v0.1.11
- **Added:** Guías de instalación y configuración detalladas
- **Added:** Documentación de debugging y troubleshooting
- **Added:** Ejemplos de uso de la API

---

## [0.1.10] - 2025-01-25

### Reestructuración del Proyecto

- ✅ Demo plugin movido de `demo/wp-feature-api-agent` a `packages/demo-agent`
- ✅ Dependencias centralizadas en package.json raíz
- ✅ Paquetes TypeScript configurados y sincronizados
- ✅ Eliminado directorio `release/` (redundante)
- ✅ Versiones de @wordpress/* sincronizadas (^6-^31.x)
- ✅ TypeScript ^5.6.3 agregado a todos los packages
- ✅ Sistema de workspaces npm configurado correctamente

### Actualización de Versiones

- Core: 0.1.9 → 0.1.10
- Client: 0.1.9 → 0.1.10
- Client Features: 1.0.0 → 1.0.1
- Demo Agent: 0.1.1 → 0.1.2

### Dependencias Actualizadas

- React/ReactDOM: ^18.3.1
- @wordpress/* packages: ^6.37.0 a ^31.0.0
- TypeScript: ^5.6.3
- rimraf: ^5.0.10

### Security & Performance

- **Security:** Added strict input validation for `api_path` in AI Proxy to prevent path traversal and restrict access to allowed hosts (OpenAI, OpenRouter).
- **Security:** Implemented `no-unsafe-wp-apis` linting rules and addressed identified vulnerabilities.
- **Performance:** Optimized React components (`ConversationProvider`, `ChatApp`) using `useCallback` and `useMemo` to reduce unnecessary re-renders.
- **Performance:** Cleaned up unused imports and variables in TypeScript files.

### Code Quality

- **Linting:** Fixed comprehensive ESLint and Stylelint issues across all packages.
- **Standards:** Aligned PHP code with WordPress coding standards.
- **Cleanup:** Removed dead code and unused dependencies.

### Fixes

- Fixed asset loading paths for the chat functionality to support both development and production directory structures.
- Resolved CSS specificity issues in `packages/demo-agent`.

---

[Unreleased]: https://github.com/Automattic/wp-feature-api/compare/v0.1.11...HEAD
[0.1.11]: https://github.com/Automattic/wp-feature-api/compare/v0.1.10...v0.1.11
[0.1.10]: https://github.com/Automattic/wp-feature-api/compare/v0.1.9...v0.1.10

