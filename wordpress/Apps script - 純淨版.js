// BYOB WordPress API 整合功能（純淨版）
// 設定常數
var WORDPRESS_API_URL = 'https://byobmap.com/wp-json/byob/v1/restaurant';
var API_KEY = 'byob-secret-key-2025';
var NOTIFICATION_EMAIL = 'byobmap.tw@gmail.com';

// 主要函數：處理表單提交並發送到 WordPress
function onFormSubmit(e) {
  try {
    Logger.log('=== 開始處理表單提交 ===');
    
    // 解析表單資料
    var formData = parseLatestSpreadsheetData();
    Logger.log('解析的表單資料:');
    Logger.log(JSON.stringify(formData, null, 2));
    
    // 檢查資料是否為空
    if (!formData || Object.keys(formData).length === 0) {
      throw new Error('解析的表單資料為空，請檢查試算表結構');
    }
    
    // 發送到 WordPress
    var result = sendToWordPress(formData);
    Logger.log('WordPress API 回應:');
    Logger.log(JSON.stringify(result, null, 2));
    
    // 發送成功通知郵件
    sendNotificationEmail(formData, result);
    
    Logger.log('表單處理完成');
    
  } catch (error) {
    Logger.log('表單處理發生錯誤: ' + error.toString());
    sendErrorNotification(error);
  }
}

// 解析試算表最新資料（修正版）
function parseLatestSpreadsheetData() {
  try {
    Logger.log('=== 開始解析試算表資料 ===');
    
    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    var formSheet = spreadsheet.getSheets()[0]; // 表單回應工作表
    var mappingSheet = spreadsheet.getSheetByName("欄位設定表");
    
    if (!mappingSheet) {
      throw new Error('找不到「欄位設定表」工作表');
    }
    
    // 取得最新一筆表單資料
    var formData = formSheet.getDataRange().getValues();
    var formHeaders = formData[0];
    var lastFormRow = formData[formData.length - 1];
    
    Logger.log('表單標題行:');
    Logger.log(formHeaders);
    Logger.log('最新表單資料:');
    Logger.log(lastFormRow);
    
    // 取得欄位映射設定
    var mappingData = mappingSheet.getDataRange().getValues();
    Logger.log('欄位設定表資料:');
    Logger.log(mappingData);
    
    // 建立表單標題索引
    var headerIndex = {};
    for (var h = 0; h < formHeaders.length; h++) {
      if (formHeaders[h]) {
        var cleanHeader = toHalfWidth(String(formHeaders[h]).trim());
        headerIndex[cleanHeader] = h;
        Logger.log('標題索引: "' + formHeaders[h] + '" -> "' + cleanHeader + '" -> 索引 ' + h);
      }
    }
    
    // 根據欄位設定表解析資料
    var parsedData = {};
    
    // 跳過標題行，從第二行開始處理映射
    for (var i = 1; i < mappingData.length; i++) {
      var mapping = mappingData[i];
      var wordpressField = mapping[0]; // WordPress 欄位名稱
      var formFieldName = mapping[1];  // 表單欄位名稱
      
      if (!wordpressField || !formFieldName) {
        continue; // 跳過空行
      }
      
      var cleanFormFieldName = toHalfWidth(String(formFieldName).trim());
      var formFieldIndex = headerIndex[cleanFormFieldName];
      
      if (formFieldIndex !== undefined) {
        var value = lastFormRow[formFieldIndex];
        
        // 跳過 restaurant_type_other_note 的處理，因為它是由餐廳類型邏輯自動生成的
        if (wordpressField === 'restaurant_type_other_note') {
          Logger.log('⏭️ 跳過 restaurant_type_other_note 的欄位映射，因為它由餐廳類型邏輯自動生成');
          continue;
        }
        
        // 特殊處理某些欄位
        if (wordpressField === 'is_charged') {
          // 轉換開瓶費選項
          if (String(value).indexOf('酌收') !== -1) {
            parsedData[wordpressField] = '酌收';
          } else if (String(value).indexOf('不收') !== -1) {
            parsedData[wordpressField] = '不收費';
          } else if (String(value).indexOf('其他') !== -1) {
            parsedData[wordpressField] = '其他';
          } else {
            parsedData[wordpressField] = value || '';
          }
        } else if (wordpressField === 'open_bottle_service') {
          // 開酒服務選項 - 直接傳送 Google 表單的值，因為 ACF 現在也是中文
          // Google 表單選項：有、無、其他
          // ACF 選項：有、無、其他
          parsedData[wordpressField] = value || '';
          Logger.log('🍷 開酒服務選項: "' + value + '" -> 直接傳送，無需轉換');
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
        } else {
          parsedData[wordpressField] = value || '';
        }
        
        Logger.log('成功映射: ' + wordpressField + ' = "' + parsedData[wordpressField] + '"');
      } else {
        Logger.log('⚠️ 找不到表單欄位: "' + formFieldName + '" -> "' + cleanFormFieldName + '"');
        parsedData[wordpressField] = ''; // 設為空值
      }
    }
    
    Logger.log('最終解析結果:');
    Logger.log(JSON.stringify(parsedData, null, 2));
    
    // 檢查必填欄位
    var requiredFields = ['restaurant_name', 'contact_person', 'email', 'restaurant_type', 'district', 'address', 'is_charged', 'phone'];
    var missingFields = [];
    
    for (var r = 0; r < requiredFields.length; r++) {
      var field = requiredFields[r];
      if (!parsedData[field] || parsedData[field] === '') {
        missingFields.push(field);
      }
    }
    
    if (missingFields.length > 0) {
      Logger.log('❌ 缺少必填欄位: ' + missingFields.join(', '));
    } else {
      Logger.log('✅ 所有必填欄位都有資料');
    }
    
    return parsedData;
    
  } catch (error) {
    Logger.log('解析試算表資料時發生錯誤: ' + error.toString());
    throw error;
  }
}

// 全形轉半形函數
function toHalfWidth(str) {
  if (!str) return '';
  
  var result = str.replace(/[\uFF01-\uFF5E]/g, function(ch) {
    return String.fromCharCode(ch.charCodeAt(0) - 0xFEE0);
  });
  
  result = result.replace(/\u3000/g, " ");
  
  var charMap = {
    '？': '?', '：': ':', '（': '(', '）': ')', '，': ',',
    '。': '.', '！': '!', '；': ';', '、': ',', '…': '...',
    '—': '-', '－': '-'
  };
  
  for (var fullWidth in charMap) {
    if (charMap.hasOwnProperty(fullWidth)) {
      var regex = new RegExp(fullWidth, 'g');
      result = result.replace(regex, charMap[fullWidth]);
    }
  }
  
  return result.replace(/^\s+|\s+$/g, ''); // trim
}

// 發送到 WordPress
function sendToWordPress(data) {
  Logger.log('準備發送到 WordPress 的資料:');
  Logger.log(JSON.stringify(data, null, 2));
  
  // 特別檢查餐廳類型相關欄位
  Logger.log('🍽️ 餐廳類型檢查:');
  Logger.log('  - restaurant_type = "' + (data.restaurant_type || '無') + '"');
  Logger.log('  - restaurant_type_other_note = "' + (data.restaurant_type_other_note || '無') + '"');
  
  // 檢查資料完整性
  if (data.restaurant_type_other_note && data.restaurant_type_other_note !== '') {
    Logger.log('✅ 其他類型說明已準備好: "' + data.restaurant_type_other_note + '"');
  } else {
    Logger.log('❌ 其他類型說明為空或未設定');
  }
  
  var options = {
    'method': 'POST',
    'headers': {
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY
    },
    'payload': JSON.stringify(data),
    'muteHttpExceptions': true // 取得完整錯誤回應
  };
  
  try {
    var response = UrlFetchApp.fetch(WORDPRESS_API_URL, options);
    var responseCode = response.getResponseCode();
    var responseText = response.getContentText();
    
    Logger.log('WordPress API 回應碼: ' + responseCode);
    Logger.log('WordPress API 回應內容: ' + responseText);
    
    if (responseCode === 200 || responseCode === 201) {
      return {
        success: true,
        message: '成功建立餐廳文章',
        response: JSON.parse(responseText)
      };
    } else {
      throw new Error('API 回應錯誤: ' + responseCode + ' - ' + responseText);
    }
    
  } catch (error) {
    Logger.log('發送到 WordPress 時發生錯誤: ' + error.toString());
    throw error;
  }
}

// 發送成功通知郵件
function sendNotificationEmail(data, result) {
  try {
    var subject = 'BYOB 新餐廳申請 - ' + (data.restaurant_name || '未知餐廳');
    var body = 
      '<h2>新餐廳申請已收到</h2>' +
      '<p><strong>餐廳名稱：</strong>' + (data.restaurant_name || '') + '</p>' +
      '<p><strong>聯絡人：</strong>' + (data.contact_person || '') + '</p>' +
      '<p><strong>電子郵件：</strong>' + (data.email || '') + '</p>' +
      '<p><strong>電話：</strong>' + (data.phone || '') + '</p>' +
      '<p><strong>地址：</strong>' + (data.address || '') + '</p>' +
      '<p><strong>開瓶費政策：</strong>' + (data.is_charged || '') + '</p>' +
      '<p><strong>處理狀態：</strong>' + (result.success ? '成功' : '失敗') + '</p>' +
      '<p><strong>訊息：</strong>' + (result.message || result.toString()) + '</p>' +
      '<hr>' +
      '<p><small>此郵件由 BYOB 自動化系統發送</small></p>';
    
    GmailApp.sendEmail(NOTIFICATION_EMAIL, subject, '', {
      htmlBody: body
    });
    
    Logger.log('已發送通知郵件');
    
  } catch (error) {
    Logger.log('發送通知郵件時發生錯誤: ' + error.toString());
  }
}

// 發送錯誤通知郵件
function sendErrorNotification(error) {
  try {
    var subject = 'BYOB 表單處理錯誤';
    var body = 
      '<h2>表單處理發生錯誤</h2>' +
      '<p><strong>錯誤訊息：</strong>' + error.toString() + '</p>' +
      '<p><strong>錯誤詳情：</strong>' + (error.stack || '無詳細資訊') + '</p>' +
      '<hr>' +
      '<p><small>此郵件由 BYOB 自動化系統發送</small></p>';
    
    GmailApp.sendEmail(NOTIFICATION_EMAIL, subject, '', {
      htmlBody: body
    });
    
  } catch (emailError) {
    Logger.log('發送錯誤通知郵件時發生錯誤: ' + emailError.toString());
  }
}

// 測試函數：檢查欄位映射
function testFieldMapping() {
  try {
    Logger.log('=== 測試欄位映射 ===');
    
    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    var mappingSheet = spreadsheet.getSheetByName("欄位設定表");
    
    if (!mappingSheet) {
      Logger.log('❌ 找不到「欄位設定表」工作表');
      return;
    }
    
    var mappingData = mappingSheet.getDataRange().getValues();
    Logger.log('欄位設定表內容:');
    Logger.log(mappingData);
    
    // 檢查映射設定
    for (var i = 1; i < mappingData.length; i++) {
      var mapping = mappingData[i];
      var wordpressField = mapping[0];
      var formFieldName = mapping[1];
      
      if (wordpressField && formFieldName) {
        Logger.log('映射 ' + i + ': ' + wordpressField + ' <- ' + formFieldName);
      }
    }
    
  } catch (error) {
    Logger.log('測試欄位映射時發生錯誤: ' + error.toString());
  }
}

// 測試函數：解析最新資料
function testParseLatestData() {
  try {
    Logger.log('=== 測試解析最新資料 ===');
    var result = parseLatestSpreadsheetData();
    Logger.log('解析結果:');
    Logger.log(JSON.stringify(result, null, 2));
    return result;
  } catch (error) {
    Logger.log('測試解析資料時發生錯誤: ' + error.toString());
  }
}

// 測試函數：完整流程測試
function testCompleteFlow() {
  try {
    Logger.log('=== 測試完整流程 ===');
    
    var formData = parseLatestSpreadsheetData();
    if (!formData || Object.keys(formData).length === 0) {
      Logger.log('❌ 資料解析失敗');
      return;
    }
    
    Logger.log('✅ 資料解析成功');
    Logger.log('發送到 WordPress...');
    
    var result = sendToWordPress(formData);
    Logger.log('WordPress 回應:');
    Logger.log(JSON.stringify(result, null, 2));
    
    if (result.success) {
      Logger.log('✅ 完整測試成功！');
    } else {
      Logger.log('❌ WordPress API 失敗');
    }
    
  } catch (error) {
    Logger.log('完整測試失敗: ' + error.toString());
  }
}

// 設定觸發器
function setupTrigger() {
  // 刪除現有觸發器
  var triggers = ScriptApp.getProjectTriggers();
  for (var t = 0; t < triggers.length; t++) {
    if (triggers[t].getHandlerFunction() === 'onFormSubmit') {
      ScriptApp.deleteTrigger(triggers[t]);
    }
  }
  
  // 建立新的表單提交觸發器（這裡需要替換為您的表單ID）
  // 如果您使用的是試算表觸發器，請使用以下程式碼：
  ScriptApp.newTrigger('onFormSubmit')
    .forSpreadsheet(SpreadsheetApp.getActiveSpreadsheet())
    .onFormSubmit()
    .create();
    
  Logger.log('觸發器設定完成');
}

// 手動轉換表單資料到資料庫格式
function convertFormToBYOBDatabase() {
  try {
    Logger.log('=== 開始手動轉換表單資料 ===');
    
    var sheet = SpreadsheetApp.getActiveSpreadsheet();
    var formSheet = sheet.getSheets()[0];
    var mappingSheet = sheet.getSheetByName("欄位設定表");
    var outputSheetName = "轉換後資料庫格式";
    var outputSheet = sheet.getSheetByName(outputSheetName);

    if (!outputSheet) {
      outputSheet = sheet.insertSheet(outputSheetName);
    } else {
      outputSheet.clearContents();
    }

    // 建立輸出表頭
    outputSheet.appendRow([
      "餐廳名稱", "餐廳類型", "行政區", "地址", "是否收開瓶費",
      "開瓶費金額", "其他：請說明", "提供酒器設備", "是否提供開酒服務？",
      "餐廳聯絡電話", "餐廳網站或訂位連結", "餐廳 Instagram 或 Facebook", "備註說明", "最後更新日期", "資料來源/ 提供人"
    ]);

    var formData = formSheet.getDataRange().getValues();
    var formHeader = formData[0];
    
    // 處理表頭，移除空格和轉換全形
    for (var h = 0; h < formHeader.length; h++) {
      formHeader[h] = toHalfWidth(String(formHeader[h]).trim());
    }

    var today = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyy-MM-dd");
    var newRows = [];

    // 處理每一行資料（跳過標題行）
    for (var i = 1; i < formData.length; i++) {
      var row = formData[i];
      
      // 清理每個儲存格的資料
      for (var j = 0; j < row.length; j++) {
        if (typeof row[j] === "string") {
          row[j] = toHalfWidth(row[j].trim());
        }
      }

      // 建立欄位索引
      var headerIndex = {};
      for (var k = 0; k < formHeader.length; k++) {
        headerIndex[formHeader[k]] = k;
      }

      // 提取各欄位資料
      var restaurantName = row[headerIndex["餐廳名稱"]] || "";
      var type = row[headerIndex["餐廳類型"]] || "";
      var district = row[headerIndex["行政區"]] || "未知";
      var address = row[headerIndex["地址"]] || "";

      // 處理開瓶費邏輯
      var corkageOption = row[headerIndex["是否收開瓶費?"]] || "";
      var corkageAmount = row[headerIndex["開瓶費金額"]] || "";
      var corkageOther = row[headerIndex["其他:請說明"]] || "";

      var corkage = cleanCorkageOption(corkageOption);
      var corkageAmountDisplay = "—";
      var corkageOtherDisplay = "—";

      if (corkageOption.indexOf("酌收") !== -1) {
        corkageAmountDisplay = corkageAmount || "—";
      } else if (corkageOption.indexOf("其他") !== -1) {
        corkageOtherDisplay = corkageOther || "—";
      }

      var wineTools = row[headerIndex["是否提供酒器設備?"]] || "";
      var wineService = row[headerIndex["是否提供開酒服務?"]] || "";

      // 處理電話號碼
      var phone = row[headerIndex["聯絡電話"]] || "";
      phone = processPhoneNumber(phone);

      var website = row[headerIndex["餐廳網站或訂位連結"]] || "—";
      var social = row[headerIndex["餐廳 Instagram 或 Facebook"]] || "—";
      var contact = website || social || "—";

      var remarks = row[headerIndex["備註"]] || "—";
      var ownerFlag = row[headerIndex["您是餐廳負責人嗎?"]] || "";
      var ownerName = row[headerIndex["您的稱呼是?"]] || "";
      var source = (ownerFlag === "是" ? "店主" : "表單填寫者") + " " + (ownerName || "—");

      newRows.push([
        restaurantName,
        type,
        district,
        address,
        corkage,
        corkageAmountDisplay,
        corkageOtherDisplay,
        wineTools,
        wineService,
        phone,
        website,
        social,
        remarks,
        today,
        source
      ]);
    }

    if (newRows.length > 0) {
      outputSheet.getRange(2, 1, newRows.length, newRows[0].length).setValues(newRows);
      outputSheet.getRange(2, 10, newRows.length).setNumberFormat("@"); // 電話欄位格式
    }
    
    Logger.log('手動轉換完成，共處理 ' + newRows.length + ' 筆資料');
    Logger.log('轉換結果已儲存到「' + outputSheetName + '」工作表');
    
  } catch (error) {
    Logger.log('手動轉換時發生錯誤: ' + error.toString());
  }
}

// 清理開瓶費選項名稱
function cleanCorkageOption(option) {
  if (!option) return "—";
  
  // 移除括號及其內容
  var cleaned = option.replace(/（[^）]*）/g, "").replace(/\([^)]*\)/g, "");
  
  // 移除多餘空格
  cleaned = cleaned.replace(/^\s+|\s+$/g, '');
  
  // 如果清理後為空，返回原始值
  return cleaned || option || "—";
}

// 處理電話號碼格式
function processPhoneNumber(phone) {
  if (!phone) return "—";
  
  if (typeof phone === "number") {
    // 數字類型：確保保留前導的 0
    var phoneStr = phone.toString();
    
    // 根據長度判斷是市話還是手機
    if (phoneStr.length === 8) {
      // 市話格式：02-12345678，補回前導 0
      phone = "'02" + phoneStr;
    } else if (phoneStr.length === 9) {
      // 可能是 09 開頭的手機號碼被截斷，補回前導 0
      phone = "'0" + phoneStr;
    } else if (phoneStr.length === 10) {
      // 10位數，可能是手機號碼，補回前導 0
      phone = "'0" + phoneStr;
    } else {
      // 其他情況，加上單引號避免 Excel 誤判
      phone = "'" + phoneStr;
    }
  } else if (typeof phone === "string") {
    // 字串類型的處理
    phone = phone.replace(/^\s+|\s+$/g, ''); // trim
    if (phone && phone.charAt(0) !== "'") {
      // 確保有單引號前綴
      phone = "'" + phone;
    }
    phone = phone || "—";
  } else {
    phone = "—";
  }
  
  return phone;
}
