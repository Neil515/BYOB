import csv
import datetime
import os
import re
import requests
from bs4 import BeautifulSoup
from dotenv import load_dotenv
import googlemaps

# 載入 .env 並讀取 API 金鑰
load_dotenv()
api_key = os.getenv("GOOGLE_MAPS_API_KEY")
gmaps = googlemaps.Client(key=api_key)

# 從 seed_list_raw.txt 讀取餐廳名稱
with open("data/seed_list_raw.txt", "r", encoding="utf-8") as f:
    restaurant_names = [(line.split("#")[0].strip(), line.split("#")[1].strip() if "#" in line else "未知")
                         for line in f if line.strip()]

# 輸出 CSV 檔案初始化
output_file = "data/BYOB 台北餐廳資料庫.csv"
fieldnames = [
    "餐廳名稱", "餐廳類型", "地區", "地址", "是否收開瓶費",
    "提供酒器設備", "餐廳聯絡電話", "官方網站/社群連結",
    "備註說明", "最後更新日期", "資料來源/提供人"
]

restaurant_data = []

# 擷取網站文字

def extract_website_text(url):
    try:
        resp = requests.get(url, timeout=5)
        soup = BeautifulSoup(resp.text, "html.parser")

        parts = []

        # 抓 title
        title = soup.title.string if soup.title else ""
        if title:
            parts.append(title.strip())

        # 抓 meta description
        meta = soup.find("meta", attrs={"name": "description"})
        if meta and meta.get("content"):
            parts.append(meta["content"].strip())

        # 抓前幾段 <p>
        p_texts = [p.get_text(strip=True) for p in soup.find_all("p")]
        parts.extend(p_texts[:5])

        # 抓部分 <div> 文字
        div_texts = [d.get_text(strip=True) for d in soup.find_all("div") if len(d.get_text(strip=True)) > 20]
        parts.extend(div_texts[:3])

        return " ".join(parts)
    except:
        return ""

# 偵測開瓶費資訊

def detect_corkage_fee(text):
    keywords_free = [
        r"免[收|付]開瓶費", r"不收開瓶費", r"開瓶費\s*[:：]?\s*0",
        r"BYOB[免費|free]", r"corkage\s*fee\s*(NT\$)?\s*0",
        r"免開瓶服務費", r"無開瓶費", r"開瓶免費",
        r"BYOB.*無須額外費用", r"自帶酒.*不收費"
    ]
    keywords_paid = [
        r"開瓶費\s*[:：]?\s*NT?\$?\s*(\d+)",
        r"BYOB.*(酌收|須付).*(NT?\$?\s*\d+)",
        r"corkage\s*fee\s*[:：]?\s*(NT\$)?\s*(\d+)",
        r"收取開瓶費.*NT?\$?\s*(\d+)", r"自帶酒.*加收.*NT?\$?\s*(\d+)"
    ]
    for kw in keywords_free:
        if re.search(kw, text, flags=re.IGNORECASE):
            return "否", "描述中表示免開瓶費"
    for kw in keywords_paid:
        match = re.search(kw, text, flags=re.IGNORECASE)
        if match:
            amount = match.group(1) or match.group(2)
            return "是", f"擷取金額 NT${amount}"
    return "不確定", "—"

# 偵測酒器設備資訊
def detect_equipment(text):
    equipment_keywords = [
        ("酒杯", [r"提供.*酒杯", r"有.*酒杯", r"wine glass"]),
        ("開瓶器", [r"提供.*開瓶器", r"有.*開瓶器", r"corkscrew"]),
        ("冰桶", [r"提供.*冰桶", r"有.*冰桶", r"ice bucket"])
    ]
    result = []
    for label, patterns in equipment_keywords:
        for p in patterns:
            if re.search(p, text, flags=re.IGNORECASE):
                result.append(label)
                break
    return "｜".join(result) if result else "未知"

# 更新 Google Maps 擷取流程（整合開瓶費與設備偵測）
def fetch_from_google_maps(name):
    try:
        search_result = gmaps.places(name + " 台北", language='zh-TW')
        if not search_result["results"]:
            print(f"⚠️ 無搜尋結果：{name}")
            return None

        place = search_result["results"][0]
        place_id = place["place_id"]
        details = gmaps.place(place_id=place_id, language='zh-TW')["result"]

        address = details.get("formatted_address", "未知")
        phone = details.get("formatted_phone_number", "—")
        website = details.get("website", "—")

        district = "未知"
        for d in ["大安區", "信義區", "中山區", "中正區", "松山區", "萬華區", "士林區", "北投區", "內湖區", "南港區", "文山區"]:
            if d in address:
                district = d
                break

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

        corkage_status = "不確定"
        corkage_note = "由 Google Maps API 擷取，需人工驗證"
        equipment_info = "未知"

        if website != "—":
            desc = extract_website_text(website)
            if desc:
                corkage_status, corkage_note = detect_corkage_fee(desc)
                equipment_info = detect_equipment(desc)
                print(f"🔍 {name} 擷取描述成功 → 開瓶費：{corkage_status}｜設備：{equipment_info}｜備註：{corkage_note}")
            else:
                print(f"⚠️ {name} 網站描述擷取失敗或為空")
        else:
            print(f"⚠️ {name} 無網站連結，略過開瓶費偵測")

        return {
            "餐廳名稱": name,
            "餐廳類型": place_type,
            "地區": district,
            "地址": address,
            "是否收開瓶費": corkage_status,
            "提供酒器設備": equipment_info,
            "餐廳聯絡電話": phone,
            "官方網站/社群連結": website,
            "備註說明": corkage_note,
            "最後更新日期": datetime.date.today().isoformat(),
            "資料來源/提供人": "—"
        }
    except Exception as e:
        print(f"❌ 查詢失敗：{name}，原因：{e}")
        return None


# 主程式：處理每間餐廳
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
