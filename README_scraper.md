# Google Maps 餐廳爬蟲使用說明

## 功能說明
這個爬蟲程式可以自動搜尋Google Maps上的餐廳，並提取每家餐廳的網站連結，然後將結果儲存為Excel檔案。

## 安裝需求

### 1. 安裝Python套件
```bash
pip install -r requirements.txt
```

### 2. 安裝Chrome瀏覽器
確保您的電腦已安裝Chrome瀏覽器。

### 3. 安裝ChromeDriver
程式會自動下載ChromeDriver，但您也可以手動安裝：
- 下載對應您Chrome版本的ChromeDriver
- 將ChromeDriver.exe放在系統PATH中

## 使用方法

### 基本使用
```bash
python google_maps_scraper.py
```

### 自訂搜尋關鍵字
在`google_maps_scraper.py`的`main()`函數中修改`search_query`變數：

```python
search_query = "您的搜尋關鍵字"
```

### 自訂結果數量
修改`max_results`參數來控制要爬取的餐廳數量：

```python
scraper.search_restaurants(search_query, max_results=50)
```

## 輸出檔案
程式會生成一個名為`台北餐廳資料.xlsx`的Excel檔案，包含以下欄位：
- **店名**: 餐廳名稱
- **官網**: 餐廳官方網站連結
- **social**: Facebook或其他社群媒體連結

## 注意事項

1. **反爬蟲機制**: Google Maps有反爬蟲機制，建議：
   - 不要設定過高的爬取速度
   - 可以設定隨機延遲時間
   - 使用代理IP（如需要）

2. **網站結構變更**: Google Maps的網站結構可能會變更，如果爬蟲無法正常運作，可能需要更新選擇器。

3. **法律合規**: 請確保您的爬蟲使用符合相關法律法規和Google的使用條款。

## 故障排除

### 常見問題

1. **ChromeDriver版本不匹配**
   - 解決方案：更新Chrome瀏覽器或下載對應版本的ChromeDriver

2. **找不到元素**
   - 解決方案：Google Maps的HTML結構可能已變更，需要更新選擇器

3. **爬取速度過慢**
   - 解決方案：調整延遲時間或使用無頭模式

### 除錯模式
將`headless=False`設為`True`可以觀察爬蟲的執行過程：

```python
scraper = GoogleMapsScraper(headless=False)
```

## 自訂功能

### 修改Facebook檢測
在`_is_facebook_url()`方法中添加更多社群媒體平台：

```python
def _is_facebook_url(self, url):
    social_domains = [
        'facebook.com',
        'instagram.com',
        'twitter.com',
        'line.me'
    ]
    # ... 其他程式碼
```

### 添加更多搜尋條件
修改搜尋關鍵字來包含更多條件：

```python
search_query = "台北 (西餐廳 OR 義式餐廳 OR 法式餐廳 OR 日式餐廳) -連鎖 -速食 -鍋貼 -咖哩飯 -咖啡 -麥當勞 -肯德基 -Subway -星巴克 -85度C -王品 -瓦城 -欣葉"
```
