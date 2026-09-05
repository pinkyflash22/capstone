param(
    [Parameter(Mandatory = $true)][string] $BaseUrl,
    [Parameter(Mandatory = $true)][string] $Email,
    [Parameter(Mandatory = $true)][string] $Password,
    [Parameter(Mandatory = $true)][string] $OutputDirectory,
    [string] $BrowserPath = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
    [switch] $Assert,
    [int] $DebugPort = 9341
)

# Example (read-only UI audit):
# .\tests\Browser\run-barangay-mobile-visual-audit.ps1 `
#   -BaseUrl https://example.test -Email barangay@example.test `
#   -Password secret -OutputDirectory .\storage\app\visual-audits\barangay -Assert

$ErrorActionPreference = 'Stop'
$profileDirectory = Join-Path $env:TEMP ("pyrosense-barangay-visual-{0}" -f [guid]::NewGuid())
$browserArguments = @(
    '--headless=new',
    '--disable-gpu',
    '--hide-scrollbars',
    '--no-first-run',
    '--disable-default-apps',
    "--remote-debugging-port=$DebugPort",
    "--user-data-dir=`"$profileDirectory`"",
    'about:blank'
)

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
New-Item -ItemType Directory -Force -Path $profileDirectory | Out-Null

$browserStart = @{ FilePath = $BrowserPath; PassThru = $true; ArgumentList = $browserArguments }
if ($IsWindows -or $env:OS -eq 'Windows_NT') {
    $browserStart.WindowStyle = 'Hidden'
}
$edge = Start-Process @browserStart
$socket = $null
$commandId = 0

function Invoke-Cdp {
    param([string] $Method, [hashtable] $Params = @{})

    $script:commandId++
    $id = $script:commandId
    $payload = @{ id = $id; method = $Method; params = $Params } | ConvertTo-Json -Depth 12 -Compress
    $bytes = [Text.Encoding]::UTF8.GetBytes($payload)
    $socket.SendAsync([ArraySegment[byte]]::new($bytes), [Net.WebSockets.WebSocketMessageType]::Text, $true, [Threading.CancellationToken]::None).GetAwaiter().GetResult()

    while ($true) {
        $stream = [IO.MemoryStream]::new()
        do {
            $buffer = New-Object byte[] 65536
            $result = $socket.ReceiveAsync([ArraySegment[byte]]::new($buffer), [Threading.CancellationToken]::None).GetAwaiter().GetResult()
            $stream.Write($buffer, 0, $result.Count)
        } while (-not $result.EndOfMessage)

        $message = [Text.Encoding]::UTF8.GetString($stream.ToArray()) | ConvertFrom-Json
        if ($message.id -eq $id) {
            if ($message.error) { throw "CDP $Method failed: $($message.error.message)" }
            return $message.result
        }
    }
}

function Invoke-JavaScript {
    param([string] $Expression)
    return Invoke-Cdp 'Runtime.evaluate' @{
        expression = $Expression
        returnByValue = $true
        awaitPromise = $true
    }
}

function Wait-ForPage {
    param([int] $Milliseconds = 1000)
    Start-Sleep -Milliseconds $Milliseconds
}

function Save-Screenshot {
    param([string] $Name)
    $capture = Invoke-Cdp 'Page.captureScreenshot' @{ format = 'png'; captureBeyondViewport = $false }
    $path = Join-Path $OutputDirectory $Name
    [IO.File]::WriteAllBytes($path, [Convert]::FromBase64String($capture.data))
    return $path
}

$pages = [ordered]@{
    dashboard = '/barangay/dashboard'
    map = '/barangay/risk-map'
    reports = '/barangay/reports'
    hazards = '/barangay/hazards'
    checklists = '/barangay/checklists'
    awareness = '/barangay/awareness'
    profile = '/barangay/profile'
    assessment = '/barangay/assessment'
    patrols = '/barangay/patrols'
}

$devices = @(
    [ordered]@{ name = 'android-compact'; width = 360; height = 800; scale = 2.5 },
    [ordered]@{ name = 'iphone'; width = 390; height = 844; scale = 3 },
    [ordered]@{ name = 'pwa-tablet'; width = 820; height = 1180; scale = 2 }
)

$results = [Collections.Generic.List[object]]::new()
$failures = [Collections.Generic.List[string]]::new()

try {
    $deadline = (Get-Date).AddSeconds(15)
    do {
        try {
            $tabs = Invoke-RestMethod "http://127.0.0.1:$DebugPort/json"
            $tab = $tabs | Where-Object { $_.type -eq 'page' } | Select-Object -First 1
        } catch {
            $tab = $null
            Start-Sleep -Milliseconds 250
        }
    } while (-not $tab -and (Get-Date) -lt $deadline)

    if (-not $tab) { throw 'The headless browser did not expose a debugging page.' }

    $socket = [Net.WebSockets.ClientWebSocket]::new()
    $socket.ConnectAsync([Uri]$tab.webSocketDebuggerUrl, [Threading.CancellationToken]::None).GetAwaiter().GetResult()
    Invoke-Cdp 'Page.enable' | Out-Null
    Invoke-Cdp 'Runtime.enable' | Out-Null
    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 3; mobile = $true } | Out-Null
    Invoke-Cdp 'Page.navigate' @{ url = "$BaseUrl/login" } | Out-Null
    Wait-ForPage 1200

    $loginScript = @"
(() => {
    const email = document.getElementById('email') || document.querySelector('input[name="email"]');
    const password = document.getElementById('password') || document.querySelector('input[name="password"]');
    const form = document.getElementById('loginForm') || email?.closest('form');
    if (!email || !password || !form) return { ok: false, reason: 'Login form unavailable' };
    email.value = $(ConvertTo-Json $Email -Compress);
    password.value = $(ConvertTo-Json $Password -Compress);
    form.requestSubmit();
    return { ok: true };
})()
"@
    $login = Invoke-JavaScript $loginScript
    if (-not $login.result.value.ok) { throw $login.result.value.reason }

    $loginDeadline = (Get-Date).AddSeconds(45)
    do {
        Wait-ForPage 750
        $loginState = Invoke-JavaScript "({ path: location.pathname, ready: document.readyState })"
    } while ($loginState.result.value.path -eq '/login' -and (Get-Date) -lt $loginDeadline)

    if ($loginState.result.value.path -eq '/login') {
        throw 'Barangay login did not complete before the browser audit timeout.'
    }

    try {
        Invoke-Cdp 'Emulation.setEmulatedMedia' @{ media = 'screen'; features = @(@{ name = 'display-mode'; value = 'standalone' }) } | Out-Null
    } catch {
        Write-Verbose 'This browser does not support display-mode emulation; viewport checks will continue.'
    }

    foreach ($device in $devices) {
        Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{
            width = $device.width
            height = $device.height
            deviceScaleFactor = $device.scale
            mobile = $true
        } | Out-Null

        foreach ($page in $pages.GetEnumerator()) {
            Invoke-Cdp 'Page.navigate' @{ url = "$BaseUrl$($page.Value)" } | Out-Null
            $pageDeadline = (Get-Date).AddSeconds(35)
            do {
                Wait-ForPage 500
                $state = Invoke-JavaScript "({ ready: document.readyState, path: location.pathname })"
            } while ($state.result.value.ready -ne 'complete' -and (Get-Date) -lt $pageDeadline)
            Wait-ForPage 500

            $audit = Invoke-JavaScript @"
(() => {
    const main = document.querySelector('.main-content');
    const sidebar = document.getElementById('sidebar');
    const dock = document.querySelector('.barangay-mobile-dock');
    const heroToggle = document.querySelector('[data-barangay-hero-toggle]');
    const cards = [...document.querySelectorAll('.mobile-card-table tbody tr')].filter(row => getComputedStyle(row).display !== 'none');
    const submitButtons = [...document.querySelectorAll('form button[type="submit"]')];
    const overflowingSubmit = submitButtons.some(button => {
        const rect = button.getBoundingClientRect();
        return rect.width > innerWidth + 1 || rect.left < -1 || rect.right > innerWidth + 1;
    });
    return {
        path: location.pathname,
        authenticated: !location.pathname.includes('/login'),
        horizontalOverflow: document.documentElement.scrollWidth > innerWidth + 1,
        mainWidth: main ? Math.round(main.getBoundingClientRect().width) : null,
        viewportWidth: innerWidth,
        sidebarHidden: sidebar ? getComputedStyle(sidebar).display === 'none' : true,
        dockVisible: dock ? getComputedStyle(dock).display !== 'none' : false,
        heroToggleVisible: heroToggle ? getComputedStyle(heroToggle).display !== 'none' : true,
        mobileCardCount: cards.length,
        mobileCardsUseGrid: cards.every(row => getComputedStyle(row).display === 'grid'),
        overflowingSubmit
    };
})()
"@
            $screenshot = Save-Screenshot "$($device.name)-$($page.Key).png"
            $entry = [ordered]@{
                device = $device.name
                page = $page.Key
                audit = $audit.result.value
                screenshot = $screenshot
            }
            $results.Add($entry)

            if ($Assert) {
                if (-not $entry.audit.authenticated) { $failures.Add("$($device.name)/$($page.Key): authentication failed.") }
                if ($entry.audit.horizontalOverflow) { $failures.Add("$($device.name)/$($page.Key): page has horizontal overflow.") }
                if ($entry.audit.mainWidth -gt $entry.audit.viewportWidth) { $failures.Add("$($device.name)/$($page.Key): main content exceeds the viewport.") }
                if (-not $entry.audit.sidebarHidden -or -not $entry.audit.dockVisible) { $failures.Add("$($device.name)/$($page.Key): mobile navigation shell is incorrect.") }
                if (-not $entry.audit.heroToggleVisible) { $failures.Add("$($device.name)/$($page.Key): compact hero control is unavailable.") }
                if (-not $entry.audit.mobileCardsUseGrid) { $failures.Add("$($device.name)/$($page.Key): record cards are not using the mobile grid.") }
                if ($entry.audit.overflowingSubmit) { $failures.Add("$($device.name)/$($page.Key): a submit button exceeds the viewport.") }
            }
        }
    }

    [ordered]@{
        pages = $pages.Count
        devices = $devices.Count
        captures = $results.Count
        results = $results
    } | ConvertTo-Json -Depth 8

    if ($Assert -and $failures.Count -gt 0) {
        throw ('Barangay mobile visual audit failed: ' + ($failures -join ' '))
    }
}
finally {
    if ($socket) {
        try { $socket.Dispose() } catch {}
    }
    if ($edge -and -not $edge.HasExited) {
        Stop-Process -Id $edge.Id -Force
    }
    if (Test-Path -LiteralPath $profileDirectory) {
        Remove-Item -LiteralPath $profileDirectory -Recurse -Force
    }
}
