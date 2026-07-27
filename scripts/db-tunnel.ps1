#!/usr/bin/env pwsh
<#
Abre el tunel SSH hacia la base de datos remota "kodigo" (misma ruta que usa
MySQL Workbench: ver conexion "cohort_main" en connections.xml).

El servidor remoto solo expone MySQL en 127.0.0.1:3306 (no publico), por eso
la app (config/database.php / app/Core/Database.php) espera encontrar MySQL
en 127.0.0.1:3306 localmente: este script hace ese forwarding.

Uso:
  pwsh scripts/db-tunnel.ps1
  (dejalo corriendo en su propia terminal; en otra terminal arranca el server
   PHP normalmente, ej: php -S localhost:8000 -t public)

Parametros opcionales por si cambian el VPS o la key:
  -SshHost, -SshPort, -SshUser, -KeyPath, -LocalPort, -RemotePort
#>
param(
    [string]$SshHost = "51.81.185.146",
    [int]$SshPort = 2244,
    [string]$SshUser = "ubuntu",
    [string]$KeyPath = "C:\Users\paizk\.ssh\kodigo_key.pem",
    [int]$LocalPort = 3306,
    [int]$RemotePort = 3306
)

if (-not (Test-Path $KeyPath)) {
    Write-Error "No se encontro la key SSH en: $KeyPath"
    exit 1
}

$existing = Get-NetTCPConnection -LocalPort $LocalPort -State Listen -ErrorAction SilentlyContinue
if ($existing) {
    Write-Warning "Ya hay algo escuchando en 127.0.0.1:$LocalPort (PID $($existing.OwningProcess) por proceso). Si es un tunel previo, no hace falta abrir otro."
    exit 0
}

Write-Host "Abriendo tunel SSH -> $SshUser@${SshHost}:$SshPort forwardeando 127.0.0.1:$LocalPort al MySQL remoto (127.0.0.1:$RemotePort del VPS)..."
Write-Host "Dejar esta terminal abierta mientras uses la app. Ctrl+C para cerrar el tunel."

ssh -i $KeyPath -p $SshPort -N -o ServerAliveInterval=30 -o ExitOnForwardFailure=yes -L "${LocalPort}:127.0.0.1:${RemotePort}" "$SshUser@$SshHost"
