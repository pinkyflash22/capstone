@php
    $showPwaInstallButton = $showPwaInstallButton ?? false;
@endphp

@laravelPwa
@pwaUpdateNotifier

@if ($showPwaInstallButton)
    <style>
        .pyrosense-pwa-install {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 1400;
            display: none;
            align-items: center;
            gap: 10px;
            border: 0;
            border-radius: 999px;
            padding: 14px 18px;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 14px 32px rgba(198, 61, 38, 0.28);
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
        }

        .pyrosense-pwa-install__icon {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .pyrosense-pwa-sheet {
            position: fixed;
            inset: 0;
            z-index: 1500;
            display: none;
            align-items: flex-end;
            justify-content: center;
            background: rgba(15, 23, 42, 0.52);
            padding: 16px;
        }

        .pyrosense-pwa-sheet.is-open {
            display: flex;
        }

        .pyrosense-pwa-sheet__card {
            width: min(100%, 420px);
            border-radius: 26px;
            background: #fffaf4;
            color: #1f2937;
            border: 1px solid rgba(198, 61, 38, 0.12);
            box-shadow: 0 26px 60px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .pyrosense-pwa-sheet__header {
            padding: 18px 20px 12px;
            background: linear-gradient(135deg, rgba(198, 61, 38, 0.12), rgba(209, 138, 22, 0.14));
        }

        .pyrosense-pwa-sheet__title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .pyrosense-pwa-sheet__body {
            padding: 16px 20px 18px;
            font-size: 14px;
            line-height: 1.6;
            color: #4b5563;
        }

        .pyrosense-pwa-sheet__body ol {
            margin: 12px 0 0 18px;
            padding: 0;
        }

        .pyrosense-pwa-sheet__body li + li {
            margin-top: 8px;
        }

        .pyrosense-pwa-sheet__actions {
            display: flex;
            justify-content: flex-end;
            padding: 0 20px 20px;
        }

        .pyrosense-pwa-sheet__close {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            background: #f3e5dc;
            color: #9a3412;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 640px) {
            .pyrosense-pwa-install {
                right: 14px;
                bottom: calc(14px + env(safe-area-inset-bottom));
                left: 14px;
                justify-content: center;
            }
        }
    </style>

    @pwaInstallButton

    <button id="pyrosense-pwa-install" class="pyrosense-pwa-install" type="button" aria-haspopup="dialog" aria-controls="pyrosense-pwa-sheet">
        <span class="pyrosense-pwa-install__icon">+</span>
        <span>Install PyroSense</span>
    </button>

    <div id="pyrosense-pwa-sheet" class="pyrosense-pwa-sheet" aria-hidden="true">
        <div class="pyrosense-pwa-sheet__card" role="dialog" aria-modal="true" aria-labelledby="pyrosense-pwa-title">
            <div class="pyrosense-pwa-sheet__header">
                <h2 id="pyrosense-pwa-title" class="pyrosense-pwa-sheet__title">Install PyroSense</h2>
            </div>
            <div id="pyrosense-pwa-sheet-body" class="pyrosense-pwa-sheet__body"></div>
            <div class="pyrosense-pwa-sheet__actions">
                <button id="pyrosense-pwa-close" class="pyrosense-pwa-sheet__close" type="button">Close</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const installBtn = document.getElementById('pyrosense-pwa-install');
            const fallbackSheet = document.getElementById('pyrosense-pwa-sheet');
            const fallbackBody = document.getElementById('pyrosense-pwa-sheet-body');
            const fallbackClose = document.getElementById('pyrosense-pwa-close');
            const packageBtn = document.getElementById('pwa-install-btn');

            if (!installBtn || !fallbackSheet || !fallbackBody || !fallbackClose) {
                return;
            }

            if (packageBtn) {
                packageBtn.style.display = 'none';
            }

            const isStandalone = function () {
                return window.laravelPwaInstall?.isStandalone?.()
                    || window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone === true;
            };

            const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
            const isSafari = /safari/i.test(window.navigator.userAgent) && !/chrome|crios|fxios|edgios/i.test(window.navigator.userAgent);

            const openSheet = function (html) {
                fallbackBody.innerHTML = html;
                fallbackSheet.classList.add('is-open');
                fallbackSheet.setAttribute('aria-hidden', 'false');
            };

            const closeSheet = function () {
                fallbackSheet.classList.remove('is-open');
                fallbackSheet.setAttribute('aria-hidden', 'true');
            };

            const showInstallButton = function () {
                if (!isStandalone()) {
                    installBtn.style.display = 'inline-flex';
                }
            };

            const updateInstallButton = function () {
                if (isStandalone()) {
                    installBtn.style.display = 'none';
                    closeSheet();
                    return;
                }

                if (window.laravelPwaInstall?.canInstall?.()) {
                    installBtn.style.display = 'inline-flex';
                    installBtn.dataset.mode = 'prompt';
                    return;
                }

                if (isIos && isSafari) {
                    installBtn.style.display = 'inline-flex';
                    installBtn.dataset.mode = 'ios';
                    return;
                }

                installBtn.style.display = 'none';
            };

            installBtn.addEventListener('click', async function () {
                const mode = installBtn.dataset.mode;

                if (mode === 'prompt' && window.laravelPwaInstall?.showPrompt) {
                    await window.laravelPwaInstall.showPrompt();
                    window.setTimeout(updateInstallButton, 250);
                    return;
                }

                openSheet(
                    '<p>Add PyroSense to your home screen for the app-style experience.</p>' +
                    '<ol>' +
                    '<li>Tap the <strong>Share</strong> button in Safari.</li>' +
                    '<li>Select <strong>Add to Home Screen</strong>.</li>' +
                    '<li>Tap <strong>Add</strong> to install PyroSense.</li>' +
                    '</ol>'
                );
            });

            fallbackClose.addEventListener('click', closeSheet);
            fallbackSheet.addEventListener('click', function (event) {
                if (event.target === fallbackSheet) {
                    closeSheet();
                }
            });

            window.addEventListener('pwa-installable', function () {
                showInstallButton();
                updateInstallButton();
            });

            window.addEventListener('pwa-installed', function () {
                updateInstallButton();
            });

            document.addEventListener('DOMContentLoaded', function () {
                updateInstallButton();
            });
        })();
    </script>
@endif
