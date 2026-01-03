# PowerShell script para crear ZIPs de plugins WordPress
# Uso: .\scripts\package.ps1

param(
    [switch]$SkipBuildCheck
)

# Configuracion
$ErrorActionPreference = "Stop"
$RootDir = Split-Path -Parent $PSScriptRoot
$DistDir = Join-Path $RootDir "dist"

# Colores para output
function Write-Info { param($Message) Write-Host "INFO: $Message" -ForegroundColor Blue }
function Write-Success { param($Message) Write-Host "SUCCESS: $Message" -ForegroundColor Green }
function Write-Warning { param($Message) Write-Host "WARNING: $Message" -ForegroundColor Yellow }
function Write-Error { param($Message) Write-Host "ERROR: $Message" -ForegroundColor Red }
function Write-Header { param($Message) Write-Host "BUILDING: $Message" -ForegroundColor Cyan }

Write-Host ""
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host "              GENERADOR DE PLUGINS ZIP - wp-feature-api                    " -ForegroundColor Cyan
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar builds
if (-not $SkipBuildCheck) {
    Write-Info "Verificando builds..."
    
    $BuildDirs = @(
        "packages\client\build",
        "packages\client-features\build", 
        "packages\demo-agent\build"
    )
    
    $Missing = @()
    foreach ($BuildDir in $BuildDirs) {
        $FullPath = Join-Path $RootDir $BuildDir
        if (-not (Test-Path $FullPath)) {
            $Missing += $BuildDir
        }
    }
    
    if ($Missing.Count -gt 0) {
        Write-Error "Builds faltantes: $($Missing -join ', ')"
        Write-Host "Ejecuta primero: npm run build" -ForegroundColor Yellow
        exit 1
    }
    
    Write-Success "Todos los builds estan presentes"
}

# Limpiar directorio dist
Write-Info "Limpiando directorio dist..."
if (Test-Path $DistDir) {
    Remove-Item $DistDir -Recurse -Force
}
New-Item -ItemType Directory -Path $DistDir -Force | Out-Null
Write-Success "Directorio dist preparado"

# Funcion para validar header de plugin
function Test-PluginHeader {
    param($PhpFile, $PluginName)
    
    if (-not (Test-Path $PhpFile)) {
        Write-Error "Archivo PHP principal no encontrado: $PhpFile"
        return $false
    }
    
    $Content = Get-Content $PhpFile -Raw
    
    if ($Content -notmatch "Plugin Name:") {
        Write-Error "Falta header 'Plugin Name' en $PhpFile"
        return $false
    }
    
    if ($Content -notmatch "Version:") {
        Write-Error "Falta header 'Version' en $PhpFile"
        return $false
    }
    
    Write-Success "Header valido: $PluginName"
    return $true
}

# Funcion para copiar archivos excluyendo desarrollo
function Copy-WithExclusions {
    param($Source, $Destination)
    
    $ExcludePatterns = @(
        "node_modules",
        ".git*",
        "*.map",
        "tsconfig*.json",
        "webpack.config.js",
        ".eslintrc*",
        ".prettierrc*",
        "*.lock",
        ".DS_Store",
        "Thumbs.db"
    )
    
    if (-not (Test-Path $Destination)) {
        New-Item -ItemType Directory -Path $Destination -Force | Out-Null
    }
    
    Get-ChildItem $Source -Recurse | ForEach-Object {
        $RelativePath = $_.FullName.Substring($Source.Length + 1)
        $ShouldExclude = $false
        
        foreach ($Pattern in $ExcludePatterns) {
            if ($_.Name -like $Pattern -or $RelativePath -like "*$Pattern*") {
                $ShouldExclude = $true
                break
            }
        }
        
        if (-not $ShouldExclude) {
            $DestPath = Join-Path $Destination $RelativePath
            $DestDir = Split-Path $DestPath -Parent
            
            if (-not (Test-Path $DestDir)) {
                New-Item -ItemType Directory -Path $DestDir -Force | Out-Null
            }
            
            if ($_.PSIsContainer) {
                if (-not (Test-Path $DestPath)) {
                    New-Item -ItemType Directory -Path $DestPath -Force | Out-Null
                }
            } else {
                Copy-Item $_.FullName $DestPath -Force
            }
        }
    }
}

# Funcion para crear ZIP
function New-PluginZip {
    param($SourceDir, $ZipName)
    
    $ZipPath = Join-Path $DistDir $ZipName
    $PluginName = $ZipName -replace '\.zip$', ''
    $ReviewDir = Join-Path $DistDir $PluginName
    $TempDir = Join-Path $DistDir ".temp-zip-$PluginName"
    
    Write-Info "Generando $ZipName..."
    
    # Crear carpeta de revision
    if (Test-Path $ReviewDir) {
        Remove-Item $ReviewDir -Recurse -Force
    }
    Copy-Item $SourceDir $ReviewDir -Recurse -Force
    Write-Success "Carpeta de revision: $PluginName/"
    
    # Crear estructura temporal para ZIP
    if (Test-Path $TempDir) {
        Remove-Item $TempDir -Recurse -Force
    }
    New-Item -ItemType Directory -Path $TempDir -Force | Out-Null
    
    $PluginDir = Join-Path $TempDir $PluginName
    if (-not (Test-Path $PluginDir)) {
        New-Item -ItemType Directory -Path $PluginDir -Force | Out-Null
    }
    Copy-Item "$SourceDir\*" $PluginDir -Recurse -Force
    
    # Crear ZIP usando PowerShell
    try {
        if (Test-Path $ZipPath) {
            Remove-Item $ZipPath -Force
        }
        
        Compress-Archive -Path $PluginDir -DestinationPath $ZipPath -CompressionLevel Optimal
        Write-Success "ZIP creado: $ZipName"
        
        # Generar SHA256
        $Hash = Get-FileHash $ZipPath -Algorithm SHA256
        $HashFile = "$ZipPath.sha256"
        "$($Hash.Hash.ToLower())  $(Split-Path $ZipPath -Leaf)" | Out-File $HashFile -Encoding ASCII
        Write-Success "SHA256: $(Split-Path $HashFile -Leaf)"
        
        # Limpiar temp
        Remove-Item $TempDir -Recurse -Force
        return $true
    }
    catch {
        Write-Error "Error al crear $ZipName : $_"
        if (Test-Path $TempDir) {
            Remove-Item $TempDir -Recurse -Force
        }
        return $false
    }
}

# Construir wp-feature-api.zip
Write-Host ""
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host "CONSTRUYENDO PLUGINS" -ForegroundColor Cyan
Write-Host "===============================================================================" -ForegroundColor Cyan
Write-Host ""

Write-Header "Construyendo: wp-feature-api"

$TempDir = Join-Path $DistDir ".temp.wp-feature-api"
if (Test-Path $TempDir) {
    Remove-Item $TempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $TempDir -Force | Out-Null

# Validar archivo PHP principal
$MainPhp = Join-Path $RootDir "wp-feature-api.php"
if (-not (Test-PluginHeader $MainPhp "wp-feature-api")) {
    exit 1
}

# Copiar archivos
Copy-Item $MainPhp $TempDir -Force
$PackageJson = Join-Path $RootDir "package.json"
if (Test-Path $PackageJson) {
    Copy-Item $PackageJson $TempDir -Force
}
$ReadMe = Join-Path $RootDir "README.md"
if (Test-Path $ReadMe) {
    Copy-Item $ReadMe $TempDir -Force
}

Copy-WithExclusions (Join-Path $RootDir "includes") (Join-Path $TempDir "includes")
Copy-WithExclusions (Join-Path $RootDir "packages\client\build") (Join-Path $TempDir "build")

$BuildTypes = Join-Path $RootDir "packages\client\build-types"
if (Test-Path $BuildTypes) {
    Copy-WithExclusions $BuildTypes (Join-Path $TempDir "build-types")
}

# Generar ZIP
if (New-PluginZip $TempDir "wp-feature-api.zip") {
    Write-Success "wp-feature-api completado"
} else {
    exit 1
}
Remove-Item $TempDir -Recurse -Force

Write-Host ""

# Construir wp-feature-api-agent.zip
Write-Header "Construyendo: wp-feature-api-agent"

$TempDir = Join-Path $DistDir ".temp.wp-feature-api-agent"
if (Test-Path $TempDir) {
    Remove-Item $TempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $TempDir -Force | Out-Null

# Validar archivo PHP principal
$AgentPhp = Join-Path $RootDir "packages\demo-agent\wp-feature-api-agent.php"
if (-not (Test-PluginHeader $AgentPhp "wp-feature-api-agent")) {
    exit 1
}

# Copiar archivos
Copy-Item $AgentPhp $TempDir -Force

$AgentPackageJson = Join-Path $RootDir "packages\demo-agent\package.json"
if (Test-Path $AgentPackageJson) {
    Copy-Item $AgentPackageJson $TempDir -Force
}

$ClientFeaturesReadMe = Join-Path $RootDir "packages\client-features\README.md"
if (Test-Path $ClientFeaturesReadMe) {
    Copy-Item $ClientFeaturesReadMe $TempDir -Force
}

Copy-WithExclusions (Join-Path $RootDir "packages\demo-agent\includes") (Join-Path $TempDir "includes")
Copy-WithExclusions (Join-Path $RootDir "packages\demo-agent\build") (Join-Path $TempDir "build")
Copy-WithExclusions (Join-Path $RootDir "packages\client-features\build") (Join-Path $TempDir "build-features")

# Generar ZIP
if (New-PluginZip $TempDir "wp-feature-api-demo-agent.zip") {
    Write-Success "wp-feature-api-demo-agent completado"
} else {
    exit 1
}
Remove-Item $TempDir -Recurse -Force

# Mostrar resumen
Write-Host ""
Write-Host "===============================================================================" -ForegroundColor Green
Write-Host "PACKAGE COMPLETADO" -ForegroundColor Green
Write-Host "===============================================================================" -ForegroundColor Green
Write-Host ""

Write-Host "Directorio: $DistDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "Archivos ZIP generados:" -ForegroundColor Cyan
Write-Host ""

$TotalSize = 0
Get-ChildItem "$DistDir\*.zip" | ForEach-Object {
    $SizeKB = [math]::Round($_.Length / 1024)
    Write-Host "   $($_.Name) ($SizeKB KB)" -ForegroundColor White
    $TotalSize += $_.Length
}

Write-Host ""
Write-Host "Carpetas de revision generadas:" -ForegroundColor Cyan
Write-Host ""

Get-ChildItem $DistDir -Directory | Where-Object { $_.Name -notlike ".*" } | ForEach-Object {
    $FileCount = (Get-ChildItem $_.FullName -Recurse -File).Count
    Write-Host "   $($_.Name)/ ($FileCount archivos)" -ForegroundColor White
}

Write-Host ""
Write-Host "Checksums SHA256:" -ForegroundColor Cyan
Write-Host ""

Get-ChildItem "$DistDir\*.sha256" | ForEach-Object {
    Write-Host "   $($_.Name)" -ForegroundColor White
}

$TotalSizeKB = [math]::Round($TotalSize / 1024)
Write-Host ""
Write-Host "Total ZIPs: $TotalSizeKB KB" -ForegroundColor Cyan
Write-Host ""

Write-Host "Plugins WordPress listos para instalar!" -ForegroundColor Green