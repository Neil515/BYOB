# 台北 BYOB 餐廳資料庫專案說明（2025-07-21 更新）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-07-21）

### ✅ 餐廳卡片前台顯示功能完成

* 成功建立並上傳 archive-restaurant.php，於 /taipei/ 頁面顯示所有餐廳卡片。
* 依據 ACF 實際欄位，完整顯示 12 欄餐廳資訊。
* 確認自訂文章類型 restaurant 設定 has_archive 為 true，Custom Rewrite Slug 設為 taipei。
* 透過「永久連結」設定儲存，網址 /taipei/ 正常顯示所有餐廳卡片。
* 測試多筆餐廳資料，前台顯示正確。

### ✅ 釐清 Flatsome 主題與 UX Builder 限制

* Blog Posts 元件無法顯示自訂文章類型（restaurant）。
* /taipei/ 歸檔頁內容只能用 PHP 編輯，無法用 UX Builder 拖拉設計。
* 若需自由設計頁面，建議用「自訂頁面＋Shortcode」方式，搭配篩選外掛。

### ✅ 安裝並初步設定 Filter Everything 篩選外掛

* 成功安裝 Filter Everything，建立 Filter Set，選擇 restaurant 為篩選對象。
* 新增多個篩選條件，支援 ACF 欄位。
* 篩選器可自動顯示於 /taipei/ 頁面上方。
* 確認 Filter Everything 僅提供篩選功能，卡片顯示仍由 archive-restaurant.php 控制。

### ✅ 討論前台主入口方案

* 比較「自訂頁面＋Shortcode」與「/taipei/ 歸檔頁」兩種做法的優缺點。
* 確認自訂頁面可用 UX Builder 自由設計，並插入篩選器與卡片列表（需 Shortcode 或外掛支援）。
* 歸檔頁彈性較低，但設定簡單。
* 明日將決定主入口方案，並規劃後續實作細節。

---

## 🔭 發展時程（Milestone 規劃）

| 階段                       | 目標                               | 工具或產出                  |
| ------------------------ | -------------------------------- | ---------------------- |
| ✅ Phase 1：網站基礎建置         | 後台資料架構、CPT、ACF 完成                | WordPress + ACF        |
| ✅ Phase 2：資料輸入與初始顯示      | 測試餐廳資料輸入 + 顯示卡片模板                | single\_restaurant.php |
| ⏳ Phase 3：篩選與搜尋功能        | 加入 FacetWP / Search & Filter Pro / Filter Everything | 實現條件搜尋                 |
| ⏳ Phase 4：網站 MVP 上線      | 至少 100 間餐廳、SEO 基本設定              | GA4 + sitemap          |
| 🔜 Phase 5：使用者行為收集與調整    | 根據篩選條件、瀏覽時間優化欄位                  | GA4 / Hotjar           |
| 🔜 Phase 6：App 設計與串接 API | 設計 React App、串接 REST API         | Expo / React Native    |
| 🔜 Phase 7：App 上架流程      | 撰寫隱私聲明、提交 Google Play            | Android Console        |

---

## 📝 明日工作預告
- 決定前台主入口採用「自訂頁面＋Shortcode」或「/taipei/ 歸檔頁」方案
- 詳細規劃所需 Shortcode、外掛查詢方式或 archive-restaurant.php 美化內容

---

本專案將每日記錄進度與調整策略，並隨開發與資料成長同步修訂規劃。
