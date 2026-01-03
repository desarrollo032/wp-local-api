# ARCHIVOS DE DESARROLLO - NO INCLUIR EN ZIPS

## ❌ ARCHIVOS QUE NO VAN EN LOS PLUGINS WORDPRESS:

### 1. `phpcs.xml.dist` - Configuración de PHP CodeSniffer
**Propósito:** Define reglas de estilo de código PHP para desarrollo
**Uso:** `composer lint` - Verifica que el código siga estándares WordPress
**Por qué NO incluir:** Es solo para desarrollo, no para producción

### 2. `phpunit.xml.dist` - Configuración de PHPUnit
**Propósito:** Define configuración para tests unitarios PHP
**Uso:** `composer test` - Ejecuta tests automatizados
**Por qué NO incluir:** Los tests no van en plugins de producción

### 3. `phpunit-watcher.yml.dist` - Configuración de PHPUnit Watcher
**Propósito:** Ejecuta tests automáticamente cuando cambian archivos
**Uso:** Desarrollo con auto-testing
**Por qué NO incluir:** Es herramienta de desarrollo, no funcionalidad

## ✅ ARCHIVOS QUE SÍ VAN EN LOS PLUGINS:

### Archivos PHP principales:
- `wp-feature-api.php` - Archivo principal del plugin
- `includes/` - Clases PHP del core
- `build/` - Assets JavaScript/CSS compilados
- `package.json` - Metadatos del paquete
- `README.md` - Documentación del usuario

## 🔧 ESTRUCTURA CORRECTA DE ZIP WORDPRESS:

```
wp-feature-api.zip
└── wp-feature-api/                    # ← Carpeta con nombre del plugin
    ├── wp-feature-api.php            # ← Archivo PHP principal
    ├── includes/                     # ← Clases PHP
    ├── build/                        # ← Assets compilados
    ├── package.json                  # ← Metadatos
    └── README.md                     # ← Documentación
```

## ❌ ESTRUCTURA INCORRECTA (causa "Archivo incompatible"):

```
wp-feature-api.zip
├── wp-feature-api.php               # ← Archivos en raíz del ZIP
├── includes/
├── build/
├── phpcs.xml.dist                   # ← Archivos de desarrollo
├── phpunit.xml.dist                 # ← NO deben estar aquí
└── phpunit-watcher.yml.dist         # ← Causan errores
```

## 🚀 SOLUCIÓN IMPLEMENTADA:

1. **✅ Estructura correcta:** Plugin en carpeta con su nombre
2. **✅ Solo archivos necesarios:** Sin archivos de desarrollo
3. **✅ Validación automática:** Scripts verifican estructura
4. **✅ Carpetas de revisión:** Para desarrollo local

## 📋 COMANDOS PARA VERIFICAR:

```bash
# Ver estructura del ZIP
tar -tzf dist/wp-feature-api.zip | head -10

# Debería mostrar:
# wp-feature-api/
# wp-feature-api/wp-feature-api.php
# wp-feature-api/includes/
# wp-feature-api/build/

# Verificar que NO hay archivos de desarrollo
tar -tzf dist/wp-feature-api.zip | grep -E "(phpcs|phpunit|\.dist)"
# No debería mostrar nada
```

## 🎯 RESULTADO:

Los ZIPs ahora tienen la **estructura correcta** que WordPress espera y **NO contienen archivos de desarrollo** que causaban el error "Archivo incompatible".