<#
.SYNOPSIS
    Собирает архив dist/pipup.tgz для Маркета дополнений MajorDoMo.

.DESCRIPTION
    Архив не должен содержать родительской папки: все пути внутри —
    относительно корня MajorDoMo. Скрипт собирает временный каталог,
    добавляет локальную копию словарей в modules/pipup/languages и
    упаковывает содержимое через bsdtar (входит в Windows 10/11).

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File build_package.ps1
#>
param(
    [string]$Output = 'dist/pipup.tgz'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$stage = Join-Path $env:TEMP ('pipup_build_' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Force -Path $stage | Out-Null

$items = @(
    'modinfo.json',
    'module.xml',
    'install.php',
    'README.md',
    'CHANGELOG.md',
    'docs',
    'languages',
    'import',
    'templates',
    'modules'
)

foreach ($item in $items) {
    $source = Join-Path $root $item
    if (-not (Test-Path $source)) {
        throw "Не найден элемент для упаковки: $item"
    }
    Copy-Item -Path $source -Destination (Join-Path $stage $item) -Recurse -Force
}

# Локальная копия словарей: нужна при ручной установке только папки modules/.
$localLanguages = Join-Path $stage 'modules/pipup/languages'
New-Item -ItemType Directory -Force -Path $localLanguages | Out-Null
Copy-Item -Path (Join-Path $root 'languages/pipup_*.php') -Destination $localLanguages -Force

$outputPath = Join-Path $root $Output
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $outputPath) | Out-Null
if (Test-Path $outputPath) {
    Remove-Item $outputPath -Force
}

Push-Location $stage
try {
    & tar -czf $outputPath @items
    if ($LASTEXITCODE -ne 0) {
        throw "tar завершился с кодом $LASTEXITCODE"
    }
} finally {
    Pop-Location
    Remove-Item $stage -Recurse -Force -ErrorAction SilentlyContinue
}

$size = (Get-Item $outputPath).Length
Write-Host "Готово: $outputPath ($size байт)"
