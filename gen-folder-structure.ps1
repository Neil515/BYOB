# 此腳本將產出一份精簡的資料夾結構樹狀圖（最多 6 層），並排除 node_modules 與 .git/objects
# 用於專案溝通資料夾架構時避免輸出過長或資訊爆炸
# 建議存放於 C:\GitHubProjects\BYOB 目錄下，執行方式：
# powershell -ExecutionPolicy Bypass -File .\gen-folder-structure.ps1 > folder-structure.txt

param (
    [string]$Path = ".",
    [int]$Indent = 0
)

function Show-Structure {
    param (
        [string]$CurrentPath,
        [int]$Level
    )

    $excludedDirs = @(".git", "node_modules", "__pycache__")
    if ($Level -ge 6) { return }

    $indentStr = "  " * $Level
    $items = Get-ChildItem -Path $CurrentPath | Sort-Object { !$_.PSIsContainer }, Name |
        Where-Object {
            if ($_.PSIsContainer) {
                -not ($excludedDirs -contains $_.Name)
            } else {
                $true
            }
        }

    foreach ($item in $items) {
        Write-Output "$indentStr$($item.Name)"
        if ($item.PSIsContainer) {
            Show-Structure -CurrentPath $item.FullName -Level ($Level + 1)
        }
    }
}

Show-Structure -CurrentPath $Path -Level $Indent
