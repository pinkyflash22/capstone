<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chiefLogsAllowAll = @json($chiefLogsAllowAll ?? true);
        const logTriggers = document.querySelectorAll('.chief-open-logs');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const chiefLogsModal = document.getElementById('chiefLogsModal');
        const chiefLogsContainer = document.getElementById('chiefLogsContainer');
        const chiefLogsSummary = document.getElementById('chiefLogsSummary');
        const chiefLogsTitle = document.getElementById('chiefLogsTitle');
        const chiefLogsSubtitle = document.getElementById('chiefLogsSubtitle');
        const refreshChiefLogsBtn = document.getElementById('refreshChiefLogsBtn');
        const closeChiefLogsModalBtn = document.getElementById('closeChiefLogsModal');
        const showMyChiefLogsBtn = document.getElementById('showMyChiefLogsBtn');
        const showAllChiefLogsBtn = document.getElementById('showAllChiefLogsBtn');
        let chiefLogsMode = 'mine';
        const chiefLogsPerPage = 10;
        const chiefLogsPageByMode = { mine: 1, all: 1 };

        function formatChiefLogDate(value) {
            const date = value ? new Date(value) : null;
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return 'Unknown date';
            }

            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function summarizeChiefUserAgent(userAgent) {
            if (!userAgent) {
                return 'Unavailable';
            }

            const trimmed = String(userAgent).trim();
            return trimmed.length > 140 ? `${trimmed.slice(0, 137)}...` : trimmed;
        }

        function renderChiefLogsLoadingState() {
            if (!chiefLogsContainer) {
                return;
            }

            chiefLogsContainer.innerHTML = `
                <div class="space-y-3">
                    ${Array.from({ length: 4 }).map(() => `
                        <div class="chief-log-skeleton">
                            <div class="chief-log-skeleton-row">
                                <div class="chief-log-skeleton-avatar"></div>
                                <div class="chief-log-skeleton-content">
                                    <div class="chief-log-skeleton-line w-60"></div>
                                    <div class="chief-log-skeleton-line w-45"></div>
                                    <div class="chief-log-skeleton-line w-90"></div>
                                    <div class="chief-log-skeleton-line w-75"></div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function setChiefLogsMode(mode) {
            chiefLogsMode = chiefLogsAllowAll && mode === 'all' ? 'all' : 'mine';

            if (chiefLogsTitle) {
                chiefLogsTitle.textContent = chiefLogsMode === 'all' ? 'All User Activities' : 'My Logs';
            }

            if (chiefLogsSubtitle) {
                chiefLogsSubtitle.textContent = chiefLogsMode === 'all'
                    ? 'Complete activity history from the user_logs table.'
                    : 'Your recorded activity in the system.';
            }

            showMyChiefLogsBtn?.classList.toggle('bg-slate-800', chiefLogsMode === 'mine');
            showMyChiefLogsBtn?.classList.toggle('text-white', chiefLogsMode === 'mine');
            showMyChiefLogsBtn?.classList.toggle('border-slate-800', chiefLogsMode === 'mine');
            showMyChiefLogsBtn?.classList.toggle('bg-slate-100', chiefLogsMode !== 'mine');
            showMyChiefLogsBtn?.classList.toggle('text-slate-700', chiefLogsMode !== 'mine');
            showMyChiefLogsBtn?.classList.toggle('border-slate-200', chiefLogsMode !== 'mine');

            showAllChiefLogsBtn?.classList.toggle('bg-amber-600', chiefLogsMode === 'all');
            showAllChiefLogsBtn?.classList.toggle('text-white', chiefLogsMode === 'all');
            showAllChiefLogsBtn?.classList.toggle('border-amber-600', chiefLogsMode === 'all');
            showAllChiefLogsBtn?.classList.toggle('bg-amber-50', chiefLogsMode !== 'all');
            showAllChiefLogsBtn?.classList.toggle('text-amber-700', chiefLogsMode !== 'all');
            showAllChiefLogsBtn?.classList.toggle('border-amber-100', chiefLogsMode !== 'all');
        }

        function renderChiefLogsPagination(meta = {}) {
            const currentPage = Number(meta?.current_page || 1);
            const lastPage = Number(meta?.last_page || 1);

            if (lastPage <= 1) {
                return '';
            }

            return `
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage <= 1 ? 'disabled' : `onclick="loadChiefLogsPage(${currentPage - 1})"`}>
                        Previous
                    </button>
                    <span class="text-xs font-medium text-slate-500">Page ${currentPage} of ${lastPage}</span>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage >= lastPage ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage >= lastPage ? 'disabled' : `onclick="loadChiefLogsPage(${currentPage + 1})"`}>
                        Next
                    </button>
                </div>
            `;
        }

        function renderChiefLogs(logs = [], meta = {}) {
            if (!chiefLogsContainer || !chiefLogsSummary) {
                return;
            }

            const totalLogs = Number(meta?.total || logs.length || 0);
            const from = Number(meta?.from || 0);
            const to = Number(meta?.to || 0);
            chiefLogsSummary.textContent = totalLogs
                ? `Showing ${from}-${to} of ${totalLogs} log entries`
                : '0 log entries found';

            if (!logs.length) {
                chiefLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <div class="text-slate-400 text-3xl mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <p class="text-sm font-semibold text-slate-700">No logs available yet</p>
                        <p class="text-xs text-slate-500 mt-1">Activity records will appear here once actions are logged.</p>
                    </div>
                `;
                return;
            }

            chiefLogsContainer.innerHTML = `
                <div class="space-y-3">
                    ${logs.map((log) => `
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-bold text-slate-800 leading-6">${log.action || 'Unknown action'}</p>
                                        <span class="text-[11px] font-semibold text-slate-400 whitespace-nowrap">#${log.user_log_id}</span>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        ${chiefLogsMode === 'all' ? `
                                            <div class="flex items-start gap-2 text-sm text-slate-600">
                                                <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-user"></i></span>
                                                <span><span class="font-semibold text-slate-700">User:</span> ${log.user_name || 'Unknown user'}${log.role ? ` (${log.role})` : ''}</span>
                                            </div>
                                            <div class="flex items-start gap-2 text-sm text-slate-600">
                                                <span class="mt-0.5 text-slate-400"><i class="fa-regular fa-envelope"></i></span>
                                                <span><span class="font-semibold text-slate-700">Email:</span> ${log.email || 'Unavailable'}</span>
                                            </div>
                                        ` : ''}
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-regular fa-calendar"></i></span>
                                            <span><span class="font-semibold text-slate-700">Date & Time:</span> ${formatChiefLogDate(log.created_at)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-desktop"></i></span>
                                            <span class="break-words"><span class="font-semibold text-slate-700">User Agent:</span> ${summarizeChiefUserAgent(log.user_agent)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-globe"></i></span>
                                            <span><span class="font-semibold text-slate-700">IP Address:</span> ${log.ip_address || 'Unavailable'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                ${renderChiefLogsPagination(meta)}
            `;
        }

        async function loadChiefLogs(mode = chiefLogsMode, page = 1) {
            if (!chiefLogsContainer || !chiefLogsSummary) {
                return;
            }

            setChiefLogsMode(mode);
            chiefLogsPageByMode[chiefLogsMode] = Math.max(1, Number(page || 1));
            chiefLogsSummary.textContent = chiefLogsMode === 'all' ? 'Loading all user activities...' : 'Loading your activity logs...';
            renderChiefLogsLoadingState();

            try {
                const endpoint = chiefLogsAllowAll && chiefLogsMode === 'all' ? '{{ route('user-logs.all') }}' : '{{ route('user-logs.index') }}';
                const response = await fetch(`${endpoint}?page=${chiefLogsPageByMode[chiefLogsMode]}&limit=${chiefLogsPerPage}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load logs (${response.status})`);
                }

                const payload = await response.json();
                renderChiefLogs(Array.isArray(payload?.data) ? payload.data : [], payload?.meta || {});
            } catch (error) {
                console.error('Error loading chief logs:', error);
                chiefLogsSummary.textContent = chiefLogsMode === 'all'
                    ? 'Unable to load all user activities'
                    : 'Unable to load your activity logs';
                chiefLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-red-100 bg-red-50 px-6 py-8 text-center">
                        <p class="text-sm font-semibold text-red-700">Failed to load logs</p>
                        <p class="text-xs text-red-500 mt-1">Please try again in a moment.</p>
                    </div>
                `;
            }
        }

        function openChiefLogsModal(mode = 'mine') {
            userDropdownMenu?.classList.remove('show');
            if (chiefLogsModal) {
                chiefLogsModal.classList.remove('hidden');
                chiefLogsModal.classList.add('flex');
            }
            loadChiefLogs(mode, 1);
        }

        function closeChiefLogsModal() {
            if (chiefLogsModal) {
                chiefLogsModal.classList.add('hidden');
                chiefLogsModal.classList.remove('flex');
            }
        }

        window.openChiefLogsModal = openChiefLogsModal;
        window.closeChiefLogsModal = closeChiefLogsModal;
        window.loadChiefLogsPage = function (page) {
            loadChiefLogs(chiefLogsMode, page);
        };

        logTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                openChiefLogsModal();
            });
        });

        refreshChiefLogsBtn?.addEventListener('click', function () {
            loadChiefLogs(chiefLogsMode, chiefLogsPageByMode[chiefLogsMode] || 1);
        });
        showMyChiefLogsBtn?.addEventListener('click', function () {
            loadChiefLogs('mine', 1);
        });
        if (chiefLogsAllowAll) {
            showAllChiefLogsBtn?.addEventListener('click', function () {
                loadChiefLogs('all', 1);
            });
        }
        closeChiefLogsModalBtn?.addEventListener('click', closeChiefLogsModal);
        chiefLogsModal?.addEventListener('click', function (e) {
            if (e.target === chiefLogsModal) {
                closeChiefLogsModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && chiefLogsModal && !chiefLogsModal.classList.contains('hidden')) {
                closeChiefLogsModal();
            }
        });
    });
</script>
