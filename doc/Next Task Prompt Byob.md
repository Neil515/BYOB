# �� BYOB 專案工作規劃與進度追蹤

## 📅 當前日期：2025年8月22日

## 🚀 已完成任務

### ✅ 餐廳資料編輯頁面欄位擴充
- [x] 新增「聯絡人姓名」欄位
- [x] 新增「行政區」下拉選單（台北市12區）
- [x] 新增「餐廳Email」欄位（與登入帳號同步）
- [x] 調整欄位順序和頁面結構
- [x] 修正「Fine dining」餐廳類型的大小寫問題

### ✅ 餐廳類型「其他」選項實作
- [x] 在餐廳類型中新增「其他」選項
- [x] 新增「其他類型說明」文字輸入欄位
- [x] 實作條件式顯示邏輯（選擇「其他」時顯示說明欄位）
- [x] 修改 JavaScript 控制邏輯
- [x] 更新 ACF 欄位設定

### ✅ 後端資料處理
- [x] 修改 `restaurant-member-functions.php` 處理新欄位
- [x] 修改 `functions.php` 的 `byob_create_restaurant_article` 函數
- [x] 修改 `functions.php` 的 `byob_create_restaurant_post` 函數
- [x] 新增 `restaurant_type_other_note` 欄位映射

### ✅ Google Apps Script 修改
- [x] 修改 `parseLatestSpreadsheetData` 函數
- [x] 新增「其他」餐廳類型的特殊處理邏輯
- [x] 從餐廳類型中提取說明文字的功能

## 🔴 待處理問題

### 🚨 緊急：餐廳類型「其他」選項資料傳遞問題

**問題描述：**
Google 表單中的「其他」餐廳類型選項無法正確傳遞到 WordPress，導致餐廳資料編輯頁面無法顯示「其他」選項和說明文字。

**問題分析：**
1. **資料流程**：Google 表單 → Apps Script → WordPress API → ACF 儲存 → 餐廳資料編輯頁面
2. **資料格式**：表單提交的餐廳類型格式為 `"Fine dining, 美式, 其他, 路邊攤"`
3. **問題點**：需要從餐廳類型中分離出「其他」和其說明文字「路邊攤」

**已完成的修改：**
- ✅ Apps Script：新增從餐廳類型中提取「其他」說明文字的邏輯
- ✅ WordPress：新增 `restaurant_type_other_note` 欄位處理
- ✅ 前端：新增「其他」選項和說明欄位的 UI

**待處理工作：**
1. **語法錯誤檢查**：檢查 `functions.php` 是否有語法錯誤
2. **完整測試流程**：從 Google 表單到 WordPress 的完整資料流程測試
3. **除錯和驗證**：確保資料在每個環節都正確傳遞
4. **最終驗證**：餐廳資料編輯頁面正確顯示「其他」選項和說明文字

**測試步驟：**
1. 在 Google 表單中選擇餐廳類型「其他」
2. 在「其他」後方輸入說明文字（如「路邊攤」）
3. 提交表單
4. 檢查 Apps Script 日誌中的除錯資訊
5. 檢查 WordPress 是否正確接收資料
6. 檢查餐廳資料編輯頁面是否正確顯示

**相關檔案：**
- `wordpress/Apps script - 純淨版.js`：Google Apps Script 邏輯
- `wordpress/functions.php`：WordPress API 處理
- `wordpress/restaurant-member-functions.php`：餐廳資料處理
- `wordpress/woocommerce/myaccount/restaurant-profile.php`：前端編輯頁面

## 📋 明日工作清單

### 🔴 優先級 1：解決「其他」餐廳類型問題
- [ ] 檢查 `functions.php` 語法錯誤
- [ ] 測試 Google 表單到 WordPress 的完整資料流程
- [ ] 驗證 Apps Script 的除錯日誌
- [ ] 確認 WordPress 正確接收和儲存資料
- [ ] 測試餐廳資料編輯頁面的顯示

### 🟡 優先級 2：CSS 下拉選單問題
- [ ] 解決下拉選單文字被截斷的問題
- [ ] 檢查 CSS 樣式和欄位高度設定

### 🟢 優先級 3：功能完善
- [ ] 測試所有新欄位的功能
- [ ] 驗證資料編輯和儲存流程
- [ ] 檢查錯誤處理和用戶體驗

## 🎯 成功標準

### 「其他」餐廳類型問題解決
- [ ] Google 表單提交「其他」類型時，說明文字正確傳遞
- [ ] WordPress 正確儲存 `restaurant_type` 和 `restaurant_type_other_note`
- [ ] 餐廳資料編輯頁面正確顯示「其他」選項和說明文字
- [ ] 整個資料流程無錯誤，用戶體驗流暢

## 📝 技術筆記

### 餐廳類型「其他」的處理邏輯
1. **Apps Script**：檢測餐廳類型是否包含「其他」，提取說明文字
2. **WordPress API**：接收並處理兩個獨立欄位
3. **ACF 儲存**：分別儲存餐廳類型和說明文字
4. **前端顯示**：條件式顯示說明欄位

### 資料格式範例
- **輸入**：`"Fine dining, 美式, 其他, 路邊攤"`
- **處理後**：
  - `restaurant_type`: `"Fine dining, 美式, 其他"`
  - `restaurant_type_other_note`: `"路邊攤"`

## 🔍 除錯資源

### 日誌檢查
- **Apps Script**：執行日誌中的除錯資訊
- **WordPress**：`error_log` 中的 BYOB API 日誌
- **瀏覽器**：Console 中的 JavaScript 除錯資訊

### 測試資料
- 使用簡單的測試資料（如「其他, 測試」）
- 檢查每個環節的資料傳遞
- 驗證最終儲存和顯示結果