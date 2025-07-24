## BYOB 進度紀錄｜2025-07-24

### ✅ 今日重點進度

1. **單一餐廳頁面美化與一致性處理**

   * 完成 `single_restaurant.php` 結構優化，使其符合列表頁邏輯
   * 保留餐廳名稱右側類型顯示（括號形式）
   * 設定 `<div class="restaurant-title-line">` 並調整 CSS，讓類型自動避開圖片區塊、不被遮擋
   * 修正類型與名稱間距、垂直對齊問題，讓類型置於名稱下緣對齊

2. **CSS 整合與統一設計**

   * 所有與卡片右上角預留區塊（圖片/Logo 區）的樣式邏輯集中於 CSS 一處管理
   * 調整不同裝置的圖片區塊尺寸（桌機、平板、手機）
   * 加入 `.restaurant-type` 與 `.restaurant-title-line` 的字體與排版控制

3. **Google Maps 連結機制**

   * 設計雙欄位邏輯：`address` 顯示純地址；`map_link` 儲存 Google Maps 網址
   * 若 `map_link` 空白，系統會 fallback 產生搜尋連結 `https://www.google.com/maps/search/?api=1&query=地址`
   * 地址欄位自動嵌入 Google Maps 連結，並在文字後加上 🌐 icon

4. **程式碼註解與精簡處理**

   * 在 `archive-restaurant.php` 中註解掉重複資訊欄位（如餐廳類型、社群連結、是否提供開酒服務）
   * 保留餐廳名稱旁括號資訊不影響讀者辨識
   * 所有欄位無資料時使用「暫無資料」統一提示語

5. **明日工作排程準備**

   * 將下階段工作新增至 `Next Task Prompt Byob.md` 檔案中
   * 明日預計進行電話連結、插入餐廳圖片、網站首頁草圖設計

---

### 📥 相關實作檔案已更新：

* `archive-restaurant.php`：加入自動 Google Maps fallback
* `single_restaurant.php`：完成類型樣式、地圖連結與 HTML 結構優化
* CSS：新增 `.restaurant-title-line`, `.restaurant-type` 等樣式

### 🗓 明日預定任務（已同步於 Next Task Prompt Byob）

1. 加入電話的連結
2. 插入店家圖片
3. 設計網站主頁

---

**今日所有邏輯與實作進度，皆已整合並記錄完畢。**
