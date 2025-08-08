function convertFormToBYOBDatabase() {
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

  // ✅ 建立輸出表頭
  outputSheet.appendRow([
    "餐廳名稱", "餐廳類型", "行政區", "地址", "是否收開瓶費",
    "開瓶費金額", "其他：請說明", "提供酒器設備", "是否提供開酒服務？",
    "餐廳聯絡電話", "官方網站/ 社群連結", "備註說明", "最後更新日期", "資料來源/ 提供人"
  ]);

  var formData = formSheet.getDataRange().getValues();
  var formHeader = formData[0].map(function(h) {
    return toHalfWidth(String(h).trim());
  });

  // ✅ 建立 mapping
  var mappingData = mappingSheet.getDataRange().getValues();
  var mapping = {};
  var report = [["對應失敗欄位", "目標表單欄位名稱"]];

  for (var i = 1; i < mappingData.length; i++) {
    var rowData = mappingData[i];
    var dbField = rowData[0];
    var formLabelRaw = rowData[1];
    var formLabel = toHalfWidth(String(formLabelRaw).trim());
    var colIndex = formHeader.findIndex(function(header) {
      return header === formLabel;
    });
    mapping[dbField] = colIndex;
    if (colIndex === -1) {
      Logger.log('⚠️ 找不到欄位：「' + formLabel + '」');
      report.push([dbField, formLabel]);
    }
  }

  // ✅ 輸出 mapping 檢查報告
  var reportSheetName = "⚠️ mapping 檢查報告";
  var reportSheet = sheet.getSheetByName(reportSheetName);
  if (!reportSheet) {
    reportSheet = sheet.insertSheet(reportSheetName);
  } else {
    reportSheet.clearContents();
  }
  if (report.length > 1) {
    reportSheet.getRange(1, 1, report.length, 2).setValues(report);
  } else {
    reportSheet.getRange(1, 1).setValue("✅ 全部欄位對應成功");
  }

  var today = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyy-MM-dd");
  var newRows = [];

  for (var i = 1; i < formData.length; i++) {
    var row = formData[i].map(function(cell) {
      return typeof cell === "string" ? toHalfWidth(cell.trim()) : cell;
    });

    var restaurantName = row[mapping["餐廳名稱"]];
    var type = row[mapping["餐廳類型"]];
    var district = row[mapping["行政區"]] || "未知";
    var address = row[mapping["地址"]];

    // ✅ 修正開瓶費邏輯
    var corkageOption = row[mapping["是否收開瓶費？"]];
    var corkageAmount = row[mapping["開瓶費金額"]];
    var corkageOther = row[mapping["其他：請說明"]];

    // 清理選項名稱，移除括號內的說明文字
    var corkage = cleanCorkageOption(corkageOption);
    var corkageAmountDisplay = "—";
    var corkageOtherDisplay = "—";

    // 根據選項設定對應的詳細資訊欄位
    if (corkageOption && corkageOption.includes("酌收")) {
      corkageAmountDisplay = corkageAmount || "—";
    } else if (corkageOption && corkageOption.includes("其他")) {
      corkageOtherDisplay = corkageOther || "—";
    }

    var wineTools = row[mapping["是否提供酒器設備？"]];
    var wineService = row[mapping["是否提供開酒服務？"]];

    // ✅ 修正電話欄位處理邏輯
    var phone = row[mapping["聯絡電話"]];
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
      phone = phone.trim();
      if (phone && !phone.startsWith("'")) {
        // 確保有單引號前綴
        phone = "'" + phone;
      }
      phone = phone || "—";
    } else {
      phone = "—";
    }

    var website = row[mapping["餐廳網站或訂位連結"]] || "";
    var social = row[mapping["餐廳 Instagram 或 Facebook"]] || ""; // 修正欄位名稱
    var contact = website || social || "—";

    var remarks = row[mapping["備註"]] || "—";
    var ownerFlag = row[mapping["您是餐廳負責人嗎？"]];
    var ownerName = row[mapping["您的稱呼是？"]];
    var source = (ownerFlag === "是" ? "店主" : "表單填寫者") + " " + (ownerName || "—");

    newRows.push([
      restaurantName,
      type,
      district,
      address,
      corkage,                    // 只顯示：不收費、酌收、其他
      corkageAmountDisplay,       // 顯示金額（如果是酌收）
      corkageOtherDisplay,        // 顯示說明（如果是其他）
      wineTools,
      wineService,
      phone,
      contact,
      remarks,
      today,
      source
    ]);
  }

  if (newRows.length > 0) {
    outputSheet.getRange(2, 1, newRows.length, newRows[0].length).setValues(newRows);
    outputSheet.getRange(2, 10, newRows.length).setNumberFormat("@"); // 電話欄位
  }
}

// 修正版：全形轉半形 + 去除空白 + 處理特殊字符
function toHalfWidth(str) {
  if (!str) return '';
  
  // 全形轉半形
  var result = str.replace(/[\uFF01-\uFF5E]/g, function(ch) {
    return String.fromCharCode(ch.charCodeAt(0) - 0xFEE0);
  });
  
  // 處理全形空格
  result = result.replace(/\u3000/g, " ");
  
  // 處理特殊字符對應 - 簡化版本
  var charMap = {
    '？': '?',
    '：': ':',
    '（': '(',
    '）': ')',
    '，': ',',
    '。': '.',
    '！': '!',
    '；': ';',
    '、': ',',
    '…': '...',
    '—': '-',
    '－': '-'
  };
  
  // 替換特殊字符
  for (var fullWidth in charMap) {
    if (charMap.hasOwnProperty(fullWidth)) {
      result = result.replace(new RegExp(fullWidth, 'g'), charMap[fullWidth]);
    }
  }
  
  // 去除前後空白
  result = result.trim();
  
  Logger.log('toHalfWidth: "' + str + '" -> "' + result + '"');
  
  return result;
}

// ✅ 新增：清理開瓶費選項名稱
function cleanCorkageOption(option) {
  if (!option) return "—";
  
  // 移除括號及其內容
  var cleaned = option.replace(/（[^）]*）/g, "").replace(/\([^)]*\)/g, "");
  
  // 移除多餘空格
  cleaned = cleaned.trim();
  
  // 如果清理後為空，返回原始值
  return cleaned || option || "—";
}

// ===== BYOB WordPress API 整合功能 =====

// 設定常數
var WORDPRESS_API_URL = 'https://byobmap.com/wp-json/byob/v1/restaurant';
var API_KEY = 'byob-secret-key-2025';
var NOTIFICATION_EMAIL = 'byobmap.tw@gmail.com';

// 主要函數：處理表單提交並發送到 WordPress
function onFormSubmit(e) {
  try {
    Logger.log('開始處理表單提交...');
    Logger.log('觸發器事件類型:', typeof e);
    Logger.log('觸發器事件內容:', JSON.stringify(e));
    
    // 檢查事件類型並取得表單資料
    var formData;
    
    if (e && e.response) {
      // 表單觸發器
      Logger.log('使用表單觸發器資料');
      formData = parseFormResponses(e.response.getItemResponses());
    } else if (e && e.range) {
      // 試算表觸發器
      Logger.log('使用試算表觸發器資料');
      formData = parseSpreadsheetData(e);
    } else if (e && e.values) {
      // 直接傳入資料的觸發器
      Logger.log('使用直接資料觸發器');
      formData = parseDirectData(e);
    } else {
      Logger.log('無法識別的觸發器事件，嘗試使用試算表資料');
      // 嘗試直接從試算表取得最新資料
      formData = parseLatestSpreadsheetData();
    }
    
    Logger.log('解析的表單資料:', formData);
    
    // 發送到 WordPress
    var result = sendToWordPress(formData);
    Logger.log('WordPress API 回應:', result);
    
    // 記錄到 Google Sheet
    var responseId = e.response ? e.response.getResponseId() : new Date().getTime();
    logSubmission(responseId, formData, result);
    
    // 發送通知郵件
    sendNotificationEmail(formData, result);
    
    Logger.log('表單處理完成');
    
  } catch (error) {
    Logger.log('處理表單時發生錯誤:', error);
    sendErrorNotification(error);
  }
}

// 新增：解析直接資料
function parseDirectData(e) {
  var data = {};
  
  // 直接使用傳入的資料
  var values = e.values || [];
  
  Logger.log('直接資料:', values);
  
  // 這裡可以根據需要處理直接傳入的資料
  // 暫時返回空物件，避免錯誤
  
  return data;
}

// 修正版：直接從試算表取得最新資料
function parseLatestSpreadsheetData() {
  var data = {};
  
  try {
    // 取得試算表資料
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
    var lastRow = sheet.getLastRow();
    var lastColumn = sheet.getLastColumn();
    
    Logger.log('試算表資訊:', {
      lastRow: lastRow,
      lastColumn: lastColumn
    });
    
    if (lastRow < 2) {
      Logger.log('試算表中沒有資料');
      return data;
    }
    
    // 取得表頭和最後一筆資料
    var headers = sheet.getRange(1, 1, 1, lastColumn).getValues()[0];
    Logger.log('原始表頭:', headers);
    
    // 取得最後一筆資料
    var values = sheet.getRange(lastRow, 1, 1, lastColumn).getValues()[0];
    Logger.log('最後一筆資料:', values);
    
    // 建立表頭對應
    var headerMap = {};
    for (var i = 0; i < headers.length; i++) {
      var header = headers[i];
      var processedHeader = toHalfWidth(String(header).trim());
      headerMap[processedHeader] = i;
      Logger.log('表頭對應:', processedHeader + ' -> ' + i);
    }
    
    Logger.log('完整的表頭對應:', headerMap);
    
    // 根據「欄位設定表」提取資料 - 修正版
    // 餐廳名稱
    var restaurantNameIndex = headerMap['餐廳名稱'];
    if (restaurantNameIndex !== undefined) {
      data.restaurant_name = values[restaurantNameIndex] || '';
      Logger.log('餐廳名稱:', data.restaurant_name);
    } else {
      Logger.log('⚠️ 找不到餐廳名稱欄位');
      data.restaurant_name = '';
    }
    
    // 聯絡人
    var contactPersonIndex = headerMap['您的稱呼是?'] || headerMap['您的稱呼是？'];
    if (contactPersonIndex !== undefined) {
      data.contact_person = values[contactPersonIndex] || '';
      Logger.log('聯絡人資料:', data.contact_person);
    } else {
      Logger.log('⚠️ 找不到聯絡人欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.contact_person = '';
    }
    
    // 電子郵件
    var emailIndex = headerMap['電子郵件地址'];
    if (emailIndex !== undefined) {
      data.email = values[emailIndex] || '';
      Logger.log('電子郵件:', data.email);
    } else {
      Logger.log('⚠️ 找不到電子郵件欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.email = '';
    }
    
    // 餐廳類型
    var restaurantTypeIndex = headerMap['餐廳類型'];
    if (restaurantTypeIndex !== undefined) {
      data.restaurant_type = values[restaurantTypeIndex] || '';
      Logger.log('餐廳類型:', data.restaurant_type);
    } else {
      Logger.log('⚠️ 找不到餐廳類型欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.restaurant_type = '';
    }
    
    // 行政區
    var districtIndex = headerMap['行政區'];
    if (districtIndex !== undefined) {
      data.district = values[districtIndex] || '';
      Logger.log('行政區:', data.district);
    } else {
      Logger.log('⚠️ 找不到行政區欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.district = '';
    }
    
    // 地址
    var addressIndex = headerMap['地址'];
    if (addressIndex !== undefined) {
      data.address = values[addressIndex] || '';
      Logger.log('地址:', data.address);
    } else {
      Logger.log('⚠️ 找不到地址欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.address = '';
    }
    
    // 處理開瓶費相關欄位
    var corkageOptionIndex = headerMap['是否收開瓶費?'] || headerMap['是否收開瓶費？'];
    var corkageAmountIndex = headerMap['開瓶費金額'];
    var corkageOtherIndex = headerMap['其他:請說明'] || headerMap['其他：請說明'];
    
    if (corkageOptionIndex !== undefined) {
      var corkageOption = values[corkageOptionIndex] || '';
      var corkageAmount = corkageAmountIndex !== undefined ? values[corkageAmountIndex] || '' : '';
      var corkageOther = corkageOtherIndex !== undefined ? values[corkageOtherIndex] || '' : '';
      
      Logger.log('開瓶費選項:', corkageOption);
      Logger.log('開瓶費金額:', corkageAmount);
      Logger.log('開瓶費其他:', corkageOther);
      
      // 設定開瓶費相關欄位
      if (corkageOption && corkageOption.includes('酌收')) {
        data.is_charged = '是';
        data.corkage_fee = corkageAmount || '—';
      } else if (corkageOption && corkageOption.includes('不收')) {
        data.is_charged = '否';
        data.corkage_fee = '—';
      } else if (corkageOption && corkageOption.includes('其他')) {
        data.is_charged = '其他';
        data.corkage_fee = corkageOther || '—';
      } else {
        data.is_charged = '—';
        data.corkage_fee = '—';
      }
    } else {
      Logger.log('⚠️ 找不到開瓶費欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.is_charged = '';
      data.corkage_fee = '';
    }
    
    // 處理酒器設備欄位
    var equipmentIndex = headerMap['是否提供酒器設備?'] || headerMap['是否提供酒器設備？'];
    if (equipmentIndex !== undefined) {
      data.equipment = values[equipmentIndex] || '';
      Logger.log('酒器設備資料:', data.equipment);
    } else {
      Logger.log('⚠️ 找不到酒器設備欄位');
      data.equipment = '';
    }
    
    // 處理開酒服務欄位
    var serviceIndex = headerMap['是否提供開酒服務?'] || headerMap['是否提供開酒服務？'];
    if (serviceIndex !== undefined) {
      data.open_bottle_service = values[serviceIndex] || '';
      Logger.log('開酒服務資料:', data.open_bottle_service);
    } else {
      Logger.log('⚠️ 找不到開酒服務欄位');
      data.open_bottle_service = '';
    }
    
    // 聯絡電話
    var phoneIndex = headerMap['聯絡電話'];
    if (phoneIndex !== undefined) {
      data.phone = values[phoneIndex] || '';
      Logger.log('聯絡電話:', data.phone);
    } else {
      Logger.log('⚠️ 找不到聯絡電話欄位');
      Logger.log('⚠️ 可用的欄位:', Object.keys(headerMap));
      data.phone = '';
    }
    
    // 餐廳網站
    var websiteIndex = headerMap['餐廳網站或訂位連結'];
    if (websiteIndex !== undefined) {
      data.website = values[websiteIndex] || '';
      Logger.log('餐廳網站:', data.website);
    } else {
      Logger.log('⚠️ 找不到餐廳網站欄位');
      data.website = '';
    }
    
    // 社群媒體
    var socialMediaIndex = headerMap['餐廳 Instagram 或 Facebook'];
    if (socialMediaIndex !== undefined) {
      data.social_media = values[socialMediaIndex] || '';
      Logger.log('社群媒體:', data.social_media);
    } else {
      Logger.log('⚠️ 找不到社群媒體欄位');
      data.social_media = '';
    }
    
    // 備註
    var notesIndex = headerMap['備註'];
    if (notesIndex !== undefined) {
      data.notes = values[notesIndex] || '';
      Logger.log('備註:', data.notes);
    } else {
      Logger.log('⚠️ 找不到備註欄位');
      data.notes = '';
    }
    
    // 處理餐廳負責人欄位
    var ownerIndex = headerMap['您是餐廳負責人嗎?'] || headerMap['您是餐廳負責人嗎？'];
    if (ownerIndex !== undefined) {
      data.is_owner = values[ownerIndex] || '';
      Logger.log('餐廳負責人資料:', data.is_owner);
    } else {
      Logger.log('⚠️ 找不到餐廳負責人欄位');
      data.is_owner = '';
    }
    
    Logger.log('最終解析的資料:', data);
    
    // 檢查必填欄位
    var requiredFields = ['restaurant_name', 'contact_person', 'email', 'restaurant_type', 'district', 'address', 'is_charged', 'phone'];
    var missingFields = [];
    
    requiredFields.forEach(function(field) {
      if (!data[field] || data[field] === '') {
        missingFields.push(field);
        Logger.log('❌ 缺少必填欄位: ' + field);
      } else {
        Logger.log('✅ 有資料: ' + field + ' = ' + data[field]);
      }
    });
    
    if (missingFields.length > 0) {
      Logger.log('❌ 缺少的必填欄位:', missingFields);
      Logger.log('❌ 這將導致 WordPress API 拒絕請求');
    } else {
      Logger.log('✅ 所有必填欄位都有資料！');
    }
    
    // 新增：檢查資料是否為空物件
    if (Object.keys(data).length === 0) {
      Logger.log('❌ 警告：解析的資料為空物件！');
    }
    
  } catch (error) {
    Logger.log('解析試算表資料時發生錯誤:', error);
  }
  
  return data;
}

// 簡化版：解析試算表資料
function parseSpreadsheetData(values, headerMap) {
  var data = {};
  
  try {
    // 基本資料
    data.restaurant_name = values[headerMap['餐廳名稱']] || '';
    data.contact_person = values[headerMap['您的稱呼是？']] || '';
    data.email = values[headerMap['電子郵件地址']] || '';
    data.restaurant_type = values[headerMap['餐廳類型']] || '';
    data.district = values[headerMap['行政區']] || '';
    data.address = values[headerMap['地址']] || '';
    
    // 開瓶費相關 - 簡化處理
    var corkageOption = values[headerMap['是否收開瓶費？']] || '';
    var corkageAmount = values[headerMap['開瓶費金額']] || '';
    var corkageOther = values[headerMap['其他：請說明']] || '';
    
    data.is_charged = corkageOption || '';
    data.corkage_fee = corkageAmount || '';
    
    // 如果開瓶費選項包含"酌收"且有金額，則使用金額
    if (corkageOption && corkageOption.includes('酌收') && corkageAmount) {
      data.corkage_fee = corkageAmount;
    }
    // 如果開瓶費選項包含"其他"且有說明，則使用說明
    else if (corkageOption && corkageOption.includes('其他') && corkageOther) {
      data.corkage_fee = corkageOther;
    }
    
    // 酒器設備
    data.equipment = values[headerMap['是否提供酒器設備？']] || '';
    
    // 開酒服務
    data.open_bottle_service = values[headerMap['是否提供開酒服務？']] || '';
    data.open_bottle_service_other_note = values[headerMap['其他：請說明']] || '';
    
    // 聯絡資訊
    data.phone = values[headerMap['聯絡電話']] || '';
    data.website = values[headerMap['餐廳網站或訂位連結']] || '';
    data.social_media = values[headerMap['餐廳 Instagram 或 Facebook']] || '';
    
    // 其他資訊
    data.notes = values[headerMap['備註']] || '';
    data.is_owner = values[headerMap['您是餐廳負責人嗎？']] || '';
    
    Logger.log('解析的資料:', data);
    
  } catch (error) {
    Logger.log('解析試算表資料時發生錯誤:', error);
  }
  
  return data;
}

// 簡化版：解析表單回應
function parseFormResponses(itemResponses) {
  var data = {};
  
  try {
    // 輔助函數：根據問題取得答案
    function getAnswerByQuestion(responses, questionText) {
      var item = responses.find(function(item) {
        return item.getItem().getTitle().includes(questionText);
      });
      return item ? item.getResponse() : '';
    }
    
    // 基本資料
    data.restaurant_name = getAnswerByQuestion(itemResponses, '餐廳名稱');
    data.contact_person = getAnswerByQuestion(itemResponses, '您的稱呼是？');
    data.email = getAnswerByQuestion(itemResponses, '電子郵件地址');
    data.restaurant_type = getAnswerByQuestion(itemResponses, '餐廳類型');
    data.district = getAnswerByQuestion(itemResponses, '行政區');
    data.address = getAnswerByQuestion(itemResponses, '地址');
    
    // 開瓶費相關 - 簡化處理
    var corkageOption = getAnswerByQuestion(itemResponses, '是否收開瓶費？');
    var corkageAmount = getAnswerByQuestion(itemResponses, '開瓶費金額');
    var corkageOther = getAnswerByQuestion(itemResponses, '其他：請說明');
    
    data.is_charged = corkageOption || '';
    data.corkage_fee = corkageAmount || '';
    
    // 如果開瓶費選項包含"酌收"且有金額，則使用金額
    if (corkageOption && corkageOption.includes('酌收') && corkageAmount) {
      data.corkage_fee = corkageAmount;
    }
    // 如果開瓶費選項包含"其他"且有說明，則使用說明
    else if (corkageOption && corkageOption.includes('其他') && corkageOther) {
      data.corkage_fee = corkageOther;
    }
    
    // 酒器設備
    data.equipment = getAnswerByQuestion(itemResponses, '是否提供酒器設備？');
    
    // 開酒服務
    data.open_bottle_service = getAnswerByQuestion(itemResponses, '是否提供開酒服務？');
    data.open_bottle_service_other_note = getAnswerByQuestion(itemResponses, '其他：請說明');
    
    // 聯絡資訊
    data.phone = getAnswerByQuestion(itemResponses, '聯絡電話');
    data.website = getAnswerByQuestion(itemResponses, '餐廳網站或訂位連結');
    data.social_media = getAnswerByQuestion(itemResponses, '餐廳 Instagram 或 Facebook');
    
    // 其他資訊
    data.notes = getAnswerByQuestion(itemResponses, '備註');
    data.is_owner = getAnswerByQuestion(itemResponses, '您是餐廳負責人嗎？');
    
    Logger.log('解析的資料:', data);
    
  } catch (error) {
    Logger.log('解析表單回應時發生錯誤:', error);
  }
  
  return data;
}

// 處理餐廳類型（多選）
function processRestaurantType(answer) {
  if (!answer) return '';
  
  // 如果是陣列（多選），轉換為字串
  if (Array.isArray(answer)) {
    return answer.join(', ');
  }
  
  return answer;
}

// 處理酒器設備（多選）
function processWineEquipment(answer) {
  if (!answer) return '';
  
  // 如果是陣列（多選），轉換為字串
  if (Array.isArray(answer)) {
    return answer.join(', ');
  }
  
  return answer;
}

// 處理電話號碼
function processPhone(phone) {
  if (!phone) return '';
  
  // 移除所有非數字字符
  var cleanPhone = phone.replace(/[^0-9]/g, '');
  
  // 根據長度格式化
  if (cleanPhone.length === 8) {
    // 市話：02-12345678
    return cleanPhone.substring(0, 2) + '-' + cleanPhone.substring(2);
  } else if (cleanPhone.length === 10 && cleanPhone.startsWith('09')) {
    // 手機：0932-123456
    return cleanPhone.substring(0, 4) + '-' + cleanPhone.substring(4);
  }
  
  return phone; // 如果無法格式化，返回原始值
}

// 處理網站連結
function processWebsite(website) {
  if (!website) return '';
  
  // 如果沒有 http 或 https，加上 https://
  if (!website.startsWith('http://') && !website.startsWith('https://')) {
    return 'https://' + website;
  }
  
  return website;
}

// 發送到 WordPress
function sendToWordPress(data) {
  // 新增除錯資訊
  Logger.log('準備發送到 WordPress 的資料:', data);
  Logger.log('資料類型:', typeof data);
  Logger.log('資料字串化:', JSON.stringify(data));
  
  var options = {
    'method': 'POST',
    'headers': {
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY
    },
    'payload': JSON.stringify(data)
  };
  
  Logger.log('API 請求選項:', options);
  
  try {
    var response = UrlFetchApp.fetch(WORDPRESS_API_URL, options);
    var responseCode = response.getResponseCode();
    var responseText = response.getContentText();
    
    Logger.log('WordPress API 回應碼:', responseCode);
    Logger.log('WordPress API 回應內容:', responseText);
    
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
    Logger.log('發送到 WordPress 時發生錯誤:', error);
    throw error;
  }
}

// 記錄提交到 Google Sheet
function logSubmission(responseId, data, result) {
  try {
    // 取得或建立日誌 Sheet
    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet() || createLogSpreadsheet();
    var sheet = spreadsheet.getSheetByName('WordPress API 日誌') || spreadsheet.insertSheet('WordPress API 日誌');
    
    // 準備記錄資料
    var logData = [
      new Date(), // 時間戳記
      responseId, // 回應 ID
      data.restaurant_name, // 餐廳名稱
      result.success ? '成功' : '失敗', // 狀態
      result.message || result.toString(), // 訊息
      JSON.stringify(data) // 完整資料
    ];
    
    // 加入記錄
    sheet.appendRow(logData);
    
    Logger.log('已記錄到 Google Sheet');
    
  } catch (error) {
    Logger.log('記錄到 Google Sheet 時發生錯誤:', error);
  }
}

// 建立日誌 Spreadsheet
function createLogSpreadsheet() {
  var spreadsheet = SpreadsheetApp.create('BYOB WordPress API 日誌');
  
  // 設定標題列
  var sheet = spreadsheet.getActiveSheet();
  sheet.getRange(1, 1, 1, 6).setValues([['時間', '回應ID', '餐廳名稱', '狀態', '訊息', '完整資料']]);
  
  return spreadsheet;
}

// 發送成功通知郵件
function sendNotificationEmail(data, result) {
  try {
    var subject = 'BYOB 新餐廳申請 - ' + data.restaurant_name;
    var body = 
      '<h2>新餐廳申請已收到</h2>' +
      '<p><strong>餐廳名稱：</strong>' + data.restaurant_name + '</p>' +
      '<p><strong>聯絡人：</strong>' + data.contact_person + '</p>' +
      '<p><strong>電子郵件：</strong>' + data.email + '</p>' +
      '<p><strong>電話：</strong>' + data.phone + '</p>' +
      '<p><strong>地址：</strong>' + data.address + '</p>' +
      '<p><strong>開瓶費政策：</strong>' + data.is_charged + '</p>' +
      '<p><strong>開瓶費詳情：</strong>' + (data.corkage_fee || '無') + '</p>' +
      '<p><strong>處理狀態：</strong>' + (result.success ? '成功' : '失敗') + '</p>' +
      '<p><strong>訊息：</strong>' + (result.message || result.toString()) + '</p>' +
      '<hr>' +
      '<p><small>此郵件由 BYOB 自動化系統發送</small></p>';
    
    GmailApp.sendEmail(NOTIFICATION_EMAIL, subject, '', {
      htmlBody: body
    });
    
    Logger.log('已發送通知郵件');
    
  } catch (error) {
    Logger.log('發送通知郵件時發生錯誤:', error);
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
    Logger.log('發送錯誤通知郵件時發生錯誤:', emailError);
  }
}

// 設定觸發器
function setupWordPressTriggers() {
  // 刪除現有觸發器
  var triggers = ScriptApp.getProjectTriggers();
  triggers.forEach(function(trigger) {
    if (trigger.getHandlerFunction() === 'onFormSubmit') {
      ScriptApp.deleteTrigger(trigger);
    }
  });
  
  // 建立新的表單提交觸發器
  ScriptApp.newTrigger('onFormSubmit')
    .forForm(FormApp.getActiveForm())
    .onFormSubmit()
    .create();
    
  Logger.log('WordPress API 觸發器設定完成');
}

// 測試 API 連接
function testWordPressApiConnection() {
  try {
    var testData = {
      restaurant_name: '測試餐廳',
      contact_person: '測試聯絡人',
      email: 'test@example.com',
      restaurant_type: '中式',
      district: '台北市',
      address: '測試地址',
      is_charged: '不收費',
      corkage_fee: '',
      phone: '02-12345678'
    };
    
    var result = sendToWordPress(testData);
    Logger.log('WordPress API 測試成功:', result);
    
    return result;
    
  } catch (error) {
    Logger.log('WordPress API 測試失敗:', error);
    throw error;
  }
}

// 清理舊日誌
function cleanupWordPressLogs() {
  try {
    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    if (!spreadsheet) return;
    
    var sheet = spreadsheet.getSheetByName('WordPress API 日誌');
    if (!sheet) return;
    
    var data = sheet.getDataRange().getValues();
    var cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - 30); // 保留 30 天
    
    // 找出需要刪除的列
    var rowsToDelete = [];
    for (var i = data.length - 1; i > 0; i--) { // 跳過標題列
      var rowDate = new Date(data[i][0]);
      if (rowDate < cutoffDate) {
        rowsToDelete.push(i + 1);
      }
    }
    
    // 刪除舊記錄
    rowsToDelete.forEach(function(rowIndex) {
      sheet.deleteRow(rowIndex);
    });
    
    Logger.log('已清理 ' + rowsToDelete.length + ' 筆舊記錄');
    
  } catch (error) {
    Logger.log('清理舊日誌時發生錯誤:', error);
  }
}

// 新增：測試欄位對應功能
function testFieldMapping() {
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet();
    var formSheet = sheet.getSheets()[0];
    
    // 取得標題列
    var headers = formSheet.getRange(1, 1, 1, formSheet.getLastColumn()).getValues()[0];
    
    Logger.log('原始標題列:', headers);
    
    // 測試欄位對應
    var headerMap = {};
    headers.forEach(function(header, index) {
      if (header) {
        var processedHeader = toHalfWidth(String(header).trim());
        headerMap[processedHeader] = index;
        Logger.log('欄位對應: "' + header + '" -> "' + processedHeader + '" -> 索引 ' + index);
      }
    });
    
    Logger.log('最終欄位對應:', headerMap);
    
    // 檢查關鍵欄位是否存在
    var keyFields = [
      '是否收開瓶費？',
      '開瓶費金額',
      '其他：請說明',
      '是否提供酒器設備？',
      '是否提供開酒服務？',
      '您的稱呼是？'
    ];
    
    keyFields.forEach(function(field) {
      var processedField = toHalfWidth(field);
      if (headerMap[processedField] !== undefined) {
        Logger.log('✅ 找到欄位: "' + field + '" -> "' + processedField + '" -> 索引 ' + headerMap[processedField]);
      } else {
        Logger.log('❌ 找不到欄位: "' + field + '" -> "' + processedField + '"');
      }
    });
    
    return headerMap;
    
  } catch (error) {
    Logger.log('測試欄位對應時發生錯誤:', error);
    throw error;
  }
}

// 新增：詳細除錯函數
function debugFieldMapping() {
  try {
    Logger.log('=== 開始詳細除錯欄位對應 ===');
    
    var sheet = SpreadsheetApp.getActiveSpreadsheet();
    var formSheet = sheet.getSheets()[0];
    
    // 取得標題列
    var headers = formSheet.getRange(1, 1, 1, formSheet.getLastColumn()).getValues()[0];
    Logger.log('原始標題列:', headers);
    
    // 測試欄位對應
    var headerMap = {};
    headers.forEach(function(header, index) {
      if (header) {
        var processedHeader = toHalfWidth(String(header).trim());
        headerMap[processedHeader] = index;
        Logger.log('欄位對應: "' + header + '" -> "' + processedHeader + '" -> 索引 ' + index);
      }
    });
    
    Logger.log('最終欄位對應:', headerMap);
    
    // 檢查關鍵欄位是否存在
    var keyFields = [
      '是否收開瓶費？',
      '開瓶費金額',
      '其他：請說明',
      '是否提供酒器設備？',
      '是否提供開酒服務？',
      '您的稱呼是？',
      '電子郵件地址', // 修正：使用正確的欄位名稱
      '餐廳類型',
      '行政區',
      '地址',
      '聯絡電話',
      '餐廳網站或訂位連結',
      '餐廳 Instagram 或 Facebook',
      '備註',
      '您是餐廳負責人嗎？'
    ];
    
    Logger.log('=== 關鍵欄位檢查結果 ===');
    keyFields.forEach(function(field) {
      var processedField = toHalfWidth(field);
      if (headerMap[processedField] !== undefined) {
        Logger.log('✅ 找到欄位: "' + field + '" -> "' + processedField + '" -> 索引 ' + headerMap[processedField]);
      } else {
        Logger.log('❌ 找不到欄位: "' + field + '" -> "' + processedField + '"');
      }
    });
    
    // 測試最新資料解析
    if (formSheet.getLastRow() > 1) {
      Logger.log('=== 測試最新資料解析 ===');
      var lastRow = formSheet.getLastRow();
      var values = formSheet.getRange(lastRow, 1, 1, formSheet.getLastColumn()).getValues()[0];
      Logger.log('最新資料行:', values);
      
      // 測試資料提取
      var testData = {
        restaurant_name: values[headerMap['餐廳名稱']] || '',
        contact_person: values[headerMap['您的稱呼是？']] || '',
        email: values[headerMap['電子郵件地址']] || '', // 修正：使用正確的欄位名稱
        restaurant_type: values[headerMap['餐廳類型']] || '',
        district: values[headerMap['行政區']] || '',
        address: values[headerMap['地址']] || '',
        is_charged: values[headerMap['是否收開瓶費？']] || '',
        corkage_fee: values[headerMap['開瓶費金額']] || '',
        corkage_other: values[headerMap['其他：請說明']] || '',
        equipment: values[headerMap['是否提供酒器設備？']] || '',
        open_bottle_service: values[headerMap['是否提供開酒服務？']] || '',
        phone: values[headerMap['聯絡電話']] || '',
        website: values[headerMap['餐廳網站或訂位連結']] || '',
        social_media: values[headerMap['餐廳 Instagram 或 Facebook']] || '',
        notes: values[headerMap['備註']] || '',
        is_owner: values[headerMap['您是餐廳負責人嗎？']] || ''
      };
      
      Logger.log('解析的測試資料:', testData);
    }
    
    return headerMap;
    
  } catch (error) {
    Logger.log('詳細除錯時發生錯誤:', error);
    throw error;
  }
}

// 新增：測試 WordPress API 連接
function testWordPressConnection() {
  try {
    Logger.log('=== 測試 WordPress API 連接 ===');
    
    var testData = {
      restaurant_name: '測試餐廳 - ' + new Date().toISOString(),
      contact_person: '測試聯絡人',
      email: 'test@example.com',
      restaurant_type: '中式',
      district: '台北市',
      address: '測試地址',
      is_charged: '不收費',
      corkage_fee: '',
      equipment: '酒杯, 開瓶器',
      open_bottle_service: '有',
      open_bottle_service_other_note: '',
      phone: '02-12345678',
      website: 'https://test.com',
      social_media: 'https://instagram.com/test',
      notes: '測試備註',
      is_owner: '是'
    };
    
    Logger.log('測試資料:', JSON.stringify(testData, null, 2));
    
    var result = sendToWordPress(testData);
    Logger.log('WordPress API 測試結果:', JSON.stringify(result, null, 2));
    
    if (result.success) {
      Logger.log('✅ WordPress API 測試成功！');
      Logger.log('建立的文章 ID:', result.response.post_id);
    } else {
      Logger.log('❌ WordPress API 測試失敗！');
      Logger.log('錯誤訊息:', result.message);
    }
    
    return result;
    
  } catch (error) {
    Logger.log('❌ WordPress API 測試失敗:', error.toString());
    Logger.log('錯誤詳情:', error.stack);
    throw error;
  }
}

// 新增：檢查觸發器狀態
function checkTriggers() {
  try {
    Logger.log('=== 檢查觸發器狀態 ===');
    
    var triggers = ScriptApp.getProjectTriggers();
    Logger.log('總觸發器數量:', triggers.length);
    
    triggers.forEach(function(trigger, index) {
      Logger.log('觸發器 ' + (index + 1) + ':', {
        handlerFunction: trigger.getHandlerFunction(),
        eventType: trigger.getEventType(),
        uniqueId: trigger.getUniqueId()
      });
    });
    
    return triggers;
    
  } catch (error) {
    Logger.log('檢查觸發器時發生錯誤:', error);
    throw error;
  }
}

// 新增：測試最新資料處理
function testLatestDataProcessing() {
  try {
    Logger.log('=== 測試最新資料處理 ===');
    
    var sheet = SpreadsheetApp.getActiveSpreadsheet();
    var formSheet = sheet.getSheets()[0];
    
    // 取得標題列
    var headers = formSheet.getRange(1, 1, 1, formSheet.getLastColumn()).getValues()[0];
    
    // 取得最新資料行
    var lastRow = formSheet.getLastRow();
    var values = formSheet.getRange(lastRow, 1, 1, formSheet.getLastColumn()).getValues()[0];
    
    Logger.log('最新資料行號:', lastRow);
    Logger.log('標題列:', headers);
    Logger.log('最新資料:', values);
    
    // 建立標題對應
    var headerMap = {};
    headers.forEach(function(header, index) {
      if (header) {
        var processedHeader = toHalfWidth(String(header).trim());
        headerMap[processedHeader] = index;
      }
    });
    
    // 測試資料提取
    var testData = {
      restaurant_name: values[headerMap['餐廳名稱']] || '',
      contact_person: values[headerMap['您的稱呼是？']] || '',
      email: values[headerMap['電子郵件地址']] || '',
      restaurant_type: values[headerMap['餐廳類型']] || '',
      district: values[headerMap['行政區']] || '',
      address: values[headerMap['地址']] || '',
      is_charged: values[headerMap['是否收開瓶費？']] || '',
      corkage_fee: values[headerMap['開瓶費金額']] || '',
      corkage_other: values[headerMap['其他：請說明']] || '',
      equipment: values[headerMap['是否提供酒器設備？']] || '',
      open_bottle_service: values[headerMap['是否提供開酒服務？']] || '',
      phone: values[headerMap['聯絡電話']] || '',
      website: values[headerMap['餐廳網站或訂位連結']] || '',
      social_media: values[headerMap['餐廳 Instagram 或 Facebook']] || '',
      notes: values[headerMap['備註']] || '',
      is_owner: values[headerMap['您是餐廳負責人嗎？']] || ''
    };
    
    Logger.log('處理後的資料:', JSON.stringify(testData, null, 2));
    
    // 測試發送到 WordPress
    Logger.log('開始發送到 WordPress...');
    var result = sendToWordPress(testData);
    Logger.log('WordPress 回應:', JSON.stringify(result, null, 2));
    
    return result;
    
  } catch (error) {
    Logger.log('測試最新資料處理時發生錯誤:', error);
    throw error;
  }
}

// 新增：簡單的 WordPress API 測試
function simpleWordPressTest() {
  try {
    Logger.log('=== 簡單 WordPress API 測試 ===');
    
    var testData = {
      restaurant_name: '測試餐廳 - ' + new Date().toISOString(),
      contact_person: '測試聯絡人',
      email: 'test@example.com',
      restaurant_type: '中式',
      district: '台北市',
      address: '測試地址',
      is_charged: '不收費',
      corkage_fee: '',
      equipment: '酒杯',
      open_bottle_service: '有',
      open_bottle_service_other_note: '',
      phone: '02-12345678',
      website: 'https://test.com',
      social_media: 'https://instagram.com/test',
      notes: '測試備註',
      is_owner: '是'
    };
    
    Logger.log('測試資料:', JSON.stringify(testData, null, 2));
    
    var result = sendToWordPress(testData);
    Logger.log('WordPress API 測試結果:', JSON.stringify(result, null, 2));
    
    if (result.success) {
      Logger.log('✅ WordPress API 測試成功！');
      Logger.log('建立的文章 ID:', result.response.post_id);
      Logger.log('文章網址:', result.response.post_url);
    } else {
      Logger.log('❌ WordPress API 測試失敗！');
      Logger.log('錯誤訊息:', result.message);
    }
    
    return result;
    
  } catch (error) {
    Logger.log('❌ WordPress API 測試失敗:', error.toString());
    Logger.log('錯誤詳情:', error.stack);
    throw error;
  }
}

// 新增：測試修正後的欄位對應功能
function testFixedFieldMapping() {
  try {
    Logger.log('=== 測試修正後的欄位對應功能 ===');
    
    var sheet = SpreadsheetApp.getActiveSpreadsheet();
    var formSheet = sheet.getSheets()[0];
    
    // 取得標題列
    var headers = formSheet.getRange(1, 1, 1, formSheet.getLastColumn()).getValues()[0];
    Logger.log('原始標題列:', headers);
    
    // 測試欄位對應
    var headerMap = {};
    headers.forEach(function(header, index) {
      if (header) {
        var processedHeader = toHalfWidth(String(header).trim());
        headerMap[processedHeader] = index;
        Logger.log('欄位對應: "' + header + '" -> "' + processedHeader + '" -> 索引 ' + index);
      }
    });
    
    Logger.log('最終欄位對應:', headerMap);
    
    // 檢查關鍵欄位是否存在
    var keyFields = [
      '是否收開瓶費？',
      '開瓶費金額',
      '其他：請說明',
      '是否提供酒器設備？',
      '是否提供開酒服務？',
      '您的稱呼是？',
      '電子郵件地址',
      '餐廳類型',
      '行政區',
      '地址',
      '聯絡電話',
      '餐廳網站或訂位連結',
      '餐廳 Instagram 或 Facebook',
      '備註',
      '您是餐廳負責人嗎？'
    ];
    
    Logger.log('=== 關鍵欄位檢查結果 ===');
    var missingFields = [];
    keyFields.forEach(function(field) {
      var processedField = toHalfWidth(field);
      if (headerMap[processedField] !== undefined) {
        Logger.log('✅ 找到欄位: "' + field + '" -> "' + processedField + '" -> 索引 ' + headerMap[processedField]);
      } else {
        Logger.log('❌ 找不到欄位: "' + field + '" -> "' + processedField + '"');
        missingFields.push(field);
      }
    });
    
    if (missingFields.length > 0) {
      Logger.log('❌ 缺少的欄位:', missingFields);
      return false;
    } else {
      Logger.log('✅ 所有關鍵欄位都找到了！');
      return true;
    }
    
  } catch (error) {
    Logger.log('測試欄位對應時發生錯誤:', error);
    throw error;
  }
}

// 新增：完整測試函數
function runCompleteTest() {
  try {
    Logger.log('=== 開始完整測試 ===');
    
    // 1. 檢查試算表實際資料
    Logger.log('1. 檢查試算表實際資料...');
    var spreadsheetCheck = checkSpreadsheetData();
    if (!spreadsheetCheck.success) {
      Logger.log('❌ 試算表資料檢查失敗');
      return false;
    }
    
    // 2. 測試欄位對應
    Logger.log('2. 測試欄位對應...');
    var fieldMappingOk = testFixedFieldMapping();
    
    if (!fieldMappingOk) {
      Logger.log('❌ 欄位對應測試失敗');
      return false;
    }
    
    // 3. 測試最新資料處理
    Logger.log('3. 測試最新資料處理...');
    var testData = parseLatestSpreadsheetData();
    Logger.log('測試資料:', JSON.stringify(testData, null, 2));
    
    // 4. 檢查關鍵欄位是否有資料
    var requiredFields = [
      'restaurant_name',
      'contact_person',
      'email',
      'restaurant_type',
      'district',
      'address',
      'is_charged',
      'corkage_fee',
      'equipment',
      'open_bottle_service',
      'phone',
      'website',
      'social_media',
      'notes',
      'is_owner'
    ];
    
    Logger.log('4. 檢查關鍵欄位資料...');
    var missingData = [];
    requiredFields.forEach(function(field) {
      if (!testData[field] || testData[field] === '') {
        missingData.push(field);
        Logger.log('❌ 缺少資料: ' + field);
      } else {
        Logger.log('✅ 有資料: ' + field + ' = ' + testData[field]);
      }
    });
    
    if (missingData.length > 0) {
      Logger.log('❌ 缺少資料的欄位:', missingData);
      Logger.log('💡 建議：請檢查 Google 試算表最新一行的資料是否完整填寫');
    } else {
      Logger.log('✅ 所有關鍵欄位都有資料！');
    }
    
    // 5. 測試 WordPress API 連接
    Logger.log('5. 測試 WordPress API 連接...');
    var result = sendToWordPress(testData);
    Logger.log('WordPress API 測試結果:', JSON.stringify(result, null, 2));
    
    if (result.success) {
      Logger.log('✅ 完整測試成功！');
      Logger.log('建立的文章 ID:', result.response.post_id);
      Logger.log('文章網址:', result.response.post_url);
      return true;
    } else {
      Logger.log('❌ WordPress API 測試失敗');
      Logger.log('錯誤訊息:', result.message);
      return false;
    }
    
  } catch (error) {
    Logger.log('❌ 完整測試失敗:', error.toString());
    Logger.log('錯誤詳情:', error.stack);
    return false;
  }
}

// 新增：檢查試算表實際資料的測試函數
function checkSpreadsheetData() {
  try {
    Logger.log('=== 檢查試算表實際資料 ===');
    
    // 取得試算表資料
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
    var lastRow = sheet.getLastRow();
    var lastColumn = sheet.getLastColumn();
    
    Logger.log('試算表資訊:', {
      lastRow: lastRow,
      lastColumn: lastColumn
    });
    
    // 取得標題列
    var headers = sheet.getRange(1, 1, 1, lastColumn).getValues()[0];
    Logger.log('原始標題列:', headers);
    
    // 取得最新資料行
    var values = sheet.getRange(lastRow, 1, 1, lastColumn).getValues()[0];
    Logger.log('原始最新資料:', values);
    
    // 建立標題對應
    var headerMap = {};
    headers.forEach(function(header, index) {
      if (header) {
        var processedHeader = toHalfWidth(String(header).trim());
        headerMap[processedHeader] = index;
      }
    });
    
    // 檢查關鍵欄位
    var criticalFields = [
      '您的稱呼是？',
      '是否收開瓶費？',
      '開瓶費金額',
      '其他：請說明',
      '是否提供酒器設備？',
      '是否提供開酒服務？',
      '您是餐廳負責人嗎？'
    ];
    
    Logger.log('=== 關鍵欄位檢查 ===');
    criticalFields.forEach(function(field) {
      var index = headerMap[field];
      if (index !== undefined) {
        var value = values[index];
        Logger.log('✅ ' + field + ': 索引 ' + index + ', 值 "' + value + '"');
      } else {
        Logger.log('❌ ' + field + ': 找不到欄位索引');
      }
    });
    
    // 檢查所有欄位的對應狀況
    Logger.log('=== 所有欄位對應狀況 ===');
    Object.keys(headerMap).forEach(function(processedHeader) {
      var index = headerMap[processedHeader];
      var value = values[index];
      Logger.log(processedHeader + ': 索引 ' + index + ', 值 "' + value + '"');
    });
    
    return {
      success: true,
      lastRow: lastRow,
      lastColumn: lastColumn,
      headers: headers,
      values: values,
      headerMap: headerMap
    };
    
  } catch (error) {
    Logger.log('❌ 檢查試算表資料時發生錯誤:', error.toString());
    return {
      success: false,
      error: error.toString()
    };
  }
}