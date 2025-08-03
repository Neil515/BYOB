## BYOB 進度紀錄｜2025-08-03

### ✅ 今日重點進度

1. **Contact 頁面設計完成**

   * 完成 Contact 頁面的整體佈局和結構設計
   * 設計用戶聯絡表單（姓名、Email、主題、訊息、電話）
   * 設計聯絡資訊展示區塊
   * 使用深酒紅色主調，確保與整體設計風格一致
   * 完成響應式設計，確保手機版體驗良好
   * 加入餐廳推薦引導，連結到 Google 表單

2. **Contact Form 7 表單設定完成**

   * 建立完整的聯絡表單結構
   * 設定必填欄位驗證（姓名、Email、聯絡主題、訊息）
   * 設計聯絡主題選項：一般問題、網站使用問題、餐廳資訊錯誤回報、合作提案、其他
   * 加入電話號碼選填欄位
   * 設定表單提交成功和失敗訊息
   * 完成表單樣式設計，符合品牌色彩

3. **WP Mail SMTP 外掛安裝與設定**

   * 安裝 WP Mail SMTP 外掛
   * 完成 Google Cloud Console 設定：
     - 建立 BYOB-Taipei 專案
     - 啟用 Gmail API
     - 建立 OAuth 2.0 用戶端 ID
     - 設定 OAuth 同意畫面
     - 加入測試使用者 byobmap.tw@gmail.com
   * 完成 Gmail SMTP 連接設定
   * 設定寄件者資訊：BYOBMAP <byobmap.tw@gmail.com>
   * 測試郵件發送功能，確認正常運作

4. **Contact Form 7 郵件功能完成**

   * 設定郵件收件者：byobmap.tw@gmail.com
   * 設計專業的 HTML 郵件範本
   * 包含寄件者資訊、聯絡主題、訊息內容
   * 加入深酒紅色邊框和品牌元素
   * 設定 Reply-To 功能
   * 完成郵件發送測試，確認功能正常

5. **FAQ 系統設計完成**

   * 設計主頁面 FAQ 區塊（4個核心問題）：
     - 什麼是 BYOB？
     - 如何找到最適合的 BYOB 餐廳？
     - 開瓶費大概多少？
     - 如何推薦新的 BYOB 餐廳？
   * 設計完整 FAQ 頁面：
     - 用戶 FAQ（10個問題）
     - 餐廳業者 FAQ（6個問題）
   * 加入 CTA 連結到完整 FAQ 頁面
   * 使用「你」而非「您」，保持親切感

6. **主頁面視覺設計完成**

   * 完成 Hero 區塊設計：台北 BYOB 餐廳地圖
   * 設計「什麼是 BYOB」介紹區塊
   * 加入視覺分隔器，區分不同內容區塊
   * 使用 Flatsome 主題的設計元素
   * 確保響應式設計效果
   * 完成整體頁面佈局和視覺平衡

7. **Google Cloud 設定完成**

   * 建立 byobmap.tw@gmail.com Gmail 帳號
   * 完成 Google Cloud Console 專案設定
   * 解決 OAuth 驗證問題
   * 設定測試使用者權限
   * 確認郵件發送功能正常運作

---

### 📥 已更新與規劃項目：

* **Contact 頁面**：完成設計和功能設定，包含聯絡表單和郵件發送
* **FAQ 系統**：完成主頁面和完整頁面設計，涵蓋用戶和餐廳業者問題
* **郵件系統**：完成 WP Mail SMTP 設定，確保郵件正常發送
* **主頁面設計**：完成視覺設計和內容佈局
* **Google Cloud**：完成 OAuth 設定和郵件功能

---

### 🗓 明日預定任務（同步於 Next Task Prompt Byob）

1. **Google 表單自動導入 WordPress 流程**
   - 評估三種實作方案（REST API、CSV 匯出、Google Sheets 外掛）
   - 選擇最佳方案並實作
   - 建立完整測試流程
   - 建立故障排除指南

2. **會員系統設計與實作**
   - 用戶會員系統（註冊、個人資料、收藏、評論）
   - 餐廳會員系統（註冊、管理後台、數據分析）
   - 會員權限管理（等級、VIP、積分系統）

3. **資料格式一致性確認**
   - 確認 Google 表單欄位與 ACF 欄位對應
   - 確認 Apps Script 轉換邏輯正確
   - 確認 WordPress 顯示格式統一

4. **文件更新**
   - 更新技術文件
   - 建立操作手冊
   - 更新專案進度文件

---

### 🔧 技術重點記錄：

**Contact Form 7 設定重點：**
- 使用 label 包含表單元素的格式
- 設定必填欄位驗證
- 設計友善的錯誤訊息
- 使用 HTML 格式郵件範本

**WP Mail SMTP 設定重點：**
- 使用 Gmail OAuth 2.0 認證
- 設定測試使用者權限
- 使用 byobmap.tw@gmail.com 作為寄件者
- 確保郵件發送可靠性

**FAQ 設計重點：**
- 主頁面放置 4 個核心問題
- 使用行銷導向的回答內容
- 區分用戶和餐廳業者 FAQ
- 加入 CTA 引導到完整頁面

**主頁面設計重點：**
- Hero 區塊標題稍微右移是正常設計
- 使用視覺分隔器區分內容區塊
- 確保響應式設計效果
- 保持品牌色彩一致性

**Google Cloud 設定重點：**
- 建立專用 Gmail 帳號 byobmap.tw@gmail.com
- 設定 OAuth 同意畫面為外部使用者
- 加入測試使用者避免驗證問題
- 使用 Gmail API 而非傳統 SMTP

**Contact Form 7 郵件範本設計：**
```html
<h3>新的聯絡表單提交</h3>
<p><strong>寄件者資訊：</strong></p>
<p>姓名： [your-name]</p>
<p>Email： [your-email]</p>
<p>電話： [tel-291]</p>
<p><strong>聯絡主題：</strong> [select-388]</p>
<p><strong>訊息內容：</strong></p>
<div style="background: #f5f5f5; padding: 15px; border-left: 4px solid #8b2635; margin: 10px 0;">
[your-message]
</div>
<hr style="border: 1px solid #e0e0e0; margin: 20px 0;">
<p style="color: #666; font-size: 12px;">
此郵件來自 BYOBMAP 網站聯絡表單<br>
網站： [_site_url]<br>
提交時間： [_date] [_time]
</p>
```

**主頁面 FAQ 設計（4個核心問題）：**
1. 什麼是 BYOB？- 強調價值主張（省錢 + 品質）
2. 如何找到最適合的 BYOB 餐廳？- 展示平台價值和專業性
3. 開瓶費大概多少？- 消除價格疑慮，強調經濟效益
4. 如何推薦新的 BYOB 餐廳？- 鼓勵參與互動，建立社群感

**完整 FAQ 頁面設計：**
- 用戶 FAQ：10個問題，涵蓋使用指南和常見問題
- 餐廳業者 FAQ：6個問題，涵蓋加入流程和管理功能
- 使用「你」而非「您」，保持親切感
- 每個回答都包含行銷元素

**Google Cloud OAuth 設定流程：**
1. 建立 BYOB-Taipei 專案
2. 啟用 Gmail API
3. 建立 OAuth 2.0 用戶端 ID
4. 設定 OAuth 同意畫面（外部使用者）
5. 加入測試使用者 byobmap.tw@gmail.com
6. 在 WP Mail SMTP 中完成連接

**郵件系統優勢：**
- 使用專業 SMTP 服務，提升送達率
- 支援 HTML 格式，提升專業形象
- 自動回覆功能，改善用戶體驗
- 郵件追蹤功能，便於管理

---

**今日進度重點：完成 Contact 頁面設計、FAQ 系統設計、郵件系統設定、主頁面視覺設計。整體網站基礎功能趨於完善，為明日 Google 表單自動導入和會員系統開發奠定良好基礎。**