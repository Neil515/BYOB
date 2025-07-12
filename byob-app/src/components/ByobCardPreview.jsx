import React from "react";
import data from "../byob_restaurants_mock.json";

export default function ByobCardPreview() {
  return (
    <div className="pl-6 pr-4 pt-4 pb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      {data.map((item, index) => {
        const fee = item["是否收開瓶費"];
        const feeColor =
          fee === "否"
            ? "bg-green-100 text-green-800"
            : fee === "是"
            ? "bg-red-100 text-red-800"
            : "bg-gray-100 text-gray-800";

        return (
          <div
            key={index}
            className="bg-white shadow-md rounded-2xl px-[45px] py-[15px] border border-gray-200 hover:shadow-lg transition-all"
          >
            <h2 className="text-xl font-semibold mb-1">{item["餐廳名稱"]}</h2>
            <p className="text-sm text-gray-600 mb-2">
              📍 {item["地區"]}・
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">
                {item["餐廳類型"]}
              </span>
            </p>
            <p className="text-sm">📍 地址：{item["地址"]}</p>
            <p className="text-sm">
              💰 開瓶費：
              <span className={`ml-1 inline-block ${feeColor} text-xs font-semibold px-2 py-1 rounded`}>
                {fee}
              </span>
            </p>
            <p className="text-sm">🍷 酒器：{item["提供酒器設備"]}</p>
            <p className="text-sm">🧑‍ 開酒服務：{item["是否提供開酒服務？"]}</p>
            <p className="text-sm">📞 電話：{item["餐廳聯絡電話"]}</p>
            <p className="text-sm">🔗 社群：{item["官方網站/ 社群連結"]}</p>
            <p className="text-sm italic">📝 備註：{item["備註說明"]}</p>
            {false && (
              <div className="mt-2 text-xs text-gray-400">
                來源：{item["資料來源/ 提供人"]}・更新：{item["最後更新日期"]}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
