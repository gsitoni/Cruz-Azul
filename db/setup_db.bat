@echo off
echo ================================
echo   CONFIGURANDO BANCO CRUZ AZUL
echo ================================

REM ===== CONFIGURAÇÕES =====
set MYSQL_USER=root
set MYSQL_PASSWORD=
set MYSQL_PATH="C:\xampp\mysql\bin\mysql.exe"
set SQL_FILE=cruzazul.sql

echo.
echo Criando banco de dados...

%MYSQL_PATH% -u %MYSQL_USER% -p%MYSQL_PASSWORD% -e "CREATE DATABASE IF NOT EXISTS cruzazul CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

IF %ERRORLEVEL% NEQ 0 (
    echo ERRO ao criar banco.
    pause
    exit /b
)

echo.
echo Importando estrutura...

%MYSQL_PATH% -u %MYSQL_USER% -p%MYSQL_PASSWORD% cruzazul < %SQL_FILE%

IF %ERRORLEVEL% NEQ 0 (
    echo ERRO ao importar SQL.
    pause
    exit /b
)

echo.
echo ================================
echo   BANCO CONFIGURADO COM SUCESSO
echo ================================
pause