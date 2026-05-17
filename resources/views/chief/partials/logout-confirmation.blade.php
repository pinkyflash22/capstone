<style>
    .chief-logout-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(6px);
        z-index: 7000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .chief-logout-modal-backdrop.active {
        display: flex;
    }

    .chief-logout-modal {
        width: min(100%, 29rem);
        background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
        border: 1px solid rgba(245, 158, 11, 0.14);
        border-radius: 1.75rem;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }

    .chief-logout-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 100%);
        border-bottom: 1px solid rgba(245, 158, 11, 0.12);
    }

    .chief-logout-modal-title {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .chief-logout-modal-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #fff;
        box-shadow: 0 12px 28px rgba(249, 115, 22, 0.28);
        font-size: 1.1rem;
    }

    .chief-logout-modal-close {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: rgba(255, 255, 255, 0.9);
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .chief-logout-modal-close:hover {
        color: #0f172a;
        background: #fff;
    }

    .chief-logout-modal-body {
        padding: 1.5rem;
    }

    .chief-logout-modal-copy {
        color: #475569;
        font-size: 0.96rem;
        line-height: 1.7;
    }

    .chief-logout-modal-panel {
        margin-top: 1rem;
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fff7ed;
        border: 1px solid rgba(251, 146, 60, 0.18);
        color: #9a3412;
        font-size: 0.88rem;
        line-height: 1.6;
    }

    .chief-logout-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .chief-logout-cancel,
    .chief-logout-confirm {
        border: none;
        border-radius: 999px;
        padding: 0.8rem 1.25rem;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .chief-logout-cancel {
        background: #f8fafc;
        color: #475569;
        border: 1px solid rgba(203, 213, 225, 0.75);
    }

    .chief-logout-cancel:hover {
        background: #f1f5f9;
    }

    .chief-logout-confirm {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(220, 38, 38, 0.24);
    }

    .chief-logout-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 34px rgba(220, 38, 38, 0.3);
    }
</style>

<div id="chiefLogoutConfirmModal" class="chief-logout-modal-backdrop" aria-hidden="true">
    <div class="chief-logout-modal" role="dialog" aria-modal="true" aria-labelledby="chiefLogoutConfirmTitle">
        <div class="chief-logout-modal-header">
            <div class="chief-logout-modal-title">
                <div class="chief-logout-modal-icon">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </div>
                <div>
                    <h2 id="chiefLogoutConfirmTitle" class="text-lg font-bold text-slate-800">Confirm Logout</h2>
                    <p class="text-sm text-slate-500">Securely end this chief command session.</p>
                </div>
            </div>
            <button type="button" id="chiefLogoutConfirmClose" class="chief-logout-modal-close" aria-label="Close logout confirmation">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="chief-logout-modal-body">
            <p class="chief-logout-modal-copy">
                You are about to sign out of your station commander workspace. Make sure any in-progress updates have been saved before you continue.
            </p>
            <div class="chief-logout-modal-panel">
                Logging out will close access to dashboards, reports, and command tools until you sign in again.
            </div>
        </div>
        <div class="chief-logout-modal-footer">
            <button type="button" id="chiefLogoutCancelBtn" class="chief-logout-cancel">Stay Signed In</button>
            <button type="button" id="chiefLogoutConfirmBtn" class="chief-logout-confirm">Yes, Logout</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const logoutForms = Array.from(document.querySelectorAll('form[action="{{ route('logout') }}"]'));
        const logoutModal = document.getElementById('chiefLogoutConfirmModal');
        const logoutConfirmBtn = document.getElementById('chiefLogoutConfirmBtn');
        const logoutCancelBtn = document.getElementById('chiefLogoutCancelBtn');
        const logoutCloseBtn = document.getElementById('chiefLogoutConfirmClose');
        let pendingLogoutForm = null;

        if (!logoutForms.length || !logoutModal || !logoutConfirmBtn) {
            return;
        }

        function openLogoutModal(form) {
            pendingLogoutForm = form;
            logoutModal.classList.add('active');
            logoutModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
        }

        function closeLogoutModal() {
            pendingLogoutForm = null;
            logoutModal.classList.remove('active');
            logoutModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('menu-open');
        }

        logoutForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                openLogoutModal(form);
            });
        });

        logoutConfirmBtn.addEventListener('click', function () {
            if (pendingLogoutForm) {
                pendingLogoutForm.submit();
            }
        });

        logoutCancelBtn?.addEventListener('click', closeLogoutModal);
        logoutCloseBtn?.addEventListener('click', closeLogoutModal);

        logoutModal.addEventListener('click', function (event) {
            if (event.target === logoutModal) {
                closeLogoutModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && logoutModal.classList.contains('active')) {
                closeLogoutModal();
            }
        });
    });
</script>
