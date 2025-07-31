### 📌 明日工作任務（2025-08-01）

**目標：完成 ACF 欄位調整與 Google 表單快速匯入 WordPress 功能**

#### 1. ACF 欄位調整與 PHP 檔案修改

**1.1 酒器設備欄位調整**
* 將「提供酒器設備」欄位改為 Checkbox 複選類型
* 設定選項：酒杯、開瓶器、冰桶、醒酒器、無提供
* 修改 `archive-restaurant.php` 和 `single_restaurant.php`
* 處理複選資料顯示邏輯，使用「、」分隔符號

**1.2 開瓶費欄位調整**
* 將「是否收開瓶費」欄位改為 Checkbox 複選類型
* 設定選項：不收費、酌收、其他
* 修改兩個 PHP 檔案的開瓶費顯示邏輯
* 確保與 Apps Script 轉換後的資料格式一致

**1.3 PHP 檔案修改重點**
* 使用 `is_array()` 檢查複選資料
* 使用 `implode()` 合併多個選項
* 加入 `esc_html()` 安全性處理
* 統一顯示格式（餐廳類型用「 / 」，酒器設備用「、」）

#### 2. Google 表單快速匯入 WordPress 功能

**2.1 方案評估與選擇**
* 評估方案 A：WordPress REST API 自動化
* 評估方案 B：CSV 匯出 + WordPress 匯入外掛
* 評估方案 C：Google Sheets 外掛 + WordPress 整合
* 選擇最適合的實作方案

**2.2 實作步驟**
* 如果選擇方案 A：
  - 設定 WordPress REST API 端點
  - 修改 Apps Script 加入 API 呼叫功能
  - 處理認證和錯誤處理
* 如果選擇方案 B：
  - 在 Apps Script 中加入 CSV 匯出功能
  - 安裝並設定 WordPress 匯入外掛
  - 建立欄位對應映射
* 如果選擇方案 C：
  - 安裝 WP All Import Pro
  - 設定 Google Sheets 作為資料來源
  - 建立自動同步機制

**2.3 測試與驗證**
* 測試完整流程：Google 表單 → Google Sheet → WordPress
* 確認資料格式正確性
* 驗證複選欄位處理
* 測試錯誤處理機制

#### 3. 資料格式一致性確認

**3.1 欄位對應檢查**
* 確認 Google 表單欄位與 ACF 欄位對應
* 確認 Apps Script 轉換邏輯正確
* 確認 WordPress 顯示格式統一

**3.2 複選欄位處理**
* 餐廳類型：最多三種，用「 / 」分隔
* 酒器設備：多選，用「、」分隔
* 開瓶費：單選，但需要處理子層級資訊

#### 4. 文件更新

**4.1 技術文件**
* 更新 ACF 欄位設定說明
* 記錄 PHP 檔案修改內容
* 建立 Google 表單匯入流程文件

**4.2 操作手冊**
* 建立餐廳資料新增標準作業程序
* 建立故障排除指南
* 更新專案進度文件

---

**今日已完成進度：**

* ✅ 餐廳類型欄位改為 Checkbox 複選
* ✅ 修改 `archive-restaurant.php` 和 `single_restaurant.php` 支援複選
* ✅ 修正 Apps Script 開瓶費欄位顯示邏輯
* ✅ 討論 Google 表單快速匯入 WordPress 方案

**明日重點：**

1. 完成酒器設備和開瓶費欄位的 ACF 調整
2. 修改對應的 PHP 檔案
3. 實作 Google 表單快速匯入 WordPress 功能
4. 建立完整的資料流程文件
5. 確保所有複選欄位正確處理和顯示

**技術注意事項：**

* 保持資料格式一致性
* 確保安全性處理（esc_html）
* 測試複選欄位的邊界情況
* 建立錯誤處理機制
