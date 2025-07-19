# 台北 BYOB 餐廳資料庫專案說明（2025-07-20 更新第三版）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-07-19）

### ✅ 架構建置與內容準備完成

* 使用 CPT UI 成功建立自訂文章類型 `restaurant`
* 使用 ACF 設定 12 個資料欄位，支援地址、電話、類型、開瓶費、備註等完整資訊
* 新增第一筆測試資料「小酒窩私廚」，欄位顯示正確
* 目前資料已可儲存與匯出，但前台尚無顯示畫面

### ✅ 預備前台卡片模板與搜尋功能

* 已確認需撰寫 `single-restaurant.php` 顯示 ACF 欄位
* 探討安裝 FacetWP / Search & Filter Pro 作為篩選模組
* WordPress REST API 資料輸出正常，可供 React App 串接

### ✅ 架構策略與上架方向明確

* 先以網站為主進行內容建置、篩選功能與瀏覽體驗完善
* 之後再導入 React App 開發，使用相同資料來源
* 確認目前架構對上架 Google Play 無障礙，只需補足隱私政策與 API 管理

---

## 🔭 發展時程（Milestone 規劃）

| 階段                       | 目標                               | 工具或產出                 |
| ------------------------ | -------------------------------- | --------------------- |
| ✅ Phase 1：網站基礎建置         | 後台資料架構、CPT、ACF 完成                | WordPress + ACF       |
| ✅ Phase 2：資料輸入與初始顯示      | 測試餐廳資料輸入 + 顯示卡片模板                | single-restaurant.php |
| ⏳ Phase 3：篩選與搜尋功能        | 加入 FacetWP / Search & Filter Pro | 實現條件搜尋                |
| ⏳ Phase 4：網站 MVP 上線      | 至少 100 間餐廳、SEO 基本設定              | GA4 + sitemap         |
| 🔜 Phase 5：使用者行為收集與調整    | 根據篩選條件、瀏覽時間優化欄位                  | GA4 / Hotjar          |
| 🔜 Phase 6：App 設計與串接 API | 設計 React App、串接 REST API         | Expo / React Native   |
| 🔜 Phase 7：App 上架流程      | 撰寫隱私聲明、提交 Google Play            | Android Console       |

---

本專案將每日記錄進度與調整策略，並隨開發與資料成長同步修訂規劃。
