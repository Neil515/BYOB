#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
餐廳Email搜尋程式
功能：從Excel檔案中的餐廳Facebook專頁和官方網站搜尋Email地址
作者：BYOB平台
版本：1.0
"""

import pandas as pd
import requests
import re
import time
import random
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import logging
from urllib.parse import urljoin, urlparse
import os
import argparse

class RestaurantEmailSearcher:
    def __init__(self, max_restaurants=None):
        """初始化Email搜尋器"""
        self.max_restaurants = max_restaurants
        self.setup_logging()
        self.setup_driver()
        
        # Email正則表達式
        self.email_pattern = re.compile(
            r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b'
        )
        
        # 常見的無效email
        self.invalid_emails = {
            'example.com', 'test.com', 'sample.com', 'demo.com',
            'noreply', 'no-reply', 'donotreply', 'admin@example.com'
        }
        
        # 請求間隔設定
        self.request_delay = (3, 6)  # 3-6秒隨機延遲
        
    def setup_logging(self):
        """設定日誌"""
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler('email_search.log', encoding='utf-8'),
                logging.StreamHandler()
            ]
        )
        self.logger = logging.getLogger(__name__)
        
    def setup_driver(self):
        """設定Selenium WebDriver"""
        chrome_options = Options()
        chrome_options.add_argument('--headless')  # 無頭模式
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--window-size=1920,1080')
        chrome_options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
        
        try:
            self.driver = webdriver.Chrome(options=chrome_options)
            self.wait = WebDriverWait(self.driver, 10)
        except Exception as e:
            self.logger.error(f"無法啟動Chrome WebDriver: {e}")
            self.driver = None
            
    def random_delay(self):
        """隨機延遲"""
        delay = random.uniform(*self.request_delay)
        time.sleep(delay)
        
    def is_valid_email(self, email):
        """驗證email是否有效"""
        if not email or len(email) < 5:
            return False
            
        # 檢查是否為無效email
        domain = email.split('@')[1].lower() if '@' in email else ''
        if domain in self.invalid_emails:
            return False
            
        # 檢查email格式
        return bool(self.email_pattern.match(email))
        
    def extract_emails_from_text(self, text):
        """從文字中提取email"""
        if not text:
            return []
            
        emails = self.email_pattern.findall(text)
        valid_emails = []
        
        for email in emails:
            email = email.lower().strip()
            if self.is_valid_email(email):
                valid_emails.append(email)
                
        return list(set(valid_emails))  # 去重
        
    def search_facebook_email(self, facebook_url):
        """搜尋Facebook專頁的email"""
        if not self.driver:
            return []
            
        try:
            self.logger.info(f"正在搜尋Facebook專頁: {facebook_url}")
            self.driver.get(facebook_url)
            self.random_delay()
            
            emails = []
            
            # 嘗試多種方法搜尋email
            search_methods = [
                self._search_facebook_about_page,
                self._search_facebook_contact_info,
                self._search_facebook_page_content
            ]
            
            for method in search_methods:
                try:
                    found_emails = method()
                    if found_emails:
                        emails.extend(found_emails)
                        break  # 找到email就停止
                except Exception as e:
                    self.logger.warning(f"Facebook搜尋方法失敗: {e}")
                    continue
                    
            return list(set(emails))  # 去重
            
        except Exception as e:
            self.logger.error(f"Facebook專頁搜尋失敗: {e}")
            return []
            
    def _search_facebook_about_page(self):
        """搜尋Facebook關於頁面"""
        emails = []
        
        # 嘗試點擊「關於」標籤
        try:
            about_tab = self.wait.until(
                EC.element_to_be_clickable((By.XPATH, "//a[contains(@href, '/about')]"))
            )
            about_tab.click()
            self.random_delay()
            
            # 搜尋頁面內容
            page_source = self.driver.page_source
            emails = self.extract_emails_from_text(page_source)
            
        except TimeoutException:
            self.logger.info("未找到Facebook關於頁面")
            
        return emails
        
    def _search_facebook_contact_info(self):
        """搜尋Facebook聯絡資訊"""
        emails = []
        
        # 搜尋聯絡資訊相關元素
        contact_selectors = [
            "//div[contains(text(), '聯絡')]",
            "//div[contains(text(), 'Contact')]",
            "//div[contains(text(), 'Email')]",
            "//div[contains(text(), '電子郵件')]"
        ]
        
        for selector in contact_selectors:
            try:
                elements = self.driver.find_elements(By.XPATH, selector)
                for element in elements:
                    text = element.text
                    found_emails = self.extract_emails_from_text(text)
                    emails.extend(found_emails)
            except Exception:
                continue
                
        return emails
        
    def _search_facebook_page_content(self):
        """搜尋Facebook頁面內容"""
        page_source = self.driver.page_source
        return self.extract_emails_from_text(page_source)
        
    def search_website_email(self, website_url):
        """搜尋官方網站的email"""
        emails = []
        
        # 要搜尋的頁面路徑
        search_paths = [
            '',  # 首頁
            '/contact',
            '/contact-us',
            '/聯絡我們',
            '/about',
            '/about-us',
            '/關於我們'
        ]
        
        for path in search_paths:
            try:
                url = urljoin(website_url, path)
                self.logger.info(f"正在搜尋網站: {url}")
                
                response = requests.get(url, timeout=10)
                response.raise_for_status()
                
                # 搜尋頁面內容
                found_emails = self.extract_emails_from_text(response.text)
                if found_emails:
                    emails.extend(found_emails)
                    break  # 找到email就停止
                    
                self.random_delay()
                
            except Exception as e:
                self.logger.warning(f"網站搜尋失敗 {url}: {e}")
                continue
                
        return list(set(emails))  # 去重
        
    def search_restaurant_email(self, website_url):
        """搜尋餐廳email的主要方法"""
        if not website_url or pd.isna(website_url):
            return []
            
        # 判斷是Facebook專頁還是官方網站
        if 'facebook.com' in website_url.lower():
            return self.search_facebook_email(website_url)
        else:
            return self.search_website_email(website_url)
            
    def process_excel_file(self, input_file):
        """處理Excel檔案"""
        try:
            # 讀取Excel檔案
            self.logger.info(f"正在讀取Excel檔案: {input_file}")
            df = pd.read_excel(input_file)
            
            # 檢查必要欄位
            required_columns = ['name', 'website']
            missing_columns = [col for col in required_columns if col not in df.columns]
            if missing_columns:
                raise ValueError(f"缺少必要欄位: {missing_columns}")
                
            # 新增email欄位
            if 'email' not in df.columns:
                df['email'] = ''
            if 'search_status' not in df.columns:
                df['search_status'] = ''
                
            total_restaurants = len(df)
            
            # 應用限制家數
            if self.max_restaurants and self.max_restaurants < total_restaurants:
                df = df.head(self.max_restaurants)
                actual_count = self.max_restaurants
                self.logger.info(f"限制執行家數: {actual_count}/{total_restaurants}")
            else:
                actual_count = total_restaurants
                
            self.logger.info(f"總共需要處理 {actual_count} 家餐廳")
            
            # 處理每家餐廳
            for index, row in df.iterrows():
                restaurant_name = row['name']
                website_url = row['website']
                
                self.logger.info(f"正在處理 ({index+1}/{total_restaurants}): {restaurant_name}")
                
                try:
                    # 搜尋email
                    emails = self.search_restaurant_email(website_url)
                    
                    if emails:
                        df.at[index, 'email'] = emails[0]  # 取第一個email
                        df.at[index, 'search_status'] = 'found'
                        self.logger.info(f"找到email: {emails[0]}")
                    else:
                        df.at[index, 'email'] = ''
                        df.at[index, 'search_status'] = 'not_found'
                        self.logger.info("未找到email")
                        
                except Exception as e:
                    df.at[index, 'email'] = ''
                    df.at[index, 'search_status'] = f'error: {str(e)[:50]}'
                    self.logger.error(f"處理失敗: {e}")
                    
                # 隨機延遲
                self.random_delay()
                
            # 儲存結果
            if self.max_restaurants and self.max_restaurants < total_restaurants:
                output_file = input_file.replace('.xlsx', f'_with_emails_limit{self.max_restaurants}.xlsx')
            else:
                output_file = input_file.replace('.xlsx', '_with_emails.xlsx')
            df.to_excel(output_file, index=False)
            self.logger.info(f"結果已儲存至: {output_file}")
            
            # 統計結果
            found_count = len(df[df['search_status'] == 'found'])
            self.logger.info(f"搜尋完成！找到 {found_count}/{actual_count} 家餐廳的email")
            
            return output_file
            
        except Exception as e:
            self.logger.error(f"處理Excel檔案失敗: {e}")
            raise
            
    def cleanup(self):
        """清理資源"""
        if self.driver:
            self.driver.quit()

def main():
    """主程式"""
    parser = argparse.ArgumentParser(description='餐廳Email搜尋程式')
    parser.add_argument('input_file', help='Excel檔案路徑')
    parser.add_argument('-n', '--max-restaurants', type=int, 
                       help='限制處理的餐廳數量 (例如: -n 10 表示只處理前10家)')
    
    args = parser.parse_args()
    
    input_file = args.input_file
    max_restaurants = args.max_restaurants
    
    if not os.path.exists(input_file):
        print(f"錯誤: 檔案不存在 - {input_file}")
        return 1
        
    searcher = RestaurantEmailSearcher(max_restaurants=max_restaurants)
    
    try:
        if max_restaurants:
            print(f"開始搜尋餐廳Email... (限制處理 {max_restaurants} 家)")
        else:
            print("開始搜尋餐廳Email...")
            
        print("這可能需要一些時間，請耐心等待...")
        
        output_file = searcher.process_excel_file(input_file)
        
        print(f"\n✅ 搜尋完成！")
        print(f"📁 結果檔案: {output_file}")
        print(f"📊 詳細日誌: email_search.log")
        
        return 0
        
    except Exception as e:
        print(f"❌ 程式執行失敗: {e}")
        return 1
        
    finally:
        searcher.cleanup()

if __name__ == "__main__":
    exit(main())
