# 台北 BYOB 餐廳資料庫專案說明（2025-08-12 更新）

本專案致力於打造一個讓民眾能快速查詢「台北市可自帶酒水（BYOB）」餐廳的資訊平台，並協助餐廳主動登錄資料。專案採用 WordPress 作為後台資料管理與資料庫平台，並預計開發 React App 作為前端介面，供行動裝置使用者快速查詢與篩選使用。

---

## 📌 最新進度概要（2025-08-12）

### ✅ 今日完成：餐廳業者註冊流程重大改進與問題解決

* **重複email發送問題解決**：統一email發送機制，避免餐廳業者收到兩封不同內容的email
* **註冊頁面404錯誤修復**：解決點擊"立即註冊會員"按鈕後出現404頁面的問題
* **註冊表單顯示問題修復**：修復邀請碼驗證邏輯，解決"邀請碼不能為空"錯誤
* **註冊頁面設計與功能大幅改進**：添加密碼顯示/隱藏、即時密碼強度檢查、密碼匹配驗證等新功能
* **註冊頁面視覺效果優化**：改善表單視覺層次、間距、字體、顏色等設計元素

### ✅ 昨日完成：使用者體驗優化與資料轉換修正

* **餐廳列表頁面標題間距優化**：解決標題與 header 距離過近問題，添加適當間距和分隔線
* **單一餐廳頁面連結格式優化**：將「官方網站/社群連結」改為「官網連結 | 社群連結」，提升用戶體驗
* **Google 表單資料轉換格式修正**：將單一欄位拆分為兩個獨立欄位，避免資料遺失

### ✅ 一鍵註冊邀請系統建置完成

* 設計並實作完整的邀請機制架構
* 建立文章發布自動觸發邀請功能
* 實作安全的 token 驗證機制（32字符隨機字串，7天有效期）
* 建立邀請資料庫表格（`wp_byob_invitations`）
* 完成自動化註冊流程（攔截、驗證、角色設定、關聯）
* 建立精美的 HTML 邀請郵件模板
* 實作歡迎郵件系統

### ✅ 註冊流程自動化優化

* 實作邀請連結攔截機制（`byob_handle_invitation_registration`）
* 建立註冊頁面歡迎訊息顯示
* 完成自動餐廳業者角色設定（`restaurant_owner`）
* 實作餐廳文章自動關聯功能
* 建立 session 處理機制確保資料傳遞
* 完成註冊後自動發送歡迎郵件

### ✅ 後台診斷和監控系統

* 建立 WordPress 後台診斷頁面（工具 → BYOB 診斷）
* 實作系統狀態檢查功能
* 建立邀請功能測試機制（模擬郵件發送）
* 完成檔案路徑和函數存在檢查
* 建立資料庫表格狀態監控
* 實作邀請統計資訊功能

### ✅ 技術問題解決和系統穩定化

* 解決伺服器 403 Forbidden 錯誤問題
* 修復檔案路徑問題（從 `get_template_directory()` 改為 `__DIR__`）
* 優化 session 處理邏輯（使用 `session_status()` 檢查）
* 改善函數載入和依賴管理
* 建立錯誤處理和日誌記錄機制
* 確保系統在伺服器環境中穩定運行

### ✅ 明日工作任務規劃

* 完成餐廳業者註冊流程的完整測試，特別關注新改進的註冊表單功能
* 測試密碼顯示/隱藏、即時密碼強度檢查、密碼匹配驗證等新功能
* 驗證註冊流程從邀請到成功創建帳號的完整流程
* 測試會員後台功能和系統區分機制

---

## 🎯 今日工作成果詳述（2025-08-12）

### 1. 🔧 重複email發送問題解決

**問題描述：**
- 餐廳業者註冊時會收到兩封不同內容的email
- 一封是文章發布時的email，另一封是審核通過時的email
- 造成業者困惑，影響用戶體驗

**解決方案：**
- 統一email發送機制，讓所有狀態變更都調用同一個email函數
- 修改 `functions.php` 中的 `byob_auto_send_invitation_on_publish` 函數
- 完全移除舊的 `byob_send_restaurant_invitation` 和 `byob_send_invitation_email` 函數
- 將 `byob_send_approval_notification` 函數移動到 `functions.php` 統一管理

**技術實現：**
- 使用 `transition_post_status` hook 監聽文章狀態變更
- 統一調用 `byob_send_approval_notification` 函數
- 動態插入餐廳名稱到email主旨，提升個人化體驗

**修改檔案：** `wordpress/functions.php`
**修改位置：**
- 第 849-890 行：修改 `byob_auto_send_invitation_on_publish` 函數
- 第 894-925 行：移除 `byob_send_restaurant_invitation` 函數
- 第 926-1027 行：移除 `byob_send_invitation_email` 函數
- 第 947-1059 行：移動並添加 `byob_send_approval_notification` 函數

### 2. 🚫 註冊頁面404錯誤修復

**問題描述：**
- 點擊"立即註冊會員"按鈕後出現404頁面
- 註冊頁面URL `/register/restaurant?token=xxx` 無法正常載入
- 影響用戶註冊流程的順暢性

**問題分析：**
- WordPress rewrite rules沒有正確註冊
- 自定義query variable `byob_restaurant_registration` 沒有註冊
- 函數載入順序問題導致rewrite rules在錯誤時機註冊

**解決方案：**
- 在 `functions.php` 中添加 `byob_maybe_flush_rewrite_rules` 函數
- 確保rewrite rules在主題/外掛啟用時自動刷新
- 在 `restaurant-member-functions.php` 中添加 `byob_add_query_vars` 函數
- 註冊自定義query variable `byob_restaurant_registration`
- 調整函數載入順序，確保rewrite rules在正確時機註冊

**新增函數：**
```php
function byob_maybe_flush_rewrite_rules() {
    $current_version = get_option('byob_rewrite_rules_version', '0');
    if ($current_version !== '1.0') {
        flush_rewrite_rules();
        update_option('byob_rewrite_rules_version', '1.0');
    }
}

function byob_add_query_vars($vars) {
    $vars[] = 'byob_restaurant_registration';
    return $vars;
}
```

### 3. 🐛 註冊表單顯示問題修復

**問題描述：**
- 註冊頁面載入後顯示"邀請碼不能為空"錯誤
- 沒有顯示任何輸入欄位
- 用戶無法進行註冊操作

**問題分析：**
- 邀請碼驗證函數 `byob_verify_invitation_code` 使用錯誤的資料庫查詢方式
- 使用 `LIKE` 查詢序列化的WordPress meta值
- 導致無法正確匹配邀請碼

**解決方案：**
- 修改 `byob_verify_invitation_code` 函數的查詢邏輯
- 改為檢索所有 `_byob_invitation_code` meta值，然後在PHP中進行匹配
- 使用 `maybe_unserialize` 函數正確處理序列化資料
- 添加大量 `error_log` 語句進行除錯
- 創建 `byob_verify_invitation_code_direct` 函數作為直接調用版本

**修改檔案：** `wordpress/restaurant-member-functions.php`
**修改位置：**
- 第 124-159 行：修改 `byob_verify_invitation_code` 函數
- 第 118-160 行：新增 `byob_verify_invitation_code_direct` 函數

**程式碼修改：**
```php
// 修改前：使用LIKE查詢序列化資料
$query = $wpdb->prepare(
    "SELECT post_id FROM {$wpdb->postmeta} 
     WHERE meta_key = '_byob_invitation_code' 
     AND meta_value LIKE %s",
    '%' . $wpdb->esc_like($invitation_code) . '%'
);

// 修改後：檢索所有meta值後在PHP中匹配
$query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
          WHERE meta_key = '_byob_invitation_code'";
$results = $wpdb->get_results($query);

foreach ($results as $result) {
    $stored_code = maybe_unserialize($result->meta_value);
    if ($stored_code === $invitation_code) {
        return $result->post_id;
    }
}
```

### 4. 🎨 註冊頁面設計與功能大幅改進

**設計改進：**
- **視覺層次**：調整容器寬度為700px，padding為50px，圓角為15px
- **標題樣式**：將"BYOB 餐廳業者註冊"標題置中，使用微軟正黑體，32px字體，700字重
- **密碼規則**：將"使用者名稱規則"改為"密碼規則"，更新內容和樣式
- **表單間距**：調整各區塊的padding、border-radius和box-shadow

**新功能添加：**
- **密碼顯示/隱藏**：在密碼欄位右側添加眼睛符號（👁️），點擊可切換密碼可見性
- **即時密碼強度檢查**：實時顯示密碼強度條和文字說明
- **密碼匹配驗證**：即時檢查密碼與確認密碼是否匹配

**修改檔案：** `wordpress/restaurant-member-functions.php`
**修改位置：**
- 第 740-800 行：更新HTML結構和CSS樣式
- 第 802-1000 行：添加JavaScript功能

**新增JavaScript功能：**
```javascript
// 密碼顯示/隱藏切換
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        button.textContent = '🙈';
    } else {
        field.type = 'password';
        button.textContent = '👁️';
    }
}

// 密碼強度檢查
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    const colors = ['#ff4444', '#ffaa00', '#ffff00', '#88ff00', '#00ff00'];
    const texts = ['很弱', '弱', '中等', '強', '很強'];
    
    strengthBar.style.width = (strength * 20) + '%';
    strengthBar.style.backgroundColor = colors[strength - 1];
    strengthText.textContent = texts[strength - 1];
    strengthText.style.color = colors[strength - 1];
}

// 密碼匹配檢查
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchText = document.getElementById('password-match-text');
    
    if (confirmPassword === '') {
        matchText.textContent = '';
        return;
    }
    
    if (password === confirmPassword) {
        matchText.textContent = '✅ 密碼匹配';
        matchText.style.color = '#00aa00';
    } else {
        matchText.textContent = '❌ 密碼不匹配';
        matchText.style.color = '#ff4444';
    }
}
```

### 5. 🧪 邀請連結註冊流程實際測試

**修改檔案：** `wordpress/restaurant-member-functions.php`

**優化內容：**
- 在註冊表單下方添加使用者名稱規則說明
- 設定 Email 長度限制為 3-50 字元（原本是 3-60 字元）
- 添加 Email 長度驗證機制
- 確認系統使用 Email 作為使用者名稱

**程式碼修改：**
```php
// 檢查 email 長度（作為使用者名稱）
if (strlen($email) < 3 || strlen($email) > 50) {
    return new WP_Error('invalid_email_length', 'Email 長度必須在 3-50 字元之間', array('status' => 400));
}
```

**新增顯示規則：**
- 長度：3-50 字元
- 允許：字母、數字、連字號(-)、底線(_)、點(.)
- 不允許：空格、特殊符號、中文字元

### 4. 📧 WP Mail SMTP 設定問題解決

**問題描述：**
```
{
    "error": "invalid_grant",
    "error_description": "Token has been expired or revoked."
}
```

**解決方案：**
1. 點擊 "Remove OAuth Connection" 按鈕
2. 重新授權 `byobmap.tw@gmail.com` 帳號（不是管理員帳號）
3. 完成 OAuth 認證流程

**技術要點：**
- 需要授權的是寄件者帳號 `byobmap.tw@gmail.com`
- 管理員帳號 `slow3605@gmail.com` 只是用來登入 WordPress
- OAuth Token 需要定期重新授權

---

## 🎯 昨日工作成果詳述（2025-08-06）

### 1. 🎨 餐廳列表頁面標題間距優化

**修改檔案：** `wordpress/archive-restaurant.php`

**問題描述：**
- 標題「所有餐廳列表」與 header 之間的距離過近
- 視覺上不夠美觀，影響使用者體驗
- 標題對齊方式需要調整

**解決方案：**
- 添加 CSS 樣式，設定適當的上下邊距（2rem）
- 添加底部邊框線，讓標題區域更加突出
- 確保標題與餐廳卡片的左側邊緣對齊
- 實現響應式設計，在桌面和手機上都能正確對齊

**技術亮點：**
- 使用 `.page-header` 和 `.page-title` 類別結構化標題區域
- 添加 `border-bottom: 2px solid #e0e0e0` 分隔線樣式
- 設定 `margin: 2rem 0` 和 `padding: 1rem 0` 適當間距

### 2. 🔗 單一餐廳頁面連結格式優化

**修改檔案：** `wordpress/single_restaurant.php`

**問題描述：**
- 原本顯示「官方網站/社群連結」的格式不夠清楚
- 用戶無法明確知道哪些是可點擊的連結
- 排版不夠整齊美觀

**解決方案：**
- 將連結標籤改為「官網連結 | 社群連結」
- 使用分隔符號 `|` 讓兩個連結並排顯示
- 改善用戶體驗，讓連結更清楚易懂

**技術亮點：**
- 明確標示可點擊的連結
- 符合現代網頁設計慣例
- 提升視覺平衡和整齊度

### 3. 📊 Google 表單資料轉換格式修正

**修改檔案：** `wordpress/Apps script - 純淨版.js`

**問題描述：**
- 原本 Google 表單有兩個獨立欄位：「餐廳網站或訂位連結」和「餐廳 Instagram 或 Facebook」
- 但在手動資料轉換後，只會出現一個「官方網站/ 社群連結」的欄位
- 如果業者兩者都填的話，只會顯示第一個，造成資料遺失

**解決方案：**
- 修改輸出表頭，將單一欄位拆分為兩個獨立欄位
- 修改資料處理邏輯，分別處理兩個連結欄位
- 修改資料輸出，確保兩個欄位都能正確顯示

**技術亮點：**
- 在 `convertFormToBYOBDatabase` 函數中進行三處關鍵修改
- 確保資料完整性，避免在轉換過程中遺失資訊
- 資料結構更清晰，便於後續處理和分析

---

## 🎉 重大突破：一鍵註冊邀請系統完成

### 系統功能特色
* 🚀 **完全自動化**：餐廳文章發布即觸發邀請流程
* 🔒 **安全可靠**：32字符隨機 token，7天有效期限制
* 💌 **精美郵件**：品牌色彩設計，專業的 HTML 郵件模板
* 👤 **無縫註冊**：自動角色設定，餐廳文章關聯
* 📊 **完整監控**：後台診斷工具，即時系統狀態檢查

### 核心功能流程
```
餐廳文章發布 → 自動觸發邀請 → 發送精美郵件 → 業者點擊註冊 → 
自動攔截驗證 → 顯示歡迎訊息 → 完成註冊 → 自動設定角色 → 
關聯餐廳文章 → 發送歡迎郵件
```

### 技術架構亮點
* **資料庫設計**：專用邀請資料表，完整的狀態追蹤
* **安全機制**：token 驗證、過期檢查、重複使用防護
* **使用者體驗**：個性化歡迎訊息、自動流程引導
* **監控機制**：後台診斷頁面、邀請統計、模擬測試

### 已解決的技術挑戰

| 挑戰類型 | 問題描述 | 解決方案 | 狀態 |
|----------|----------|----------|------|
| 伺服器錯誤 | 網站出現嚴重錯誤 | 修復檔案路徑和 session 處理 | ✅ 已解決 |
| 檔案載入 | `get_template_directory()` 路徑問題 | 改用 `__DIR__` 和檔案存在檢查 | ✅ 已解決 |
| Session 處理 | session 啟動和狀態檢查 | 使用 `session_status()` 標準檢查 | ✅ 已解決 |
| 循環依賴 | 函數載入順序問題 | 增加 `function_exists()` 安全檢查 | ✅ 已解決 |

### 系統測試完成狀態

| 測試項目 | 狀態 | 備註 |
|----------|------|------|
| 餐廳文章發布觸發邀請 | ✅ 通過 | 自動發送邀請郵件 |
| 邀請 Token 生成和驗證 | ✅ 通過 | 32字符隨機字串，7天有效 |
| 邀請郵件發送 | ✅ 通過 | HTML 格式，品牌設計 |
| 註冊頁面攔截和歡迎 | ✅ 通過 | 顯示個性化歡迎訊息 |
| 自動角色設定 | ✅ 通過 | 設定為 `restaurant_owner` |
| 餐廳文章關聯 | ✅ 通過 | 自動建立 meta 關聯 |
| 歡迎郵件發送 | ✅ 通過 | 註冊成功後自動發送 |
| 後台診斷工具 | ✅ 通過 | 完整系統狀態檢查 |
| 伺服器穩定性 | ✅ 通過 | 修復路徑和 session 問題 |

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
* **✨ 重大更新：一鍵註冊邀請系統完整建置完成**
* **✨ 重大更新：後台診斷和監控系統建立完成**
* **✨ 重大更新：系統穩定性大幅提升**
* **✨ 重大更新：註冊流程全自動化完成**

---

## 🗓 明日預定任務（2025-08-10）

### 🔴 最高優先級：前端使用者體驗優化

**1. 餐廳單頁功能改善**（預計 2 小時）
* [ ] 新增「返回上一頁」按鈕
* [ ] 修復官網連結功能  
* [ ] 修復社群連結功能
* [ ] 優化連結開啟方式（新分頁）

**2. 邀請郵件優化**（預計 1 小時）
* [ ] 修改邀請郵件模板和內容
* [ ] 優化郵件發送者資訊
* [ ] 改善郵件視覺設計

**3. 註冊連結修復**（預計 1 小時）
* [ ] 診斷「立即註冊會員」連結失效問題
* [ ] 檢查 token 驗證邏輯
* [ ] 測試完整註冊流程

### 🟡 中等優先級：資料處理優化

**4. Google 表單資料分欄處理**（預計 1.5 小時）
* [ ] 識別官網及社群連結欄位
* [ ] 在轉換後資料庫格式中拆分為兩欄
* [ ] 更新欄位映射邏輯

### 技術實作重點
* 修改檔案：`single-restaurant.php`、`functions.php`、`Apps script - 純淨版.js`
* 使用後台診斷工具進行測試和驗證
* 確保所有改動都有適當的錯誤處理和日誌記錄

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

### 使用者體驗驗證
- [ ] 餐廳單頁有「返回上一頁」按鈕且功能正常
- [ ] 官網和社群連結能正確顯示並在新分頁開啟
- [ ] 邀請郵件內容和視覺設計符合品牌要求
- [ ] 「立即註冊會員」連結能正常運作
- [ ] 註冊流程能正確設定餐廳業者角色

### 資料處理驗證
- [ ] Google 表單官網和社群連結能正確分欄處理
- [ ] 所有功能經過完整測試驗證
- [ ] 後台診斷工具顯示系統狀態正常
- [ ] 錯誤處理和日誌記錄完善

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

### 一鍵註冊邀請系統架構

**核心檔案和功能：**

**`functions.php` 新增功能：**
```php
// 文章發布自動觸發邀請
add_action('transition_post_status', 'byob_auto_send_invitation_on_publish', 10, 3);

// 邀請生成和發送
function byob_send_restaurant_invitation($restaurant_id) {
    // 生成 32字符隨機 token
    $token = wp_generate_password(32, false, false);
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    // ... 邀請邏輯
}

// 精美郵件模板
function byob_send_invitation_email($restaurant, $invitation_data) {
    // HTML 郵件模板，品牌色彩設計
    // 個性化內容，一鍵註冊按鈕
}

// 後台診斷工具
function byob_diagnostic_page() {
    // 系統狀態檢查、邀請測試、統計資訊
}
```

**`invitation-handler.php` 核心功能：**
```php
// Token 驗證
function byob_verify_invitation_token($token) {
    // 檢查 token 有效性、過期時間、使用狀態
}

// 餐廳業者設定
function byob_setup_restaurant_owner($user_id, $restaurant_id) {
    // 添加角色、關聯餐廳、設定 meta
}

// 邀請統計
function byob_get_invitation_stats() {
    // 總數、已使用、過期、轉換率統計
}
```

### 資料庫設計

**邀請資料表：**
```sql
CREATE TABLE wp_byob_invitations (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    token varchar(32) NOT NULL UNIQUE,
    restaurant_id bigint(20) NOT NULL,
    email varchar(100) NOT NULL,
    contact_person varchar(100) NOT NULL,
    expires datetime NOT NULL,
    used tinyint(1) DEFAULT 0,
    used_at datetime NULL,
    user_id bigint(20) NULL,
    created datetime NOT NULL,
    PRIMARY KEY (id)
);
```

### 技術挑戰解決方案

**1. 伺服器錯誤修復：**
```php
// 修正前
require_once get_template_directory() . '/invitation-handler.php';

// 修正後
$invitation_handler_path = __DIR__ . '/invitation-handler.php';
if (file_exists($invitation_handler_path)) {
    require_once $invitation_handler_path;
}
```

**2. Session 處理優化：**
```php
// 修正前
if (!session_id()) { session_start(); }

// 修正後
if (session_status() === PHP_SESSION_NONE) { session_start(); }
```

### 郵件模板設計

**視覺特色：**
- 酒紅色主題 (#8b2635)
- 響應式 HTML 設計
- 個性化歡迎訊息
- 清晰的 CTA 按鈕
- 會員權益說明

**內容結構：**
- 恭喜餐廳上架通知
- 一鍵註冊會員連結
- 餐廳頁面預覽
- 會員專屬功能介紹
- 聯絡資訊和注意事項

---

## 🔍 今日技術問題與解決方案總結

### 1. 重複email發送問題
**根本原因：** WordPress的 `transition_post_status` hook在文章狀態變更時觸發多個函數
**解決方案：** 統一email發送機制，讓所有狀態變更都調用同一個email函數
**技術要點：** 使用 `add_action('transition_post_status', 'byob_auto_send_invitation_on_publish', 10, 3)` 來監聽狀態變更

### 2. 404錯誤問題
**根本原因：** WordPress rewrite rules沒有正確註冊，自定義query variables缺失
**解決方案：** 手動刷新rewrite rules，註冊自定義query variables
**技術要點：** 使用 `flush_rewrite_rules()` 和 `add_filter('query_vars', 'byob_add_query_vars')`

### 3. 邀請碼驗證失敗問題
**根本原因：** 使用 `LIKE` 查詢序列化的WordPress meta值
**解決方案：** 檢索所有meta值後在PHP中進行 `maybe_unserialize` 和匹配
**技術要點：** WordPress meta值可能被序列化，需要使用 `maybe_unserialize()` 函數

### 4. 註冊表單功能單調問題
**根本原因：** 原始表單缺乏現代化的用戶體驗功能
**解決方案：** 添加JavaScript功能，改善CSS樣式
**技術要點：** 使用原生JavaScript實現密碼強度檢查、匹配驗證和顯示切換

## 📊 今日進度統計

### 完成度評估
- **重複email問題解決：** 100% ✅
- **404錯誤修復：** 100% ✅
- **註冊表單顯示修復：** 100% ✅
- **註冊頁面設計改進：** 100% ✅
- **新功能添加：** 100% ✅
- **實際流程測試：** 90% ⚠️

### 整體專案進度提升
- **邀請系統：** 90% → 95% ✅
- **註冊流程：** 85% → 95% ✅
- **註冊頁面UI/UX：** 60% → 90% ✅
- **會員後台：** 70% ⚠️
- **權限控制：** 80% ⚠️
- **錯誤處理：** 75% → 85% ✅

## 🎯 明日工作重點

**主要目標：** 完成餐廳業者註冊流程的完整測試，特別關注新改進的註冊表單功能

**測試重點：**
1. **新註冊表單功能測試**
   - 密碼顯示/隱藏功能（眼睛符號）
   - 即時密碼強度檢查
   - 密碼匹配即時驗證

2. **註冊流程完整性測試**
   - 邀請碼驗證機制
   - 表單提交和驗證
   - 註冊成功後流程

3. **會員後台功能測試**
   - 用戶角色和權限設定
   - 餐廳業者儀表板
   - 系統區分和隔離

---

**今日進度重點：成功解決餐廳業者註冊流程中的多個關鍵問題，包括重複email發送、404錯誤、註冊表單顯示問題等，並大幅改進了註冊頁面的設計與功能。新增的密碼顯示/隱藏、即時密碼強度檢查、密碼匹配驗證等功能顯著提升了用戶體驗。這是專案的重要技術突破，為後續的完整流程測試奠定了堅實基礎。明日將專注於新功能的完整測試，確保整個註冊流程穩定可靠。** 