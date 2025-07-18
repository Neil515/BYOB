# 台北 BYOB 餐廳資料庫專案說明（2025-07-18 更新第二版）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。2025-07-18 起，專案正式採用 WordPress + Flatsome 主題，搭配自訂文章類型（CPT）與進階自訂欄位（ACF），以利大量資料快速上架、彈性搜尋篩選、資料完整呈現與未來擴充。

---

## 📌 最新進度概要（2025-07-18）

### ✅ 架構選型與功能實作啟動

* 採用 WordPress + Flatsome 架構，支援 RWD、搜尋篩選與可視化建構。
* 安裝並啟用核心外掛：

  * Custom Post Type UI ✅（建立餐廳文章類型）
  * Advanced Custom Fields ✅（設計餐廳欄位）
* 未安裝但未來考慮：

  * WP All Import ⏸（CSV 批次匯入工具）
  * FacetWP / Search & Filter Pro ⏸（前台多條件篩選）
  * Custom Post Type Permalinks ⏸（自訂網址結構）

### ✅ 網址結構與搜尋邏輯可行性確認

* 確認可實作 `/city/taipei/餐廳名稱` 或 `/taipei/餐廳名稱` 格式的網址結構。
* 探討用 ACF 搭配篩選外掛實現「不需點進文章就可即時搜尋與條件篩選」的功能。

### ✅ 明日工作任務（2025-07-19）

* 建立「餐廳」文章類型（slug: `restaurant`）
* 用 ACF 設計以下欄位：

  * 地址（address）
  * 電話（phone）
  * 開瓶費（corkage\_fee）
  * 是否收費（is\_charged）
  * 餐廳類型（restaurant\_type）
  * Google Maps 連結（map\_link）
  * 社群連結（social\_links）
  * 備註（notes）
* 新增一筆測試餐廳資料，準備前台卡片設計與篩選模組實作。

---

# （以下為歷史紀錄與原專案說明）

// ... existing code ...
