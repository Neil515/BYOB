import React from "react";
import data from "../byob_restaurants_mock.json";

export default function ByobCardPreview() {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-7xl mx-auto px-4 py-6">
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
            className="flex flex-col bg-white shadow-md rounded-xl border border-gray-200 p-4 hover:shadow-lg transition-all text-sm w-full max-w-xs mx-auto break-words overflow-hidden"
          >
            <h2 className="text-lg font-semibold mb-2">{item["餐廳名稱"]}</h2>
            <p className="text-gray-600 mb-1">
              📍 {item["地區"]}・
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded">
                {item["餐廳類型"]}
              </span>
            </p>
            <p className="mb-1">📌 地址：{item["地址"]}</p>
            <p className="mb-1">
              💰 開瓶費：
              <span className={`ml-1 inline-block ${feeColor} text-xs font-semibold px-2 py-1 rounded`}>
                {fee}
              </span>
            </p>
            <p className="mb-1">🍷 酒器：{item["提供酒器設備"]}</p>
            <p className="mb-1">🧑‍ 開酒服務：{item["是否提供開酒服務？"]}</p>
            <p className="mb-1">📞 電話：{item["餐廳聯絡電話"]}</p>
            <p className="mb-1 break-all">
              🔗 社群：<a href={item["官方網站/ 社群連結"]} className="text-blue-600 underline" target="_blank" rel="noopener noreferrer">
                {item["官方網站/ 社群連結"]}
              </a>
            </p>
            <p className="italic text-gray-700 mt-2">📝 備註：{item["備註說明"]}</p>
          </div>
        );
      })}
    </div>
  );
}
