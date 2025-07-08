# 此腳本將產出一份精簡的資料夾結構樹狀圖（最多 3 層），並排除 node_modules 與 .git/objects
# 用於專案溝通資料夾架構時避免輸出過長或資訊爆炸
# 建議存放於 C:\GitHubProjects\BYOB 目錄下，執行方式：
# powershell -ExecutionPolicy Bypass -File .\gen-folder-structure.ps1

# 取得目前資料夾路徑

$base = (Get-Location).Path
$maxDepth = 3

$annotations = @{
  "byob-app" = "Frontend React App"
  "crawler" = "Web crawler scripts"
  "data" = "Data processing"
  "doc" = "Project documentation"
  "supplement" = "Supplementary resources"
}

Get-ChildItem -Recurse -Force | Where-Object {
  $_.PSIsContainer -and
  $_.FullName -notmatch 'node_modules|\\.git\\objects'
} | ForEach-Object {
  $relativePath = $_.FullName.Substring($base.Length).TrimStart('\')
  $depth = ($relativePath.Split('\')).Count
  if ($depth -le $maxDepth) {
    $indent = '  ' * ($depth - 1)
    $folderName = $_.Name
    $comment = $annotations[$folderName]
    if ($comment) {
      "$indent$relativePath    # $comment"
    } else {
      "$indent$relativePath"
    }
  }
} | Out-File -Encoding utf8 folder-structure.txt



