## ☀️ 明日工作進度（2025-07-14）

### ✅ 預計進行兩項任務：

---

### 🎨 1. 修正與強化卡片樣式與顏色標籤

* 檢查 Tailwind CSS 是否正確載入，目前 badge 顏色樣式效果未明顯生效，須檢查樣式檔案載入順序與設定檔內容
* 針對以下欄位調整為 badge 呈現並套用顏色（淺底色＋文字顏色）：

  * 「是否收開瓶費」：否（綠）、是（紅）
  * 「餐廳類型」：依照類別設計不同色彩，例如私廚為紫、日式為藍、西式為橘
* 可考慮封裝 badge 為可重用元件，未來便於維護與調整

---

### 🧱 2. 確保桌機版顯示三欄排版生效

* 目前於桌機仍為一欄，請確認：

  * Tailwind 的 `grid-cols-3` 是否正確套用
  * 瀏覽器視窗寬度已達 `xl`（預設為 1280px）或是否使用了錯誤 breakpoint（如 md 而非 xl）
  * grid 設定是否被其他樣式覆蓋
* 可加入卡片邊框作為 debug 輔助視覺，確認是否正確對齊三欄

---

📌 備註：樣式修正完成後可考慮為所有欄位加上 hover 提示說明（例如備註欄補充說明，電話欄點擊可撥號）作為下一階段 enhancement。
