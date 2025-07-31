# 台北 BYOB 餐廳資料庫專案說明（2025-07-31 更新）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-07-31）

### ✅ ACF 欄位複選功能完成

* 成功將「餐廳類型」欄位改為 Checkbox 複選類型
* 設定 14 個選項：台式、法式、義式、日式、美式、小酒館、咖啡廳、私廚、異國料理、燒烤、火鍋、牛排、Lounge Bar、Buffet
* 修改 `archive-restaurant.php` 和 `single_restaurant.php` 支援複選顯示
* 建立統一的複選資料處理邏輯，使用 `is_array()` 檢查和 `implode()` 合併

### ✅ Apps Script 開瓶費邏輯修正完成

* 修正開瓶費欄位顯示問題，移除括號內的說明文字
* 新增 `cleanCorkageOption()` 函式處理選項名稱清理
* 修正後顯示格式：不收費、酌收、其他
* 詳細資訊分別顯示在對應欄位，保持資料結構清晰

### ✅ PHP 檔案複選處理邏輯建立

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

### ✅ Google 表單快速匯入 WordPress 方案規劃

* 評估三種實作方案：
  - 方案 A：WordPress REST API 自動化
  - 方案 B：CSV 匯出 + WordPress 匯入外掛
  - 方案 C：Google Sheets 外掛 + WordPress 整合
* 討論各方案優缺點和適用場景
* 準備明日進行方案選擇和實作

### ✅ 完整測試流程規劃完成

* 建立 Google 表單到餐廳卡片的完整測試流程
* 包含：表單填寫 → Google Sheet → Apps Script 轉換 → WordPress 後台 → 前端顯示
* 設計測試資料和驗證步驟
* 準備故障排除指南

---

## 🗂️ 已完成項目

* 熱門區域桌機與手機版完成
* 熱門餐廳類型手機版完成
* 精選餐廳桌機版 Grid 確立，手機版 Slider 完成
* 桌機版 hover 效果設定完成
* Slider 導覽樣式優化完成
* 設計規劃工作完成
* 技術問題解決完成
* 餐廳類型複選功能完成
* Apps Script 開瓶費邏輯修正完成
* PHP 檔案複選處理邏輯建立完成
* Google 表單匯入方案規劃完成

---

## 🗓 明日預定任務

1. 完成酒器設備和開瓶費欄位的 ACF 調整
2. 修改對應的 PHP 檔案
3. 實作 Google 表單快速匯入 WordPress 功能
4. 建立完整的資料流程文件
5. 確保所有複選欄位正確處理和顯示

---

## 🎨 設計規範

### 色彩系統
* **主要色彩**：深酒紅色 `#8b2635`
* **輔助色彩**：深灰黑色 `#1a1a1a`
* **背景色彩**：白色 `#ffffff`

### 圖片規範
* **比例**：4:3（餐廳類型圖片）、16:9（橫幅圖片）
* **風格**：深酒紅色調、專業攝影風格、符合 BYOB 氛圍
* **命名**：`restaurant_type_[類型]_[編號].png`

### 技術規範
* **Slider 設定**：80% Slide Width + Center Align
* **Hover 效果**：Zoom + Remove Overlay
* **CSS 選擇器**：使用專用 class 避免衝突
* **複選欄位處理**：使用 `is_array()` 檢查，`implode()` 合併
* **顯示格式**：餐廳類型用「 / 」，酒器設備用「、」

---

## 🔧 技術架構

### ACF 欄位設定
* **餐廳類型**：Checkbox 複選，最多三種
* **酒器設備**：Checkbox 複選（規劃中）
* **開瓶費**：單選，但需要處理子層級資訊

### PHP 檔案修改
* **archive-restaurant.php**：支援複選顯示
* **single_restaurant.php**：支援複選顯示
* **安全性處理**：使用 `esc_html()` 確保輸出安全

### Apps Script 優化
* **開瓶費邏輯**：清理選項名稱，移除括號說明
* **資料轉換**：統一格式處理
* **錯誤處理**：建立檢查報告機制

---

本日進度完成 ACF 欄位複選功能、修正 Apps Script 邏輯、建立 PHP 檔案處理模式，並規劃 Google 表單匯入方案。整體技術架構趨於完善，為後續功能開發奠定良好基礎。
