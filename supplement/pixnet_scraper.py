import requests
import time
import csv
import os
from collections import defaultdict, Counter
from bs4 import BeautifulSoup
from tqdm import tqdm

QUERIES = [
    "自帶酒水", "自帶酒", "帶酒", "BYOB", "自備酒", "免開瓶費",
    "可帶酒", "酒自帶", "攜帶酒", "侍酒費", "洗杯費",
    "酒杯服務", "提供酒杯", "收杯費", "免酒杯費"
]

PIXNET_HITS_PATH = "data/pixnet_hits.csv"
PIXNET_SUMMARY_PATH = "data/pixnet_summary.csv"
LOG_PATH = "data/pixnet_log.txt"
SEED_LIST_PATH = "data/seed_list_raw.txt"

FAST_MODE = True  # True = 只用 meta 篩選；False = 一律深入內文分析


def read_seed_list(path):
    with open(path, 'r', encoding='utf-8') as f:
        return [line.split('#')[0].strip() for line in f if line.strip()]


def search_pixnet(broad_query, target_name, num_results=10, in_content=False):
    api_key = "7583e8557b72d1542cb957969f3a70df8ad0156866dc5cdfbf3103a1e5074ca4"
    params = {
        "engine": "google",
        "q": f"{broad_query} site:pixnet.net",
        "api_key": api_key,
        "hl": "zh-tw",
        "num": num_results
    }
    response = requests.get("https://serpapi.com/search", params=params)
    data = response.json()

    results = []
    for item in data.get("organic_results", []):
        title = item.get("title", "")
        link = item.get("link", "")
        snippet = item.get("snippet", "")

        contains_in_meta = target_name in title or target_name in link or target_name in snippet
        results.append({
            "restaurant": target_name,
            "keyword": broad_query,
            "title": title,
            "url": link,
            "snippet": snippet,
            "meta_match": contains_in_meta
        })
    return results


def fetch_pixnet_article(url):
    headers = {"User-Agent": "Mozilla/5.0"}
    response = requests.get(url, headers=headers)
    soup = BeautifulSoup(response.text, 'html.parser')

    article_body = soup.find('div', class_='article-content-inner') or \
                   soup.find('div', class_='article-content') or \
                   soup.find('div', class_='content')

    if not article_body:
        return ""

    paragraphs = article_body.find_all('p')
    content = "\n".join(p.get_text(strip=True) for p in paragraphs if p.get_text(strip=True))
    return content.strip()


def deduplicate_hits(hits):
    combined_hits = {}
    for hit in hits:
        key = (hit["restaurant"], hit["url"])
        if key not in combined_hits:
            combined_hits[key] = {
                "restaurant": hit["restaurant"],
                "keywords": set([hit["keyword"]]),
                "title": hit["title"],
                "url": hit["url"],
                "snippet": hit["snippet"]
            }
        else:
            combined_hits[key]["keywords"].add(hit["keyword"])
    return list(combined_hits.values())


def save_hits_to_csv(hits, path, overwrite=False):
    mode = 'w' if overwrite else 'a'
    write_header = overwrite or not os.path.exists(path)
    with open(path, mode=mode, newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=["餐廳名稱", "命中關鍵字", "文章標題", "網址", "內文摘要"])
        if write_header:
            writer.writeheader()
        for hit in hits:
            writer.writerow({
                "餐廳名稱": hit["restaurant"],
                "命中關鍵字": ";".join(sorted(hit["keywords"])),
                "文章標題": hit["title"],
                "網址": hit["url"],
                "內文摘要": hit["snippet"]
            })


def log_message(message):
    print(message)
    with open(LOG_PATH, 'a', encoding='utf-8') as log_file:
        log_file.write(message + '\n')


if __name__ == "__main__":
    targets = read_seed_list(SEED_LIST_PATH)

    all_hits_total = []
    keyword_counter = Counter()
    with open(LOG_PATH, 'w', encoding='utf-8') as f:
        f.write("Pixnet 命中紀錄\n\n")

    for target_name in tqdm(targets, desc="查詢進度"):
        log_message(f"\n🔍 餐廳：{target_name}")
        phase1_hits = []
        final_hits = []

        for broad_query in QUERIES:
            log_message(f"→ 嘗試關鍵字：{broad_query}")
            meta_hits = search_pixnet(broad_query, target_name, in_content=False)
            filtered = [hit for hit in meta_hits if hit["meta_match"] and "pixnet.net" in hit["url"]]
            phase1_hits.extend(filtered)
            time.sleep(1)

        for hit in phase1_hits:
            content = fetch_pixnet_article(hit["url"]) if not FAST_MODE else ""
            if FAST_MODE or target_name in content:
                hit["snippet"] = content[:300] if content else hit["snippet"]
                final_hits.append(hit)
                keyword_counter[hit["keyword"]] += 1
                log_message(f"✅ 命中：{hit['title']} → {hit['url']}")

        if final_hits:
            deduped_hits = deduplicate_hits(final_hits)
            all_hits_total.extend(deduped_hits)
        else:
            log_message("❌ 無命中")

    if all_hits_total:
        save_hits_to_csv(all_hits_total, PIXNET_HITS_PATH, overwrite=True)
        print("\n✅ 完成輸出，共 {} 筆命中結果。".format(len(all_hits_total)))
        print("📊 命中關鍵字統計：")
        for kw, count in keyword_counter.items():
            print(f"{kw}：{count} 筆")
    else:
        print("❌ 無命中結果。")
