## BYOB 進度紀錄｜2025-07-20（第四版）

### ✅ 今日完成事項

1. **成功實作餐廳前台資料顯示模板 `single_restaurant.php`**

   * 完成資料讀取邏輯與欄位呈現，ACF 資料顯示正常
   * 將卡片內容分區塊（基本資訊、設備與服務、備註與來源）顯示
   * 加入除錯機制 `print_r(get_fields())` 協助欄位資料驗證

2. **解決 Flatsome 主題架構導致模板無法套用的問題**

   * 建立 `single.php` 並加入判斷邏輯 `get_post_type() === 'restaurant'`
   * 成功讓 `restaurant` 類型套用 `single_restaurant.php`
   * 測試多篇文章皆能正確顯示前台卡片資訊

3. **優化 FTP 上傳流程與檔案調整註解說明**

   * 指導檔案命名一致性（如 `single_restaurant.php` 而非 `single-restaurant.php`）
   * 教學如何使用 `/* */` 註解 `print_r()` 區塊保留除錯碼

4. **完成次日任務規劃與調整進度文件**

   * 整理 7/21 工作重點（Google 表單欄位比對、卡片樣式優化、ACF 篩選檢查）
   * 建立新任務文檔 `Next Task 2025 07 21`，與進度同步紀錄

---

### 🔭 未來里程碑更新（Milestone）

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

此紀錄建議合併入總進度文件，並與每日任務提醒同步，追蹤欄位設計與前台體驗持續改善情況。
