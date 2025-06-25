# 台北 BYOB 餐廳資料爬蟲專案（2025 全新 README 更新）

本專案致力於建立一個完整且可查詢的「台北地區支援 BYOB（自帶酒水）」餐廳資料庫，提供使用者查詢功能，協助推動自由飲酒文化。資料來源涵蓋部落格評論、Google 評論、與官網解析，並朝向餐廳主動申報的轉型模式邁進。

---

## 📦 專案結構簡述

```
📁 crawler/
    byob_scraper.py              # 用 Google Maps API 補齊結構化欄位（地址、電話、網站）並分析官網開瓶資訊

📁 supplement/
    pixnet_scraper.py            # 透過 SerpAPI 搜尋 Pixnet，進行關鍵字命中分析（支援 FAST_MODE）
    google_review_checker.py     # 擷取 Google Maps 的評論與官網描述，分析 BYOB 話題

📁 data/
    seed_list_raw.txt            # 餐廳清單原始種子資料（名稱 + 來源）
    BYOB 台北餐廳資料庫.csv       # 最終主資料表，供 App 顯示用
    pixnet_hits.csv              # Pixnet 命中分析結果
    pixnet_summary.csv           # Pixnet 命中關鍵字與餐廳數統計
    pixnet_log.txt               # Pixnet 分析過程紀錄與命中記錄
    google_hits.csv              # Google Maps 命中資料（評論、網站）

📁 doc/
    ai_progress_byob.md          # AI 工作進度與策略分析記錄
    Next Task Prompt Byob.md     # 隔日任務引導提示
    README.md                    # 本文件
    byob_invitation_formal.md    # 正式邀請文案（專業版）
    byob_invitation_friendly.md  # 口語邀請文案（輕鬆版）
    byob_apply_flowchart.md      # 餐廳上架流程草圖（人工審核）
```

---

## ✅ 各模組職責與階段

| 模組名稱                       | 功能描述                                          | 出場階段   | 是否主動判斷 BYOB |
| -------------------------- | --------------------------------------------- | ------ | ----------- |
| `pixnet_scraper.py`        | 用 SerpAPI 搜尋 Pixnet 並分析關鍵字命中（支援 snippet + 內文） | 前期初篩   | ✅ 是         |
| `google_review_checker.py` | 擷取 Google Maps 上評論與官網，再分析是否提及 BYOB            | 判斷補強階段 | ✅ 是         |
| `byob_scraper.py`          | 補齊地址、電話、地區等欄位並擷取官網資訊（解析開瓶費與設備）                | 後期補充階段 | ⚠️ 可能能判斷    |

---

## 🐌 面臨挑戰與對策

### 命中率低落問題

* 網友用詞不一致，關鍵字無法涵蓋所有情況
* Pixnet 與 Google 資料有限（特別是小店與中餐館）
* 餐廳名稱模糊（易誤判）

✅ 對策：

* 導入主動申報模式（表單申請加入）
* 與酒商合作提供初始名單
* 加入「疑似 BYOB」→ 人工驗證的過濾流程

### 爬蟲速度問題

* Pixnet 全文解析太慢（435 次搜尋+串流）
* 解法：加入 FAST\_MODE 模式 → 僅抓 meta 快速初篩

---

## 🧠 餐廳接觸策略：主動 + 被動並行

### ✉️ 主動聯絡策略

* 自行挑選潛力店家（由命中結果、內部推薦或店面評估）
* 私訊或 email 聯絡，附上專業或輕鬆版邀請文案 + 表單連結
* 初期可優先建立 10–20 間示範合作店家，作為上架範例

### 📣 被動吸引策略

* 製作公開表單入口，供餐廳自行申請加入資料庫
* 在 IG / FB / Dcard 等發表徵求貼文，吸引店家與使用者推薦
* 可與酒商、活動社群合作導入合作名單

---

## 🚧 開發中與近期任務（2025-06-25）

* ✅ 撰寫邀請文案（正式 + 口語）
* ✅ 建立申請上架流程草圖，供他人理解與參與
* 🔜 設計 Google 表單與公開申請連結
* 🔜 整合 `pixnet_hits.csv` + `google_hits.csv` 成統一資料表

---

## 🔁 專案未來走向：兩條主路線

### Plan A：轉向「餐廳自主上傳／人工審核」資料庫

* 餐廳可申請上架，自報開瓶政策與設備
* 使用者可推薦 → 管理者驗證後加入主資料表
* 爬蟲與分析僅作為輔助工具

### Plan B：維持資訊自動搜集工具，作為策展輔助

* AI 自動掃描 Google 與 Pixnet，初步分類
* 人工確認後新增至主資料表
* 工具維持 API 與搜尋整合角色

---

## 📎 使用注意事項

* `.env` 檔需設定 `GOOGLE_MAPS_API_KEY` 與 `SERPAPI_KEY`
* 所有資料預設輸出在 `data/` 資料夾
* 每次更新建議先清除 `pixnet_log.txt` 與命中檔，以免重複
* 可手動切換 `FAST_MODE`（效能 vs 準確度取捨）

---

若需協助部署、自動化排程、或前端查詢 UI，請另洽後續模組規劃
