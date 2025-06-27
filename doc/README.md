# 台北 BYOB 餐廳資料平台專案（2025-06-27 更新版 README）

本專案致力於打造一個可查詢的「台北支援 BYOB（自帶酒水）」餐廳地圖 App，協助消費者快速找到可自帶酒的餐廳。2025 年起已正式轉向「餐廳主動申報」為主要營運模式，並搭配人工審核與資料補全。

目前資料蒐集策略採雙軌制：

* **主力資料來源（Plan A）**：由餐廳主動填寫 Google 表單，經人工審核後加入主資料庫。
* **輔助資料來源**：透過 Pixnet / Google Maps 評論分析工具交叉驗證、補強資料。

---

## 📦 專案結構簡述

```
📁 crawler/
    byob_scraper.py              # Google Maps 補齊欄位與開瓶資訊擷取

📁 supplement/
    pixnet_scraper.py            # Pixnet 關鍵字快篩工具
    google_review_checker.py     # Google 評論提及 BYOB 檢查器

📁 data/
    seed_list_raw.txt            # 早期潛力名單
    BYOB 台北餐廳資料庫.csv       # 主資料表（App 使用）
    pixnet_hits.csv              # Pixnet 結果
    google_hits.csv              # Google 評論結果
    pixnet_log.txt / google_review_log.txt # 爬蟲執行紀錄

📁 doc/
    ai_progress_byob.md          # 每日進度紀錄
    Next Task Prompt Byob.md     # 隔日任務安排
    byob_apply_flowchart.md      # 餐廳申請流程圖
    README.md                    # 本文件

📁 doc/message_and_form/
    byob_invitation_*.md         # 三版邀請文案（正式／口語／稀缺）

```

---

## ✅ BYOB 餐廳申請與上架流程（Plan A）

1. **鎖定潛力餐廳**：爬蟲或人工挑選，發送邀請文案與表單連結
2. **填寫表單**：採用 Google 表單設計，涵蓋 11 欄資料欄位（含 BYOB 設備與收費）
3. **人工審核與補資料**：補充聯絡方式、Google Maps、社群連結等
4. **轉換格式並上架**：輸入至主資料庫 `csv`，並等待 App 資料同步
5. **回報與感謝**：寄送上架通知，邀請餐廳分享平台頁面

---

## 🧠 文案策略與誘因（2025-06 更新）

現行使用三種文案版本：

* **正式邀請版**：適用 email / 商業聯絡
* **輕鬆邀請版**：適用 IG 私訊、Messenger、面對面
* **限量精選版**：強調「首批合作店家」「推薦排序優先」等品牌榮譽誘因

---

## 🛠️ 表單設計進度（2025-06-27 完成）

* 已完成 Google 表單設計與測試，具備條件跳題與「其他類型」填寫功能
* 設定完成送出確認訊息（目前 App 尚在建置中，將另行通知上架時間）
* 表單連結、嵌入碼與 QR Code 已可生成，後續將整合至文宣素材

---

## 🔍 開發工具與資料驗證策略

| 工具                    | 用途說明            | 使用頻率 |
| --------------------- | --------------- | ---- |
| Pixnet Scraper        | 關鍵字快篩 BYOB 提及情境 | 每月一次 |
| Google Review Checker | 評論驗證 BYOB 有無提及  | 中頻率  |
| Google 表單 + Sheet     | 資料蒐集與審核工作流程核心   | 每日   |

---

## 🔧 近期任務（2025-06-28 起）

* 製作填寫教學 PDF（逐欄示意、填寫範例、常見錯誤提醒）
* 建立人工審核用資料欄位清單（是否補件、推薦排序判斷）

---

## 📌 注意事項與部署提醒

* `.env` 檔需設定 `GOOGLE_MAPS_API_KEY` 與 `SERPAPI_KEY`
* 主資料表需定期備份與版本控管
* App 顯示端尚在 UI 設計階段，資料已準備整合

---

若需參與開發、協助審核、或推廣合作，歡迎聯繫：[hello@byob.taipei](mailto:hello@byob.taipei)
