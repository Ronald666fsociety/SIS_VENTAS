@echo off
title Sistema de Ventas - Backend + Internet
echo ===================================================
echo  Iniciando API Laravel (SQLite local, 20MB uploads)
echo ===================================================
start "API Laravel 8000" cmd /k "cd /d c:\Users\RON\Desktop\apk2\backend && php -d upload_max_filesize=20M -d post_max_size=24M artisan serve --host=0.0.0.0 --port=8000"

timeout /t 3 /nobreak > nul

echo ===================================================
echo  Abriendo Tunel HTTPS para usar con Datos Moviles
echo  (Copia la URL https://...trycloudflare.com si cambia)
echo ===================================================
"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8000
pause
