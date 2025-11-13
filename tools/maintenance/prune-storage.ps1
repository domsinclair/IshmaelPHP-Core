# Prune Ishmael storage: sessions, cache, and (optionally) logs
# Usage examples:
#   pwsh tools/maintenance/prune-storage.ps1 -RootPath . -SessionDays 3 -CacheDays 1 -LogsDays 14 -WhatIf
#   pwsh tools/maintenance/prune-storage.ps1 -RootPath . -SessionDays 7 -CacheDays 2 -IncludeLogs

[CmdletBinding(SupportsShouldProcess)]
param(
    [Parameter(Position=0)]
    [string]$RootPath = (Resolve-Path ".\").Path,

    # Sessions older than N days will be removed (match your session lifetime)
    [int]$SessionDays = 7,

    # Cache older than N days will be removed (set to 0 to skip cache pruning)
    [int]$CacheDays = 2,

    # Logs older than N days will be removed when -IncludeLogs is set
    [int]$LogsDays = 14,

    # Set to include logs cleanup as well
    [switch]$IncludeLogs,

    # Dry run: adds -WhatIf to deletions
    [switch]$WhatIf
)

function Remove-OlderThan {
    param(
        [Parameter(Mandatory)] [string]$Path,
        [Parameter(Mandatory)] [datetime]$Threshold,
        [switch]$Recurse,
        [switch]$WhatIf
    )

    if (Test-Path $Path) {
        $items = Get-ChildItem -Path $Path -File -Recurse:$Recurse | Where-Object { $_.LastWriteTime -lt $Threshold }
        foreach ($item in $items) {
            if ($PSCmdlet.ShouldProcess($item.FullName, "Remove-Item")) {
                if ($WhatIf) {
                    Remove-Item -LiteralPath $item.FullName -Force -ErrorAction SilentlyContinue -WhatIf
                } else {
                    Remove-Item -LiteralPath $item.FullName -Force -ErrorAction SilentlyContinue
                }
            }
        }
    }
}

Write-Host "Pruning storage under: $RootPath" -ForegroundColor Cyan

# Sessions
$sessionThreshold = (Get-Date).AddDays(-1 * [Math]::Max(0, $SessionDays))
foreach ($folder in @(
    # Core repo (when script is placed inside IshmaelPHP-Core)
    (Join-Path $RootPath "storage\sessions"),
    # Umbrella repo layout
    (Join-Path $RootPath "IshmaelPHP-Core\storage\sessions"),
    (Join-Path $RootPath "SkeletonApp\storage\sessions")
)) {
    Write-Host "Session cleanup: $folder (older than $SessionDays days; threshold $sessionThreshold)" -ForegroundColor Yellow
    Remove-OlderThan -Path $folder -Threshold $sessionThreshold -WhatIf:$WhatIf
}

# Cache
if ($CacheDays -gt 0) {
    $cacheThreshold = (Get-Date).AddDays(-1 * $CacheDays)
    foreach ($folder in @(
        # Core repo (when script is placed inside IshmaelPHP-Core)
        (Join-Path $RootPath "storage\cache"),
        # Umbrella repo layout
        (Join-Path $RootPath "IshmaelPHP-Core\storage\cache"),
        (Join-Path $RootPath "SkeletonApp\storage\cache")
    )) {
        Write-Host "Cache cleanup: $folder (older than $CacheDays days; threshold $cacheThreshold)" -ForegroundColor Yellow
        Remove-OlderThan -Path $folder -Threshold $cacheThreshold -Recurse -WhatIf:$WhatIf
    }
}

# Logs (optional)
if ($IncludeLogs) {
    $logsThreshold = (Get-Date).AddDays(-1 * [Math]::Max(1, $LogsDays))
    foreach ($folder in @(
        # Core repo (when script is placed inside IshmaelPHP-Core)
        (Join-Path $RootPath "storage\logs"),
        # Umbrella repo layout
        (Join-Path $RootPath "IshmaelPHP-Core\storage\logs"),
        (Join-Path $RootPath "SkeletonApp\storage\logs"),
        (Join-Path $RootPath "storage\logs")
    )) {
        Write-Host "Logs cleanup: $folder (older than $LogsDays days; threshold $logsThreshold)" -ForegroundColor Yellow
        Remove-OlderThan -Path $folder -Threshold $logsThreshold -Recurse -WhatIf:$WhatIf
    }
}

Write-Host "Prune completed." -ForegroundColor Green
