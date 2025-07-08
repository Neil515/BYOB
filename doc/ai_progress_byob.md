## BYOB 進度紀錄｜2025-07-08

### ✅ 今日完成事項

1. **初始 JSON 模擬資料製作與輸出**：

   * 根據主資料庫欄位格式，自動產出 20 筆完整且結構一致的虛擬餐廳資料（含開瓶費、酒器、地區等資訊）。
   * 匯出為 JSON 檔案，供前端測試使用。

2. **前台卡片預覽測試**：

   * 使用 React + Vite 建立初步 Web App 專案結構（byob-app/）。
   * 建立 ByobCardPreview\.jsx 並嘗試導入虛擬資料。
   * 解決顯示空白問題並成功看到卡片出現。

3. **Tailwind CSS 安裝與診斷排錯**：

   * 多次嘗試 tailwindcss 套件安裝與執行初始化（遇到 `npx tailwindcss init -p` 錯誤）。
   * 成功移除衝突套件 `@tailwindcss/postcss`，改以正確方式安裝 tailwindcss、postcss、autoprefixer。
   * 最終完成 tailwind 啟用並正常渲染樣式。

4. **資料夾結構輸出優化**：

   * 為避免 5000 行結構過長問題，自行建立 `gen-folder-structure.ps1` 腳本：限制輸出層級並自動加註說明。
   * 解決亂碼問題並統一輸出為 UTF-8 格式。

5. **版本管理與結構清理**：

   * 初步檢查哪些檔案與資料夾可移除（如 .git/logs、node\_modules、未使用 public 靜態資源等）。
   * 提出加入 .gitignore 的最佳實務清單。

---

### 🔜 明日建議工作（已獨立建檔）

請參考文件：《Next Tasks 2025 0709》
