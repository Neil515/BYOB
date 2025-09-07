import requests
import json
import time
import pandas as pd
import os
from datetime import datetime
from config import GOOGLE_PLACES_API_KEY, SEARCH_QUERY, MAX_RESULTS, OUTPUT_FILE

class GooglePlacesCrawler:
    def __init__(self, api_key):
        self.api_key = api_key
        self.base_url = "https://maps.googleapis.com/maps/api/place"
        self.session = requests.Session()
        
    def search_restaurants(self, query, max_results=20):
        """
        使用Text Search API搜尋餐廳
        """
        url = f"{self.base_url}/textsearch/json"
        params = {
            'query': query,
            'key': self.api_key,
            'language': 'zh-TW',
            'region': 'tw'
        }
        
        restaurants = []
        next_page_token = None
        
        while len(restaurants) < max_results:
            if next_page_token:
                params['pagetoken'] = next_page_token
                time.sleep(2)  # 等待2秒，避免API限制
            
            try:
                response = self.session.get(url, params=params)
                response.raise_for_status()
                data = response.json()
                
                if data['status'] != 'OK':
                    print(f"API錯誤: {data['status']}")
                    break
                
                restaurants.extend(data['results'])
                
                # 檢查是否有下一頁
                next_page_token = data.get('next_page_token')
                if not next_page_token:
                    break
                    
            except requests.exceptions.RequestException as e:
                print(f"請求錯誤: {e}")
                break
        
        return restaurants[:max_results]
    
    def get_place_details(self, place_id):
        """
        使用Place Details API獲取餐廳詳細資訊
        """
        url = f"{self.base_url}/details/json"
        params = {
            'place_id': place_id,
            'key': self.api_key,
            'language': 'zh-TW',
            'fields': 'name,formatted_address,formatted_phone_number,website,rating,user_ratings_total'
        }
        
        try:
            response = self.session.get(url, params=params)
            response.raise_for_status()
            data = response.json()
            
            if data['status'] == 'OK':
                return data['result']
            else:
                print(f"Place Details API錯誤: {data['status']}")
                return None
                
        except requests.exceptions.RequestException as e:
            print(f"請求錯誤: {e}")
            return None
    
    def crawl_restaurants(self, query, max_results=20):
        """
        主要爬蟲功能
        """
        print(f"開始搜尋: {query}")
        
        # 步驟1: 搜尋餐廳列表
        restaurants = self.search_restaurants(query, max_results)
        print(f"找到 {len(restaurants)} 家餐廳")
        
        # 步驟2: 獲取每家餐廳的詳細資訊
        detailed_restaurants = []
        
        for i, restaurant in enumerate(restaurants, 1):
            print(f"正在處理第 {i}/{len(restaurants)} 家餐廳: {restaurant['name']}")
            
            place_id = restaurant['place_id']
            details = self.get_place_details(place_id)
            
            if details:
                restaurant_info = {
                    'name': details.get('name', ''),
                    'address': details.get('formatted_address', ''),
                    'phone': details.get('formatted_phone_number', ''),
                    'website': details.get('website', ''),
                    'rating': details.get('rating', ''),
                    'user_ratings_total': details.get('user_ratings_total', ''),
                    'place_id': place_id
                }
                detailed_restaurants.append(restaurant_info)
            
            # 避免API限制
            time.sleep(0.1)
        
        return detailed_restaurants
    
    def generate_unique_filename(self, base_filename, query):
        """
        生成唯一的檔案名稱，避免重複
        """
        # 取得當前時間
        now = datetime.now()
        timestamp = now.strftime("%Y%m%d_%H%M%S")
        
        # 清理查詢字串，移除特殊字元
        clean_query = query.replace(" ", "_").replace("/", "_").replace("\\", "_")
        clean_query = "".join(c for c in clean_query if c.isalnum() or c in "_-")
        
        # 分割檔案名稱和副檔名
        if "." in base_filename:
            name, ext = base_filename.rsplit(".", 1)
        else:
            name = base_filename
            ext = "xlsx"
        
        # 生成新的檔案名稱
        new_filename = f"{name}_{clean_query}_{timestamp}.{ext}"
        
        # 檢查檔案是否存在，如果存在則加入編號
        counter = 1
        original_filename = new_filename
        while os.path.exists(new_filename):
            if "." in original_filename:
                name_part, ext_part = original_filename.rsplit(".", 1)
                new_filename = f"{name_part}_{counter:03d}.{ext_part}"
            else:
                new_filename = f"{original_filename}_{counter:03d}"
            counter += 1
        
        return new_filename

def main():
    # 檢查API金鑰
    if GOOGLE_PLACES_API_KEY == "your_api_key_here":
        print("請先在config.py中設定您的Google Places API金鑰")
        return
    
    # 建立爬蟲實例
    crawler = GooglePlacesCrawler(GOOGLE_PLACES_API_KEY)
    
    # 開始爬取
    restaurants = crawler.crawl_restaurants(SEARCH_QUERY, MAX_RESULTS)
    
    if restaurants:
        # 顯示結果
        print(f"\n成功獲取 {len(restaurants)} 家餐廳的詳細資訊:")
        for restaurant in restaurants:
            print(f"\n餐廳名稱: {restaurant['name']}")
            print(f"地址: {restaurant['address']}")
            print(f"電話: {restaurant['phone']}")
            print(f"網站: {restaurant['website']}")
            print(f"評分: {restaurant['rating']}")
            print("-" * 50)
        
        # 生成唯一檔案名稱並儲存到Excel
        unique_filename = crawler.generate_unique_filename(OUTPUT_FILE, SEARCH_QUERY)
        df = pd.DataFrame(restaurants)
        df.to_excel(unique_filename, index=False, engine='openpyxl')
        print(f"結果已儲存到: {unique_filename}")
    else:
        print("沒有找到任何餐廳")

if __name__ == "__main__":
    main()
