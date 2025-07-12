# 台北 BYOB 餐廳資料庫專案說明（2025-07-12 更新）

本專案旨在建立一套讓民眾能快速查詢「台北市可自帶酒水（BYOB）」的餐廳 App 系統，並協助餐廳主動登錄資訊。資料來源以「餐廳主動申報制」為核心，由餐廳透過 Google 表單提交資訊，經由 App Script 自動轉換為標準格式，供主資料庫與 App 使用。

---

## 📌 最新進度概要（2025-07-12）

### ✅ 前端樣式與架構強化

* 解決 `tailwindcss` 初始化與 PostCSS 相容問題（轉為 `.cjs`）
* 成功啟用 Tailwind + Vite 開發環境並正常渲染畫面
* 修正卡片元件（`ByobCardPreview.jsx`）的內距與項目呈現順序，並隱藏不必要資訊欄位

### ✅ 開始導入 badge 標籤美化資料欄位

* 測試以淺色背景與文字色組成 badge 標籤，強化辨識性
* 將「是否收開瓶費」與「餐廳類型」欄位轉為 badge 呈現
* 預計進一步根據值內容改變顏色（如「否」為綠、「是」為紅）

### ✅ 排版與卡片樣式調整

* 預設卡片樣式設為 45px 左右內距與 15px 上下間距
* grid 排版設定為 `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`，但桌機上仍顯示為單欄，初判為元件未正確掛載或樣式被覆蓋
* 正確認識 Tailwind 斷點 (`lg = 1024px`)，以瀏覽器開發工具協助排查

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

## 🔧 下一步任務（2025-07-13）

1. 強化 badge 顏色邏輯與元件化封裝設計
2. 確保桌機版本畫面能正確呈現三欄排版
3. 將其他欄位（如提供酒器、備註）進行 UX 分層優化

---

若需參與開發、審核、設計或資料串接合作，請聯繫：[hello@byob.taipei](mailto:hello@byob.taipei)
