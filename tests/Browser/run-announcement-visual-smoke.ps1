param(
    [Parameter(Mandatory = $true)][string] $BaseUrl,
    [Parameter(Mandatory = $true)][string] $Email,
    [Parameter(Mandatory = $true)][string] $Password,
    [Parameter(Mandatory = $true)][string] $Role,
    [Parameter(Mandatory = $true)][string] $OutputDirectory,
    [ValidateSet('announcements', 'checklists', 'schedule')][string] $AuditMode = 'announcements',
    [string] $TargetPath = '',
    [string] $BrowserPath = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
    [switch] $Assert,
    [int] $DebugPort = 9333
)

$ErrorActionPreference = 'Stop'
$profileDirectory = Join-Path $env:TEMP ("pyrosense-visual-{0}-{1}" -f $Role, [guid]::NewGuid())
$targetUrl = if ($TargetPath) { "$BaseUrl$TargetPath" } elseif ($Role -eq 'chief') { "$BaseUrl/station-admin/announcements" } else { "$BaseUrl/firefighter/announcements" }

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
New-Item -ItemType Directory -Force -Path $profileDirectory | Out-Null

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
    param([int] $Milliseconds = 1800)
    Start-Sleep -Milliseconds $Milliseconds
}

function Save-Screenshot {
    param([string] $Name)
    $capture = Invoke-Cdp 'Page.captureScreenshot' @{ format = 'png'; captureBeyondViewport = $false }
    $path = Join-Path $OutputDirectory $Name
    [IO.File]::WriteAllBytes($path, [Convert]::FromBase64String($capture.data))
    return $path
}

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

    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 1440; height = 1000; deviceScaleFactor = 1; mobile = $false } | Out-Null
    Invoke-Cdp 'Page.navigate' @{ url = "$BaseUrl/login" } | Out-Null
    Wait-ForPage 1200

    $loginScript = @"
(() => {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const form = document.getElementById('loginForm');
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
        $loginState = Invoke-JavaScript "({ path: location.pathname, loginFormVisible: !!document.getElementById('loginForm') })"
    } while ($loginState.result.value.path -eq '/login' -and (Get-Date) -lt $loginDeadline)

    if ($loginState.result.value.path -eq '/login') {
        throw 'Login did not complete before the browser audit timeout.'
    }

    Invoke-Cdp 'Page.navigate' @{ url = $targetUrl } | Out-Null
    $targetDeadline = (Get-Date).AddSeconds(45)
    do {
        Wait-ForPage 750
        $targetState = Invoke-JavaScript "({ path: location.pathname, ready: document.readyState, hasTarget: !!document.querySelector('#checklistWorkspace, #chiefScheduleCalendarGrid, [data-announcement-page]') })"
    } while (-not $targetState.result.value.hasTarget -and $targetState.result.value.path -notlike '*/login' -and (Get-Date) -lt $targetDeadline)

    if ($AuditMode -eq 'schedule') {
        $desktopScheduleAudit = Invoke-JavaScript @"
(() => ({
    authenticated: !location.pathname.includes('/login'),
    calendar: !!document.getElementById('chiefScheduleCalendarGrid'),
    dayCount: document.querySelectorAll('#chiefScheduleCalendarGrid .calendar-day').length,
    horizontalOverflow: document.documentElement.scrollWidth > innerWidth + 1
}))()
"@
        $desktopSchedulePath = Save-Screenshot "$Role-schedule-desktop.png"

        Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
        Wait-ForPage 700
        $mobileScheduleAudit = Invoke-JavaScript @"
(() => {
    const grid = document.getElementById('chiefScheduleCalendarGrid');
    const firstDay = grid?.querySelector('.calendar-day');
    const topbar = document.querySelector('.lector-top-bar');
    return {
        horizontalOverflow: document.documentElement.scrollWidth > innerWidth + 1,
        profileHeaderHidden: topbar ? getComputedStyle(topbar).display === 'none' : true,
        dayCount: grid?.querySelectorAll('.calendar-day').length || 0,
        gridWidth: grid ? Math.round(grid.getBoundingClientRect().width) : null,
        dayWidth: firstDay ? Math.round(firstDay.getBoundingClientRect().width) : null,
        viewportWidth: innerWidth,
        inlineAddButtons: grid?.querySelectorAll('[data-add-date]').length || 0
    };
})()
"@
        $mobileSchedulePath = Save-Screenshot "$Role-schedule-mobile.png"

        $dayModalAudit = Invoke-JavaScript @"
(() => {
    const today = new Date();
    const key = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
    const day = document.querySelector('#chiefScheduleCalendarGrid .calendar-day[data-date="' + key + '"]') || document.querySelector('#chiefScheduleCalendarGrid .calendar-day:not(.muted)');
    day?.click();
    const modal = document.getElementById('dayAgendaModal');
    const add = document.getElementById('selectedDayAddBtn');
    return {
        available: !!day,
        visible: modal?.classList.contains('show') || false,
        zIndex: modal ? Number(getComputedStyle(modal).zIndex) : null,
        addVisible: add ? add.getBoundingClientRect().height > 0 : false
    };
})()
"@
        Wait-ForPage 250
        $daySchedulePath = Save-Screenshot "$Role-schedule-mobile-day.png"

        $formModalAudit = Invoke-JavaScript @"
(() => {
    document.getElementById('selectedDayAddBtn')?.click();
    const modal = document.getElementById('scheduleEventModal');
    const actions = modal?.querySelector('.schedule-modal-actions');
    const rect = actions?.getBoundingClientRect();
    return {
        visible: modal?.classList.contains('show') || false,
        zIndex: modal ? Number(getComputedStyle(modal).zIndex) : null,
        actionsVisible: rect ? rect.top < innerHeight && rect.bottom > 0 : false,
        actionsWidth: rect ? Math.round(rect.width) : null,
        viewportWidth: innerWidth
    };
})()
"@
        Wait-ForPage 250
        $formSchedulePath = Save-Screenshot "$Role-schedule-mobile-form.png"

        $requestModalAudit = Invoke-JavaScript @"
(() => {
    document.getElementById('scheduleModalClose')?.click();
    document.getElementById('openRequestReviewBtn')?.click();
    const modal = document.getElementById('requestReviewModal');
    const card = modal?.querySelector('.schedule-modal-card');
    return {
        visible: modal?.classList.contains('show') || false,
        zIndex: modal ? Number(getComputedStyle(modal).zIndex) : null,
        cardWidth: card ? Math.round(card.getBoundingClientRect().width) : null,
        viewportWidth: innerWidth
    };
})()
"@
        Wait-ForPage 250
        $requestSchedulePath = Save-Screenshot "$Role-schedule-mobile-requests.png"

        $scheduleAudit = [ordered]@{
            role = $Role
            desktop = $desktopScheduleAudit.result.value
            mobile = $mobileScheduleAudit.result.value
            dayModal = $dayModalAudit.result.value
            formModal = $formModalAudit.result.value
            requestModal = $requestModalAudit.result.value
            screenshots = @($desktopSchedulePath, $mobileSchedulePath, $daySchedulePath, $formSchedulePath, $requestSchedulePath)
        }
        $scheduleAudit | ConvertTo-Json -Depth 8

        if ($Assert) {
            $failures = [Collections.Generic.List[string]]::new()
            if (-not $scheduleAudit.desktop.authenticated) { $failures.Add('Authentication failed.') }
            if (-not $scheduleAudit.desktop.calendar -or $scheduleAudit.desktop.dayCount -ne 42) { $failures.Add('The monthly calendar did not render 42 day cells.') }
            if ($scheduleAudit.desktop.horizontalOverflow -or $scheduleAudit.mobile.horizontalOverflow) { $failures.Add('The schedule has horizontal overflow.') }
            if (-not $scheduleAudit.mobile.profileHeaderHidden) { $failures.Add('The desktop profile header remains visible on mobile.') }
            if ($scheduleAudit.mobile.gridWidth -gt $scheduleAudit.mobile.viewportWidth -or $scheduleAudit.mobile.inlineAddButtons -ne 0) { $failures.Add('The mobile calendar grid is stretched or still contains inline Add buttons.') }
            if (-not $scheduleAudit.dayModal.visible -or -not $scheduleAudit.dayModal.addVisible -or $scheduleAudit.dayModal.zIndex -lt 10000) { $failures.Add('The day timeline sheet is not usable above navigation.') }
            if (-not $scheduleAudit.formModal.visible -or -not $scheduleAudit.formModal.actionsVisible -or $scheduleAudit.formModal.zIndex -le $scheduleAudit.dayModal.zIndex) { $failures.Add('The schedule form or its actions are not visible.') }
            if (-not $scheduleAudit.requestModal.visible -or $scheduleAudit.requestModal.cardWidth -gt $scheduleAudit.requestModal.viewportWidth) { $failures.Add('The request review sheet exceeds the phone viewport.') }
            if ($failures.Count -gt 0) { throw ('Schedule browser audit failed: ' + ($failures -join ' ')) }
        }
        return
    }

    if ($AuditMode -eq 'checklists') {
        $desktopChecklistAudit = Invoke-JavaScript @"
(() => {
    const workspace = document.getElementById('checklistWorkspace');
    const firstRow = document.querySelector('[data-checklist-row]');
    return {
        url: location.href,
        authenticated: !location.pathname.includes('/login'),
        workspace: !!workspace,
        rows: document.querySelectorAll('[data-checklist-row]').length,
        horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        firstRowWidth: firstRow ? Math.round(firstRow.getBoundingClientRect().width) : null
    };
})()
"@
        $desktopChecklistPath = Save-Screenshot "$Role-checklists-desktop.png"

        Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
        Wait-ForPage 700
        $mobileChecklistAudit = Invoke-JavaScript @"
(() => {
    const topbar = document.querySelector('.checklist-topbar');
    const firstRow = document.querySelector('[data-checklist-row]');
    const actions = firstRow?.querySelector('.table-actions');
    return {
        horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        profileHeaderHidden: topbar ? getComputedStyle(topbar).display === 'none' : true,
        rowDisplay: firstRow ? getComputedStyle(firstRow).display : null,
        rowWidth: firstRow ? Math.round(firstRow.getBoundingClientRect().width) : null,
        viewportWidth: innerWidth,
        actionColumns: actions ? getComputedStyle(actions).gridTemplateColumns : null,
        bottomPadding: getComputedStyle(document.querySelector('.checklist-main')).paddingBottom
    };
})()
"@
        $mobileChecklistPath = Save-Screenshot "$Role-checklists-mobile.png"

        $openChecklist = Invoke-JavaScript @"
(() => {
    const trigger = document.querySelector('[data-open-checklist]');
    if (!trigger) return { available: false };
    trigger.click();
    return { available: true, checklistId: trigger.dataset.openChecklist };
})()
"@
        Wait-ForPage 1000
        $modalChecklistAudit = Invoke-JavaScript @"
(() => {
    const modal = document.getElementById('checklistModal');
    const card = modal?.querySelector('.checklist-modal__card');
    const history = modal?.querySelector('[data-checklist-history]');
    return {
        available: !!modal,
        visible: modal ? !modal.hidden : false,
        zIndex: modal ? Number(getComputedStyle(modal).zIndex) : null,
        cardWidth: card ? Math.round(card.getBoundingClientRect().width) : null,
        historyFailed: history ? /could not|failed|unavailable/i.test(history.textContent) : null,
        horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1
    };
})()
"@
        $modalChecklistPath = Save-Screenshot "$Role-checklists-mobile-modal.png"

        $requestAudit = Invoke-JavaScript @"
(async () => {
    const id = document.querySelector('[data-checklist-row]')?.dataset.checklistRow;
    if (!id) return { available: false };
    try {
        const response = await checklistFetch(checklistEndpoint('items', id), {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({item_id: 0, status: 'pending'})
        });
        const result = await response.json().catch(() => ({}));
        return { available: true, status: response.status, reachedServer: response.status === 422, message: result.message || null };
    } catch (error) {
        return { available: true, status: 0, reachedServer: false, message: error.message };
    }
})()
"@

        $checklistAudit = [ordered]@{
            role = $Role
            desktop = $desktopChecklistAudit.result.value
            mobile = $mobileChecklistAudit.result.value
            modal = $modalChecklistAudit.result.value
            request = $requestAudit.result.value
            screenshots = @($desktopChecklistPath, $mobileChecklistPath, $modalChecklistPath)
        }
        $checklistAudit | ConvertTo-Json -Depth 8

        if ($Assert) {
            $failures = [Collections.Generic.List[string]]::new()
            if (-not $checklistAudit.desktop.authenticated) { $failures.Add('Authentication failed.') }
            if (-not $checklistAudit.desktop.workspace) { $failures.Add('Checklist workspace was not rendered.') }
            if ($checklistAudit.desktop.rows -lt 1) { $failures.Add('The audit requires at least one checklist fixture.') }
            if ($checklistAudit.desktop.horizontalOverflow) { $failures.Add('Desktop checklist has horizontal overflow.') }
            if ($checklistAudit.mobile.horizontalOverflow) { $failures.Add('Mobile checklist has horizontal overflow.') }
            if (-not $checklistAudit.mobile.profileHeaderHidden) { $failures.Add('Desktop profile header remains visible on mobile.') }
            if ($checklistAudit.mobile.rowDisplay -ne 'grid') { $failures.Add('Mobile checklist rows are not card grids.') }
            if ($checklistAudit.mobile.rowWidth -gt $checklistAudit.mobile.viewportWidth) { $failures.Add('A mobile checklist card exceeds the viewport.') }
            if (-not $checklistAudit.modal.visible -or $checklistAudit.modal.zIndex -lt 10000) { $failures.Add('Checklist review sheet is hidden behind navigation or overlays.') }
            if ($checklistAudit.modal.historyFailed) { $failures.Add('Checklist history failed to load.') }
            if (-not $checklistAudit.request.reachedServer) { $failures.Add('Authenticated checklist PATCH did not reach Laravel.') }
            if ($failures.Count -gt 0) { throw ('Checklist browser audit failed: ' + ($failures -join ' ')) }
        }
        return
    }

    $desktopAudit = Invoke-JavaScript @"
(() => {
    const root = document.querySelector('.staff-announcements__workspace');
    const composer = document.querySelector('.composer-card');
    const heading = document.querySelector('.staff-announcements__heading');
    return {
        url: location.href,
        authenticated: !location.pathname.includes('/login'),
        root: !!root,
        composer: !!composer,
        heading: !!heading,
        posts: document.querySelectorAll('.post-card').length,
        horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        workspaceWidth: root ? Math.round(root.getBoundingClientRect().width) : null,
        composerWidth: composer ? Math.round(composer.getBoundingClientRect().width) : null
    };
})()
"@
    $desktopPath = Save-Screenshot "$Role-desktop.png"

    Invoke-Cdp 'Emulation.setDeviceMetricsOverride' @{ width = 390; height = 844; deviceScaleFactor = 1; mobile = $true } | Out-Null
    Wait-ForPage 700

    $mobileAudit = Invoke-JavaScript @"
(() => {
    const root = document.querySelector('.staff-announcements__workspace');
    const composer = document.querySelector('.composer-card');
    const refresh = document.querySelector('.staff-announcements__refresh');
    const buttons = [...document.querySelectorAll('.attachment-btn')].map(node => Math.round(node.getBoundingClientRect().width));
    return {
        horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        workspaceWidth: root ? Math.round(root.getBoundingClientRect().width) : null,
        composerWidth: composer ? Math.round(composer.getBoundingClientRect().width) : null,
        refreshWidth: refresh ? Math.round(refresh.getBoundingClientRect().width) : null,
        attachmentWidths: buttons,
        bottomPadding: root ? getComputedStyle(root).paddingBottom : null
    };
})()
"@
    $mobilePath = Save-Screenshot "$Role-mobile.png"

    $modalAudit = Invoke-JavaScript @"
(() => {
    const modal = document.getElementById('postEditorModal');
    const body = document.getElementById('postEditorBody');
    if (!modal || !body) return { available: false };
    body.innerHTML = '<label class="composer-role">Announcement text</label><textarea class="post-input" rows="5">Responsive editor smoke test</textarea>';
    modal.classList.add('active');
    const panel = modal.querySelector('.editor-panel');
    const rect = panel.getBoundingClientRect();
    return {
        available: true,
        visible: getComputedStyle(modal).display !== 'none',
        panelWidth: Math.round(rect.width),
        panelBottom: Math.round(rect.bottom),
        viewportHeight: innerHeight,
        horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1
    };
})()
"@
    Wait-ForPage 300
    $modalPath = Save-Screenshot "$Role-mobile-editor.png"

    [ordered]@{
        role = $Role
        desktop = $desktopAudit.result.value
        mobile = $mobileAudit.result.value
        modal = $modalAudit.result.value
        screenshots = @($desktopPath, $mobilePath, $modalPath)
    } | ConvertTo-Json -Depth 8
}
finally {
    if ($socket) {
        try { $socket.Dispose() } catch {}
    }
    if ($edge -and -not $edge.HasExited) {
        Stop-Process -Id $edge.Id -Force
    }
}
