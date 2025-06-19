import csv
import datetime
import requests
from bs4 import BeautifulSoup

# 從 seed_list_raw.txt 讀取餐廳名稱
with open("../data/seed_list_raw.txt", "r", encoding="utf-8") as f:
    restaurant_names = [(line.split("#")[0].strip(), line.split("#")[1].strip() if "#" in line else "未知")
                         for line in f if line.strip()]

# 輸出 CSV 檔案初始化
output_file = "../data/BYOB 台北餐廳資料庫.csv"
fieldnames = [
    "餐廳名稱", "餐廳類型", "地區", "地址", "是否收開瓶費",
    "提供酒器設備", "餐廳聯絡電話", "官方網站/社群連結",
    "備註說明", "最後更新日期", "資料來源/提供人"
]

# 建立空列表存放資料
restaurant_data = []

def mock_scrape_data(name):
    # TODO: 真實實作用 API 或網頁擷取，這裡先用假資料模擬
    return {
        "餐廳名稱": name,
        "餐廳類型": "西式",  # 假設值
        "地區": "大安區",     # 假設值
        "地址": "台北市大安區某路123號",
        "是否收開瓶費": "不確定",
        "提供酒器設備": "酒杯｜開瓶器",
        "餐廳聯絡電話": "02-1234-5678",
        "官方網站/社群連結": "https://example.com",
        "備註說明": "資料由爬蟲擷取，需人工驗證",
        "最後更新日期": datetime.date.today().isoformat(),
        "資料來源/提供人": "GPT（來源自 seed_list）"
    }

# 處理每一間餐廳
for name, source in restaurant_names:
    print(f"📡 擷取中：{name}")
    data = mock_scrape_data(name)
    data["資料來源/提供人"] = source if source else "GPT（來源不明）"
    restaurant_data.append(data)

# 寫入 CSV
with open(output_file, "w", newline="", encoding="utf-8-sig") as csvfile:
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(restaurant_data)

print(f"✅ 共寫入 {len(restaurant_data)} 筆資料至 {output_file}")
