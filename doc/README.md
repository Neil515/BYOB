# 台北 BYOB 餐廳資料庫專案說明（2025-07-22 更新）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-07-22）

### ✅ 主入口方案決定
- 採用靜態頁面 `/taipei` 作為前台主入口，利用 Shortcode 或外掛插入餐廳卡片與篩選功能，確保網址乾淨、UX 佳。
- 不採用 taxonomy 歸檔頁 `/city/taipei`，避免多一層網址結構。

### ✅ CPT Slug 命名調整
- Custom Post Type Slug 維持 `restaurant`，Custom Rewrite Slug 改為 `byob-restaurant`，避免與城市頁面衝突，結構更清晰。
- Plural Label 設為「餐廳清單」，Singular Label 設為「餐廳」。

### ✅ 篩選外掛比較與選擇
- 詳細比較 Filter Everything Pro、FacetWP、WP Grid Builder、Search & Filter Pro 四款外掛的功能、費用、彈性、退款條款。
- 確認所有外掛皆支援多城市分頁、ACF 欄位篩選，且有 14~30 天退款保障。
- 已整理成 Markdown 文件，方便團隊評估與選購。

### ✅ Filter Everything 免費/付費版差異
- 免費版無法在靜態頁面插入篩選器，只能在歸檔頁自動顯示。
- Pro 版可在任意頁面用 Shortcode 插入篩選器與文章列表，並可設定預設查詢條件（如城市）。
- 若需多城市分頁與自訂查詢，建議升級 Pro 版或考慮其他外掛。

### ✅ 卡片美化規劃
- 美化餐廳卡片需修改 `archive-restaurant.php`（餐廳列表）與 `single_restaurant.php`（單一餐廳頁）。
- 可先行設計與調整 HTML/CSS，無需等外掛購買。
- 若未來用外掛自訂模板，再將設計搬移即可。

### ✅ Google 表單與 ACF 欄位對應
- 建議 Google 表單主要資料欄位與 ACF 欄位一一對應，方便自動化匯入與維護。
- 非必要欄位可選擇性對應，並建立欄位對照表。

### ✅ 明日工作任務
- 1. 美化餐廳卡片（優化 HTML/CSS，確保 RWD）
- 2. 核對 Google 表單與 ACF 欄位是否完整對應，補齊缺漏或調整命名。

---

本專案將每日記錄進度與調整策略，並隨開發與資料成長同步修訂規劃。
