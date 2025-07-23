# 台北 BYOB 餐廳資料庫專案說明（2025-07-23 更新）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-07-23）

### ✅ 餐廳卡片美化完成
- 成功優化 `archive-restaurant.php` 的 HTML 結構，將每個欄位用 `<div class="field">` 包裹
- 移除所有 inline style，改用 class 控制，便於 CSS 管理
- 實作欄位分組：基本資料、酒水相關、連結資訊、其他資訊
- 加入欄位顯示邏輯，無資料時顯示「暫無資料」
- 外部連結加上 `target="_blank"` 及 `rel="noopener"`，確保新分頁開啟

### ✅ CSS 樣式設計與實作
- 採用「外觀 → 自訂 → 額外 CSS」方式，避免修改 Flatsome 主題原始檔案
- 實作卡片並列顯示：桌機一列三個、平板一列兩個、手機一列一個
- 加入卡片樣式：邊框、陰影、圓角、間距
- 設定字距：餐廳名稱 2px、內容標題 1px
- 右上角預留 80px × 80px 空白區塊，供未來放置餐廳照片或 logo
- 調整內容右邊距，避免被預留區塊遮擋

### ✅ RWD 響應式設計
- 桌機版（>1024px）：一列三個卡片
- 平板版（769px-1024px）：一列兩個卡片
- 手機版（≤768px）：一列一個卡片，預留區塊縮小為 60px × 60px

### ✅ 技術問題解決
- 解決 CSS 選擇器優先級問題，使用 `!important` 確保覆蓋 Flatsome 主題樣式
- 解決卡片靠右排列問題，加入 `margin: 0 auto` 實現水平置中
- 解決預留區塊遮擋內容問題，調整區塊大小與內容邊距

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

### ✅ 未來規劃討論
- 討論餐廳照片上傳機制：建議用 ACF 新增圖片欄位
- 討論卡片排序機制：建議用 ACF 自訂排序欄位 + PHP 排序，最靈活
- 討論篩選外掛整合：建議用短代碼插入餐廳卡片到自訂頁面，版面設計更自由

### ✅ 明日工作任務
- 1. 美化單一餐廳頁面（修改 `single_restaurant.php`，確保與列表頁面視覺一致性）
- 2. 建立 Google 表單匯入機制（建立自動化資料匯入流程）
- 3. 測試與優化（確保所有功能正常運作）

---

## 🛠️ 技術實作成果

### HTML 結構優化
```php
<div class="restaurant-archive-list">
  <div class="restaurant-card">
    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <div class="acf-fields">
      <div class="info-group basic-info">
        <?php if(get_field('address')): ?>
          <div class="field"><strong>地址：</strong><?php the_field('address'); ?></div>
        <?php else: ?>
          <div class="field"><strong>地址：</strong>暫無資料</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
```

### CSS 樣式實作
```css
.restaurant-archive-list {
  display: grid !important;
  grid-template-columns: repeat(3, 1fr) !important;
  gap: 24px !important;
  max-width: 1200px !important;
  margin: 0 auto !important;
  padding: 0 20px !important;
}

.restaurant-card {
  border: 1px solid #e0e0e0 !important;
  border-radius: 8px !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
  padding: 20px !important;
  background: #fff !important;
  position: relative !important;
}
```

---

## 📋 專案架構

### 前台展示
- **主入口**：`/byob-restaurant/` 餐廳列表頁面
- **單一餐廳**：`/byob-restaurant/restaurant-slug/` 單一餐廳詳細頁面
- **篩選功能**：預計整合篩選外掛，支援城市、類型、開瓶費等篩選

### 後台管理
- **WordPress 後台**：餐廳資料管理
- **ACF 欄位**：12 個主要資料欄位
- **Google 表單**：餐廳申請上架表單

### 資料流程
- **餐廳申請**：Google 表單 → Google Sheets → Apps Script 處理 → WordPress 後台匯入
- **資料展示**：WordPress 後台 → 前台餐廳卡片 → 用戶查詢

---

本專案將每日記錄進度與調整策略，並隨開發與資料成長同步修訂規劃。餐廳卡片美化工作已圓滿完成，為後續功能開發奠定良好基礎。
