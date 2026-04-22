@echo off
REM Script para resetear y crear nuevo SuperAdmin
REM Uso: reset-superadmin.bat [email] [nombre] [password]

echo ========================================
echo  RESET SUPERADMIN - Kartenant
echo ========================================
echo.

REM Verificar si se proporcionaron parámetros
set EMAIL=%1
set NAME=%2
set PASSWORD=%3

if "%EMAIL%"=="" set EMAIL=admin@ejemplo.com
if "%NAME%"=="" set NAME=Super Admin

echo Email: %EMAIL%
echo Nombre: %NAME%
echo.

REM Paso 1: Eliminar superadmin existente
echo [1/4] Eliminando superadmin existente...
php artisan kartenant:delete-superadmin %EMAIL% --force
if %ERRORLEVEL% NEQ 0 (
    echo ADVERTENCIA: No se pudo eliminar el superadmin existente o no existe
)
echo.

REM Paso 2: Crear nuevo superadmin
echo [2/4] Creando nuevo superadmin...
if "%PASSWORD%"=="" (
    php artisan kartenant:make-superadmin %EMAIL% --name="%NAME%"
) else (
    php artisan kartenant:make-superadmin %EMAIL% --name="%NAME%" --password="%PASSWORD%"
)
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: No se pudo crear el superadmin
    pause
    exit /b 1
)
echo.

REM Paso 3: Limpiar caché
echo [3/4] Limpiando cache...
php artisan optimize:clear
echo.

REM Paso 4: Verificar
echo [4/4] Verificando configuracion...
php artisan route:list --path=admin/cambiar
echo.

echo ========================================
echo  COMPLETADO EXITOSAMENTE
echo ========================================
echo.
echo Ahora puedes acceder a:
echo https://kartenant.test/admin/login
echo.
echo Email: %EMAIL%
if "%PASSWORD%"=="" (
    echo Password: [Ver arriba - generada automaticamente]
) else (
    echo Password: %PASSWORD%
)
echo.
echo IMPORTANTE: Se requerira cambiar la password en el primer login
echo Ruta de cambio: https://kartenant.test/admin/cambiar-contrasena
echo.
pause
