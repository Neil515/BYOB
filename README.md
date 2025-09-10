# 餐廳Email搜尋程式使用說明

## 🚀 快速開始

### 1. 安裝套件（只需執行一次）
```bash
python install_requirements.py
```

### 2. 執行程式

#### **處理全部餐廳**
```bash
python restaurant_email_search.py 您的Excel檔案.xlsx
```

#### **限制處理家數**
```bash
# 只處理前10家餐廳
python restaurant_email_search.py 您的Excel檔案.xlsx -n 10

# 只處理前5家餐廳
python restaurant_email_search.py 您的Excel檔案.xlsx -n 5

# 只處理前20家餐廳
python restaurant_email_search.py 您的Excel檔案.xlsx -n 20
```

## 📋 限制家數功能說明

### 🎯 **使用場景**

1. **測試階段**：先用少量資料測試程式是否正常
2. **分批處理**：避免一次處理太多，降低風險
3. **時間控制**：控制每次執行的時間長度
4. **資源管理**：避免長時間佔用電腦資源

### ⚙️ **參數說明**

- `-n` 或 `--max-restaurants`：限制處理的餐廳數量
- 數值範圍：1-999（建議不超過50）
- 預設值：無限制（處理全部）

### 📊 **輸出檔案命名**

- **無限制**：`原檔名_with_emails.xlsx`
- **有限制**：`原檔名_with_emails_limit10.xlsx`（限制10家）

## 💡 使用建議

### 🔬 **測試階段**
```bash
# 先用5家測試
python restaurant_email_search.py restaurants.xlsx -n 5
```

### 📈 **分批處理**
```bash
# 第一批：前10家
python restaurant_email_search.py restaurants.xlsx -n 10

# 第二批：第11-20家（需要修改Excel檔案）
python restaurant_email_search.py restaurants_11-20.xlsx -n 10
```

### ⏱️ **時間控制**
```bash
# 5家：約2-3分鐘
python restaurant_email_search.py restaurants.xlsx -n 5

# 10家：約5-8分鐘
python restaurant_email_search.py restaurants.xlsx -n 10

# 20家：約10-15分鐘
python restaurant_email_search.py restaurants.xlsx -n 20
```

## 📋 程式功能

- **自動搜尋Email**：從Facebook專頁和官方網站搜尋聯絡email
- **智能識別**：自動判斷是Facebook專頁還是官方網站
- **結果輸出**：在原Excel檔案後方新增email欄位
- **錯誤處理**：自動跳過無法存取的網站
- **家數限制**：可設定每次處理的餐廳數量

## 📊 輸入檔案格式

Excel檔案必須包含以下欄位：
- `name`：餐廳名稱
- `website`：社群連結（Facebook專頁或官方網站）
- 其他欄位：`address`, `phone`, `rating`, `user_ratings_total`, `place_id`

## 📈 輸出結果

程式會生成新檔案，新增欄位：
- `email`：找到的email地址
- `search_status`：搜尋狀態（found/not_found/error）

## ⏱️ 執行時間參考

| 餐廳數量 | 預估時間 | 建議用途 |
|---------|---------|---------|
| 5家 | 2-3分鐘 | 測試 |
| 10家 | 5-8分鐘 | 小批量 |
| 20家 | 10-15分鐘 | 中批量 |
| 50家 | 25-40分鐘 | 大批量 |

## 🔧 技術細節

### Facebook專頁搜尋
- 使用Selenium模擬瀏覽器
- 搜尋「關於」頁面和聯絡資訊
- 自動處理需要登入的頁面

### 官方網站搜尋
- 搜尋常見頁面：/contact, /about, /聯絡我們
- 使用HTTP請求，速度較快
- 自動解析HTML內容

### Email驗證
- 標準email格式驗證
- 過濾無效email（example.com等）
- 自動去重

## 📝 日誌檔案

程式執行時會生成 `email_search.log` 檔案，記錄：
- 搜尋進度
- 找到的email
- 錯誤訊息
- 限制家數資訊

## ⚠️ 注意事項

1. **需要Chrome瀏覽器**：程式使用Chrome WebDriver
2. **網路連線**：需要穩定的網路連線
3. **執行時間**：請耐心等待，不要中途關閉程式
4. **檔案備份**：建議先備份原始Excel檔案
5. **限制家數**：建議不超過50家，避免執行時間過長

## 🆘 常見問題

### Q: 如何知道程式正在執行？
A: 查看命令提示字元會顯示處理進度，或查看 `email_search.log` 檔案

### Q: 可以中途停止程式嗎？
A: 可以按 Ctrl+C 停止，但建議讓程式自然完成

### Q: 限制家數後如何處理剩餘的餐廳？
A: 需要手動分割Excel檔案，或修改原始檔案後重新執行

### Q: 程式執行失敗怎麼辦？
A: 檢查Chrome瀏覽器是否已安裝，或手動安裝套件：
```bash
pip install pandas requests selenium openpyxl
```

## 📞 技術支援

如有問題，請檢查：
1. Python版本（建議3.7+）
2. Chrome瀏覽器版本
3. 網路連線狀態
4. Excel檔案格式
5. 限制家數參數是否正確