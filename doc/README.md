# 台北 BYOB 餐廳資料庫專案說明（2025-07-15 更新）

本專案旨在建立一套讓民眾能快速查詢「台北市可自帶酒水（BYOB）」的餐廳 App 系統，並協助餐廳主動登錄資訊。資料來源以「餐廳主動申報制」為核心，由餐廳透過 Google 表單提交資訊，經由 App Script 自動轉換為標準格式，供主資料庫與 App 使用。

---

## 📌 最新進度概要（2025-07-15）

### ✅ 前端 RWD 與樣式問題徹底解決

* 修正 postcss.config.cjs 設定錯誤（誤用 @tailwindcss/postcss），改為正確引用 require('tailwindcss') 與 require('autoprefixer')。
* 移除多餘套件，重啟開發伺服器後，Tailwind 樣式與 RWD 排版（grid-cols-1/2/3）皆正常顯示。
* 成功驗證 bg-red-200、卡片三欄排版與 hover 效果。

### ✅ ByobCardPreview 卡片設計優化

* 卡片加上 max-w-xs、mx-auto，確保多欄排版時每張卡片寬度適中且置中。
* 測試不同欄位內容長度，確保排版穩定。
* 卡片樣式與間距更美觀，RWD 響應式效果佳。

### ✅ 前端美化與體驗優化建議彙整

* 詳細列出標題區塊、卡片設計、badge 標籤、RWD、互動、搜尋、主題切換等多項美化與功能建議。
* 依照 Next Task Prompt Byob.md 格式，整理成明日工作，並生成新檔案《Next Task Prompt Byob (美化建議).md》供後續開發參考。

---

## 🔄 使用說明與注意事項（前台）

* 開發環境建議使用 VS Code + Git Bash + Powershell + npm 10 以上
* 初次開啟請依序執行：

  1. `npm install`
  2. `npm run dev`
* 若啟動失敗請確認：

  * `postcss.config.js` 是否改為 `.cjs`
  * Tailwind 套件安裝正確，無 global 衝突

---

## 🧪 測試與常見錯誤排查

* ❗ `npx tailwindcss init -p` 無法執行：請重新安裝 tailwind 並排除 global 安裝遺留檔案
* ❗ `vite` 無法啟動：請確認 `postcss.config.js` 格式與 `vite` 版本
* ❗ 畫面顯示為單欄：請檢查 `main.jsx` 是否正確渲染元件、是否套用 `lg:grid-cols-3`

---

## 🔧 下一步任務（2025-07-16）

1. 依據《Next Task Prompt Byob (美化建議).md》進行前端美化與體驗優化
2. 強化 badge 顏色邏輯與元件化封裝設計
3. 搜尋、篩選、分頁等互動功能預研與設計
4. 深色模式與細節優化

---

若需參與開發、審核、設計或資料串接合作，請聯繫：[hello@byob.taipei](mailto:hello@byob.taipei)
