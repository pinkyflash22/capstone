param(
    [string]$BaseUrl = "http://127.0.0.1:8000",
    [switch]$SkipLoad
)

$ErrorActionPreference = "Stop"

php artisan test --testsuite=Feature
if ($LASTEXITCODE -ne 0) { throw "Feature tests failed." }

php artisan ops:backup-database --verify
if ($LASTEXITCODE -ne 0) { throw "Backup verification failed." }

if (-not $SkipLoad) {
    if (-not (Get-Command k6 -ErrorAction SilentlyContinue)) {
        Write-Warning "k6 is unavailable; running the dependency-free smoke load instead. Production approval still requires the k6 profile."
        $env:BASE_URL = $BaseUrl
        node tests/Load/smoke-load.mjs
        if ($LASTEXITCODE -ne 0) { throw "Smoke-load thresholds failed." }
        return
    }
    $env:BASE_URL = $BaseUrl
    k6 run tests/Load/province-readiness.js
    if ($LASTEXITCODE -ne 0) { throw "Load thresholds failed." }
}

Write-Host "Province-readiness gates passed."
