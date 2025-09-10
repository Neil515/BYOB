#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
餐廳Email搜尋程式 - 安裝腳本
功能：自動安裝所需的Python套件
"""

import subprocess
import sys
import os

def install_package(package):
    """安裝Python套件"""
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])
        print(f"✅ {package} 安裝成功")
        return True
    except subprocess.CalledProcessError:
        print(f"❌ {package} 安裝失敗")
        return False

def main():
    """主安裝程式"""
    print("🚀 開始安裝餐廳Email搜尋程式所需的套件...")
    print("=" * 50)
    
    # 需要安裝的套件
    packages = [
        "pandas",
        "requests", 
        "selenium",
        "openpyxl"
    ]
    
    success_count = 0
    
    for package in packages:
        print(f"📦 正在安裝 {package}...")
        if install_package(package):
            success_count += 1
    
    print("=" * 50)
    
    if success_count == len(packages):
        print("🎉 所有套件安裝完成！")
        print("\n📋 使用說明:")
        print("1. 將您的Excel檔案放在與程式相同的資料夾")
        print("2. 開啟命令提示字元")
        print("3. 執行: python restaurant_email_search.py 您的檔案.xlsx")
        print("4. 等待10-15分鐘完成搜尋")
        print("5. 查看結果檔案: 您的檔案_with_emails.xlsx")
    else:
        print("⚠️ 部分套件安裝失敗，請手動安裝:")
        print("pip install pandas requests selenium openpyxl")
    
    print("\n💡 注意事項:")
    print("- 程式需要Chrome瀏覽器")
    print("- 如果沒有Chrome，請先安裝Chrome瀏覽器")
    print("- 程式會自動下載ChromeDriver")

if __name__ == "__main__":
    main()
