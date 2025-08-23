# Google表單「其他」選項顯示問題解決方案

## 📋 問題描述

### 問題現象
當使用者在 Google 表單的餐廳類型欄位中：
1. 勾選「其他」選項
2. 在後方文字框輸入描述（例如：「路上」、「路邊攤」等）

**結果：**
- ✅ 前台文章預覽正常顯示「路上」
- ❌ ACF 欄位的「其他」選項沒有被勾選
- ❌ ACF 欄位的「其他餐廳類型說明」為空白
- ❌ 餐廳編輯後台無法顯示「其他」選項和說明文字

### 問題影響
- 業者進入後台編輯餐廳資料時，看不到「其他」選項
- 儲存後，前台的「路上」描述會消失
- 需要手動在 ACF 中設定，增加審核工作量

## 🔍 問題分析

### 根本原因
**Google 表單的「其他」選項機制與我們的程式邏輯不匹配：**

1. **Google 表單的「其他」選項：**
   - 是 Google 內建的功能
   - 當勾選「其他」時，後方會出現文字輸入框
   - 提交時，只會傳送使用者輸入的文字（如「路上」）
   - **不會傳送「其他」這個選項本身**

2. **我們的 ACF 欄位：**
   - `restaurant_type`：多選核取方塊，包含「其他」選項
   - `restaurant_type_other_note`：文字欄位，存放「其他」的說明

3. **問題所在：**
   - Apps Script 收到「路上」，但無法識別這是「其他」的說明
   - 因為「路上」不在已知的餐廳類型清單中
   - 所以 ACF 的「其他」選項沒有被勾選
   - `restaurant_type_other_note` 欄位也沒有被設定

## 🛠️ 解決方案

### 解決思路
**使用「排除法」識別未知的餐廳類型：**

1. **建立已知餐廳類型清單**
2. **分析 Google 表單傳來的餐廳類型**
3. **識別出不在清單中的類型**
4. **自動將這些未知類型歸類為「其他」**
5. **將未知類型的描述存入 `restaurant_type_other_note`**

### 具體實作

#### 1. 修改 Google Apps Script

**檔案：** `wordpress/Apps script - 純淨版.js`

**修改內容：**

```javascript
} else if (wordpressField === 'restaurant_type') {
  // 特殊處理餐廳類型，使用「排除法」識別「其他」內容
  var restaurantTypes = value || '';
  Logger.log('🔍 處理餐廳類型: "' + restaurantTypes + '"');
  
  // 防護機制：檢查是否已經處理過
  if (parsedData.hasOwnProperty('restaurant_type')) {
    Logger.log('⚠️ 餐廳類型已經處理過，跳過重複處理');
    continue; // 使用 continue 而不是 return
  }
  
  // 已知的餐廳類型清單
  var knownTypes = [
    '台式', '法式', '義式', '日式', '美式', '熱炒', '小酒館', '咖啡廳',
    '私廚', '異國料理', '燒烤', '火鍋', '牛排', 'Lounge Bar', 'Buffet', 'Fine dining'
  ];
  
  // 分割餐廳類型
  var typesArray = restaurantTypes.split(',').map(function(type) {
    return type.trim();
  });
  Logger.log('📋 分割後的類型陣列: [' + typesArray.join(', ') + ']');
  
  // 使用「排除法」識別「其他」內容
  var validTypes = [];
  var otherNote = '';
  var hasOther = false;
  
  for (var i = 0; i < typesArray.length; i++) {
    var type = typesArray[i];
    
    if (knownTypes.includes(type) || type === '其他') {
      // 這是已知類型或「其他」選項
      validTypes.push(type);
      if (type === '其他') {
        hasOther = true;
      }
      Logger.log('✅ 識別到已知類型: "' + type + '"');
    } else {
      // 這是未知類型，可能是「其他」的說明文字
      otherNote = type;
      Logger.log('🔍 識別到未知類型，可能是「其他」說明: "' + type + '"');
    }
  }
  
  // 處理結果
  Logger.log('🔍 處理結果檢查:');
  Logger.log('  - otherNote = "' + otherNote + '"');
  Logger.log('  - hasOther = ' + hasOther);
  Logger.log('  - validTypes = [' + validTypes.join(', ') + ']');
  
  if (otherNote && hasOther) {
    // 有「其他」選項且有說明文字
    Logger.log('🎯 檢測到「其他」選項 + 說明文字: "' + otherNote + '"');
    parsedData[wordpressField] = validTypes.join(', ');
    parsedData['restaurant_type_other_note'] = otherNote;
    Logger.log('✅ 已設定 restaurant_type_other_note = "' + otherNote + '"');
  } else if (otherNote && !hasOther) {
    // 有未知類型但沒有「其他」選項，自動添加「其他」
    Logger.log('🔄 檢測到未知類型但無「其他」選項，自動添加「其他」');
    validTypes.push('其他');
    parsedData[wordpressField] = validTypes.join(', ');
    parsedData['restaurant_type_other_note'] = otherNote;
    Logger.log('✅ 已設定 restaurant_type_other_note = "' + otherNote + '"');
  } else {
    // 沒有未知類型，或沒有說明文字
    Logger.log('📝 沒有檢測到「其他」內容');
    parsedData[wordpressField] = validTypes.join(', ');
  }
  
  Logger.log('🏷️ 最終餐廳類型: "' + parsedData[wordpressField] + '"');
  Logger.log('📝 最終其他類型說明: "' + (parsedData['restaurant_type_other_note'] || '無') + '"');
  
  // 強制檢查和設定
  if (otherNote && otherNote !== '' && (!parsedData['restaurant_type_other_note'] || parsedData['restaurant_type_other_note'] === '')) {
    Logger.log('⚠️ 強制設定 restaurant_type_other_note = "' + otherNote + '"');
    parsedData['restaurant_type_other_note'] = otherNote;
  }
  
  // 標記為已處理，防止重複處理
  parsedData['_restaurant_type_processed'] = true;
}
```

**關鍵修改點：**
- 使用 `continue` 而不是 `return`，避免無限迴圈
- 添加防護機制，防止重複處理
- 實現「排除法」邏輯，自動識別未知類型

#### 2. 跳過 `restaurant_type_other_note` 的欄位映射

**在 Apps Script 的欄位映射循環中添加：**

```javascript
// 跳過 restaurant_type_other_note 的處理，因為它是由餐廳類型邏輯自動生成的
if (wordpressField === 'restaurant_type_other_note') {
  Logger.log('⏭️ 跳過 restaurant_type_other_note 的欄位映射，因為它由餐廳類型邏輯自動生成');
  continue;
}
```

#### 3. 移除欄位設定表中的映射

**重要：** 從 Google 試算表的欄位設定表中，**完全移除** `restaurant_type_other_note` 的映射行。

**原因：** 這個欄位是由程式邏輯自動生成的，不需要從 Google 表單中讀取。如果保留映射，Apps Script 會嘗試尋找對應的表單欄位，找不到時會將值設為空字串，覆蓋之前設定的值。

## 🔧 其他相關修改

### 1. 修改 WordPress functions.php

**檔案：** `wordpress/functions.php`

**修改內容：**

```php
// 處理餐廳類型 - 增強處理邏輯，支援「其他」選項
$types = $restaurant_data['restaurant_type'];
$other_note = $restaurant_data['restaurant_type_other_note'] ?? '';

if (!empty($types)) {
    // 確保「其他」選項存在（如果沒有「其他」但有說明文字，自動添加）
    if (!empty($other_note) && !in_array('其他', $types)) {
        $types[] = '其他';
        error_log('BYOB API: 自動添加「其他」選項，因為有說明文字: "' . $other_note . '"');
    }
    
    // 清理空值
    $types = array_filter($types, function($type) {
        return !empty(trim($type));
    });
}
```

### 2. 修改餐廳編輯頁面

**檔案：** `wordpress/woocommerce/myaccount/restaurant-profile.php`

**修改內容：**

```php
// 除錯：檢查餐廳類型和其他類型說明
$other_note = get_field('restaurant_type_other_note', $restaurant_id);
echo '<!-- DEBUG: restaurant_type = ' . print_r($current_types, true) . ' -->';
echo '<!-- DEBUG: restaurant_type_other_note = ' . $other_note . ' -->';

// 其他類型說明欄位
echo '<div id="other_type_note_field" class="form-group" style="margin-bottom: 25px; display: none;">';
echo '<label for="restaurant_type_other_note" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">其他類型說明</label>';
echo '<input type="text" id="restaurant_type_other_note" name="restaurant_type_other_note" value="' . esc_attr(get_field('restaurant_type_other_note', $restaurant_id)) . '" placeholder="請說明您的餐廳類型..." style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;">';
echo '</div>';
```

## ✅ 測試結果

### 測試步驟
1. 在 Google 表單中選擇餐廳類型「其他」
2. 輸入描述文字「路上」
3. 提交表單
4. 執行 Apps Script
5. 檢查 WordPress ACF 欄位

### 預期結果
- ✅ ACF 的 `restaurant_type` 欄位勾選「其他」
- ✅ ACF 的 `restaurant_type_other_note` 欄位顯示「路上」
- ✅ 餐廳編輯後台正確顯示「其他」選項和說明文字
- ✅ 前台文章正確顯示「其他」

## 🎯 解決方案總結

### 核心思路
**使用「排除法」自動識別 Google 表單中的未知餐廳類型，並將其歸類為「其他」選項的說明文字。**

### 關鍵修改
1. **Apps Script：** 實現「排除法」邏輯，自動識別未知類型
2. **欄位映射：** 跳過 `restaurant_type_other_note` 的處理
3. **欄位設定表：** 移除 `restaurant_type_other_note` 的映射
4. **WordPress 後端：** 增強餐廳類型處理邏輯
5. **前端顯示：** 正確顯示「其他」選項和說明文字

### 技術要點
- **防護機制：** 避免無限迴圈和重複處理
- **自動識別：** 不需要預先定義所有可能的「其他」內容
- **資料一致性：** 確保 ACF 欄位與前台顯示一致
- **除錯支援：** 詳細的日誌記錄，便於問題排查

## 📚 相關檔案

- `wordpress/Apps script - 純淨版.js` - Google Apps Script 主要邏輯
- `wordpress/functions.php` - WordPress REST API 處理
- `wordpress/woocommerce/myaccount/restaurant-profile.php` - 餐廳編輯頁面
- Google 試算表欄位設定表

## 🔮 未來改進建議

1. **動態餐廳類型清單：** 考慮將餐廳類型清單移到設定檔中，便於維護
2. **多語言支援：** 如果未來需要支援多語言，可以考慮使用語言包
3. **驗證機制：** 添加對「其他」說明文字的長度限制和格式驗證
4. **備份機制：** 考慮在 ACF 中保留原始的表單資料，便於除錯

---

**最後更新：** 2025年8月23日  
**解決狀態：** ✅ 已解決  
**測試狀態：** ✅ 測試通過
