# 台北 BYOB 餐廳資料庫專案說明（2025-07-08 更新）

本專案旨在建立一套讓民眾能快速查詢「台北市可自帶酒水（BYOB）」的餐廳 App 系統，並協助餐廳主動登錄資訊。資料來源以「餐廳主動申報制」為核心，由餐廳透過 Google 表單提交資訊，經由 App Script 自動轉換為標準格式，供主資料庫與 App 使用。

---

## 📌 最新進度概要（2025-07-08）

### ✅ 前端 Web App 架構初步建置

* 使用 Vite 建立 React 專案架構，命名為 `byob-app`
* 完成卡片元件 `ByobCardPreview.jsx` 初步呈現測試
* 導入 20 筆 JSON 模擬餐廳資料，驗證展示效果

### ✅ Tailwind CSS 成功整合

* 安裝 tailwindcss / postcss / autoprefixer 並成功排除初始化錯誤
* 正確設置 `tailwind.config.js`、`postcss.config.js` 與 `index.css`
* 卡片樣式成功導入 Tailwind 元件樣式顯示

### ✅ 自動化產出資料夾結構（含註解）

* 建立 PowerShell 腳本 `gen-folder-structure.ps1`
* 可產出指定深度的結構（含英文說明標註），並避免亂碼問題

### ✅ 清查冗餘資料與建立版本管理基礎

* 分析 `.git/`、`public/`、`node_modules` 等是否需版本控制
* 提供 .gitignore 推薦內容：忽略 node\_modules、log、暫存輸出

---

## 🔄 使用說明與注意事項（前台）

* 開發環境建議使用 VS Code + Git Bash + Powershell + npm 10 以上
* 初次開啟請依序執行：

  1. `npm install`
  2. `npm run dev`
* 若需重新啟用 Tailwind 請執行：
  `npx tailwindcss init -p`

---

## 🧪 測試與常見錯誤排查

* ❗ `npx tailwindcss init -p` 錯誤：請確認無 `@tailwindcss/postcss` 衝突
* ❗ 卡片無法渲染：請確認是否匯入模擬資料與元件組件正確

---

## 🔧 下一步任務（2025-07-09）

1. 強化卡片資訊完整度與樣式排版層次
2. 建立資料備份與版本命名標準作業流程

---

若需參與開發、審核、設計或資料串接合作，請聯繫：[hello@byob.taipei](mailto:hello@byob.taipei)
