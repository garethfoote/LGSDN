param(
    [string] $OutputDirectory = (Join-Path $PSScriptRoot '..\wp-content')
)

$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$outputRoot = (Resolve-Path -LiteralPath $OutputDirectory -ErrorAction SilentlyContinue)

if (-not $outputRoot) {
    New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null
    $outputRoot = Resolve-Path -LiteralPath $OutputDirectory
}

$packages = @(
    @{
        Name = 'lgsdn'
        Source = Join-Path $projectRoot 'wp-content\themes\lgsdn'
        Archive = Join-Path $outputRoot.Path 'themes\lgsdn-theme.zip'
        Exclude = @()
    },
    @{
        Name = 'lgsdn-core'
        Source = Join-Path $projectRoot 'wp-content\plugins\lgsdn-core'
        Archive = Join-Path $outputRoot.Path 'plugins\lgsdn-core.zip'
        Exclude = @('tests')
    }
)

$stagingRoot = Join-Path ([System.IO.Path]::GetTempPath()) "lgsdn-wordpress-export-$([guid]::NewGuid().ToString('N'))"

try {
    foreach ($package in $packages) {
        if (-not (Test-Path -LiteralPath $package.Source -PathType Container)) {
            throw "Source directory not found: $($package.Source)"
        }

        $stage = Join-Path $stagingRoot $package.Name
        New-Item -ItemType Directory -Path $stage -Force | Out-Null
        Get-ChildItem -LiteralPath $package.Source -Force | Copy-Item -Destination $stage -Recurse -Force

        foreach ($excluded in $package.Exclude) {
            $excludedPath = Join-Path $stage $excluded
            if (Test-Path -LiteralPath $excludedPath) {
                Remove-Item -LiteralPath $excludedPath -Recurse -Force
            }
        }

        $archiveDirectory = Split-Path -Parent $package.Archive
        New-Item -ItemType Directory -Path $archiveDirectory -Force | Out-Null
        Compress-Archive -Path $stage -DestinationPath $package.Archive -Force

        $archive = Get-Item -LiteralPath $package.Archive
        Write-Output "Created $($archive.FullName) ($([math]::Round($archive.Length / 1KB, 1)) KB)"
    }
}
finally {
    if (Test-Path -LiteralPath $stagingRoot) {
        Remove-Item -LiteralPath $stagingRoot -Recurse -Force
    }
}
