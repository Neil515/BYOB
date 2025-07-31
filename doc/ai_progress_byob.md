## BYOB 進度紀錄｜2025-07-31

### ✅ 今日重點進度

1. **餐廳類型欄位改為複選功能**

   * 成功將 ACF 欄位「餐廳類型」改為 Checkbox 複選類型
   * 設定選項：台式、法式、義式、日式、美式、小酒館、咖啡廳、私廚、異國料理、燒烤、火鍋、牛排、Lounge Bar、Buffet
   * 修改 `archive-restaurant.php` 和 `single_restaurant.php` 支援複選顯示
   * 使用 `is_array()` 檢查複選資料，`implode(' / ', $types)` 合併多個類型
   * 顯示格式：單一類型「（中式）」，複選類型「（中式 / 火鍋 / 燒肉）」

2. **Apps Script 開瓶費欄位邏輯修正**

   * 修正開瓶費欄位顯示問題，移除括號內的說明文字
   * 新增 `cleanCorkageOption()` 函式處理選項名稱清理
   * 修正後顯示格式：
     - 不收費
     - 酌收
     - 其他
   * 詳細資訊分別顯示在「開瓶費金額」和「其他：請說明」欄位

3. **酒器設備欄位複選規劃**

   * 規劃將「提供酒器設備」欄位改為 Checkbox 複選類型
   * 建議選項：酒杯、開瓶器、冰桶、醒酒器、無提供
   * 設計顯示格式使用中文頓號「、」分隔
   * 準備修改兩個 PHP 檔案支援複選功能

4. **PHP 檔案複選處理邏輯建立**

   * 建立統一的複選資料處理模式：
     ```php
     $types = get_field('restaurant_type');
     if ($types): 
       if (is_array($types)) {
         $type_output = implode(' / ', $types);
       } else {
         $type_output = $types;
       }
       echo esc_html($type_output);
     endif;
     ```
   * 加入 `esc_html()` 安全性處理
   * 統一顯示格式規範

5. **Google 表單快速匯入 WordPress 方案討論**

   * 評估三種實作方案：
     - 方案 A：WordPress REST API 自動化
     - 方案 B：CSV 匯出 + WordPress 匯入外掛
     - 方案 C：Google Sheets 外掛 + WordPress 整合
   * 討論各方案優缺點和適用場景
   * 準備明日進行方案選擇和實作

6. **完整測試流程規劃**

   * 建立 Google 表單到餐廳卡片的完整測試流程
   * 包含：表單填寫 → Google Sheet → Apps Script 轉換 → WordPress 後台 → 前端顯示
   * 設計測試資料和驗證步驟
   * 準備故障排除指南

---

### 📥 已更新與規劃項目：

* **ACF 欄位設定**：餐廳類型改為複選完成，酒器設備和開瓶費規劃中
* **PHP 檔案修改**：兩個檔案都支援複選顯示，建立統一處理邏輯
* **Apps Script 優化**：開瓶費欄位邏輯修正完成
* **資料流程規劃**：Google 表單匯入 WordPress 方案討論完成
* **測試流程**：完整測試流程規劃完成

---

### 🗓 明日預定任務（同步於 Next Task Prompt Byob）

1. 完成酒器設備和開瓶費欄位的 ACF 調整
2. 修改對應的 PHP 檔案
3. 實作 Google 表單快速匯入 WordPress 功能
4. 建立完整的資料流程文件
5. 確保所有複選欄位正確處理和顯示

---

### 🔧 技術重點記錄：

**複選欄位處理模式：**
```php
// 檢查是否為陣列（複選）
if (is_array($field_data)) {
  $output = implode('分隔符號', $field_data);
} else {
  $output = $field_data;
}
echo esc_html($output);
```

**顯示格式規範：**
- 餐廳類型：使用「 / 」分隔
- 酒器設備：使用「、」分隔
- 開瓶費：單選，但需要處理子層級資訊

**Apps Script 修正重點：**
- 新增 `cleanCorkageOption()` 函式
- 移除括號內的說明文字
- 保持選項名稱簡潔

---

**今日進度重點：完成餐廳類型複選功能，修正 Apps Script 開瓶費邏輯，規劃酒器設備複選功能，討論 Google 表單匯入 WordPress 方案。整體技術架構趨於完善，為明日工作奠定良好基礎。**
