import csv
import datetime
import os
from dotenv import load_dotenv
import googlemaps

# 載入 .env 並讀取 API 金鑰
load_dotenv()
api_key = os.getenv("GOOGLE_MAPS_API_KEY")
gmaps = googlemaps.Client(key=api_key)

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

def fetch_from_google_maps(name):
    try:
        search_result = gmaps.places(name + " 台北", language='zh-TW')
        if not search_result["results"]:
            return None

        place = search_result["results"][0]
        place_id = place["place_id"]
        details = gmaps.place(place_id=place_id, language='zh-TW')["result"]

        address = details.get("formatted_address", "未知")
        phone = details.get("formatted_phone_number", "—")
        website = details.get("website", "—")

        # 從地址自動擷取台北行政區名
        district = "未知"
        for d in ["大安區", "信義區", "中山區", "中正區", "松山區", "萬華區", "士林區", "北投區", "內湖區", "南港區", "文山區"]:
            if d in address:
                district = d
                break

        # 根據 types 自動判斷餐廳類型
        types = details.get("types", [])
        type_map = {
            "restaurant": "西式",
            "bar": "酒吧",
            "cafe": "咖啡館"
        }
        place_type = "未知"
        for t in types:
            if t in type_map:
                place_type = type_map[t]
                break

        return {
            "餐廳名稱": name,
            "餐廳類型": place_type,
            "地區": district,
            "地址": address,
            "是否收開瓶費": "不確定",
            "提供酒器設備": "未知",
            "餐廳聯絡電話": phone,
            "官方網站/社群連結": website,
            "備註說明": "由 Google Maps API 擷取，需人工驗證",
            "最後更新日期": datetime.date.today().isoformat(),
            "資料來源/提供人": "—"
        }
    except Exception as e:
        print(f"❌ 查詢失敗：{name}，原因：{e}")
        return None

# 處理每一間餐廳
for name, source in restaurant_names:
    print(f"📡 擷取中：{name}")
    data = fetch_from_google_maps(name)
    if data:
        data["資料來源/提供人"] = source if source else "GPT（來源不明）"
        restaurant_data.append(data)

# 寫入 CSV
with open(output_file, "w", newline="", encoding="utf-8-sig") as csvfile:
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(restaurant_data)

print(f"✅ 共寫入 {len(restaurant_data)} 筆資料至 {output_file}")
