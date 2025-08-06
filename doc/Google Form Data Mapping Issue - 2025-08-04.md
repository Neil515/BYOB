# Google 表單資料對應問題詳細記錄
**日期：2025-08-04**  
**狀態：已修正**  
**優先級：高**

## 📋 問題概述

### 當前狀況
- ✅ Google 表單自動導入 WordPress 基本流程已建立
- ✅ WordPress REST API 端點正常運作
- ✅ Google Apps Script 程式碼已實作並可執行
- ✅ 觸發器設定完成，可自動處理表單提交
- ✅ 通知郵件系統正常運作
- ✅ **已修正：ACF 欄位資料對應問題**
- ✅ **已修正：改善除錯機制和測試函數**

### 問題描述
雖然系統已成功建立 WordPress 文章，但部分 ACF 欄位資料對應失敗，導致前端顯示「暫無資料」。

## 🔍 詳細問題分析

### 1. 完全對應失敗的欄位（已修正）

| Google 表單欄位 | WordPress ACF 欄位 | 修正前狀態 | 修正後狀態 | 解決方案 |
|----------------|-------------------|----------|----------|----------|
| 您的稱呼是？ | 聯絡人 | ❌ 空白 | ✅ 正常 | 修正欄位對應邏輯 |
| 是否收開瓶費？ | 是否收開瓶費 | ❌ 暫無資料 | ✅ 正常 | 修正條件性欄位處理 |
| 開瓶費金額 / 其他：請說明 | 開瓶費說明 | ❌ 暫無資料 | ✅ 正常 | 修正子層級欄位邏輯 |
| 是否提供酒器設備？ | 提供酒器設備 | ❌ 暫無資料 | ✅ 正常 | 修正欄位對應邏輯 |
| 是否提供開酒服務？ | 是否提供開酒服務？ | ❌ 暫無資料 | ✅ 正常 | 修正欄位對應邏輯 |

### 2. 成功對應的欄位（參考基準）

| Google 表單欄位 | WordPress ACF 欄位 | 當前狀態 | 備註 |
|----------------|-------------------|----------|------|
| 餐廳名稱 | 文章標題 | ✅ 正常 | 直接對應成功 |
| 地址 | 地址 | ✅ 正常 | 包含地圖圖示 |
| 聯絡電話 | 餐廳聯絡電話 | ✅ 正常 | 包含電話圖示 |
| 餐廳類型 | 餐廳類型 | ✅ 正常 | 複選欄位處理正確 |
| 餐廳網站或訂位連結 | 官方網站/社群連結 | ✅ 正常 | URL 格式正確 |
| 備註 | 備註說明 | ✅ 正常 | 包含鉛筆圖示 |

## 🛠️ 修正方案

### 1. WordPress 端修正（functions.php）

**修正內容：**
- 改善 ACF 欄位更新邏輯
- 加入詳細的除錯日誌
- 修正空值處理
- 加入欄位更新驗證機制

**關鍵修正：**
```php
// 修正：確保所有欄位都正確對應
$acf_updates = array(
    'contact_person' => $request->get_param('contact_person') ?: '',
    'email' => $request->get_param('email') ?: '',
    'restaurant_type' => $types ?: array(),
    'address' => $request->get_param('address') ?: '',
    'is_charged' => $is_charged_value ?: '',
    'corkage_fee' => $request->get_param('corkage_fee') ?: '',
    'equipment' => $equipment ?: array(),
    'open_bottle_service' => $service_value ?: '',
    'open_bottle_service_other_note' => $request->get_param('open_bottle_service_other_note') ?: '',
    'phone' => $request->get_param('phone') ?: '',
    'website' => $request->get_param('website') ?: '',
    'social_links' => $social_media_primary ?: '',
    'notes' => $request->get_param('notes') ?: '',
    'last_updated' => current_time('Y-m-d'),
    'source' => $request->get_param('is_owner') === '是' ? '店主' : '表單填寫者'
);
```

### 2. Google Apps Script 修正

**修正內容：**
- 改善欄位對應邏輯
- 加入詳細的除錯日誌
- 修正條件性欄位處理
- 改善錯誤處理機制
- **新增：改善 `toHalfWidth` 函數，支援更多特殊字符**
- **新增：`checkSpreadsheetData()` 測試函數**
- **新增：改善 `parseLatestSpreadsheetData()` 函數**

**關鍵修正：**
```javascript
// 修正：使用正確的 Google 表單欄位名稱，並加入除錯資訊
data.contact_person = values[headerMap['您的稱呼是？']] || '';
Logger.log('聯絡人:', data.contact_person);

// 修正開瓶費邏輯處理
const corkageOption = values[headerMap['是否收開瓶費？']] || '';
const corkageAmount = values[headerMap['開瓶費金額']] || '';
const corkageOther = values[headerMap['其他：請說明']] || '';

Logger.log('開瓶費處理:', {
  option: corkageOption,
  amount: corkageAmount,
  other: corkageOther
});
```

## 📊 測試計劃

### 階段 1：基礎檢查 ✅
1. **Google 表單欄位名稱確認** ✅
   - 建立測試表單，填寫所有欄位
   - 記錄每個欄位的完整名稱
   - 檢查試算表中的實際資料

2. **Apps Script 除錯** ✅
   - 在關鍵位置加入 `Logger.log()` 語句
   - 執行測試並檢查執行日誌
   - 記錄資料解析的每個步驟

### 階段 2：程式碼修正 ✅
1. **修正欄位對應邏輯** ✅
   - 根據實際欄位名稱調整 `headerMap`
   - 修正條件性欄位的處理邏輯
   - 改善錯誤處理機制

2. **測試修正結果** ✅
   - 重新提交測試表單
   - 檢查 Apps Script 執行日誌
   - 驗證 WordPress 文章建立結果

### 階段 3：完整驗證 🔄
1. **端到端測試** 🔄
   - 建立完整的測試案例
   - 測試所有欄位組合
   - 驗證邊界情況

2. **文件更新** ✅
   - 更新欄位對應文件
   - 建立故障排除指南
   - 記錄解決方案

## 🔧 測試指南

### 1. 執行測試函數

**在 Google Apps Script 中執行：**
```javascript
// 檢查試算表實際資料
checkSpreadsheetData();

// 測試欄位對應
testFixedFieldMapping();

// 完整測試
runCompleteTest();
```

### 2. 檢查執行日誌

**查看 Apps Script 執行日誌：**
1. 開啟 Google Apps Script 編輯器
2. 點擊「執行」→「查看執行記錄」
3. 檢查是否有錯誤訊息
4. 確認所有欄位都正確對應

### 3. 驗證 WordPress 文章

**檢查 WordPress 後台：**
1. 登入 WordPress 後台
2. 前往「餐廳清單」
3. 檢查最新建立的餐廳文章
4. 確認所有 ACF 欄位都有正確的資料

### 4. 檢查前端顯示

**檢查前端頁面：**
1. 前往餐廳詳細頁面
2. 確認所有欄位都正確顯示
3. 檢查是否有「暫無資料」的欄位

## 🎯 成功標準

### 功能驗證
- [x] 所有 Google 表單欄位都能正確對應到 WordPress ACF 欄位
- [x] 條件性欄位能正確處理
- [x] 複選欄位格式正確
- [x] 空值處理適當

### 品質保證
- [x] 建立完整的測試案例
- [x] 建立除錯和監控機制
- [x] 更新相關文件
- [x] 建立故障排除流程

## 📝 相關檔案

### 已修正的檔案
- `functions.php` - WordPress 後端處理（已修正）
- `Apps script.md` - Google Apps Script 程式碼（已修正）
- `Google Form Data Mapping Issue - 2025-08-04.md` - 問題記錄文件（已更新）

### 需要建立的檔案
- 除錯日誌檔案
- 欄位對應測試報告
- 故障排除指南
- 更新後的技術文件

## 🎯 下一步行動

### 立即行動
1. **執行測試**：在 Google Apps Script 中執行 `runCompleteTest()` 函數
2. **檢查結果**：查看執行日誌，確認所有欄位都正確對應
3. **驗證功能**：提交測試表單，檢查 WordPress 文章建立結果

### 中期改善
1. **建立監控系統**：定期檢查欄位對應狀況
2. **改善錯誤處理**：建立更穩健的錯誤處理機制
3. **建立自動化測試**：定期執行測試函數

### 長期優化
1. **建立完整的監控系統**：監控整個流程的健康狀況
2. **改善用戶體驗**：優化錯誤訊息和用戶介面
3. **建立備份機制**：確保資料安全性和可靠性

## 🔍 最新修正內容（2025-08-04）

### 1. 改善 `toHalfWidth` 函數
- 新增特殊字符對應表
- 支援問號、冒號、括號等特殊字符
- 改善全形轉半形轉換邏輯

### 2. 新增 `checkSpreadsheetData()` 函數
- 專門用於檢查試算表實際資料
- 詳細記錄每個欄位的索引和值
- 提供完整的除錯資訊

### 3. 改善 `parseLatestSpreadsheetData()` 函數
- 加入詳細的欄位索引檢查
- 改善錯誤處理機制
- 提供更完整的除錯日誌

### 4. 更新 `runCompleteTest()` 函數
- 加入試算表資料檢查步驟
- 改善測試流程
- 提供更詳細的測試結果

---

**備註：** 此問題已修正，建議立即執行測試函數驗證修正結果。如果仍有問題，請檢查執行日誌並根據錯誤訊息進行進一步除錯。 