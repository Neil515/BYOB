# 📌 BYOB App 專案 - 明日任務指引（2025-06-22）

今日已完成：

* 完成 Pixnet 食記／部落格爬蟲（使用 SerpAPI）
* 加入餐廳名稱與標題/snippet/連結的比對過濾條件
* 實作一餐廳多關鍵字輪詢機制（提升命中率）

---

## ✅ 明日建議任務清單（優先強化內容擷取與標記精度）

### 1. 🧠 擴充命中邏輯：比對文章內容內文是否包含餐廳名

* 目前僅比對標題、連結、摘要文字
* 建議增加「文章內文全文是否包含餐廳名」邏輯，以補強命中檢出率
* 可實作 `in_content=True` 條件參數進行控制

---

### 2. 🔁 每間餐廳多組關鍵字輪詢（已實作基礎版）

* 可進一步調整每組關鍵字的搜尋結果數（例如擴充為每組取 5 筆）
* 記錄每個關鍵字對應命中情況，做為後續權重排序依據

---

### 3. 📄 儲存有效命中記錄

* 建立 `data/pixnet_hits.csv`
* 欄位建議：餐廳名稱、命中關鍵字、文章標題、網址、擷取摘要（前 300 字）

---

### 4. 🧹 去重與有效性清洗

* 檢查多組關鍵字造成的重複命中紀錄
* 確保每篇文章只被計算一次（同一餐廳名稱）

---

如需我協助撰寫上述任何模組，或需快速產出邏輯草稿與範例資料，請明日直接交代我執行。
