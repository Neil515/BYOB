# 台北 BYOB 餐廳資料庫專案說明（2025-07-20 更新第四版）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-07-20）

### ✅ 單篇餐廳前台顯示功能完成

* 成功撰寫並套用 `single_restaurant.php`，顯示完整 ACF 自訂欄位資訊
* 設定正確的子主題 `single.php`，條件式導向自訂文章類型 `restaurant`
* 測試實例「小酒窩私廚」頁面資料成功顯示
* 解決檔名命名錯誤（dash vs underscore）與模板無法導向問題
* 指導註解 `print_r(get_fields())` 保留除錯機制

### ✅ 次日任務規劃與文件整合

* 建立 2025-07-21 任務提醒（欄位一致性、樣式優化、篩選檢查）
* 整理進度至 `ai_progress_byob` 第四版
* 將任務目標拆解為三條實作方向，進入篩選模組整合準備階段

---

## 🔭 發展時程（Milestone 規劃）

| 階段                       | 目標                               | 工具或產出                  |
| ------------------------ | -------------------------------- | ---------------------- |
| ✅ Phase 1：網站基礎建置         | 後台資料架構、CPT、ACF 完成                | WordPress + ACF        |
| ✅ Phase 2：資料輸入與初始顯示      | 測試餐廳資料輸入 + 顯示卡片模板                | single\_restaurant.php |
| ⏳ Phase 3：篩選與搜尋功能        | 加入 FacetWP / Search & Filter Pro | 實現條件搜尋                 |
| ⏳ Phase 4：網站 MVP 上線      | 至少 100 間餐廳、SEO 基本設定              | GA4 + sitemap          |
| 🔜 Phase 5：使用者行為收集與調整    | 根據篩選條件、瀏覽時間優化欄位                  | GA4 / Hotjar           |
| 🔜 Phase 6：App 設計與串接 API | 設計 React App、串接 REST API         | Expo / React Native    |
| 🔜 Phase 7：App 上架流程      | 撰寫隱私聲明、提交 Google Play            | Android Console        |

---

本專案將每日記錄進度與調整策略，並隨開發與資料成長同步修訂規劃。
