/**
 * Shared client-side safety for every Chief Command page.
 *
 * This complements (and never replaces) Laravel's server-side validation. It is
 * deliberately attached at document level so forms and modal controls inserted
 * after page load receive the same treatment as controls rendered by Blade.
 */
const CHIEF_SAFETY_SELECTOR = 'input, textarea, select';
const TEXT_INPUT_TYPES = new Set(['', 'text', 'search', 'email', 'tel', 'url']);
const SKIPPED_INPUT_TYPES = new Set([
    'button',
    'checkbox',
    'color',
    'file',
    'hidden',
    'image',
    'password',
    'radio',
    'range',
    'reset',
    'submit',
]);

const getInputType = (control) =>
    control instanceof HTMLInputElement ? control.type.toLowerCase() : '';

const canonicalizeKey = (value) => String(value || '')
    .replace(/([a-z\d])([A-Z])/g, '$1_$2')
    .replace(/\[[^\]]*]/g, ' ')
    .replace(/[^A-Za-z\d]+/g, ' ')
    .trim()
    .toLowerCase();

const isTextControl = (control) => {
    if (control instanceof HTMLTextAreaElement) return true;
    if (!(control instanceof HTMLInputElement)) return false;

    const type = getInputType(control);
    return TEXT_INPUT_TYPES.has(type) && !SKIPPED_INPUT_TYPES.has(type);
};

const fieldKey = (control) =>
    canonicalizeKey(`${control.name || ''} ${control.id || ''}`);

const isPersonNameField = (control, key = fieldKey(control)) =>
    control instanceof HTMLInputElement
    && getInputType(control) === 'text'
    && /\b(first name|middle name|last name|full name|owner name|signature name|contact person)\b/.test(key);

const isPhoneField = (control, key = fieldKey(control)) =>
    control instanceof HTMLInputElement
    && (getInputType(control) === 'tel' || /\b(contact number|phone|mobile)\b/.test(key));

const isPersonalMobileField = (control, key = fieldKey(control)) =>
    isPhoneField(control, key)
    && !/\b(station|barangay hall)\b/.test(key);

const isTinField = (control, key = fieldKey(control)) =>
    control instanceof HTMLInputElement && getInputType(control) === 'text' && /\btin number\b/.test(key);

const isCodeField = (control, key = fieldKey(control)) =>
    control instanceof HTMLInputElement
    && getInputType(control) === 'text'
    && /\b(station code|hydrant code|fsic number|account code|bfp id|registration no|permit no|plate number|unit number)\b/.test(key);

const isRegistrationField = (control, key = fieldKey(control)) =>
    control instanceof HTMLInputElement
    && getInputType(control) === 'text'
    && /\b(registration no|permit no)\b/.test(key);

const isEntityNameField = (control, key = fieldKey(control)) =>
    control instanceof HTMLInputElement
    && getInputType(control) === 'text'
    && /\b(business name|station name|school name|barangay hall name|barangay name|equipment name|team name|category name)\b/.test(key);

const defaultMaxLength = (control) => {
    const key = fieldKey(control);
    const type = getInputType(control);

    if (type === 'email') return 254;
    if (isPhoneField(control, key)) return 20;
    if (type === 'url') return 2048;
    if (type === 'search' || /(^|[_-])search($|[_-])|\bquery\b/.test(key)) return 200;
    if (/description|remarks|comments|notes|findings|details|context|reason|result|indicator|items_text/.test(key)) {
        return control instanceof HTMLTextAreaElement ? 4000 : 1000;
    }
    if (control instanceof HTMLTextAreaElement) return 2000;
    if (isPersonNameField(control, key)) return 100;
    if (/\b(title|subject|heading|business name|station name|equipment name|team name|event)\b/.test(key)) return 200;

    return 255;
};

const sanitizeText = (value, multiline = false) => {
    let sanitized = String(value ?? '').normalize('NFKC');

    // Remove C0/C1 control characters and bidi formatting controls commonly
    // used to disguise pasted content. Keep tabs/newlines only in textareas.
    sanitized = multiline
        ? sanitized.replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F-\u009F\u202A-\u202E\u2066-\u2069]/g, '')
        : sanitized.replace(/[\u0000-\u001F\u007F-\u009F\u202A-\u202E\u2066-\u2069]/g, ' ');

    // User-entered values are plain text throughout Chief Command. Removing
    // angle brackets prevents pasted markup from surviving into previews that
    // may later be composed with innerHTML by legacy page scripts.
    sanitized = sanitized.replace(/[<>]/g, '');

    if (multiline) {
        return sanitized
            .replace(/\r\n?/g, '\n')
            .replace(/\n{4,}/g, '\n\n\n');
    }

    return sanitized.replace(/\s+/g, ' ');
};

const sanitizeControl = (control, { trim = false } = {}) => {
    if (!isTextControl(control) || control.dataset.chiefSanitize === 'off') return;

    const oldValue = control.value;
    let newValue = sanitizeText(oldValue, control instanceof HTMLTextAreaElement);
    const key = fieldKey(control);

    if (isTinField(control, key) || isPersonalMobileField(control, key)) {
        newValue = newValue.replace(/\D/g, '');
    } else if (isPhoneField(control, key)) {
        newValue = newValue.replace(/[^\d\s()+.-]/g, '');
    } else if (isPersonNameField(control, key)) {
        newValue = newValue.replace(/[^\p{L}\p{M}\s.'\u2019-]/gu, '');
    } else if (/\bbusiness name\b/.test(key)) {
        newValue = newValue.replace(/[^\p{L}\p{M}\d\s]/gu, '');
    } else if (isEntityNameField(control, key)) {
        newValue = newValue.replace(/[^\p{L}\p{M}\d\s.,'\u2019&()/-]/gu, '');
    } else if (isRegistrationField(control, key)) {
        newValue = newValue.replace(/[^A-Za-z\d\s]/g, '');
    } else if (isCodeField(control, key)) {
        newValue = newValue.replace(/[^A-Za-z\d\s./#-]/g, '');
    } else if (/\b(street|building address)\b/.test(key)) {
        newValue = newValue.replace(/[^\p{L}\p{M}\d\s,.'\u2019#()/-]/gu, '');
    }

    if (trim) {
        newValue = control instanceof HTMLTextAreaElement
            ? newValue.replace(/[ \t]+$/gm, '').trim()
            : newValue.trim();
    }
    if (newValue === oldValue) return;

    const start = control.selectionStart;
    const removedBeforeCaret = typeof start === 'number'
        ? oldValue.slice(0, start).length - sanitizeText(oldValue.slice(0, start), control instanceof HTMLTextAreaElement).length
        : 0;

    control.value = newValue;
    if (document.activeElement === control && typeof start === 'number') {
        const nextCaret = Math.max(0, start - removedBeforeCaret);
        control.setSelectionRange(nextCaret, nextCaret);
    }
};

const configureControl = (control) => {
    if (!(control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement || control instanceof HTMLSelectElement)) {
        return;
    }
    if (control.dataset.chiefSafetyReady === 'true') return;

    control.dataset.chiefSafetyReady = 'true';

    const type = getInputType(control);
    const key = fieldKey(control);

    if (isTextControl(control) && control.dataset.chiefSanitize !== 'off') {
        if (!control.hasAttribute('maxlength')) {
            control.maxLength = defaultMaxLength(control);
        }

        if (type === 'email' && !control.hasAttribute('autocomplete')) {
            control.autocomplete = 'email';
        }
        if ((isPhoneField(control, key) || isTinField(control, key)) && !control.hasAttribute('inputmode')) {
            control.inputMode = 'numeric';
        } else if (type === 'tel' && !control.hasAttribute('inputmode')) {
            control.inputMode = 'tel';
        }
    }

    if (control instanceof HTMLInputElement && type === 'password') {
        if (!control.hasAttribute('minlength')) control.minLength = 8;
        if (!control.hasAttribute('maxlength')) control.maxLength = 128;
    }

    if (control instanceof HTMLInputElement && type === 'number') {
        if (/\b(lat|lng|latitude|longitude)\b/.test(key)) {
            if (!control.hasAttribute('step')) control.step = 'any';
            if (!control.hasAttribute('min')) control.min = /\b(lat|latitude)\b/.test(key) ? '-90' : '-180';
            if (!control.hasAttribute('max')) control.max = /\b(lat|latitude)\b/.test(key) ? '90' : '180';
        } else if (/\b(quantity|capacity|count|radius|days|interval)\b/.test(key)) {
            if (!control.hasAttribute('min')) control.min = '0';
            if (!control.hasAttribute('step')) control.step = '1';
        }
        if (!control.hasAttribute('inputmode')) control.inputMode = 'decimal';
    }
};

const configureTree = (root) => {
    if (root instanceof Element && root.matches(CHIEF_SAFETY_SELECTOR)) configureControl(root);
    root.querySelectorAll?.(CHIEF_SAFETY_SELECTOR).forEach(configureControl);
};

const fileMatchesAccept = (file, accept) => {
    const tokens = String(accept || '').split(',').map((token) => token.trim().toLowerCase()).filter(Boolean);
    if (!tokens.length) return true;

    const mime = String(file.type || '').toLowerCase();
    const extension = `.${String(file.name || '').split('.').pop().toLowerCase()}`;

    return tokens.some((token) => {
        if (token.startsWith('.')) return extension === token;
        if (token.endsWith('/*')) return mime.startsWith(token.slice(0, -1));
        return mime === token;
    });
};

const validateFiles = (control, key) => {
    const files = [...(control.files || [])];
    if (!files.length) return '';

    const maxCount = /\bgallery images\b/.test(key) ? 6 : /\b(risk hazard photos|attachments)\b/.test(key) ? 5 : null;
    if (maxCount && files.length > maxCount) return `Select no more than ${maxCount} files.`;

    for (const file of files) {
        if (!fileMatchesAccept(file, control.accept)) return `${file.name} is not an accepted file type.`;

        const maxBytes = file.type.startsWith('video/')
            ? 50 * 1024 * 1024
            : file.type.startsWith('image/')
                ? 5 * 1024 * 1024
                : 10 * 1024 * 1024;
        if (file.size > maxBytes) {
            const maxMb = Math.round(maxBytes / 1024 / 1024);
            return `${file.name} must be ${maxMb} MB or smaller.`;
        }
    }

    return '';
};

const setFieldValidity = (control) => {
    if (!(control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement || control instanceof HTMLSelectElement)) {
        return true;
    }

    control.setCustomValidity('');
    if (control.disabled || control.dataset.chiefValidate === 'off') return true;

    const value = typeof control.value === 'string' ? control.value.trim() : '';
    const key = fieldKey(control);
    const type = getInputType(control);

    if (control.required && !value) {
        control.setCustomValidity('This field is required.');
    } else if (control instanceof HTMLSelectElement
        && value
        && ![...control.options].some((option) => option.value === control.value)) {
        control.setCustomValidity('Choose a valid option from the list.');
    } else if (control instanceof HTMLInputElement && type === 'file') {
        control.setCustomValidity(validateFiles(control, key));
    } else if (type === 'number' && value && !Number.isFinite(Number(value))) {
        control.setCustomValidity('Enter a valid number.');
    } else if (isPersonalMobileField(control, key) && value && !/^09\d{9}$/.test(value)) {
        control.setCustomValidity('Enter an 11-digit Philippine mobile number beginning with 09.');
    } else if (isPhoneField(control, key) && value && !/^[+()\d][\d\s()+.-]{5,19}$/.test(value)) {
        control.setCustomValidity('Enter a valid phone number using digits and standard phone symbols.');
    } else if (isPersonNameField(control, key)
        && value
        && !/^[\p{L}\p{M}][\p{L}\p{M}\s.'\u2019-]*$/u.test(value)) {
        control.setCustomValidity('Names may contain letters, spaces, apostrophes, periods, and hyphens only.');
    } else if (/\bbusiness name\b/.test(key) && value && !/^[\p{L}\p{M}\d][\p{L}\p{M}\d\s]*$/u.test(value)) {
        control.setCustomValidity('Business name may contain letters, numbers, and spaces only.');
    } else if (isEntityNameField(control, key)
        && value
        && !/^[\p{L}\p{M}\d][\p{L}\p{M}\d\s.,'\u2019&()/-]*$/u.test(value)) {
        control.setCustomValidity('Use letters, numbers, spaces, and standard name punctuation only.');
    } else if (isTinField(control, key) && value && !/^\d{9,15}$/.test(value)) {
        control.setCustomValidity('TIN must contain 9 to 15 digits only.');
    } else if (isRegistrationField(control, key) && value && !/^[A-Za-z\d][A-Za-z\d\s]*$/.test(value)) {
        control.setCustomValidity('Registration and permit numbers may contain letters, numbers, and spaces only.');
    } else if (isCodeField(control, key) && value && !/^[A-Za-z\d][A-Za-z\d\s./#-]*$/.test(value)) {
        control.setCustomValidity('Use letters, numbers, spaces, periods, slashes, hashes, or hyphens only.');
    } else if (control instanceof HTMLInputElement
        && type === 'text'
        && /\b(street|building address)\b/.test(key)
        && value
        && !/^[\p{L}\p{M}\d][\p{L}\p{M}\d\s,.'\u2019#()/-]*$/u.test(value)) {
        control.setCustomValidity('Use letters, numbers, spaces, and standard address punctuation only.');
    } else if (control instanceof HTMLInputElement
        && type === 'password'
        && value
        && value.length < 8) {
        control.setCustomValidity('Password must contain at least 8 characters.');
    } else if (control instanceof HTMLInputElement
        && type === 'password'
        && /\b(password confirmation|password confirm)\b/.test(key)) {
        const password = control.form?.querySelector('input[name="password"]');
        if (password && value !== password.value) {
            control.setCustomValidity('Password confirmation must match the password.');
        }
    }

    const valid = control.checkValidity();
    if (valid) control.removeAttribute('aria-invalid');
    else control.setAttribute('aria-invalid', 'true');
    return valid;
};

const validateRelationships = (controls) => {
    const findByName = (name) => controls.find((control) => canonicalizeKey(control.name) === name);
    const pairs = [
        ['date from', 'date to', 'End date must be on or after the start date.'],
        ['starts at', 'ends at', 'End date and time must be after the start date and time.'],
        ['start time', 'end time', 'End time must be after the start time.'],
        ['last maintenance at', 'next maintenance due at', 'Next maintenance must be on or after the last maintenance date.'],
    ];

    pairs.forEach(([startName, endName, message]) => {
        const start = findByName(startName);
        const end = findByName(endName);
        if (!start || !end || !start.value || !end.value || end.validationMessage) return;

        const permitsEqual = startName === 'date from' || startName === 'last maintenance at';
        if (permitsEqual ? end.value < start.value : end.value <= start.value) {
            end.setCustomValidity(message);
            end.setAttribute('aria-invalid', 'true');
        }
    });
};

const validateControls = (controls, { report = false } = {}) => {
    let firstInvalid = null;

    controls.forEach((control) => {
        sanitizeControl(control, { trim: true });
        if (!setFieldValidity(control) && !firstInvalid) firstInvalid = control;
    });

    validateRelationships(controls);
    if (!firstInvalid) firstInvalid = controls.find((control) => !control.checkValidity()) || null;

    if (report && firstInvalid) {
        firstInvalid.reportValidity();
        firstInvalid.focus({ preventScroll: false });
    }

    return firstInvalid === null;
};

const isLegacyActionButton = (button) => {
    const actionHint = [
        button.id,
        button.getAttribute('onclick'),
        button.textContent,
    ].filter(Boolean).join(' ').toLowerCase();

    return /\b(save|submit|approve|update|issue)\b/.test(actionHint);
};

const bootChiefFormSafety = () => {
    configureTree(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach(({ addedNodes }) => {
            addedNodes.forEach((node) => {
                if (node instanceof Element) configureTree(node);
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('beforeinput', (event) => {
        const control = event.target;
        if (!(control instanceof HTMLInputElement)
            || getInputType(control) !== 'number'
            || !event.data
            || event.inputType.startsWith('delete')) return;

        const permitsNegative = !control.min || Number(control.min) < 0;
        const permitsDecimal = control.step === 'any' || (control.step && !Number.isInteger(Number(control.step)));
        const allowed = permitsDecimal ? /[\d.-]/ : /[\d-]/;
        if ([...event.data].some((character) => !allowed.test(character))
            || (!permitsNegative && event.data.includes('-'))
            || (event.data.includes('-') && (control.selectionStart !== 0 || control.value.includes('-')))
            || (event.data.includes('.') && control.value.includes('.'))) {
            event.preventDefault();
        }
    }, true);

    document.addEventListener('input', (event) => {
        const control = event.target;
        if (isTextControl(control) && !event.isComposing) sanitizeControl(control);
        if (control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement) {
            setFieldValidity(control);
        }
    }, true);

    document.addEventListener('invalid', (event) => {
        if (event.target instanceof HTMLInputElement
            || event.target instanceof HTMLTextAreaElement
            || event.target instanceof HTMLSelectElement) {
            event.target.setAttribute('aria-invalid', 'true');
        }
    }, true);

    document.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement
            || event.target instanceof HTMLTextAreaElement
            || event.target instanceof HTMLSelectElement) {
            setFieldValidity(event.target);
        }
    }, true);

    document.addEventListener('focusout', (event) => {
        const control = event.target;
        if (!isTextControl(control)) return;
        sanitizeControl(control, { trim: true });
        setFieldValidity(control);
    }, true);

    document.addEventListener('submit', (event) => {
        if (!(event.target instanceof HTMLFormElement)) return;

        const controls = [...event.target.querySelectorAll(CHIEF_SAFETY_SELECTOR)];
        if (!validateControls(controls, { report: true })) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    // Some older Chief modals call fetch directly from an inline button click.
    // Validate during capture so an invalid click cannot reach those handlers.
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('button');
        if (!(button instanceof HTMLButtonElement) || !isLegacyActionButton(button)) return;

        const scope = button.form
            || button.closest('dialog, [role="dialog"], .modal, .modal-content, .composer-card');
        if (!scope) return;

        const controls = [...scope.querySelectorAll(CHIEF_SAFETY_SELECTOR)];
        if (!validateControls(controls, { report: true })) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    // Legacy modal actions sometimes use controls without a form wrapper. Their
    // values still get normalized on input/focusout; this helper lets page code
    // request a full validity pass before a fetch without duplicating rules.
    window.ChiefFormSafety = Object.freeze({
        sanitize: sanitizeControl,
        validate(container = document, options = { report: true }) {
            return validateControls([...container.querySelectorAll(CHIEF_SAFETY_SELECTOR)], options);
        },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootChiefFormSafety, { once: true });
} else {
    bootChiefFormSafety();
}
