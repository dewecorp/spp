@echo off
setlocal

:: Konfigurasi
set REPO_URL=https://github.com/dewecorp/spp.git
set ZIP_NAME=backup.zip

echo ==========================================
echo    SPP Project Backup & Push Script
echo ==========================================

:: 1. Cek dan Inisialisasi Git
if not exist .git (
    echo [INFO] Inisialisasi repository Git...
    git init
    git branch -M main
    git remote add origin %REPO_URL%
) else (
    echo [INFO] Repository Git ditemukan.
    :: Pastikan remote origin mengarah ke URL yang benar
    git remote set-url origin %REPO_URL%
)

:: 2. Git Commit & Push
echo.
echo [STEP 1] Git Commit & Push
echo ------------------------------------------
git add .

:INPUT_MSG
set commit_msg=
set /p commit_msg="Masukkan pesan commit (Tekan Enter untuk default): "
if "%commit_msg%"=="" set commit_msg=Auto backup %date% %time%

echo.
echo ------------------------------------------
echo Pesan Commit: %commit_msg%
echo ------------------------------------------
set /p confirm="Lanjutkan dengan pesan ini? (Y/N): "
if /i "%confirm%" neq "Y" (
    echo.
    echo Silakan masukkan ulang pesan commit.
    goto INPUT_MSG
)

git commit -m "%commit_msg%"

echo.
echo [INFO] Upload ke GitHub...
git push -u origin main

:: 3. Buat Zip Backup
echo.
echo [STEP 2] Membuat Zip Backup
echo ------------------------------------------
echo [INFO] Memperbarui %ZIP_NAME%...
:: Menggunakan PowerShell untuk kompresi
:: -Force akan menimpa file lama (overwrite) sesuai permintaan "bersifat update/menimpa"
powershell -Command "Get-ChildItem -Path . -Exclude '%ZIP_NAME%','.git' | Compress-Archive -DestinationPath '%ZIP_NAME%' -Force"

echo.
echo ==========================================
echo    Backup Selesai!
echo ==========================================
pause
