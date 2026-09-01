<style>
    @media (max-width: 768px) {
        #firefighterMyLogsModal {
            align-items: flex-end;
            padding: 10px;
        }

        #firefighterMyLogsModal > div {
            width: 100%;
            max-width: 100%;
            max-height: min(84dvh, 760px);
            border-radius: 24px 24px 18px 18px;
        }

        #firefighterMyLogsModal > div::before {
            content: "";
            display: block;
            width: 52px;
            height: 5px;
            border-radius: 999px;
            background: #cbd5e1;
            margin: 14px auto 0;
        }

        #firefighterMyLogsModal .px-6,
        #firefighterMyLogsModal .py-5,
        #firefighterMyLogsModal .py-4 {
            padding-left: 16px !important;
            padding-right: 16px !important;
        }

        #firefighterMyLogsModal #firefighterMyLogsContainer {
            max-height: min(62dvh, 560px);
            padding: 16px !important;
        }
    }
</style>

<div id="firefighterMyLogsModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm hidden items-center justify-center z-[4000] p-4">
    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 bg-slate-50">
            <div>
                <h2 class="text-lg font-bold text-slate-800">My Logs</h2>
                <p class="text-sm text-slate-500">Your recorded activity in the firefighter portal.</p>
            </div>
            <button type="button" id="closeFirefighterMyLogsModal" class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-100">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
            <p id="firefighterMyLogsSummary" class="text-sm text-slate-500">Loading your activity logs...</p>
            <button type="button" id="refreshFirefighterMyLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-50 text-orange-700 border border-orange-100 hover:bg-orange-100 text-sm font-semibold">
                <i class="fa-solid fa-rotate"></i>
                Refresh
            </button>
        </div>
        <div id="firefighterMyLogsContainer" class="max-h-[70vh] overflow-y-auto px-6 py-5 space-y-4 bg-white">
            <div class="text-sm text-slate-500">Loading logs...</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const myLogsModal = document.getElementById('firefighterMyLogsModal');
        const myLogsContainer = document.getElementById('firefighterMyLogsContainer');
        const myLogsSummary = document.getElementById('firefighterMyLogsSummary');
        const refreshMyLogsBtn = document.getElementById('refreshFirefighterMyLogsBtn');
        const closeMyLogsModalBtn = document.getElementById('closeFirefighterMyLogsModal');
        let firefighterLogsCurrentPage = 1;
        const firefighterLogsPerPage = 10;

        function formatLogDate(value) {
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

        function summarizeUserAgent(userAgent) {
            if (!userAgent) {
                return 'Unavailable';
            }

            const trimmed = String(userAgent).trim();
            return trimmed.length > 140 ? `${trimmed.slice(0, 137)}...` : trimmed;
        }

        function renderMyLogsPagination(meta = {}) {
            const currentPage = Number(meta?.current_page || 1);
            const lastPage = Number(meta?.last_page || 1);

            if (lastPage <= 1) {
                return '';
            }

            return `
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage <= 1 ? 'disabled' : `onclick="loadFirefighterLogsPage(${currentPage - 1})"`}>
                        Previous
                    </button>
                    <span class="text-xs font-medium text-slate-500">Page ${currentPage} of ${lastPage}</span>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 ${currentPage >= lastPage ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-50'}" ${currentPage >= lastPage ? 'disabled' : `onclick="loadFirefighterLogsPage(${currentPage + 1})"`}>
                        Next
                    </button>
                </div>
            `;
        }

        function renderMyLogs(logs, meta = {}) {
            if (!myLogsContainer || !myLogsSummary) {
                return;
            }

            const totalLogs = Number(meta?.total || logs.length || 0);
            const from = Number(meta?.from || 0);
            const to = Number(meta?.to || 0);
            myLogsSummary.textContent = totalLogs
                ? `Showing ${from}-${to} of ${totalLogs} log entries`
                : '0 log entries found';

            if (!logs.length) {
                myLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <div class="text-slate-400 text-3xl mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <p class="text-sm font-semibold text-slate-700">No logs available yet</p>
                        <p class="text-xs text-slate-500 mt-1">Your future activity will appear here.</p>
                    </div>
                `;
                return;
            }

            myLogsContainer.innerHTML = `
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
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-regular fa-calendar"></i></span>
                                            <span><span class="font-semibold text-slate-700">Date & Time:</span> ${formatLogDate(log.created_at)}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="mt-0.5 text-slate-400"><i class="fa-solid fa-desktop"></i></span>
                                            <span class="break-words"><span class="font-semibold text-slate-700">Device information:</span> ${log.device_info || summarizeUserAgent(log.user_agent)}</span>
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
                ${renderMyLogsPagination(meta)}
            `;
        }

        async function loadMyLogs(page = 1) {
            if (!myLogsContainer || !myLogsSummary) {
                return;
            }

            firefighterLogsCurrentPage = Math.max(1, Number(page || 1));
            myLogsSummary.textContent = 'Loading your activity logs...';
            myLogsContainer.innerHTML = '<div class="text-sm text-slate-500">Loading logs...</div>';

            try {
                const response = await fetch(`{{ route('user-logs.index') }}?page=${firefighterLogsCurrentPage}&limit=${firefighterLogsPerPage}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load logs (${response.status})`);
                }

                const payload = await response.json();
                renderMyLogs(Array.isArray(payload?.data) ? payload.data : [], payload?.meta || {});
            } catch (error) {
                console.error('Error loading firefighter user logs:', error);
                myLogsSummary.textContent = 'Unable to load your activity logs';
                myLogsContainer.innerHTML = `
                    <div class="rounded-2xl border border-red-100 bg-red-50 px-6 py-8 text-center">
                        <p class="text-sm font-semibold text-red-700">Failed to load logs</p>
                        <p class="text-xs text-red-500 mt-1">Please try again in a moment.</p>
                    </div>
                `;
            }
        }

        function openMyLogsModal() {
            document.getElementById('ffUserDropdownMenu')?.classList.remove('show');
            document.getElementById('userDropdownMenu')?.classList.remove('show');
            if (myLogsModal) {
                myLogsModal.classList.remove('hidden');
                myLogsModal.classList.add('flex');
                document.body.classList.add('menu-open');
            }
            loadMyLogs(1);
        }

        function closeMyLogsModal() {
            if (myLogsModal) {
                myLogsModal.classList.add('hidden');
                myLogsModal.classList.remove('flex');
            }
            document.body.classList.remove('menu-open');
        }

        window.openMyLogsModal = openMyLogsModal;
        window.closeMyLogsModal = closeMyLogsModal;
        window.loadFirefighterLogsPage = function (page) {
            loadMyLogs(page);
        };

        refreshMyLogsBtn?.addEventListener('click', function () {
            loadMyLogs(firefighterLogsCurrentPage);
        });
        closeMyLogsModalBtn?.addEventListener('click', closeMyLogsModal);
        myLogsModal?.addEventListener('click', function (event) {
            if (event.target === myLogsModal) {
                closeMyLogsModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && myLogsModal && !myLogsModal.classList.contains('hidden')) {
                closeMyLogsModal();
            }
        });
    });
</script>
