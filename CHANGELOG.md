# Historial de Cambios

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/spec/v2.0.0.html).

## [0.1.11] - 2025-01-26

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

[Unreleased]: https://github.com/Automattic/wp-feature-api/compare/v0.1.10...HEAD
[0.1.10]: https://github.com/Automattic/wp-feature-api/compare/v0.1.9...v0.1.10

