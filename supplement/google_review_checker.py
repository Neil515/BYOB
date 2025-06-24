import googlemaps
import csv
import time
import os
from collections import defaultdict, Counter
from dotenv import load_dotenv  # ✅ 新增 .env 支援
from tqdm import tqdm  # ✅ 加入進度條

load_dotenv()  # ✅ 載入 .env 檔案中的變數

GOOGLE_HITS_PATH = "data/google_hits.csv"
SEED_LIST_PATH = "data/seed_list_raw.txt"
LOG_PATH = "data/google_review_log.txt"

QUERIES = [
    # 主題型
    "自帶酒水", "自帶酒", "帶酒", "BYOB", "自備酒", "免開瓶費",
    # 行為型
    "可帶酒", "酒自帶", "攜帶酒", "侍酒費", "洗杯費",
    # 服務型
    "酒杯服務", "提供酒杯", "收杯費", "免酒杯費"
]

# 輸出欄位格式與 pixnet_hits.csv 對齊
CSV_HEADERS = ["餐廳名稱", "命中關鍵字", "命中類型", "命中內容摘要", "判斷結果", "資料來源"]


def read_seed_list(path):
    with open(path, 'r', encoding='utf-8') as f:
        return [line.split('#')[0].strip() for line in f if line.strip()]


def search_google_reviews(gmaps, restaurant_name):
    search_results = gmaps.places(query=restaurant_name, language='zh-TW')
    if not search_results['results']:
        return [], ""

    place_id = search_results['results'][0]['place_id']
    details = gmaps.place(place_id=place_id, language='zh-TW')
    result = details.get('result', {})

    reviews = [r['text'] for r in result.get('reviews', [])]
    website = result.get('website', '')
    return reviews, website


def analyze_reviews(texts):
    hits = []
    for text in texts:
        matched_keywords = [kw for kw in QUERIES if kw in text]
        if matched_keywords:
            hits.append({
                "keywords": matched_keywords,
                "content": text
            })
    return hits


def save_hits_to_csv(hits, path, overwrite=False):
    mode = 'w' if overwrite else 'a'
    write_header = overwrite or not os.path.exists(path)
    with open(path, mode=mode, newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=CSV_HEADERS)
        if write_header:
            writer.writeheader()
        for hit in hits:
            writer.writerow(hit)


def log_message(message):
    print(message)
    with open(LOG_PATH, 'a', encoding='utf-8') as log_file:
        log_file.write(message + '\n')


if __name__ == "__main__":
    API_KEY = os.getenv("GOOGLE_MAPS_API_KEY")
    if not API_KEY:
        raise ValueError("❌ 請在 .env 檔案中設定 GOOGLE_MAPS_API_KEY")

    gmaps = googlemaps.Client(key=API_KEY)
    targets = read_seed_list(SEED_LIST_PATH)
    all_hits = []
    source_counter = Counter()

    with open(LOG_PATH, 'w', encoding='utf-8') as f:
        f.write("Google Review 命中紀錄\n\n")

    for name in tqdm(targets, desc="查詢進度"):
        log_message(f"\n🔍 餐廳：{name}")
        try:
            reviews, website_text = search_google_reviews(gmaps, name)
        except Exception as e:
            log_message(f"⚠️ 錯誤：{e}")
            continue

        text_sources = reviews + ([website_text] if website_text else [])
        analyzed_hits = analyze_reviews(text_sources)

        if analyzed_hits:
            for result in analyzed_hits:
                hit_type = "評論" if result["content"] in reviews else "官網"
                source_counter[hit_type] += 1
                all_hits.append({
                    "餐廳名稱": name,
                    "命中關鍵字": ";".join(result["keywords"]),
                    "命中類型": hit_type,
                    "命中內容摘要": result["content"][:100],
                    "判斷結果": "可能 BYOB",
                    "資料來源": "Google Maps"
                })
                log_message(f"✅ 命中（{hit_type}）：{'；'.join(result['keywords'])} → {result['content'][:50]}...")
        else:
            log_message("❌ 無命中")
        time.sleep(1)

    if all_hits:
        save_hits_to_csv(all_hits, GOOGLE_HITS_PATH, overwrite=True)
        log_message(f"\n✅ 完成輸出，共 {len(all_hits)} 筆命中結果。")
        log_message(f"📊 命中來源統計：評論 {source_counter['評論']} 筆、官網 {source_counter['官網']} 筆")
    else:
        log_message("❌ 無命中結果。")
