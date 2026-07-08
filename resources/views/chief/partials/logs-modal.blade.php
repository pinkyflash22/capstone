@php
    $chiefLogsAllowAll = $chiefLogsAllowAll ?? true;
@endphp

<style>
    .chief-log-skeleton {
        border: 1px solid rgb(226 232 240);
        background: rgba(248, 250, 252, 0.92);
        border-radius: 1rem;
        padding: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .chief-log-skeleton-row {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .chief-log-skeleton-avatar {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 1rem;
        background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
        background-size: 200% 100%;
        animation: chief-log-shimmer 1.5s infinite linear;
        flex-shrink: 0;
    }

    .chief-log-skeleton-content {
        flex: 1;
        min-width: 0;
    }

    .chief-log-skeleton-line {
        height: 0.8rem;
        border-radius: 9999px;
        margin-bottom: 0.75rem;
        background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
        background-size: 200% 100%;
        animation: chief-log-shimmer 1.5s infinite linear;
    }

    .chief-log-skeleton-line.w-60 { width: 60%; }
    .chief-log-skeleton-line.w-45 { width: 45%; }
    .chief-log-skeleton-line.w-90 { width: 90%; }
    .chief-log-skeleton-line.w-75 { width: 75%; }

    @keyframes chief-log-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    @media (max-width: 768px) {
        #chiefLogsModal {
            align-items: flex-end;
            padding: 10px;
        }

        #chiefLogsModal > div {
            width: 100%;
            max-width: 100%;
            max-height: min(84dvh, 760px);
            border-radius: 24px 24px 18px 18px;
        }

        #chiefLogsModal > div::before {
            content: "";
            display: block;
            width: 52px;
            height: 5px;
            border-radius: 999px;
            background: #cbd5e1;
            margin: 14px auto 0;
        }

        #chiefLogsModal .px-6,
        #chiefLogsModal .py-5,
        #chiefLogsModal .py-4 {
            padding-left: 16px !important;
            padding-right: 16px !important;
        }

        #chiefLogsContainer {
            max-height: min(62dvh, 560px);
            padding: 16px !important;
        }
    }
</style>

<div id="chiefLogsModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm hidden items-center justify-center z-[5000] p-4">
    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 bg-slate-50">
            <div>
                <h2 id="chiefLogsTitle" class="text-lg font-bold text-slate-800">My Logs</h2>
                <p id="chiefLogsSubtitle" class="text-sm text-slate-500">Your recorded activity in the system.</p>
            </div>
            <button type="button" id="closeChiefLogsModal" class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-100">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <p id="chiefLogsSummary" class="text-sm text-slate-500">Loading activity logs...</p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="showMyChiefLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 text-sm font-semibold">
                    <i class="fa-solid fa-user"></i>
                    My Activity
                </button>
                @if ($chiefLogsAllowAll)
                    <button type="button" id="showAllChiefLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 text-sm font-semibold">
                        <i class="fa-solid fa-users"></i>
                        Check All User Activities
                    </button>
                @endif
                <button type="button" id="refreshChiefLogsBtn" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-orange-50 text-orange-700 border border-orange-100 hover:bg-orange-100 text-sm font-semibold">
                    <i class="fa-solid fa-rotate"></i>
                    Refresh
                </button>
            </div>
        </div>
        <div id="chiefLogsContainer" class="max-h-[70vh] overflow-y-auto px-6 py-5 space-y-4 bg-white">
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
        </div>
    </div>
</div>
