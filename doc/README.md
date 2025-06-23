# 台北 BYOB 餐廳資料爬蟲專案（新版 README with 模組角色釐清）

本專案目的為建立台北地區支援 BYOB（自帶酒水）餐廳的資料庫，包含地區、開瓶費、設備等資訊，供 App 與網站查詢功能使用。

---

## 📦 專案結構簡述

* `crawler/byob_scraper.py`: **主資料補齊模組**，整合 Google Maps API，自動擷取餐廳的結構化資訊（地址、電話、網站等），並嘗試解析網站是否提及 BYOB／開瓶費。用途是補充結構化欄位，不負責主動判斷。
* `supplement/pixnet_scraper.py`: **關鍵字命中模組（判斷型）**，利用 SerpAPI 搜尋 Pixnet 是否有餐廳被提及 BYOB。屬於判斷來源之一。
* `data/seed_list_raw.txt`: 初始餐廳清單，每行格式為「餐廳名稱 # 資料來源」。
* `data/BYOB 台北餐廳資料庫.csv`: 最終主資料庫表，遵循 11 欄欄位格式。
* `data/pixnet_hits.csv`: Pixnet 判斷命中結果輸出。
* `data/pixnet_summary.csv`: Pixnet 命中數統計（餐廳／關鍵字篇數）。
* `doc/`: 存放進度說明、任務提示與原始 README。

---

## ✅ 模組責任與使用階段說明

為避免混淆，特別列出各模組的角色與出場時機：

| 程式名稱                | 功能核心            | 出場時機                       | 是否負責判斷「能不能 BYOB」     |
| ------------------- | --------------- | -------------------------- | -------------------- |
| `pixnet_scraper.py` | 看部落格文章有無提及 BYOB | 🔍 搜尋可 BYOB 餐廳階段（**判斷階段**） | ✅ 是，根據 Pixnet 內容判斷為主 |
| `byob_scraper.py`   | 擷取地址電話類型與官網描述   | 🧱 填補已確認餐廳的詳細資訊（**補充階段**）  | ⚠️ 有嘗試判斷，但僅供參考       |

---

## 🆕 新模組開發中：Google Maps 判斷模組

我們正開發第三個來源模組，作為與 Pixnet 平行的判斷工具：

### `google_review_checker.py`（開發中）

* 從 Google Maps 上擷取評論與官網描述
* 用與 Pixnet 相同的關鍵字邏輯去判斷是否可 BYOB
* 若評論中提到「自帶酒」、「免開瓶費」等詞，即記錄為命中
* 最終產出 `google_hits.csv` 可供比對與匯入

---

## 🚧 最新建議任務清單（2025-06-23 起）

1. 起草 `google_review_checker.py` 並建立分析流程
2. 撰寫 `analyze_reviews()` 函式，重用既有關鍵字分類邏輯
3. 設計 `google_hits.csv` 輸出結構（可與 Pixnet 統一格式）
4. 設計 `source/` 模組資料夾架構，統一管理資料來源模組

---

## 📎 爬蟲執行需知

執行 `byob_scraper.py` 前請確認：

* `.env` 中設定 `GOOGLE_MAPS_API_KEY`
* 若使用 `pixnet_scraper.py`，需設定 `SERPAPI_KEY`
* 程式將自動讀取 `seed_list_raw.txt` 並依照命名規則進行擷取與資料輸出

如需進一步自動化整合流程，可規劃判斷模組整合器與資料清洗中介腳本。
