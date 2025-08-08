### 📌 明日工作任務（2025-08-07）

**目標：完成 Google 表單資料對應問題的最終修正和測試**

#### 1. 🚨 優先任務：Google 表單資料對應問題最終解決

**1.1 今日進展總結（2025-08-05）**

**✅ 已完成的重要進展：**
* 🔧 **語法錯誤修復**：成功修復 Google Apps Script 中的所有 ES6+ 語法錯誤
  - 轉換箭頭函數為傳統函數
  - 轉換模板字符串為字符串連接
  - 轉換 `let`/`const` 為 `var`
  - 轉換解構賦值為傳統賦值
* 🔍 **問題根本原因發現**：ACF 欄位期望的值格式與傳入值不匹配
* 🛠️ **資料轉換邏輯修正**：修正了 `is_charged` 和 `open_bottle_service` 欄位的值映射
* 📊 **除錯工具建立**：建立了完整的 ACF 欄位除錯頁面和測試函數

**1.2 新發現的緊急問題（2025-08-05 晚間）**

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

**Google Apps Script 執行日誌分析：**
- `解析試算表資料時發生錯誤:` - 表示 `parseLatestSpreadsheetData()` 函數執行失敗
- `解析的表單資料:` 為空 - 表示資料解析結果為空物件
- 結果：空的資料被發送到 WordPress，導致所有必要參數缺失

**根本原因分析：**
1. **資料解析失敗**：`parseLatestSpreadsheetData()` 函數無法正確解析 Google 試算表資料
2. **欄位映射問題**：可能是欄位名稱或格式不匹配導致解析失敗
3. **試算表結構變更**：Google 試算表的欄位結構可能已變更
4. **編碼問題**：中文字符編碼可能導致欄位名稱匹配失敗

**1.3 問題根本原因分析**

**🔍 核心問題：ACF 欄位值格式不匹配**

| 問題欄位 | ACF 欄位類型 | ACF 期望值 | 我們傳入的值 | 問題 |
|----------|-------------|-----------|-------------|------|
| `is_charged` | radio | `'yes'`, `'no'`, `'other'` | `'是'`, `'否'`, `'其他'` | 值格式不匹配 |
| `open_bottle_service` | select | `'yes'`, `'no'`, `'other'` | `'是'`, `'否'`, `'其他'` | 值格式不匹配 |
| `equipment` | checkbox | `array(['酒杯', '開瓶器'])` | `array(['無提供', '醒酒器'])` | 陣列格式正確 |
| `contact_person` | text | `string` | `string` | 格式正確 |
| `corkage_fee` | text | `string` | `string` | 格式正確 |

**🔍 新增緊急問題：Google Apps Script 資料解析完全失敗**

| 問題環節 | 預期行為 | 實際行為 | 問題 |
|----------|---------|---------|------|
| `parseLatestSpreadsheetData()` | 解析試算表資料 | 解析失敗，返回空物件 | 資料解析邏輯有誤 |
| `sendToWordPress()` | 發送完整資料 | 發送空 payload | 上游資料解析失敗 |
| WordPress API | 接收完整參數 | 接收空參數 | 導致 "Missing parameters" 錯誤 |

**1.4 今日修正內容**

**🔧 主要修正項目：**

1. **Google Apps Script 語法修正**：
   ```javascript
   // 修正前（ES6+ 語法）
   const result = sendToWordPress(formData);
   Logger.log(`toHalfWidth: "${str}" -> "${result}"`);
   array.map(item => ...)
   
   // 修正後（ES5 語法）
   var result = sendToWordPress(formData);
   Logger.log('toHalfWidth: "' + str + '" -> "' + result + '"');
   array.map(function(item) { ... })
   ```

2. **WordPress functions.php 資料轉換修正**：
   ```php
   // 修正前
   $is_charged_map = [
       '酌收' => '是',
       '不收費' => '否', 
       '其他' => '其他'
   ];
   
   // 修正後
   $is_charged_map = [
       '酌收' => 'yes',
       '不收費' => 'no', 
       '其他' => 'other',
       '是' => 'yes',
       '否' => 'no'
   ];
   ```

3. **新增除錯工具**：
   - `byob_debug_admin_page()`：ACF 欄位除錯頁面
   - `byob_test_acf_configuration()`：ACF 配置測試函數
   - `byob_check_acf_fields()`：ACF 欄位狀態檢查函數

**🔧 新增緊急修正項目（待完成）：**

4. **Google Apps Script 資料解析除錯**：
   - 在 `parseLatestSpreadsheetData()` 中添加詳細除錯日誌
   - 檢查試算表欄位名稱和格式
   - 驗證資料解析邏輯
   - 建立資料解析失敗的處理機制

**1.5 下一步測試方法**

**🎯 立即測試步驟（預計 30 分鐘）：**

1. **重新提交 Google 表單測試**：
   ```bash
   # 步驟 1：提交新的測試表單
   - 填寫所有欄位，特別是問題欄位
   - 提交表單並等待處理完成
   
   # 步驟 2：檢查 WordPress 除錯頁面
   - 前往 工具 → BYOB ACF 除錯
   - 檢查所有欄位的狀態
   - 確認問題欄位是否已修正
   ```

2. **驗證修正結果**：
   ```bash
   # 檢查項目：
   - [ ] contact_person 欄位有值
   - [ ] is_charged 欄位有值（應為 'yes'/'no'/'other'）
   - [ ] corkage_fee 欄位有值
   - [ ] equipment 欄位有值（陣列格式）
   - [ ] open_bottle_service 欄位有值（應為 'yes'/'no'/'other'）
   - [ ] district 欄位有值
   - [ ] is_owner 欄位有值
   ```

3. **前端顯示驗證**：
   ```bash
   # 檢查前端頁面：
   - [ ] 餐廳詳情頁面不再顯示「暫無資料」
   - [ ] 所有欄位都正確顯示
   - [ ] 資料格式正確（如開瓶費政策顯示「酌收」而非「yes」）
   ```

**🎯 緊急測試步驟（新增）：**

4. **Google Apps Script 資料解析測試**：
   ```bash
   # 步驟 1：檢查 Google Apps Script 執行日誌
   - 查看 Apps Script 專案的執行日誌
   - 確認 `parseLatestSpreadsheetData()` 函數的執行狀態
   - 檢查是否有詳細的錯誤訊息
   
   # 步驟 2：手動測試資料解析
   - 在 Apps Script 編輯器中手動執行 `parseLatestSpreadsheetData()` 函數
   - 檢查返回的資料結構
   - 確認所有必要欄位都有值
   
   # 步驟 3：檢查試算表結構
   - 確認 Google 試算表的欄位名稱和順序
   - 檢查是否有新增或刪除的欄位
   - 確認欄位名稱的編碼格式
   ```

**1.6 如果仍有問題的處理方案**

**🔍 進一步除錯步驟：**

1. **檢查 WordPress 錯誤日誌**：
   ```bash
   # 查看錯誤日誌
   - 檢查 wp-content/debug.log
   - 尋找 "BYOB" 開頭的錯誤訊息
   - 檢查 ACF 相關錯誤
   ```

2. **手動測試 ACF 欄位更新**：
   ```php
   // 在 WordPress 管理後台執行
   - 前往 工具 → BYOB ACF 除錯
   - 點擊「測試更新 ACF 欄位」按鈕
   - 檢查測試結果
   ```

3. **檢查 ACF 欄位配置**：
   ```php
   // 確認 ACF 欄位設定
   - 檢查欄位名稱是否正確
   - 確認欄位類型設定
   - 驗證欄位選項配置
   ```

**🔍 緊急除錯步驟（新增）：**

4. **Google Apps Script 深度除錯**：
   ```javascript
   // 在 parseLatestSpreadsheetData() 函數中添加詳細日誌
   Logger.log('開始解析試算表資料');
   Logger.log('試算表 ID: ' + spreadsheetId);
   Logger.log('工作表名稱: ' + sheetName);
   
   // 檢查試算表結構
   var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
   Logger.log('試算表標題行: ' + JSON.stringify(headers));
   
   // 檢查資料行
   var lastRow = sheet.getLastRow();
   Logger.log('最後一行: ' + lastRow);
   
   // 檢查欄位映射
   for (var i = 0; i < mappingData.length; i++) {
     var mapping = mappingData[i];
     Logger.log('欄位映射 ' + i + ': ' + mapping[0] + ' -> ' + mapping[1]);
   }
   ```

5. **試算表結構驗證**：
   ```bash
   # 檢查 Google 試算表
   - 確認試算表 ID 是否正確
   - 確認工作表名稱是否正確
   - 檢查欄位名稱是否有變更
   - 確認資料格式是否一致
   ```

#### 2. 技術細節記錄

**2.1 修正的檔案清單**

1. **`c:\GitHubProjects\BYOB\wordpress\Apps script.md`**：
   - 修正所有 ES6+ 語法錯誤
   - 改善欄位映射邏輯
   - 添加詳細除錯日誌
   - **新增**：需要添加 `parseLatestSpreadsheetData()` 函數的詳細除錯日誌

2. **`c:\GitHubProjects\BYOB\wordpress\functions.php`**：
   - 修正 ACF 欄位值映射
   - 添加除錯工具和測試函數
   - 改善錯誤處理邏輯

**2.2 關鍵程式碼修正**

**Google Apps Script 語法修正**：
```javascript
// 修正前
const [dbField, formLabelRaw] = mappingData[i];
Logger.log(`toHalfWidth: "${str}" -> "${result}"`);
array.forEach(item => ...);

// 修正後
var rowData = mappingData[i];
var dbField = rowData[0];
var formLabelRaw = rowData[1];
Logger.log('toHalfWidth: "' + str + '" -> "' + result + '"');
array.forEach(function(item) { ... });
```

**WordPress ACF 值映射修正**：
```php
// 修正前
$is_charged_map = [
    '酌收' => '是',
    '不收費' => '否', 
    '其他' => '其他'
];

// 修正後
$is_charged_map = [
    '酌收' => 'yes',
    '不收費' => 'no', 
    '其他' => 'other',
    '是' => 'yes',
    '否' => 'no'
];
```

**2.3 新增緊急修正需求**

**Google Apps Script 資料解析除錯**：
```javascript
// 需要在 parseLatestSpreadsheetData() 函數中添加的除錯日誌
function parseLatestSpreadsheetData() {
  Logger.log('=== 開始解析試算表資料 ===');
  
  try {
    // 獲取試算表
    var spreadsheet = SpreadsheetApp.openById(spreadsheetId);
    Logger.log('試算表名稱: ' + spreadsheet.getName());
    
    var sheet = spreadsheet.getSheetByName(sheetName);
    Logger.log('工作表名稱: ' + sheetName);
    Logger.log('工作表存在: ' + (sheet !== null));
    
    if (!sheet) {
      Logger.log('錯誤：找不到工作表 ' + sheetName);
      return {};
    }
    
    // 獲取標題行
    var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    Logger.log('標題行: ' + JSON.stringify(headers));
    
    // 獲取最後一行資料
    var lastRow = sheet.getLastRow();
    Logger.log('最後一行: ' + lastRow);
    
    if (lastRow <= 1) {
      Logger.log('錯誤：沒有資料行');
      return {};
    }
    
    var rowData = sheet.getRange(lastRow, 1, 1, sheet.getLastColumn()).getValues()[0];
    Logger.log('最後一行資料: ' + JSON.stringify(rowData));
    
    // 解析資料
    var parsedData = {};
    for (var i = 0; i < mappingData.length; i++) {
      var mapping = mappingData[i];
      var dbField = mapping[0];
      var formLabel = mapping[1];
      
      Logger.log('處理欄位映射: ' + dbField + ' -> ' + formLabel);
      
      var headerIndex = headers.indexOf(formLabel);
      if (headerIndex === -1) {
        Logger.log('警告：找不到欄位 ' + formLabel);
        continue;
      }
      
      var value = rowData[headerIndex];
      Logger.log('欄位值: ' + dbField + ' = ' + value);
      parsedData[dbField] = value;
    }
    
    Logger.log('解析完成，資料: ' + JSON.stringify(parsedData));
    return parsedData;
    
  } catch (error) {
    Logger.log('解析試算表資料時發生錯誤: ' + error.toString());
    return {};
  }
}
```

#### 3. 明日工作計劃

**3.1 優先級排序**

1. **🔴 最高優先級：解決 Google Apps Script 資料解析失敗問題**
   - 診斷 `parseLatestSpreadsheetData()` 函數的失敗原因
   - 修復資料解析邏輯
   - 添加詳細的除錯日誌
   - 測試資料解析功能

2. **🔴 高優先級：完成資料對應問題測試**
   - 重新提交測試表單
   - 驗證所有欄位修正結果
   - 確認前端顯示正確

3. **🟡 中等優先級：建立監控機制**
   - 建立自動化測試流程
   - 設定錯誤通知機制
   - 建立資料驗證規則

4. **🟢 低優先級：文件更新**
   - 更新技術文件
   - 建立操作手冊
   - 記錄解決方案

**3.2 時間分配建議**

- **上午（3小時）**：解決 Google Apps Script 資料解析問題
- **下午（2小時）**：完成測試和驗證
- **晚上（1小時）**：建立監控機制和文件更新

**3.3 成功標準**

- [ ] Google Apps Script 能正確解析試算表資料
- [ ] 所有 Google 表單欄位都能正確對應到 WordPress ACF 欄位
- [ ] 前端不再顯示「暫無資料」
- [ ] 資料格式正確且用戶友好
- [ ] 建立完整的測試和監控機制
- [ ] 更新相關技術文件

---

**今日已完成進度（2025-08-05）：**

* ✅ **新增：修復 Google Apps Script 語法錯誤**
  - 轉換所有 ES6+ 語法為 ES5 兼容語法
  - 修正箭頭函數、模板字符串、解構賦值等問題
* ✅ **新增：發現 ACF 欄位值格式不匹配問題**
  - 識別 `is_charged` 和 `open_bottle_service` 欄位的值映射問題
  - 確認 ACF 期望值格式與傳入值格式不匹配
* ✅ **新增：修正 WordPress functions.php 資料轉換邏輯**
  - 修正 `is_charged` 欄位值映射（'是' → 'yes', '否' → 'no'）
  - 修正 `open_bottle_service` 欄位值映射（'是' → 'yes', '否' → 'no'）
  - 改善 `equipment` 欄位陣列處理邏輯
* ✅ **新增：建立完整的除錯工具**
  - 建立 ACF 欄位除錯頁面（工具 → BYOB ACF 除錯）
  - 建立 ACF 配置測試函數
  - 建立 ACF 欄位狀態檢查函數
* ✅ **新增：完成系統性問題診斷**
  - 確認問題根本原因
  - 建立詳細的除錯和測試機制
  - 準備完整的修正方案
* 🚨 **新增：發現緊急問題 - Google Apps Script 資料解析完全失敗**
  - 識別 `parseLatestSpreadsheetData()` 函數執行失敗
  - 確認資料解析結果為空物件
  - 導致空的 payload 發送到 WordPress
  - 需要立即修復的緊急問題

**明日重點（按優先級排序）：**

1. **🔴 最高優先級：解決 Google Apps Script 資料解析失敗問題**
   - 診斷 `parseLatestSpreadsheetData()` 函數的失敗原因
   - 修復資料解析邏輯
   - 添加詳細的除錯日誌
   - 測試資料解析功能

2. **🔴 高優先級：完成資料對應問題的最終測試**
   - 重新提交 Google 表單測試
   - 驗證所有欄位修正結果
   - 確認前端顯示正確

3. **🟡 中等優先級：建立監控和測試機制**
   - 建立自動化測試流程
   - 設定錯誤通知機制
   - 建立資料驗證規則

4. **🟢 低優先級：文件更新和整理**
   - 更新技術文件
   - 建立操作手冊
   - 記錄完整解決方案

**技術注意事項：**

* 🔴 **緊急**：需要立即解決 Google Apps Script 資料解析失敗問題
* 🔴 **緊急**：需要完成最終測試驗證
* 🔴 **緊急**：確認前端顯示正確
* 🟡 **重要**：建立監控機制
* 🟡 **重要**：更新技術文件
* 🟢 **一般**：建立自動化測試
* 🟢 **一般**：改善錯誤處理
* 🟢 **一般**：建立故障排除流程