@php
    $notificationRoutePrefix = $notificationRoutePrefix ?? 'station-admin';
    $commandNotificationUrls = [
        'index' => route($notificationRoutePrefix.'.command-notifications.index'),
        'read_all' => route($notificationRoutePrefix.'.command-notifications.read-all'),
        'read' => route($notificationRoutePrefix.'.command-notifications.read', ['notificationId' => '__ID__']),
    ];
@endphp

<div class="command-notification-center" data-command-notification-center>
    <button type="button" class="command-notification-center__trigger" data-command-notification-trigger aria-label="Open notifications" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
        <span class="command-notification-center__badge" data-command-notification-badge hidden>0</span>
    </button>
    <section class="command-notification-center__panel" data-command-notification-panel hidden>
        <header>
            <div><small>Command center</small><strong>Notifications</strong></div>
            <button type="button" data-command-notification-read-all>Mark all read</button>
        </header>
        <div class="command-notification-center__list" data-command-notification-list>
            <p class="command-notification-center__empty">Loading notifications…</p>
        </div>
    </section>
</div>

<style>
    .command-notification-center { position:fixed; left:220px; bottom:118px; z-index:10020; }
    .command-notification-center__trigger { position:relative; width:44px; height:44px; border:1px solid #fed7aa; border-radius:14px; background:#fff; color:#b45309; box-shadow:0 12px 30px rgba(15,23,42,.16); display:flex; align-items:center; justify-content:center; cursor:pointer; }
    .command-notification-center__trigger svg { width:21px; height:21px; fill:none; stroke:currentColor; stroke-width:1.9; stroke-linecap:round; stroke-linejoin:round; }
    .command-notification-center__badge { position:absolute; top:-6px; right:-6px; min-width:20px; height:20px; padding:0 5px; border-radius:999px; background:#dc2626; color:#fff; border:2px solid #fff; font:800 10px/16px Inter,sans-serif; text-align:center; }
    .command-notification-center__panel { position:absolute; left:52px; bottom:0; width:min(360px,calc(100vw - 300px)); max-height:460px; overflow:hidden; border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 24px 60px rgba(15,23,42,.22); }
    .command-notification-center__panel header { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:15px 16px; border-bottom:1px solid #e2e8f0; background:#fff7ed; }
    .command-notification-center__panel header div { display:flex; flex-direction:column; gap:2px; }
    .command-notification-center__panel header small { color:#9a3412; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .command-notification-center__panel header strong { color:#0f172a; font-size:15px; }
    .command-notification-center__panel header button { border:0; background:transparent; color:#c2410c; font-size:11px; font-weight:800; cursor:pointer; }
    .command-notification-center__list { max-height:390px; overflow-y:auto; }
    .command-notification-center__item { display:block; padding:14px 16px; border-bottom:1px solid #f1f5f9; color:#334155; text-decoration:none; transition:background .18s ease; }
    .command-notification-center__item:hover { background:#f8fafc; }
    .command-notification-center__item.is-unread { border-left:3px solid #f97316; background:#fffaf5; }
    .command-notification-center__item strong,.command-notification-center__item span,.command-notification-center__item small { display:block; }
    .command-notification-center__item strong { color:#0f172a; font-size:13px; }
    .command-notification-center__item span { margin-top:4px; font-size:12px; line-height:1.45; }
    .command-notification-center__item small { margin-top:7px; color:#94a3b8; font-size:10px; }
    .command-notification-center__empty { padding:28px 18px; color:#64748b; text-align:center; font-size:12px; }
    .command-notification-mobile-badge { position:absolute; top:3px; right:16%; min-width:17px; height:17px; padding:0 4px; border-radius:999px; background:#dc2626; color:#fff; border:2px solid #fff; font-size:9px; font-weight:900; line-height:13px; text-align:center; }
    @media (max-width:767px) { .command-notification-center { display:none; } }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const center = document.querySelector('[data-command-notification-center]');
        if (!center || center.dataset.ready === '1') return;
        center.dataset.ready = '1';

        const urls = @json($commandNotificationUrls);
        const trigger = center.querySelector('[data-command-notification-trigger]');
        const panel = center.querySelector('[data-command-notification-panel]');
        const list = center.querySelector('[data-command-notification-list]');
        const badge = center.querySelector('[data-command-notification-badge]');
        const readAll = center.querySelector('[data-command-notification-read-all]');
        const mobileLists = document.querySelectorAll('[data-command-notification-mobile]');
        const mobileBadges = document.querySelectorAll('[data-command-notification-mobile-badge]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
        const safeUrl = (value) => {
            try {
                const target = new URL(String(value || '#'), window.location.origin);
                return target.origin === window.location.origin ? target.href : '#';
            } catch (error) {
                return '#';
            }
        };
        let notifications = [];

        const render = (unreadCount = 0) => {
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.hidden = unreadCount < 1;
            mobileBadges.forEach((mobileBadge) => {
                mobileBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                mobileBadge.hidden = unreadCount < 1;
            });
            const html = notifications.length ? notifications.map((item) => `
                <a class="command-notification-center__item ${item.is_read ? '' : 'is-unread'}" href="${escapeHtml(safeUrl(item.url))}" data-command-notification-id="${escapeHtml(item.id)}">
                    <strong>${escapeHtml(item.title)}</strong>
                    <span>${escapeHtml(item.message)}</span>
                    <small>${escapeHtml(item.created_at_human || '')}</small>
                </a>
            `).join('') : '<p class="command-notification-center__empty">You have no notifications yet.</p>';
            list.innerHTML = html;
            mobileLists.forEach((mobileList) => { mobileList.innerHTML = html; });
        };

        const refresh = async () => {
            try {
                const response = await fetch(urls.index, { headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
                if (!response.ok) throw new Error('Notification refresh failed.');
                const payload = await response.json();
                notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
                render(Number(payload.unread_count || 0));
            } catch (error) {
                list.innerHTML = '<p class="command-notification-center__empty">Notifications are unavailable right now.</p>';
            }
        };

        const post = async (url) => fetch(url, { method:'POST', headers:{Accept:'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });

        document.addEventListener('click', async (event) => {
            const item = event.target.closest('[data-command-notification-id]');
            if (!item) return;
            const id = item.dataset.commandNotificationId;
            if (id) await post(urls.read.replace('__ID__', encodeURIComponent(id)));
        });

        trigger?.addEventListener('click', () => {
            const willOpen = panel.hidden;
            panel.hidden = !willOpen;
            trigger.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) refresh();
        });
        readAll?.addEventListener('click', async () => { await post(urls.read_all); await refresh(); });
        document.addEventListener('click', (event) => {
            if (!center.contains(event.target)) { panel.hidden = true; trigger?.setAttribute('aria-expanded','false'); }
        });

        window.refreshCommandNotifications = refresh;
        refresh();
        window.setInterval(refresh, 30000);
    });
</script>
