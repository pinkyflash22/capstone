(function () {
    const DRAFT_PREFIX = 'pyrosense-form-draft';
    const config = window.pyroOfflineConfig || {};
    const accountScope = String(config.userId || 'guest');
    const draftTimers = new WeakMap();

    function formAction(form) {
        try {
            return new URL(form.action || window.location.href, window.location.origin);
        } catch (error) {
            return null;
        }
    }

    function isSensitiveForm(form) {
        const path = formAction(form)?.pathname || '';
        return path.startsWith('/auth')
            || /\/(?:login|logout|password|otp)(?:\/|$)/i.test(path)
            || Boolean(form.querySelector('input[type="password"]'));
    }

    function isDestructiveForm(form) {
        const intendedMethod = String(form.querySelector('input[name="_method"]')?.value || form.method || 'GET').toUpperCase();
        return intendedMethod === 'DELETE' || form.matches('[data-no-offline-draft]');
    }

    function supportsDraft(form) {
        return !isSensitiveForm(form)
            && !isDestructiveForm(form)
            && String(form.method || 'GET').toUpperCase() !== 'GET';
    }

    function supportsOfflineSubmit(form) {
        return form.matches('[data-offline-submit]')
            && supportsDraft(form)
            && !form.querySelector('input[type="file"]');
    }

    function draftKey(form) {
        const action = formAction(form);
        const index = Math.max(0, Array.from(document.forms).indexOf(form));
        const identity = form.id || form.getAttribute('name') || `${action?.pathname || window.location.pathname}:${index}`;
        return `${DRAFT_PREFIX}:${accountScope}:${window.location.pathname}:${identity}`;
    }

    function draftableControls(form) {
        return Array.from(form.elements).filter((control) => {
            if (!control.name || control.disabled) return false;
            if (['_token', '_method'].includes(control.name)) return false;
            if (/password|secret|token|otp/i.test(control.name)) return false;
            return !['hidden', 'password', 'file', 'submit', 'button', 'reset', 'image'].includes(String(control.type || '').toLowerCase());
        });
    }

    function readDraft(form) {
        try {
            const raw = window.localStorage.getItem(draftKey(form));
            const draft = raw ? JSON.parse(raw) : null;
            if (!draft || !Array.isArray(draft.controls)) return null;
            if (Date.now() - Number(draft.savedAt || 0) > 7 * 24 * 60 * 60 * 1000) {
                window.localStorage.removeItem(draftKey(form));
                return null;
            }
            return draft;
        } catch (error) {
            return null;
        }
    }

    function saveDraft(form) {
        if (!supportsDraft(form)) return;
        const controls = draftableControls(form).map((control, index) => ({
            index,
            name: control.name,
            value: control.value,
            checked: 'checked' in control ? Boolean(control.checked) : null,
        }));

        try {
            window.localStorage.setItem(draftKey(form), JSON.stringify({ savedAt: Date.now(), controls }));
        } catch (error) {
            console.warn('[PyroSense Offline] Unable to save form draft:', error);
        }
    }

    function clearDraft(form) {
        try {
            window.localStorage.removeItem(draftKey(form));
        } catch (error) {
            // Storage can be unavailable in private browsing modes.
        }
    }

    function restoreDraft(form) {
        const draft = readDraft(form);
        if (!draft) return false;
        const controls = draftableControls(form);

        draft.controls.forEach((saved) => {
            const control = controls[saved.index];
            if (!control || control.name !== saved.name) return;
            if (saved.checked !== null && 'checked' in control) control.checked = saved.checked;
            else control.value = saved.value;
            control.dispatchEvent(new Event('change', { bubbles: true }));
        });

        return true;
    }

    function addCapabilityLabel(form, restored) {
        if (!supportsDraft(form) || form.querySelector('[data-offline-form-status]')) return;
        const hasFiles = Boolean(form.querySelector('input[type="file"]'));
        const offlineSupported = supportsOfflineSubmit(form);
        form.dataset.offlineCapability = offlineSupported ? 'queue' : 'draft-only';
        form.title = offlineSupported
            ? 'Offline submission supported'
            : (hasFiles ? 'Text draft autosaves; files require internet' : 'Draft autosaves; internet required to submit');

        if (!offlineSupported && !hasFiles && !restored) return;

        const label = document.createElement('p');
        label.dataset.offlineFormStatus = 'true';
        label.setAttribute('role', 'status');
        label.style.cssText = 'margin:8px 0;padding:8px 10px;border-radius:10px;background:#f8fafc;color:#475569;font-size:12px;font-weight:600;border:1px solid #e2e8f0;';
        label.textContent = restored
            ? 'Saved draft restored on this device.'
            : (offlineSupported
                ? 'Offline supported: this submission will sync after reconnecting.'
                : (hasFiles
                    ? 'Text draft autosaves. File uploads require internet.'
                    : 'Draft autosaves on this device. Internet is required to submit.'));
        form.prepend(label);
    }

    function serializeFormBody(form) {
        const body = new URLSearchParams();
        new FormData(form).forEach((value, key) => {
            if (key !== '_token' && typeof value === 'string') body.append(key, value);
        });
        return body.toString();
    }

    function queueFormSubmission(form) {
        if (!window.pyroPwa?.enqueueOfflineReport) {
            throw new Error('The offline queue is not ready. Keep this page open and try again.');
        }

        const item = window.pyroPwa.enqueueOfflineReport({
            url: form.action || window.location.href,
            method: String(form.method || 'POST').toUpperCase(),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'Accept': 'application/json',
            },
            body: serializeFormBody(form),
            type: form.dataset.offlineType || 'form-submission',
            meta: { form_key: draftKey(form), queued_by: accountScope },
        });

        clearDraft(form);
        return item;
    }

    async function submitSupportedFormOnline(form) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const request = window.pyroFetch || window.fetch.bind(window);
        const response = await request(form.action || window.location.href, {
            method: String(form.method || 'POST').toUpperCase(),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: serializeFormBody(form),
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            throw Object.assign(new Error(payload?.message || 'The form could not be submitted.'), {
                isHttpError: !payload?.offline && response.status !== 503,
            });
        }

        clearDraft(form);
        window.PyroNotify?.success?.('Submission saved successfully.');
        window.setTimeout(() => window.location.reload(), 450);
    }

    function initializeForms() {
        document.querySelectorAll('form').forEach((form) => {
            if (!supportsDraft(form)) return;
            const restored = restoreDraft(form);
            addCapabilityLabel(form, restored);

            const scheduleDraft = () => {
                window.clearTimeout(draftTimers.get(form));
                draftTimers.set(form, window.setTimeout(() => saveDraft(form), 250));
            };

            form.addEventListener('input', scheduleDraft);
            form.addEventListener('change', scheduleDraft);
            form.addEventListener('reset', () => window.setTimeout(() => clearDraft(form), 0));
        });
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !supportsDraft(form)) return;

        if (supportsOfflineSubmit(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();

            if (form.dataset.offlineSubmitting === 'true') return;
            form.dataset.offlineSubmitting = 'true';

            if (!navigator.onLine) {
                try {
                    queueFormSubmission(form);
                    form.reset();
                    window.alert('You are offline. This submission was queued and will sync after reconnecting.');
                } catch (error) {
                    saveDraft(form);
                    window.alert(error.message || 'Unable to queue this form. Its draft remains saved on this device.');
                }
                delete form.dataset.offlineSubmitting;
                return;
            }

            try {
                await submitSupportedFormOnline(form);
            } catch (error) {
                if (error?.isHttpError) {
                    saveDraft(form);
                    window.alert(error.message);
                    delete form.dataset.offlineSubmitting;
                    return;
                }

                try {
                    queueFormSubmission(form);
                    form.reset();
                    window.alert('The connection failed. This submission was queued and will sync automatically.');
                } catch (queueError) {
                    saveDraft(form);
                    window.alert(queueError.message || 'Submission failed. Its draft remains saved on this device.');
                }
                delete form.dataset.offlineSubmitting;
            }
            return;
        }

        if (navigator.onLine) {
            clearDraft(form);
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        saveDraft(form);
        const hasFiles = Boolean(form.querySelector('input[type="file"]'));
        window.alert(hasFiles
            ? 'You are offline. Your text fields were saved as a draft, but files require internet before submission.'
            : 'You are offline. Your draft was saved on this device. Submit it after reconnecting.');
    }, true);

    document.addEventListener('DOMContentLoaded', initializeForms);

    window.pyroOfflineForms = { saveDraft, clearDraft, supportsOfflineSubmit };
})();
