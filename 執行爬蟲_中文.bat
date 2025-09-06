@echo off
chcp 65001 >nul
echo ========================================
echo    Google Maps 爬蟲程式 - 自動執行
echo ========================================
echo.

echo 正在檢查 Python...
python --version >nul 2>&1
if errorlevel 1 (
    echo 錯誤：找不到 Python
    echo 請先安裝 Python：https://www.python.org/downloads/
    echo 安裝時務必勾選 "Add Python to PATH"
    pause
    exit /b 1
)

echo Python 已安裝
echo.

echo 正在安裝必要的套件...
pip install -r requirements.txt
if errorlevel 1 (
    echo 套件安裝失敗
    echo 請檢查網路連線或手動執行：pip install -r requirements.txt
    pause
    exit /b 1
)

echo 套件安裝完成
echo.

echo 正在啟動爬蟲程式...
echo 注意：程式會自動開啟 Chrome 瀏覽器
echo 請不要關閉瀏覽器視窗，讓程式自動執行
echo.

echo 執行進階版爬蟲程式...
echo 支援搜尋關鍵字和Google Maps URL輸入
echo.

python google_maps_scraper_advanced.py

echo.
echo ========================================
echo 程式執行完成！
echo 請檢查是否生成了 Excel 檔案
echo ========================================
pause
