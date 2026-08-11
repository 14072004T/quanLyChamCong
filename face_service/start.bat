@echo off
echo ====================================
echo  Face Recognition Service - Start
echo ====================================

:: Kiểm tra Python
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python chua duoc cai dat!
    echo Tai Python tai: https://www.python.org/downloads/
    pause
    exit /b 1
)

:: Kiểm tra virtual environment
if not exist "venv\" (
    echo [INFO] Tao virtual environment...
    python -m venv venv
)

:: Kích hoạt venv
call venv\Scripts\activate.bat

:: Kiểm tra và cài dependencies
echo [INFO] Kiem tra dependencies...
pip show fastapi >nul 2>&1
if errorlevel 1 (
    echo [INFO] Cai dat dependencies...
    pip install -r requirements.txt
    if errorlevel 1 (
        echo [ERROR] Cai dat that bai!
        pause
        exit /b 1
    )
)

:: Kiểm tra file .env
if not exist ".env" (
    echo [WARN] File .env chua ton tai!
    echo Sao chep tu .env.example...
    copy .env.example .env
    echo [WARN] Hay chinh sua .env truoc khi chay!
    notepad .env
)

:: Kiểm tra model weights
if not exist "weights\w600k_r50.onnx" (
    echo [WARN] Chua co model weights!
    echo Chay download_weights.py de tai ve...
    python download_weights.py
)

:: Khởi động service
echo [INFO] Khoi dong Face Service...
echo [INFO] URL: http://127.0.0.1:8000
echo [INFO] Docs: http://127.0.0.1:8000/docs
echo.
python main.py

pause
