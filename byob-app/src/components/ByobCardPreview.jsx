import React from "react";

import data from "../byob_restaurants_mock.json";

export default function ByobCardPreview() {
  return (
    <div className="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      {data.map((item, index) => (
        <div
          key={index}
          className="bg-white shadow-md rounded-2xl p-4 border border-gray-200 hover:shadow-lg transition-all"
        >
          <h2 className="text-xl font-semibold mb-1">{item["餐廳名稱"]}</h2>
          <p className="text-sm text-gray-600 mb-2">
            📍 {item["地區"]}・{item["餐廳類型"]}
          </p>
          <p className="text-sm">📍 地址：{item["地址"]}</p>
          <p className="text-sm">💰 開瓶費：{item["是否收開瓶費"]}</p>
          <p className="text-sm">🍷 酒器：{item["提供酒器設備"]}</p>
          <p className="text-sm">🧑‍🍳 開酒服務：{item["是否提供開酒服務？"]}</p>
          <p className="text-sm">📞 電話：{item["餐廳聯絡電話"]}</p>
          <p className="text-sm">🔗 社群：{item["官方網站/ 社群連結"]}</p>
          <p className="text-sm italic">📝 備註：{item["備註說明"]}</p>
          <div className="mt-2 text-xs text-gray-400">
            來源：{item["資料來源/ 提供人"]}・更新：{item["最後更新日期"]}
          </div>
        </div>
      ))}
    </div>
  );
}
