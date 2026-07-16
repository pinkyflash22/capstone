<style>
    .citizen-logout-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(6px);
        z-index: 7000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .citizen-logout-modal-backdrop.active {
        display: flex;
    }

    .citizen-logout-modal {
        position: relative;
        width: min(100%, 22rem);
        background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
        border: 1px solid rgba(245, 158, 11, 0.14);
        border-radius: 1.5rem;
        box-shadow: 0 20px 44px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .citizen-logout-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 100%);
        border-bottom: 1px solid rgba(245, 158, 11, 0.12);
    }

    .citizen-logout-modal-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .citizen-logout-modal-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #fff;
        box-shadow: none;
        font-size: 1.1rem;
    }

    .citizen-logout-modal-close {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: rgba(255, 255, 255, 0.9);
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .citizen-logout-modal-close:hover {
        color: #0f172a;
        background: #fff;
    }

    .citizen-logout-modal-body {
        padding: 1.1rem 1.25rem 0.25rem;
    }

    .citizen-logout-modal-copy {
        color: #475569;
        font-size: 1rem;
        line-height: 1.55;
        text-align: center;
    }

    .citizen-logout-modal-footer {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem 1.25rem;
    }

    .citizen-logout-cancel,
    .citizen-logout-confirm {
        border: none;
        border-radius: 999px;
        min-width: 7.5rem;
        min-height: 2.75rem;
        padding: 0.75rem 1rem;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .citizen-logout-cancel {
        background: #f8fafc;
        color: #475569;
        border: 1px solid rgba(203, 213, 225, 0.75);
    }

    .citizen-logout-cancel:hover {
        background: #f1f5f9;
    }

    .citizen-logout-confirm {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(220, 38, 38, 0.24);
    }

    .citizen-logout-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 34px rgba(220, 38, 38, 0.3);
    }

    @media (max-width: 480px) {
        .citizen-logout-modal-backdrop {
            align-items: center;
            padding: 1rem;
        }

        .citizen-logout-modal {
            width: min(100%, 20.5rem);
            border-radius: 1.35rem;
        }

        .citizen-logout-modal-header {
            justify-content: center;
            padding: 1.1rem 3.75rem 0.75rem 1.1rem;
        }

        .citizen-logout-modal-title {
            text-align: center;
        }

        .citizen-logout-modal-title > div:last-child p {
            display: none;
        }

        .citizen-logout-modal-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
        }

        .citizen-logout-modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .citizen-logout-cancel,
        .citizen-logout-confirm {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div id="citizenLogoutConfirmModal" class="citizen-logout-modal-backdrop" aria-hidden="true">
    <div class="citizen-logout-modal" role="dialog" aria-modal="true" aria-labelledby="citizenLogoutConfirmTitle">
        <div class="citizen-logout-modal-header">
            <div class="citizen-logout-modal-title">
                <div class="citizen-logout-modal-icon">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </div>
                <div>
                    <h2 id="citizenLogoutConfirmTitle" class="text-lg font-bold text-slate-800">Log out?</h2>
                    <p class="text-sm text-slate-500">End this session.</p>
                </div>
            </div>
            <button type="button" id="citizenLogoutConfirmClose" class="citizen-logout-modal-close" aria-label="Close logout confirmation">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="citizen-logout-modal-body">
            <p class="citizen-logout-modal-copy">
                Are you sure you want to log out?
            </p>
        </div>
        <div class="citizen-logout-modal-footer">
            <button type="button" id="citizenLogoutCancelBtn" class="citizen-logout-cancel">No</button>
            <button type="button" id="citizenLogoutConfirmBtn" class="citizen-logout-confirm">Yes, log out</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const logoutForms = Array.from(document.querySelectorAll('form[action="{{ route('logout') }}"]'));
        const logoutModal = document.getElementById('citizenLogoutConfirmModal');
        const logoutConfirmBtn = document.getElementById('citizenLogoutConfirmBtn');
        const logoutCancelBtn = document.getElementById('citizenLogoutCancelBtn');
        const logoutCloseBtn = document.getElementById('citizenLogoutConfirmClose');
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
