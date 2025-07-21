---

### 📌 明日工作任務（2025-07-22）

**目標：決定前台餐廳卡片與篩選的主入口方案**

#### 1. 比較兩種做法：

**A. 自訂頁面＋Shortcode**
- 用 WordPress 頁面（如 /find、/search）＋ UX Builder 自由設計版面
- 插入 Shortcode（如 Filter Everything 篩選器＋自訂餐廳卡片列表）
- 可加 Banner、說明、FAQ、地圖、活動等各種元素
- 版面彈性高，易於品牌化與行銷
- 方便日後擴充與維護
- 缺點：需額外設定 Shortcode 或外掛查詢，初期設定較多

**B. byobmap.com/taipei（自訂文章類型歸檔頁）**
- 由 archive-restaurant.php 控制內容，網址自動產生
- 篩選器可自動加在頁面上方（如 Filter Everything）
- 不需額外設定 Shortcode，直接顯示所有餐廳卡片
- 缺點：只能用 PHP 編輯，無法用 UX Builder 拖拉設計
- 版面彈性較低，難以加多元內容

#### 2. 明日討論重點：
- 根據專案需求、未來擴充性、行銷設計彈性，決定採用哪一種方案作為主入口
- 若選擇自訂頁面＋Shortcode，規劃所需 Shortcode 或外掛查詢方式
- 若選擇 /taipei 歸檔頁，規劃 archive-restaurant.php 的美化與內容擴充

---

**請依據實際需求與團隊討論結果，決定最適合的前台入口方案。**
