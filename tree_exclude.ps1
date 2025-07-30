#.\tree_exclude.ps1 -Path "C:\GitHubProjects\BYOB" -Exclude "Mid" -OutputFile ".\output.txt"

param(
    [string]$Path = ".",                # 預設為目前資料夾
    [string[]]$Exclude = @(),           # 可接受多個要排除的資料夾名稱
    [string]$OutputFile = ""            # 輸出檔案路徑（可選）
)

Function Show-Tree {
    param(
        [string]$CurrentPath,
        [int]$Level = 0
    )

    $indent = "  " * $Level
    $items = Get-ChildItem -LiteralPath $CurrentPath | Sort-Object { -not $_.PSIsContainer }, Name

    foreach ($item in $items) {
        if ($item.PSIsContainer) {
            if ($Exclude -notcontains $item.Name) {
                Write-Output "$indent- $($item.Name)"
                Show-Tree -CurrentPath $item.FullName -Level ($Level + 1)
            }
        }
        else {
            Write-Output "$indent  $($item.Name)"
        }
    }
}

# 產生結果
$result = Show-Tree -CurrentPath $Path

# 用 UTF-8 無 BOM 寫檔（保留中文 + 無亂碼）
if ($OutputFile -ne "") {
    $utf8NoBOM = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllLines((Resolve-Path $OutputFile), $result, $utf8NoBOM)
    Write-Host "✅ Tree structure saved to $OutputFile"
}
else {
    $result
}



