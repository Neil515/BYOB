# 🔗 Google Maps URL 使用說明

## 📋 什麼是Google Maps URL？

Google Maps URL是當您在Google Maps上搜尋餐廳後，瀏覽器網址列顯示的完整網址。

## 🎯 如何獲取Google Maps URL？

### 方法1：直接搜尋
1. 開啟瀏覽器，前往 https://maps.google.com
2. 在搜尋框輸入您的關鍵字
3. 按Enter搜尋
4. 複製瀏覽器網址列的完整URL

### 方法2：從現有搜尋結果
1. 在Google Maps上搜尋餐廳
2. 滾動瀏覽結果
3. 複製當前頁面的URL

## 📝 URL範例

### 搜尋關鍵字URL
```
https://www.google.com/maps/search/台北+西餐廳
```

### 複雜搜尋URL
```
https://www.google.com/maps/search/台北+(西餐廳+OR+義式餐廳+OR+法式餐廳)+-連鎖+-速食
```

### 特定區域搜尋URL
```
https://www.google.com/maps/search/信義區+日式餐廳
```

## 🚀 使用URL的優點

1. **精確搜尋**：可以保留您已經設定好的搜尋條件
2. **節省時間**：不需要重新輸入複雜的搜尋關鍵字
3. **重現結果**：可以重現相同的搜尋結果
4. **分享便利**：可以分享給其他人使用

## 💡 使用技巧

### 技巧1：預先設定搜尋條件
1. 在Google Maps上設定好所有搜尋條件
2. 複製URL
3. 在爬蟲程式中選擇「輸入Google Maps URL」
4. 貼上URL

### 技巧2：修改URL參數
您可以在URL中修改搜尋參數：
- 更改地區：將「台北」改為「台中」
- 更改餐廳類型：將「西餐廳」改為「日式餐廳」
- 添加排除條件：在URL末尾添加「-連鎖」

### 技巧3：儲存常用URL
建立一個文字檔案，儲存常用的搜尋URL：
```
台北西餐廳: https://www.google.com/maps/search/台北+西餐廳
台北日式餐廳: https://www.google.com/maps/search/台北+日式餐廳
信義區餐廳: https://www.google.com/maps/search/信義區+餐廳
```

## ⚠️ 注意事項

1. **URL格式**：確保URL以 `https://` 開頭
2. **編碼問題**：中文字符在URL中會被編碼，這是正常的
3. **搜尋結果**：URL會保留搜尋結果的狀態，包括篩選條件
4. **時效性**：URL可能會過期，建議定期更新

## 🔧 故障排除

### 問題1：URL無效
**解決方案**：
- 檢查URL是否完整
- 確認URL以 `https://` 開頭
- 嘗試在瀏覽器中開啟URL確認

### 問題2：搜尋結果不正確
**解決方案**：
- 重新在Google Maps上搜尋
- 複製新的URL
- 檢查搜尋條件是否正確

### 問題3：程式無法載入URL
**解決方案**：
- 檢查網路連線
- 確認Chrome瀏覽器已安裝
- 嘗試使用搜尋關鍵字模式

## 📚 進階用法

### 自訂搜尋URL
您可以手動建立搜尋URL：
```
https://www.google.com/maps/search/[搜尋關鍵字]
```

### 添加地理座標
```
https://www.google.com/maps/search/餐廳/@25.0330,121.5654,15z
```

### 添加時間篩選
```
https://www.google.com/maps/search/台北+餐廳+營業中
```

---

**🎉 現在您已經學會如何使用Google Maps URL了！**
**建議使用進階版爬蟲程式 (`google_maps_scraper_advanced.py`) 來享受URL輸入的便利性。**
