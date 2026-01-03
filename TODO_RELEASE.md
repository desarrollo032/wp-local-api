# TODO: Workflow de Release Automatizado

## Objetivo
Crear un workflow de GitHub Actions que genere releases automáticos al hacer push de tags v*

## Pasos completados

### [x] 1. Actualizar workflow `.github/workflows/release.yml`
   - [x] Usar `softprops/action-gh-release` para creación de releases
   - [x] Mejorar extracción de changelog desde CHANGELOG.md
   - [x] Añadir validación de estructura ZIP
   - [x] Configurar artifacts temporales

### [x] 2. Mejorar script de validación `scripts/validate-plugin.js`
   - [x] Mejorar extracción de changelog
   - [x] Añadir checks de integridad SHA256
   - [x] Mejorar mensajes de error

### [x] 3. Documentación
   - [x] Crear explicación del workflow en `docs/RELEASE_PROCESS.md`

## Comandos de uso
```bash
git tag -a v0.1.11 -m "Release v0.1.11"
git push origin v0.1.11
```

