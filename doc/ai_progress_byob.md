## BYOB 進度紀錄｜2025-07-21

### ✅ 今日重點進度

1. **完成餐廳卡片前台顯示（自訂文章類型 restaurant）**
   - 成功建立並上傳 archive-restaurant.php，顯示所有餐廳卡片於 /taipei/ 頁面。
   - 依據 ACF 實際欄位，完整顯示 12 欄餐廳資訊。
   - 確認自訂文章類型 restaurant 設定 has_archive 為 true，Custom Rewrite Slug 設為 taipei。
   - 透過「永久連結」設定儲存，網址 /taipei/ 正常顯示所有餐廳卡片。
   - 測試多筆餐廳資料，前台顯示正確。

2. **釐清 Flatsome 主題與 UX Builder 限制**
   - Blog Posts 元件無法顯示自訂文章類型（restaurant）。
   - /taipei/ 歸檔頁內容只能用 PHP 編輯，無法用 UX Builder 拖拉設計。
   - 若需自由設計頁面，建議用「自訂頁面＋Shortcode」方式，搭配篩選外掛。

3. **安裝並初步設定 Filter Everything 篩選外掛**
   - 成功安裝 Filter Everything，建立 Filter Set，選擇 restaurant 為篩選對象。
   - 新增多個篩選條件，支援 ACF 欄位。
   - 篩選器可自動顯示於 /taipei/ 頁面上方。
   - 確認 Filter Everything 僅提供篩選功能，卡片顯示仍由 archive-restaurant.php 控制。

4. **討論前台主入口方案**
   - 比較「自訂頁面＋Shortcode」與「/taipei/ 歸檔頁」兩種做法的優缺點。
   - 確認自訂頁面可用 UX Builder 自由設計，並插入篩選器與卡片列表（需 Shortcode 或外掛支援）。
   - 歸檔頁彈性較低，但設定簡單。
   - 明日將決定主入口方案，並規劃後續實作細節。

---

### 📝 明日工作預告
- 決定前台主入口採用「自訂頁面＋Shortcode」或「/taipei/ 歸檔頁」方案
- 詳細規劃所需 Shortcode、外掛查詢方式或 archive-restaurant.php 美化內容

---

**本日進度已同步至進度文件，後續將依團隊討論結果調整開發方向。**
