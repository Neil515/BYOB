import requests
import time
from bs4 import BeautifulSoup


QUERIES = [
    "自帶酒水",
    "BYOB",
    "開瓶費",
    "自帶酒",
    "餐廳 酒杯"
]


def search_pixnet(broad_query, target_name, num_results=10):
    """
    使用 SerpAPI 搜尋 Pixnet 文章，並篩選標題、摘要或連結中含有指定餐廳名稱者
    """
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
        if "pixnet.net" in link and (target_name in title or target_name in link or target_name in snippet):
            results.append({"title": title, "url": link})
    return results


def fetch_pixnet_article(url):
    """
    抓取 Pixnet 文章內容文字摘要（修正版）
    """
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


if __name__ == "__main__":
    targets = [
        "Allez Bistro",
        "Dancing Pig",
        "VG Seafood Bar",
        "花滔廚房",
        "Big Pancia"
    ]

    for target_name in targets:
        print(f"\n=== 搜尋：{target_name} ===")
        matched = False
        for broad_query in QUERIES:
            print(f"→ 嘗試關鍵字：{broad_query}")
            results = search_pixnet(broad_query, target_name)
            if not results:
                continue
            for result in results:
                print("[文章標題]", result['title'])
                print("[連結]", result['url'])
                content = fetch_pixnet_article(result['url'])
                print("[內文摘要]", content[:300], "...\n")
                matched = True
                time.sleep(2)
            if matched:
                break
        if not matched:
            print("[無搜尋結果]")
