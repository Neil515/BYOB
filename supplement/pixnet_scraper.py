import requests
import time
import csv
import os
from collections import defaultdict
from bs4 import BeautifulSoup

QUERIES = [
    # 主題型
    "自帶酒水",
    "自帶酒",
    "帶酒",
    "BYOB",
    "自備酒",
    "免開瓶費",
    # 行為型
    "可帶酒",
    "酒自帶",
    "攜帶酒",
    "侍酒費",
    "洗杯費",
    # 服務型
    "酒杯服務",
    "提供酒杯",
    "收杯費",
    "免酒杯費"
]

PIXNET_HITS_PATH = "data/pixnet_hits.csv"
PIXNET_SUMMARY_PATH = "data/pixnet_summary.csv"

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
        contains_in_content = False

        content = ""
        if in_content and "pixnet.net" in link:
            content = fetch_pixnet_article(link)
            contains_in_content = target_name in content

        if "pixnet.net" in link and (contains_in_meta or contains_in_content):
            results.append({
                "restaurant": target_name,
                "keyword": broad_query,
                "title": title,
                "url": link,
                "snippet": content[:300] if content else snippet
            })
    return results

def fetch_pixnet_article(url):
    headers = {"User-Agent": "Mozilla/5.0"}
    response = requests.get(url, headers=headers)
    soup = BeautifulSoup(response.text, 'html.parser')

    article_body = soup.find('div', class_='article-content-inner')
    if not article_body:
        article_body = soup.find('div', class_='article-content')
    if not article_body:
        article_body = soup.find('div', class_='content')

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

def save_summary_to_csv(summary_restaurant, summary_keywords, path):
    with open(path, mode='w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(["分類", "名稱", "命中篇數"])
        for restaurant, count in summary_restaurant.items():
            writer.writerow(["餐廳", restaurant, count])
        for keyword, count in summary_keywords.items():
            writer.writerow(["關鍵字", keyword, count])

def print_summary_stats(hits):
    unique_by_restaurant = {(hit["restaurant"], hit["url"]): hit for hit in hits}
    keyword_url_set = set()
    for hit in hits:
        for kw in hit["keywords"]:
            keyword_url_set.add((kw, hit["url"]))

    summary_restaurant = defaultdict(int)
    summary_keywords = defaultdict(int)

    for (restaurant, _), _hit in unique_by_restaurant.items():
        summary_restaurant[restaurant] += 1
    for (keyword, _url) in keyword_url_set:
        summary_keywords[keyword] += 1

    print("\n=== 命中文章統計摘要（餐廳） ===")
    for restaurant, count in summary_restaurant.items():
        print(f"{restaurant} 命中 {count} 篇文章")

    print("\n=== 命中文章統計摘要（關鍵字） ===")
    for keyword, count in summary_keywords.items():
        print(f"關鍵字「{keyword}」命中 {count} 篇文章")

    save_summary_to_csv(summary_restaurant, summary_keywords, PIXNET_SUMMARY_PATH)

if __name__ == "__main__":
    targets = [
        "Allez Bistro",
        "Dancing Pig",
        "VG Seafood Bar",
        "花滔廚房",
        "Big Pancia"
    ]

    all_hits_total = []

    for target_name in targets:
        print(f"\n=== 搜尋：{target_name} ===")
        all_hits = []
        for broad_query in QUERIES:
            print(f"→ 嘗試關鍵字：{broad_query}")
            results = search_pixnet(broad_query, target_name, in_content=True)
            if not results:
                continue
            all_hits.extend(results)
            for result in results:
                print("[文章標題]", result['title'])
                print("[連結]", result['url'])
                print("[內文摘要]", result['snippet'], "...\n")
                time.sleep(2)
        if all_hits:
            deduped_hits = deduplicate_hits(all_hits)
            all_hits_total.extend(deduped_hits)
        else:
            print("[無搜尋結果]")

    if all_hits_total:
        save_hits_to_csv(all_hits_total, PIXNET_HITS_PATH, overwrite=True)
        print_summary_stats(all_hits_total)
