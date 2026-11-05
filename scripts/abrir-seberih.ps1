$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

function Stop-WithMessage([string] $message) {
    Write-Host "`n$message" -ForegroundColor Red
    exit 1
}

function New-LocalSecret {
    $bytes = New-Object byte[] 32
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $random.GetBytes($bytes)
    } finally {
        $random.Dispose()
    }
    return (($bytes | ForEach-Object { $_.ToString('x2') }) -join '')
}

function Set-EnvValue([string[]] $lines, [string] $name, [string] $value) {
    $pattern = '^' + [regex]::Escape($name) + '='
    $found = $false
    $updated = foreach ($line in $lines) {
        if ($line -match $pattern) {
            $found = $true
            "$name=$value"
        } else {
            $line
        }
    }
    if (-not $found) {
        $updated += "$name=$value"
    }
    return ,$updated
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Stop-WithMessage 'No encontramos Docker. Instala Docker Desktop para Windows y vuelve a abrir este archivo.'
}

try {
    docker compose version | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker Compose no está disponible.'
    }
} catch {
    Stop-WithMessage 'Docker Compose no está disponible. Actualiza o instala Docker Desktop.'
}

try {
    docker info | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'El motor de Docker no está iniciado.'
    }
} catch {
    $dockerDesktop = Join-Path $env:ProgramFiles 'Docker\Docker\Docker Desktop.exe'
    if (Test-Path $dockerDesktop) {
        Write-Host 'Iniciando Docker Desktop…'
        Start-Process $dockerDesktop
        $engineReady = $false
        for ($attempt = 0; $attempt -lt 60; $attempt++) {
            Start-Sleep -Seconds 2
            docker info | Out-Null 2>$null
            if ($LASTEXITCODE -eq 0) {
                $engineReady = $true
                break
            }
        }
        if (-not $engineReady) {
            Stop-WithMessage 'Docker Desktop no terminó de iniciar. Ábrelo, espera a que esté listo e inténtalo de nuevo.'
        }
    } else {
        Stop-WithMessage 'Docker no está iniciado. Abre Docker Desktop y vuelve a hacer doble clic en este archivo.'
    }
}

$envFile = Join-Path $projectRoot '.env'
if (-not (Test-Path $envFile)) {
    $lines = @(Get-Content (Join-Path $projectRoot '.env.example'))
    $lines = Set-EnvValue $lines 'DB_PASSWORD' (New-LocalSecret)
    $lines = Set-EnvValue $lines 'DB_ROOT_PASSWORD' (New-LocalSecret)
    $utf8WithoutBom = New-Object -TypeName System.Text.UTF8Encoding -ArgumentList $false
    [System.IO.File]::WriteAllLines($envFile, $lines, $utf8WithoutBom)
    Write-Host 'Se creó la configuración local para esta computadora.'
}

# Estas opciones solo aplican al contenedor iniciado desde este acceso directo.
$env:APP_ENV = 'local'
$env:BOOKING_DEMO_MODE = 'true'
$env:SESSION_COOKIE_SECURE = 'false'
$port = 8080
$envPort = Select-String -Path $envFile -Pattern '^WEB_PORT=(\d+)$' | Select-Object -First 1
if ($envPort -and [int]::TryParse($envPort.Matches[0].Groups[1].Value, [ref]$port)) {
    $port = [int]$envPort.Matches[0].Groups[1].Value
}
$url = "http://localhost:$port"

Write-Host 'Preparando Seberih. La primera apertura puede tardar mientras se descargan los componentes…'
try {
    docker compose up --build -d
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker Compose no pudo iniciar los servicios.'
    }
} catch {
    docker compose logs --tail 30 web db
    Stop-WithMessage 'No se pudo iniciar la página. Revisa el mensaje anterior y confirma que Docker tenga conexión a internet.'
}

$siteReady = $false
for ($attempt = 0; $attempt -lt 90; $attempt++) {
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 3
        if ($response.StatusCode -eq 200) {
            $siteReady = $true
            break
        }
    } catch {
        Start-Sleep -Seconds 2
    }
}

if (-not $siteReady) {
    docker compose logs --tail 30 web db
    Stop-WithMessage 'Los contenedores iniciaron, pero la página no respondió a tiempo. Revisa los registros anteriores.'
}

Write-Host "Abriendo $url"
Start-Process $url
Write-Host 'Listo. Puedes cerrar esta ventana; Docker mantendrá el sitio disponible.' -ForegroundColor Green
