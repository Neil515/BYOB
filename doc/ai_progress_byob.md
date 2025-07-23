## BYOB 進度紀錄｜2025-07-23

### ✅ 今日重點進度

1. **餐廳卡片美化完成**
   - 成功優化 `archive-restaurant.php` 的 HTML 結構，將每個欄位用 `<div class="field">` 包裹
   - 移除所有 inline style，改用 class 控制，便於 CSS 管理
   - 實作欄位分組：基本資料、酒水相關、連結資訊、其他資訊
   - 加入欄位顯示邏輯，無資料時顯示「暫無資料」
   - 外部連結加上 `target="_blank"` 及 `rel="noopener"`，確保新分頁開啟

2. **CSS 樣式設計與實作**
   - 採用「外觀 → 自訂 → 額外 CSS」方式，避免修改 Flatsome 主題原始檔案
   - 實作卡片並列顯示：桌機一列三個、平板一列兩個、手機一列一個
   - 加入卡片樣式：邊框、陰影、圓角、間距
   - 設定字距：餐廳名稱 2px、內容標題 1px
   - 右上角預留 80px × 80px 空白區塊，供未來放置餐廳照片或 logo
   - 調整內容右邊距，避免被預留區塊遮擋

3. **RWD 響應式設計**
   - 桌機版（>1024px）：一列三個卡片
   - 平板版（769px-1024px）：一列兩個卡片
   - 手機版（≤768px）：一列一個卡片，預留區塊縮小為 60px × 60px

4. **技術問題解決**
   - 解決 CSS 選擇器優先級問題，使用 `!important` 確保覆蓋 Flatsome 主題樣式
   - 解決卡片靠右排列問題，加入 `margin: 0 auto` 實現水平置中
   - 解決預留區塊遮擋內容問題，調整區塊大小與內容邊距

5. **未來規劃討論**
   - 討論餐廳照片上傳機制：建議用 ACF 新增圖片欄位
   - 討論卡片排序機制：建議用 ACF 自訂排序欄位 + PHP 排序，最靈活
   - 討論篩選外掛整合：建議用短代碼插入餐廳卡片到自訂頁面，版面設計更自由

### 📋 完成的技術實作

#### HTML 結構優化
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
        <!-- 其他欄位... -->
      </div>
    </div>
  </div>
</div>
```

#### CSS 樣式實作
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

.restaurant-card::before {
  content: '' !important;
  position: absolute !important;
  top: 15px !important;
  right: 15px !important;
  width: 80px !important;
  height: 80px !important;
  background: #f8f8f8 !important;
  border: 1px dashed #ddd !important;
  border-radius: 4px !important;
  z-index: 1 !important;
}
```

### 🎯 明日工作重點

1. **美化單一餐廳頁面**：修改 `single_restaurant.php`，確保與列表頁面視覺一致性
2. **建立 Google 表單匯入機制**：建立自動化資料匯入流程
3. **測試與優化**：確保所有功能正常運作

---

**本日進度已同步至進度文件，餐廳卡片美化工作圓滿完成，為明日工作奠定良好基礎。**
