# 台北 BYOB 餐廳資料庫專案說明（2025-08-05 更新）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-08-05）

### ✅ ACF 欄位設定更新與程式碼同步完成

* 接收並分析新的 ACF 欄位定義（JSON 格式）
* 更新 WordPress `functions.php` 中的 ACF 欄位對應
* 修正 `social_links` 欄位名稱為 `social_media`
* 新增 ACF 欄位：`review_status`, `submitted_date`, `review_date`, `review_notes`
* 更新 `restaurant-member-functions.php` 中的 ACF 欄位處理邏輯
* 驗證 Google Apps Script 中的欄位名稱對應

### ✅ Google Apps Script 語法錯誤修復完成

* 識別並修復所有 ES6+ 語法錯誤
* 轉換箭頭函數為傳統函數
* 轉換模板字符串為字符串連接
* 轉換 `let`/`const` 為 `var`
* 轉換解構賦值為傳統賦值
* 確保 Google Apps Script 兼容性

### ✅ WordPress 程式碼優化與除錯完成

* 移除檔案上傳工具選單（`byob-file-upload`）
* 改善檔案路徑檢查邏輯，優先使用子主題目錄
* 新增詳細除錯日誌到 `byob_create_restaurant_post` 函數
* 實作參數映射系統，支援多種參數名稱格式
* 新增 `byob_test_endpoint` REST API 端點用於除錯
* 建立功能開關系統（`byob_get_feature_settings()`）

### 🚨 發現並記錄緊急問題：Google Apps Script 資料解析完全失敗

* 識別 `parseLatestSpreadsheetData()` 函數執行失敗
* 確認資料解析結果為空物件
* 導致空的 payload 發送到 WordPress
* 建立詳細的問題分析和除錯計劃
* 記錄為明日工作的最高優先級任務

### ✅ 技術文件更新與完善

* 更新 `Next Task Prompt Byob.md` 明日工作規劃
* 新增緊急問題的詳細記錄和分析
* 建立完整的除錯步驟和程式碼範例
* 重新排序工作優先級和時間分配

---

## 🚨 當前關鍵問題：Google Apps Script 資料解析完全失敗

### 問題嚴重性評估
* 🔴 **最高優先級**：影響核心功能正常運作
* 🔴 **影響範圍**：所有 Google 表單資料無法正確解析
* 🔴 **用戶體驗**：無法建立餐廳草稿，影響整個自動化流程

### 詳細問題分析

**🚨 緊急問題：Google Apps Script 資料解析失敗**

**問題描述：**
- 用戶重新提交 Google 表單後，仍然出現相同的 "Missing parameters" 錯誤
- WordPress 後台沒有生成草稿
- Google Apps Script 執行日誌顯示：`解析試算表資料時發生錯誤:` 和 `解析的表單資料:` 為空
- 結果：空的 payload 被發送到 WordPress，導致 "Missing parameters" 錯誤

**錯誤詳情：**
```
表單處理發生錯誤
錯誤訊息：Exception: Request failed for https://byobmap.com returned code 400. 
Truncated server response: {"code":"rest_missing_callback_param","message":"\u7f3a\u5c11\u7684\u53c3\u6578: restaurant_name, contact_person, email, restaurant_type, district,... (use muteHttpExceptions option to examine full response)

錯誤詳情：Exception: Request failed for https://byobmap.com returned code 400. 
Truncated server response: {"code":"rest_missing_callback_param","message":"\u7f3a\u5c11\u7684\u53c3\u6578: restaurant_name, contact_person, email, restaurant_type, district,... (use muteHttpExceptions option to examine full response) at sendToWordPress (程式碼:703:32) at onFormSubmit (程式碼:253:18) at __GS_INTERNAL_top_function_call__.gs:1:8
```

**根本原因分析：**
1. **資料解析失敗**：`parseLatestSpreadsheetData()` 函數無法正確解析 Google 試算表資料
2. **欄位映射問題**：可能是欄位名稱或格式不匹配導致解析失敗
3. **試算表結構變更**：Google 試算表的欄位結構可能已變更
4. **編碼問題**：中文字符編碼可能導致欄位名稱匹配失敗

### 已修正的問題

| 問題類型 | 問題描述 | 修正狀態 | 修正內容 |
|----------|----------|----------|----------|
| ACF 欄位名稱 | `social_links` 應為 `social_media` | ✅ 已修正 | 更新 `functions.php` 和 `restaurant-member-functions.php` |
| ES6+ 語法錯誤 | Google Apps Script 不支援 ES6+ 語法 | ✅ 已修正 | 轉換所有語法為 ES5 兼容 |
| 檔案路徑問題 | 檔案路徑檢查邏輯不完善 | ✅ 已修正 | 優先使用子主題目錄 |
| 參數映射 | 參數名稱格式不統一 | ✅ 已修正 | 實作參數映射系統 |

### 待解決問題

| 問題類型 | 問題描述 | 優先級 | 影響程度 |
|----------|----------|--------|----------|
| 資料解析失敗 | `parseLatestSpreadsheetData()` 函數執行失敗 | 🔴 最高 | 高 |
| 欄位映射問題 | 試算表欄位名稱與程式碼不匹配 | 🔴 最高 | 高 |
| 試算表結構 | 試算表結構可能已變更 | 🔴 最高 | 高 |

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
* PHP 檔案 emoji 亂碼問題修正完成
* 開酒服務欄位邏輯修改完成
* 開瓶費說明欄位邏輯修改完成
* Apps Script 電話號碼處理邏輯修正完成
* Logo 設計概念生成與選擇完成
* Logo 設計完成與優化完成
* About 頁面設計與內容撰寫完成
* 網站頁面最後微調完成
* Google 表單自動導入 WordPress 方案規劃完成
* Contact 頁面設計和功能設定完成
* Contact Form 7 表單設定完成
* WP Mail SMTP 外掛安裝與設定完成
* Contact Form 7 郵件功能完成
* FAQ 系統設計完成
* 主頁面視覺設計完成
* Google Cloud 設定完成
* Google 表單自動導入 WordPress 基本功能實作完成
* WordPress REST API 端點建立完成
* Google Apps Script 自動化程式實作完成
* 試算表觸發器設定和授權完成
* 通知郵件系統建立完成
* 詳細的技術文件建立完成
* **新增：ACF 欄位設定更新與程式碼同步完成**
* **新增：Google Apps Script 語法錯誤修復完成**
* **新增：WordPress 程式碼優化與除錯完成**
* **新增：參數映射系統實作完成**
* **新增：功能開關系統建立完成**
* **新增：詳細除錯工具建立完成**

---

## 🗓 明日預定任務（按優先級排序）

### 🔴 最高優先級：解決 Google Apps Script 資料解析失敗問題

**階段 1：Google Apps Script 深度除錯（預計 3 小時）**
* [ ] 在 `parseLatestSpreadsheetData()` 函數中添加詳細除錯日誌
* [ ] 檢查試算表欄位名稱和格式
* [ ] 驗證資料解析邏輯
* [ ] 建立資料解析失敗的處理機制

**階段 2：試算表結構驗證（預計 2 小時）**
* [ ] 確認試算表 ID 是否正確
* [ ] 確認工作表名稱是否正確
* [ ] 檢查欄位名稱是否有變更
* [ ] 確認資料格式是否一致

**階段 3：端到端測試（預計 2 小時）**
* [ ] 重新提交 Google 表單測試
* [ ] 驗證所有欄位修正結果
* [ ] 確認前端顯示正確

### 🟡 中等優先級：建立監控機制
* [ ] 建立自動化測試流程
* [ ] 設定錯誤通知機制
* [ ] 建立資料驗證規則

### 🟢 低優先級：文件更新
* [ ] 更新技術文件
* [ ] 建立操作手冊
* [ ] 記錄完整解決方案

---

## 🎨 設計規範

### 色彩系統
* **主要色彩**：深酒紅色 `#8b2635`
* **輔助色彩**：深灰黑色 `#1a1a1a`
* **背景色彩**：白色 `#ffffff`

### Logo 設計規範
* **主概念**：高腳杯與地圖融合設計
* **色彩**：深酒紅色主調，符合專案色彩規範
* **風格**：扁平化設計，適合 logo 需求
* **應用**：網站 header、App 圖示、宣傳材料
* **最終版本**：深酒紅色高腳杯 + 地圖網格 + 定位圖釘，深酒紅色「BYOBMAP」文字，白色背景，文字陰影效果

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

### ACF 欄位設定（已更新）
* **餐廳類型**：Checkbox 複選，最多三種
* **酒器設備**：Checkbox 複選（規劃中）
* **開瓶費**：單選，但需要處理子層級資訊
* **開酒服務**：支援「其他」選項和說明
* **社交媒體**：`social_media` 欄位（已修正）
* **審核狀態**：`review_status`, `submitted_date`, `review_date`, `review_notes`（新增）

### PHP 檔案修改（已優化）
* **archive-restaurant.php**：支援複選顯示
* **single_restaurant.php**：支援複選顯示
* **安全性處理**：使用 `esc_html()` 確保輸出安全
* **參數映射系統**：支援多種參數名稱格式
* **檔案路徑檢查**：優先使用子主題目錄
* **功能開關系統**：`byob_get_feature_settings()` 函數

### Apps Script 優化（已修正）
* **開瓶費邏輯**：清理選項名稱，移除括號說明
* **電話號碼處理**：修正前導 0 消失問題
* **資料轉換**：統一格式處理
* **錯誤處理**：建立檢查報告機制
* **語法兼容性**：轉換所有 ES6+ 語法為 ES5 兼容

### Contact Form 7 設定
* **表單結構**：姓名、Email、聯絡主題、訊息、電話
* **必填欄位**：姓名、Email、聯絡主題、訊息
* **聯絡主題選項**：一般問題、網站使用問題、餐廳資訊錯誤回報、合作提案、其他
* **郵件範本**：HTML 格式，包含品牌元素
* **寄件者設定**：BYOBMAP <byobmap.tw@gmail.com>

### WP Mail SMTP 設定
* **郵件服務**：Gmail OAuth 2.0
* **寄件者帳號**：byobmap.tw@gmail.com
* **Google Cloud 專案**：BYOB-Taipei
* **OAuth 設定**：外部使用者，測試模式
* **郵件格式**：HTML，支援品牌色彩

### FAQ 系統設計
* **主頁面 FAQ**：4個核心問題，行銷導向
* **完整 FAQ 頁面**：用戶 FAQ（10個）、餐廳業者 FAQ（6個）
* **語言風格**：使用「你」而非「您」，保持親切感
* **CTA 設計**：引導到完整 FAQ 頁面

### Logo 設計規範
* **主概念**：高腳杯與地圖融合設計
* **色彩**：深酒紅色主調，符合專案色彩規範
* **風格**：扁平化設計，適合 logo 需求
* **應用**：網站 header、App 圖示、宣傳材料

### About 頁面設計重點
* **開場問句**：「你是不是也想在享用美食的時候，搭配你珍藏的美酒？」
* **內容結構**：問題描述 → 解決方案 → 使用場景 → 行動召喚
* **視覺設計**：深酒紅色主調，簡潔現代設計
* **推廣策略**：強調平台價值和專業性

### Google 表單自動導入系統（已優化）
* **實作方案**：WordPress REST API 自動化
* **技術架構**：Google 表單 → Google Sheets → Apps Script → WordPress REST API
* **觸發機制**：試算表觸發器，自動處理表單提交
* **通知系統**：成功建立文章後發送通知郵件
* **當前狀態**：基本功能完成，資料解析問題待解決
* **新增功能**：參數映射系統、詳細除錯日誌、功能開關系統

---

## 📊 資料庫欄位（已更新）

### 主要欄位（12個）
1. **餐廳名稱**：文字欄位
2. **餐廳類型**：Checkbox 複選（台式、法式、義式、日式、美式、小酒館、咖啡廳、私廚、異國料理、燒烤、火鍋、牛排、Lounge Bar、Buffet）
3. **行政區**：下拉選單
4. **地址**：文字欄位
5. **是否收開瓶費**：單選（不收費/酌收/其他）
6. **開瓶費說明**：條件式文字欄位
7. **提供酒器設備**：Checkbox 複選（酒杯、開瓶器、冰桶、醒酒器、無提供）
8. **是否提供開酒服務**：單選（是/否/其他）+ 條件式說明
9. **餐廳聯絡電話**：文字欄位（自動格式化）
10. **官方網站/社群連結**：URL 欄位
11. **備註說明**：文字區域
12. **餐廳照片**：圖片上傳

### 新增欄位（4個）
13. **社交媒體**：`social_media` 欄位（已修正）
14. **審核狀態**：`review_status` 欄位（新增）
15. **提交日期**：`submitted_date` 欄位（新增）
16. **審核日期**：`review_date` 欄位（新增）
17. **審核備註**：`review_notes` 欄位（新增）

---

## 🔄 完整流程

### 餐廳申請上架流程
1. **挑選與接觸潛力餐廳**：根據爬蟲資料或社群推薦找潛在支援 BYOB 的餐廳
2. **餐廳填寫申請表單**：表單內容包含所有主要資料庫欄位
3. **人工審核與補資料**：每週由管理者檢視表單資料
4. **轉為 WordPress 餐廳文章**：將轉換後資料手動匯入 WordPress 後台
5. **同步顯示於 App**：WordPress 餐廳資料自動開放於 REST API
6. **通知餐廳與感謝曝光**：發信或私訊通知餐廳「已成功上架」

### 資料流程
* **Google 表單** → **Google Sheets** → **Apps Script 轉換** → **WordPress 後台** → **前端顯示**

### 聯絡表單流程
* **用戶提交表單** → **Contact Form 7 處理** → **WP Mail SMTP 發送** → **byobmap.tw@gmail.com 接收**

### 自動化導入流程（已優化）
* **Google 表單提交** → **Google Sheets 觸發器** → **Apps Script 處理** → **WordPress REST API** → **自動建立文章** → **通知郵件發送**
* **新增**：參數映射系統、詳細除錯日誌、功能開關系統

---

## 🎯 明日成功標準

### 功能驗證
- [ ] Google Apps Script 能正確解析試算表資料
- [ ] 所有 Google 表單欄位都能正確對應到 WordPress ACF 欄位
- [ ] 前端不再顯示「暫無資料」
- [ ] 資料格式正確且用戶友好

### 品質保證
- [ ] 建立完整的測試案例
- [ ] 建立除錯和監控機制
- [ ] 更新相關文件
- [ ] 建立故障排除流程

---

## 💡 技術洞察

### 自動化系統的挑戰
* 資料對應的複雜性：Google 表單欄位名稱與 WordPress ACF 欄位名稱的匹配
* 條件性欄位的處理：需要特殊的邏輯來處理動態顯示的欄位
* 錯誤處理的重要性：需要建立完善的除錯和監控機制
* 語法兼容性：不同平台對 JavaScript 語法的支援差異

### 解決方案設計
* 系統性除錯方法：從資料來源到最終顯示的完整追蹤
* 分階段處理：將複雜問題分解為可管理的步驟
* 文件化的重要性：詳細記錄問題和解決方案
* 參數映射系統：支援多種參數名稱格式的靈活處理

### 品質保證
* 測試驅動開發：建立完整的測試案例
* 監控機制：建立持續的監控和警報系統
* 文件維護：保持技術文件的更新和完整性
* 錯誤處理：建立完善的錯誤處理和除錯機制

---

## 🔧 今日技術實作成果

### WordPress 程式碼優化

**`functions.php` 主要更新：**
```php
// 新增參數映射系統
$param_mapping = [
    'restaurant_name' => ['name', 'restaurant_name'],
    'contact_person' => ['contact_person', 'contact'],
    'email' => ['email', 'contact_email'],
    // ... 更多映射
];

// 新增除錯日誌
error_log('BYOB: 接收到的參數: ' . print_r($_POST, true));

// 新增功能開關系統
function byob_get_feature_settings() {
    return [
        'restaurant_submission' => true,
        'member_system' => true,
        'review_system' => true
    ];
}
```

**`restaurant-member-functions.php` 主要更新：**
```php
// 新增 ACF 欄位更新
update_field('review_status', 'pending', $post_id);
update_field('submitted_date', current_time('mysql'), $post_id);
update_field('review_date', '', $post_id);
update_field('review_notes', '', $post_id);
```

### Google Apps Script 語法修正

**修正前（ES6+ 語法）：**
```javascript
const result = sendToWordPress(formData);
Logger.log(`toHalfWidth: "${str}" -> "${result}"`);
array.map(item => ...);
```

**修正後（ES5 語法）：**
```javascript
var result = sendToWordPress(formData);
Logger.log('toHalfWidth: "' + str + '" -> "' + result + '"');
array.map(function(item) { ... });
```

### 新增除錯工具

1. **`byob_test_endpoint`**：REST API 測試端點
2. **`byob_debug_admin_page()`**：ACF 欄位除錯頁面
3. **`byob_test_acf_configuration()`**：ACF 配置測試函數
4. **`byob_check_acf_fields()`**：ACF 欄位狀態檢查函數

---

本日進度完成 ACF 欄位設定更新與程式碼同步，修復 Google Apps Script 語法錯誤，優化 WordPress 程式碼，並發現並記錄了 Google Apps Script 資料解析失敗的緊急問題。雖然遇到新的技術挑戰，但已建立系統性的解決方案和詳細的除錯計劃，為明日的高效問題解決工作奠定良好基礎。 