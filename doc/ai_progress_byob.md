## BYOB 進度紀錄｜2025-07-19（第三版）

### ✅ 今日完成事項

1. **完成餐廳文章類型與 ACF 欄位建置：**

   * 成功使用 CPT UI 建立自訂文章類型 `restaurant`，並設定網址結構為 `/taipei/slug`
   * 完成 12 個 ACF 欄位設定（包含地址、電話、開瓶費、餐廳類型等）
   * 正確設置 Location Rule 讓欄位在後台出現
   * 成功新增第一筆模擬餐廳「小酒窩私廚」，後台顯示正常

2. **確認 ACF 資料儲存與呈現機制：**

   * 瞭解 WordPress 預設不會在前台自動顯示 ACF 資料
   * 測試 `[acf]` shortcode 可讀出資料，但非理想作法
   * 決定改為撰寫 `single-restaurant.php` 自訂模板以實現前台卡片顯示

3. **架構策略與 App 上架規劃討論：**

   * 確認目標為：網站先行成熟 → 確保餐廳數與搜尋體驗 → 再進入 App 上架階段
   * React App 將串接 WordPress REST API，資料與網站共用
   * 評估 WordPress + React 架構不影響 Google Play 上架，僅需注意隱私政策、資料授權與 API 安全

---

### 🔭 未來里程碑規劃（Milestone）

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

此紀錄建議合併入總進度文件，並與每日任務進度同步。
