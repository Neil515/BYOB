import ByobCardPreview from "./components/ByobCardPreview";

function App() {
  return (
    <div className="bg-gray-50 min-h-screen">
      <div className="max-w-7xl mx-auto p-6">
        <h1 className="bg-red-200 text-2xl font-bold mb-4">🍷 台北 BYOB 餐廳清單</h1>
        <ByobCardPreview />

        {/* Debug RWD Breakpoint 標籤（右下角） */}
        <div className="fixed bottom-2 right-2 z-50 text-white text-xs font-mono rounded overflow-hidden shadow">
          <div className="bg-red-600 sm:hidden px-2 py-1">xs</div>
          <div className="hidden sm:block md:hidden bg-orange-600 px-2 py-1">sm</div>
          <div className="hidden md:block lg:hidden bg-yellow-600 px-2 py-1">md</div>
          <div className="hidden lg:block xl:hidden bg-green-600 px-2 py-1">lg</div>
          <div className="hidden xl:block bg-blue-600 px-2 py-1">xl</div>
        </div>
      </div>
    </div>
  );
}

export default App;
