<style>
    .firefighter-logout-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(6px);
        z-index: 10020;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .firefighter-logout-modal-backdrop.active {
        display: flex;
    }

    .firefighter-logout-modal {
        position: relative;
        width: min(100%, 22rem);
        background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
        border: 1px solid rgba(245, 158, 11, 0.14);
        border-radius: 1.5rem;
        box-shadow: 0 20px 44px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .firefighter-logout-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 100%);
        border-bottom: 1px solid rgba(245, 158, 11, 0.12);
    }

    .firefighter-logout-modal-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .firefighter-logout-modal-icon {
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

    .firefighter-logout-modal-close {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        background: rgba(255, 255, 255, 0.9);
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .firefighter-logout-modal-close:hover {
        color: #0f172a;
        background: #fff;
    }

    .firefighter-logout-modal-body {
        padding: 1.1rem 1.25rem 0.25rem;
    }

    .firefighter-logout-modal-copy {
        color: #475569;
        font-size: 1rem;
        line-height: 1.55;
        text-align: center;
    }

    .firefighter-logout-modal-footer {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem 1.25rem;
    }

    .firefighter-logout-cancel,
    .firefighter-logout-confirm {
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

    .firefighter-logout-cancel {
        background: #f8fafc;
        color: #475569;
        border: 1px solid rgba(203, 213, 225, 0.75);
    }

    .firefighter-logout-cancel:hover {
        background: #f1f5f9;
    }

    .firefighter-logout-confirm {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(220, 38, 38, 0.24);
    }

    .firefighter-logout-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 34px rgba(220, 38, 38, 0.3);
    }

    @media (max-width: 480px) {
        .firefighter-logout-modal-backdrop {
            align-items: center;
            padding: 1rem;
        }

        .firefighter-logout-modal {
            width: min(100%, 20.5rem);
            border-radius: 1.35rem;
        }

        .firefighter-logout-modal-header {
            justify-content: center;
            padding: 1.1rem 3.75rem 0.75rem 1.1rem;
        }

        .firefighter-logout-modal-title {
            text-align: center;
        }

        .firefighter-logout-modal-title > div:last-child p {
            display: none;
        }

        .firefighter-logout-modal-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
        }

        .firefighter-logout-modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .firefighter-logout-cancel,
        .firefighter-logout-confirm {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div id="firefighterLogoutConfirmModal" class="firefighter-logout-modal-backdrop" aria-hidden="true">
    <div class="firefighter-logout-modal" role="dialog" aria-modal="true" aria-labelledby="firefighterLogoutConfirmTitle">
        <div class="firefighter-logout-modal-header">
            <div class="firefighter-logout-modal-title">
                <div class="firefighter-logout-modal-icon">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </div>
                <div>
                    <h2 id="firefighterLogoutConfirmTitle" class="text-lg font-bold text-slate-800">Log out?</h2>
                    <p class="text-sm text-slate-500">End this session.</p>
                </div>
            </div>
            <button type="button" id="firefighterLogoutConfirmClose" class="firefighter-logout-modal-close" aria-label="Close logout confirmation">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="firefighter-logout-modal-body">
            <p class="firefighter-logout-modal-copy">
                Are you sure you want to log out?
            </p>
        </div>
        <div class="firefighter-logout-modal-footer">
            <button type="button" id="firefighterLogoutCancelBtn" class="firefighter-logout-cancel">No</button>
            <button type="button" id="firefighterLogoutConfirmBtn" class="firefighter-logout-confirm">Yes, log out</button>
        </div>
    </div>
</div>

<script>
    (function () {
        if (window.__firefighterLogoutConfirmationBound) {
            return;
        }

        const logoutModal = document.getElementById('firefighterLogoutConfirmModal');
        const logoutConfirmBtn = document.getElementById('firefighterLogoutConfirmBtn');
        const logoutCancelBtn = document.getElementById('firefighterLogoutCancelBtn');
        const logoutCloseBtn = document.getElementById('firefighterLogoutConfirmClose');
        let pendingLogoutForm = null;

        if (!logoutModal || !logoutConfirmBtn) {
            return;
        }

        window.__firefighterLogoutConfirmationBound = true;

        function openLogoutModal(form) {
            pendingLogoutForm = form;
            document.querySelectorAll('.ff-mobile-panel.is-open').forEach(function (panel) {
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
            });
            logoutModal.classList.add('active');
            logoutModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
            window.requestAnimationFrame(function () {
                logoutCancelBtn?.focus();
            });
        }

        function closeLogoutModal() {
            pendingLogoutForm = null;
            logoutModal.classList.remove('active');
            logoutModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('menu-open');
        }

        // The firefighter shell swaps page content without a full reload. Delegation
        // keeps confirmation working for logout forms inserted by that navigation.
        document.addEventListener('submit', function (event) {
            const form = event.target instanceof HTMLFormElement
                ? event.target
                : event.target?.closest?.('form');

            if (!form?.matches('[data-firefighter-logout-form]')) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            openLogoutModal(form);
        }, true);

        logoutConfirmBtn.addEventListener('click', function () {
            if (pendingLogoutForm) {
                const form = pendingLogoutForm;
                pendingLogoutForm = null;
                logoutConfirmBtn.disabled = true;
                logoutConfirmBtn.textContent = 'Logging out...';
                HTMLFormElement.prototype.submit.call(form);
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
    })();
</script>
