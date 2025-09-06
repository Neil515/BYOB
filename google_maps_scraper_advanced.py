#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Google Maps 餐廳爬蟲程式 - 進階版
支援直接輸入Google Maps URL或搜尋關鍵字
"""

import time
import pandas as pd
import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from urllib.parse import urlparse, parse_qs
import logging
import re

# 設定日誌
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

class GoogleMapsScraper:
    def __init__(self, headless=True):
        """
        初始化爬蟲
        
        Args:
            headless (bool): 是否使用無頭模式
        """
        self.driver = None
        self.headless = headless
        self.results = []
        
    def setup_driver(self):
        """設定Chrome瀏覽器驅動程式"""
        chrome_options = Options()
        if self.headless:
            chrome_options.add_argument('--headless')
        
        # 基本設定
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--window-size=1920,1080')
        
        # 反檢測設定
        chrome_options.add_argument('--disable-blink-features=AutomationControlled')
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        # 更真實的User-Agent
        chrome_options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
        
        # 禁用圖片和CSS載入以提高速度
        prefs = {
            "profile.managed_default_content_settings.images": 2,
            "profile.default_content_setting_values.notifications": 2
        }
        chrome_options.add_experimental_option("prefs", prefs)
        
        try:
            self.driver = webdriver.Chrome(options=chrome_options)
            
            # 執行反檢測腳本
            self.driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
            
            logger.info("Chrome瀏覽器驅動程式已啟動")
        except Exception as e:
            logger.error(f"無法啟動Chrome瀏覽器: {e}")
            raise
    
    def search_restaurants(self, search_input, max_results=50):
        """
        搜尋餐廳 - 支援URL或關鍵字
        
        Args:
            search_input (str): Google Maps URL 或搜尋關鍵字
            max_results (int): 最大結果數量
        """
        if not self.driver:
            self.setup_driver()
        
        # 判斷輸入是URL還是關鍵字
        if self._is_google_maps_url(search_input):
            # 直接使用URL
            logger.info(f"正在載入Google Maps URL: {search_input}")
            self.driver.get(search_input)
            
            # 等待頁面載入
            time.sleep(5)
            
            # 檢查是否成功載入Google Maps
            current_url = self.driver.current_url
            page_title = self.driver.title
            
            logger.info(f"當前URL: {current_url}")
            logger.info(f"頁面標題: {page_title}")
            
            # 檢查是否被重定向到非Google Maps頁面
            if "maps.google.com" not in current_url and "google.com/maps" not in current_url:
                logger.error(f"URL重定向到非Google Maps頁面: {current_url}")
                logger.error("可能的原因：")
                logger.error("1. Google的反爬蟲機制")
                logger.error("2. 網路連線問題")
                logger.error("3. URL格式錯誤")
                return
            
            # 檢查頁面標題是否包含Google Maps
            if "Google Maps" not in page_title and "地圖" not in page_title:
                logger.warning(f"頁面標題異常: {page_title}")
                logger.warning("可能沒有正確載入Google Maps")
            
            logger.info("Google Maps頁面載入成功")
        else:
            # 使用關鍵字搜尋
            self.driver.get("https://www.google.com/maps")
            
            # 等待搜尋框出現並輸入搜尋關鍵字
            try:
                search_box = WebDriverWait(self.driver, 10).until(
                    EC.presence_of_element_located((By.ID, "searchboxinput"))
                )
                search_box.clear()
                search_box.send_keys(search_input)
                
                # 點擊搜尋按鈕
                search_button = self.driver.find_element(By.ID, "searchbox-searchbutton")
                search_button.click()
                
                logger.info(f"已搜尋: {search_input}")
                
            except TimeoutException:
                logger.error("搜尋框載入超時")
                return
        
        # 等待結果載入
        time.sleep(3)
        
        # 滾動頁面載入更多結果
        self._scroll_to_load_results(max_results)
        
        # 提取餐廳資訊
        self._extract_restaurant_info()
    
    def _is_google_maps_url(self, input_string):
        """判斷輸入是否為Google Maps URL"""
        google_maps_patterns = [
            r'https?://.*maps\.google\.com',
            r'https?://.*goo\.gl/maps',
            r'https?://maps\.app\.goo\.gl'
        ]
        
        for pattern in google_maps_patterns:
            if re.match(pattern, input_string):
                return True
        return False
    
    def _scroll_to_load_results(self, max_results):
        """滾動頁面載入更多搜尋結果"""
        last_height = self.driver.execute_script("return document.body.scrollHeight")
        results_loaded = 0
        
        while results_loaded < max_results:
            # 滾動到頁面底部
            self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(2)
            
            # 檢查是否有新內容載入
            new_height = self.driver.execute_script("return document.body.scrollHeight")
            if new_height == last_height:
                break
            
            last_height = new_height
            
            # 計算已載入的結果數量
            try:
                results_elements = self.driver.find_elements(By.CSS_SELECTOR, "[data-result-index]")
                results_loaded = len(results_elements)
                logger.info(f"已載入 {results_loaded} 個結果")
            except:
                pass
    
    def _extract_restaurant_info(self):
        """提取餐廳資訊"""
        try:
            # 等待頁面載入
            time.sleep(5)
            
            # 嘗試多種選擇器來找到餐廳結果
            restaurant_selectors = [
                "[data-result-index]",  # 舊版選擇器
                "[jsaction*='pane.resultSection.click']",  # 新版選擇器
                ".Nv2PK",  # 餐廳卡片
                ".THOPZb",  # 搜尋結果項目
                "[role='button'][jsaction*='pane.resultSection.click']",  # 按鈕式結果
                ".lI9IFe",  # 列表項目
                ".VkpGBb"   # 結果容器
            ]
            
            restaurant_elements = []
            for selector in restaurant_selectors:
                try:
                    elements = self.driver.find_elements(By.CSS_SELECTOR, selector)
                    if elements:
                        restaurant_elements = elements
                        logger.info(f"使用選擇器 '{selector}' 找到 {len(elements)} 個餐廳結果")
                        break
                except:
                    continue
            
            if not restaurant_elements:
                logger.warning("無法找到餐廳結果元素，嘗試滾動載入更多內容")
                # 嘗試滾動載入更多內容
                self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
                time.sleep(3)
                
                # 再次嘗試尋找元素
                for selector in restaurant_selectors:
                    try:
                        elements = self.driver.find_elements(By.CSS_SELECTOR, selector)
                        if elements:
                            restaurant_elements = elements
                            logger.info(f"滾動後使用選擇器 '{selector}' 找到 {len(elements)} 個餐廳結果")
                            break
                    except:
                        continue
            
            if not restaurant_elements:
                logger.error("無法找到任何餐廳結果元素")
                return
            
            logger.info(f"開始提取 {len(restaurant_elements)} 個餐廳的資訊")
            
            for i, element in enumerate(restaurant_elements[:20]):  # 限制前20個
                try:
                    # 滾動到元素可見
                    self.driver.execute_script("arguments[0].scrollIntoView(true);", element)
                    time.sleep(1)
                    
                    # 點擊餐廳項目
                    element.click()
                    time.sleep(3)
                    
                    # 提取餐廳名稱
                    restaurant_name = self._get_restaurant_name()
                    
                    # 提取網站連結
                    website_url = self._get_website_url()
                    
                    if restaurant_name:
                        # 判斷是否為Facebook連結
                        is_facebook = self._is_facebook_url(website_url) if website_url else False
                        
                        result = {
                            '店名': restaurant_name,
                            '官網': website_url if website_url and not is_facebook else '',
                            'social': website_url if website_url and is_facebook else ''
                        }
                        
                        self.results.append(result)
                        logger.info(f"已提取: {restaurant_name} - {website_url or '無網站'}")
                    else:
                        logger.warning(f"第 {i+1} 個餐廳無法提取名稱")
                    
                except Exception as e:
                    logger.warning(f"提取第 {i+1} 個餐廳資訊時發生錯誤: {e}")
                    continue
                    
        except Exception as e:
            logger.error(f"提取餐廳資訊時發生錯誤: {e}")
    
    def _get_restaurant_name(self):
        """獲取餐廳名稱"""
        try:
            # 嘗試多種選擇器來找到餐廳名稱
            selectors = [
                "h1[data-attrid='title']",
                "h1",
                "[data-attrid='title']",
                ".x3AX1-LfntMc-header-title-title",
                ".DUwDvf",  # 新版標題
                ".SPZz6b",  # 餐廳名稱
                ".qrShPb",  # 標題容器
                "[data-value*='title']",  # 包含title的屬性
                ".fontHeadlineLarge"  # 大標題字體
            ]
            
            for selector in selectors:
                try:
                    name_element = self.driver.find_element(By.CSS_SELECTOR, selector)
                    if name_element.text.strip():
                        return name_element.text.strip()
                except:
                    continue
            
            # 如果上述選擇器都失敗，嘗試從頁面標題提取
            try:
                page_title = self.driver.title
                if page_title and "Google Maps" not in page_title:
                    return page_title.split(" - ")[0]  # 取標題的第一部分
            except:
                pass
            
            return None
        except:
            return None
    
    def _get_website_url(self):
        """獲取網站連結"""
        try:
            # 尋找"複製網站"按鈕或網站連結
            # 嘗試多種方式找到網站連結
            
            # 方法1: 尋找包含"複製網站"文字的按鈕
            try:
                copy_buttons = self.driver.find_elements(By.XPATH, "//button[contains(text(), '複製網站')]")
                if copy_buttons:
                    # 點擊複製按鈕
                    copy_buttons[0].click()
                    time.sleep(1)
                    
                    # 嘗試從剪貼簿獲取連結（需要額外處理）
                    # 這裡我們改用其他方法
            except:
                pass
            
            # 方法2: 尋找網站連結元素
            website_selectors = [
                "a[href*='http']",
                "[data-value*='http']",
                ".section-action-chip[href*='http']"
            ]
            
            for selector in website_selectors:
                try:
                    elements = self.driver.find_elements(By.CSS_SELECTOR, selector)
                    for element in elements:
                        href = element.get_attribute('href')
                        if href and ('http' in href) and not self._is_google_maps_url(href):
                            return href
                except:
                    continue
            
            # 方法3: 在詳細資訊面板中尋找
            try:
                # 點擊"網站"或相關按鈕
                website_buttons = self.driver.find_elements(By.XPATH, "//button[contains(text(), '網站') or contains(text(), 'Website')]")
                if website_buttons:
                    website_buttons[0].click()
                    time.sleep(1)
                    
                    # 尋找新出現的連結
                    links = self.driver.find_elements(By.CSS_SELECTOR, "a[href*='http']")
                    for link in links:
                        href = link.get_attribute('href')
                        if href and not self._is_google_maps_url(href):
                            return href
            except:
                pass
            
            return None
            
        except Exception as e:
            logger.warning(f"獲取網站連結時發生錯誤: {e}")
            return None
    
    def _is_google_maps_url(self, url):
        """判斷是否為Google Maps相關URL"""
        if not url:
            return True
        
        google_maps_domains = [
            'maps.google.com',
            'google.com/maps',
            'goo.gl/maps'
        ]
        
        parsed_url = urlparse(url)
        domain = parsed_url.netloc.lower()
        
        return any(gm_domain in domain for gm_domain in google_maps_domains)
    
    def _is_facebook_url(self, url):
        """判斷是否為Facebook URL"""
        if not url:
            return False
        
        facebook_domains = [
            'facebook.com',
            'fb.com',
            'm.facebook.com'
        ]
        
        parsed_url = urlparse(url)
        domain = parsed_url.netloc.lower()
        
        return any(fb_domain in domain for fb_domain in facebook_domains)
    
    def save_to_excel(self, filename='restaurants_data.xlsx'):
        """將結果儲存為Excel檔案"""
        if not self.results:
            logger.warning("沒有資料可儲存")
            return
        
        try:
            df = pd.DataFrame(self.results)
            df.to_excel(filename, index=False, engine='openpyxl')
            logger.info(f"已將 {len(self.results)} 筆資料儲存至 {filename}")
        except Exception as e:
            logger.error(f"儲存Excel檔案時發生錯誤: {e}")
    
    def close(self):
        """關閉瀏覽器"""
        if self.driver:
            self.driver.quit()
            logger.info("瀏覽器已關閉")

def main():
    """主程式"""
    print("=" * 70)
    print("    Google Maps 餐廳爬蟲程式 - 進階版")
    print("=" * 70)
    print()
    
    print("🔍 支援兩種輸入方式：")
    print("1. 搜尋關鍵字（例如：台北 西餐廳）")
    print("2. Google Maps URL（例如：https://maps.google.com/...）")
    print()
    
    # 讓使用者選擇輸入方式
    print("請選擇輸入方式：")
    print("1. 輸入搜尋關鍵字")
    print("2. 輸入Google Maps URL")
    
    choice = input("請選擇 (1 或 2): ").strip()
    
    if choice == "1":
        # 搜尋關鍵字模式
        print()
        print("請輸入您要搜尋的關鍵字：")
        print("範例：台北 (西餐廳 OR 義式餐廳 OR 法式餐廳 OR 日式餐廳) -連鎖 -速食")
        print()
        
        search_input = input("搜尋關鍵字: ").strip()
        
        if not search_input:
            print("❌ 錯誤：請輸入搜尋關鍵字")
            return
            
    elif choice == "2":
        # URL模式
        print()
        print("請輸入Google Maps的完整URL：")
        print("範例：https://www.google.com/maps/search/台北+西餐廳")
        print()
        
        search_input = input("Google Maps URL: ").strip()
        
        if not search_input:
            print("❌ 錯誤：請輸入Google Maps URL")
            return
            
        if not search_input.startswith(('http://', 'https://')):
            search_input = 'https://' + search_input
            
    else:
        print("❌ 錯誤：請選擇 1 或 2")
        return
    
    # 讓使用者輸入結果數量
    print()
    print("請輸入要爬取的餐廳數量（建議 10-50）：")
    try:
        max_results = int(input("數量 (直接按Enter預設30): ") or "30")
    except ValueError:
        max_results = 30
        print("使用預設數量：30")
    
    # 讓使用者輸入檔案名稱
    print()
    print("請輸入Excel檔案名稱（不含副檔名）：")
    filename = input("檔案名稱 (直接按Enter預設：餐廳資料): ") or "餐廳資料"
    filename = f"{filename}.xlsx"
    
    print()
    print(f"開始處理：{search_input}")
    print(f"目標數量：{max_results} 家餐廳")
    print(f"輸出檔案：{filename}")
    print()
    
    # 建立爬蟲實例
    scraper = GoogleMapsScraper(headless=False)  # 設為False以便觀察過程
    
    try:
        # 搜尋餐廳
        scraper.search_restaurants(search_input, max_results=max_results)
        
        # 儲存結果
        scraper.save_to_excel(filename)
        
        # 顯示結果摘要
        print(f"\n🎉 爬蟲完成！共找到 {len(scraper.results)} 家餐廳")
        print(f"📁 檔案已儲存為：{filename}")
        print()
        
        if scraper.results:
            print("前5筆結果預覽：")
            for i, result in enumerate(scraper.results[:5], 1):
                print(f"{i}. {result['店名']}")
                if result['官網']:
                    print(f"   官網: {result['官網']}")
                if result['social']:
                    print(f"   社群: {result['social']}")
                print()
        
    except Exception as e:
        logger.error(f"程式執行時發生錯誤: {e}")
        print(f"❌ 發生錯誤：{e}")
    finally:
        scraper.close()
        print("程式執行完畢，按任意鍵結束...")
        input()

if __name__ == "__main__":
    main()
