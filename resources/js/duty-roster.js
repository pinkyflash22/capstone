const app = document.getElementById('dutyRosterApp');

if (app) {
    const config = JSON.parse(document.getElementById('dutyRosterConfig')?.textContent || '{}');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const state = { data: null, timer: null, editingShift: null };
    const roles = [
        ['team_leader', 'Team leader', true],
        ['commander_driver', 'Driver', true],
        ['pumper', 'Pumper', false],
        ['firefighter_officer_1', 'Officer 1', false],
        ['firefighter_officer_2', 'Officer 2', false],
        ['firefighter_officer_3', 'Officer 3', false],
        ['rescue_radio', 'Rescue / Radio', false],
    ];
    const roleLabels = Object.fromEntries([...roles.map(([key, label]) => [key, label]), ['station_duty', 'Station duty']]);
    const $ = (selector, root = document) => root.querySelector(selector);
    const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char]);
    const parse = value => value ? new Date(value) : null;
    const fmt = value => new Intl.DateTimeFormat('en-PH', { timeZone: config.timezone, month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }).format(parse(value));
    const localInput = date => {
        const pad = value => String(value).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };
    const inputFromIso = value => {
        const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
            timeZone: config.timezone, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        }).formatToParts(parse(value)).filter(part => part.type !== 'literal').map(part => [part.type, part.value]));
        return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
    };
    const isActive = (item, now = new Date()) => parse(item.starts_at) <= now && parse(item.ends_at) > now;
    const officerById = id => state.data?.officers.find(officer => Number(officer.id) === Number(id));

    async function request(path = '', options = {}) {
        const response = await fetch(`${config.baseUrl}${path}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) },
            ...options,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(validation || payload.message || 'The roster request could not be completed.');
        }
        return payload;
    }

    function toast(message, error = false) {
        const node = document.createElement('div');
        node.className = `duty-roster__toast${error ? ' duty-roster__toast--error' : ''}`;
        node.textContent = message;
        document.body.appendChild(node);
        setTimeout(() => node.remove(), 3200);
    }

    function options(officers, placeholder = 'Select personnel') {
        return `<option value="">${placeholder}</option>${officers.map(officer => `<option value="${officer.id}">${escapeHtml(officer.name)}${officer.rank ? ` · ${escapeHtml(officer.rank)}` : ''}</option>`).join('')}`;
    }

    function hydrateForms() {
        const firefighters = state.data.officers.filter(officer => String(officer.role).toLowerCase() === 'firefighter');
        const command = state.data.officers.filter(officer => ['chief', 'station admin', 'chief station admin'].includes(String(officer.role).toLowerCase()));
        const teamSelect = $('#dutyShiftForm [name="team_id"]');
        teamSelect.innerHTML = state.data.teams.map(team => `<option value="${team.team_id}">Team ${team.team_id}</option>`).join('');
        $('#dutyAssignments').innerHTML = roles.map(([key, label, required]) => `<label>${label}<select name="${key}" ${required ? 'required' : ''}>${options(firefighters, required ? 'Select firefighter' : 'Optional')}</select></label>`).join('');
        $('#dutyShiftForm [name="station_duty"]').innerHTML = options(command, 'Select chief/admin');
        $('#dutyShiftForm [name="station_duty_backup"]').innerHTML = options(command, 'None');
        $('#dutyExceptionForm [name="station_officer_id"]').innerHTML = options(state.data.officers);
        syncAssignmentAvailability();
    }

    function syncAssignmentAvailability() {
        const form = $('#dutyShiftForm');
        if (!form) return;

        const personnelSelects = [
            ...roles.map(([key]) => form.elements[key]),
            form.elements.station_duty,
            form.elements.station_duty_backup,
        ].filter(Boolean);
        const selectedValues = personnelSelects
            .map(select => String(select.value || ''))
            .filter(Boolean);

        personnelSelects.forEach(select => {
            [...select.options].forEach(option => {
                if (!option.value) return;
                option.disabled = option.value !== String(select.value || '') && selectedValues.includes(option.value);
            });
        });
    }

    function duplicateAssignmentName(form) {
        const selects = [
            ...roles.map(([key]) => form.elements[key]),
            form.elements.station_duty,
            form.elements.station_duty_backup,
        ].filter(Boolean);
        const seen = new Set();

        for (const select of selects) {
            const value = String(select.value || '');
            if (!value) continue;
            if (seen.has(value)) return select.selectedOptions[0]?.textContent?.trim() || 'Selected personnel';
            seen.add(value);
        }

        return null;
    }

    function renderMetrics() {
        const now = new Date();
        const activeShifts = state.data.shifts.filter(shift => isActive(shift, now));
        const activeExceptions = state.data.exceptions.filter(item => isActive(item, now));
        const assignments = activeShifts.flatMap(shift => shift.assignments).filter(assignment => !activeExceptions.some(item => Number(item.station_officer_id) === Number(assignment.station_officer_id)));
        const deployable = new Set(assignments.filter(item => item.team_role !== 'station_duty').map(item => item.station_officer_id));
        const command = new Set(assignments.filter(item => item.team_role === 'station_duty').map(item => item.station_officer_id));
        const next = state.data.shifts.filter(shift => parse(shift.starts_at) > now).sort((a, b) => parse(a.starts_at) - parse(b.starts_at))[0];
        $('#dutyMetricDeployable').textContent = deployable.size;
        $('#dutyMetricCommand').textContent = command.size;
        $('#dutyMetricExceptions').textContent = activeExceptions.length;
        $('#dutyMetricHandoff').textContent = next ? fmt(next.starts_at).split(',').slice(-1)[0].trim() : '—';
        $('#dutyMetricHandoffLabel').textContent = next ? `${next.label} · ${fmt(next.starts_at)}` : 'No upcoming shift';
    }

    function renderTimeline() {
        const now = new Date();
        const shifts = [...state.data.shifts].sort((a, b) => parse(a.starts_at) - parse(b.starts_at));
        $('#dutyShiftTimeline').innerHTML = shifts.length ? shifts.map(shift => {
            const active = isActive(shift, now);
            const upcoming = parse(shift.starts_at) > now;
            const chips = shift.assignments.map(item => `<span class="duty-roster__chip${item.team_role === 'station_duty' ? ' duty-roster__chip--command' : ''}">${escapeHtml(item.officer.name)} · ${escapeHtml(roleLabels[item.team_role] || item.team_role)}</span>`).join('');
            const recurrenceBadges = `${shift.is_recurring ? '<span class="duty-roster__chip duty-roster__chip--recurring"><i class="fa-solid fa-repeat"></i> Weekly</span>' : ''}${shift.is_override ? '<span class="duty-roster__chip">Changed occurrence</span>' : ''}`;
            const actions = parse(shift.ends_at) > now
                ? `<div class="duty-roster__shift-actions"><button class="duty-roster__delete" type="button" data-edit-shift="${shift.id}" title="Edit shift"><i class="fa-solid fa-pen"></i></button><button class="duty-roster__delete" type="button" data-delete-shift="${shift.id}" title="Delete shift"><i class="fa-solid fa-trash"></i></button></div>`
                : '<span class="duty-roster__history" title="Completed duty history"><i class="fa-solid fa-lock"></i></span>';
            return `<article class="duty-roster__shift ${active ? 'duty-roster__shift--active' : upcoming ? 'duty-roster__shift--upcoming' : ''}"><span class="duty-roster__shift-bar"></span><div><h5>${escapeHtml(shift.label)} · Team ${shift.team_id}${active ? ' <span class="duty-roster__chip">LIVE</span>' : ''} ${recurrenceBadges}</h5><p>${fmt(shift.starts_at)} → ${fmt(shift.ends_at)}</p><div class="duty-roster__chips">${chips}</div></div>${actions}</article>`;
        }).join('') : '<div class="duty-roster__empty">No shifts scheduled in this window.</div>';
    }

    function renderExceptions() {
        const items = [...state.data.exceptions].sort((a, b) => parse(a.starts_at) - parse(b.starts_at));
        $('#dutyExceptionList').innerHTML = items.length ? items.map(item => `<article class="duty-roster__exception"><span class="duty-roster__exception-icon"><i class="fa-solid ${item.type === 'leave' ? 'fa-plane-departure' : 'fa-bed'}"></i></span><div><strong>${escapeHtml(item.officer.name)} · ${item.type === 'leave' ? 'Leave' : 'Rest day'}</strong><small>${fmt(item.starts_at)} → ${fmt(item.ends_at)}</small></div><button class="duty-roster__delete" type="button" data-delete-exception="${item.id}" title="Remove"><i class="fa-solid fa-xmark"></i></button></article>`).join('') : '<div class="duty-roster__empty">No rest days or leave in this window.</div>';
    }

    function updateTeamTable() {
        const active = state.data.shifts.filter(shift => isActive(shift));
        const exceptions = state.data.exceptions.filter(item => isActive(item));
        state.data.teams.forEach(team => {
            const row = document.querySelector(`.team-row[data-team-id="${team.team_id}"]`);
            if (!row || !team.roster_managed) return;
            const shift = active.find(item => Number(item.id) === Number(team.active_duty_shift_id)) || active.find(item => Number(item.team_id) === Number(team.team_id));
            const byRole = Object.fromEntries((shift?.assignments || []).filter(item => item.team_role !== 'station_duty' && !exceptions.some(exception => Number(exception.station_officer_id) === Number(item.station_officer_id))).map(item => [item.team_role, item.officer]));
            const labels = { team_leader: 'Team Leader', commander_driver: 'Driver', pumper: 'Pumper', firefighter_officer_1: 'Officer 1', firefighter_officer_2: 'Officer 2', firefighter_officer_3: 'Officer 3', rescue_radio: 'Rescue/Radio' };
            Object.entries(labels).forEach(([role, label]) => {
                const cell = [...row.querySelectorAll('td')].find(td => td.dataset.label === label);
                if (cell) cell.innerHTML = `<strong>${escapeHtml(byRole[role]?.name || 'Unassigned')}</strong><br><small>${shift ? escapeHtml(roleLabels[role]) : 'Off duty'}</small>`;
            });
            const select = row.querySelector('.status-select');
            if (select) { select.value = team.status; select.disabled = true; select.title = 'Controlled by the dynamic duty roster'; }
            row.dataset.status = team.status;
        });
    }

    async function refresh(silent = false) {
        try {
            state.data = await request('');
            hydrateForms(); renderMetrics(); renderTimeline(); renderExceptions(); updateTeamTable();
        } catch (error) {
            if (!silent) toast(error.message, true);
        }
    }

    function toggleRecurrenceFields(form) {
        const weekly = form.elements.recurrence?.value === 'weekly';
        form.querySelector('[data-repeat-until]').hidden = !weekly;
        form.elements.recurrence_until.required = weekly;
    }

    function openDialog(id) {
        const dialog = $(id);
        const start = new Date(); start.setMinutes(0, 0, 0); start.setHours(start.getHours() + 1);
        const end = new Date(start); end.setHours(end.getHours() + 11);
        if (id === '#dutyShiftDialog') {
            state.editingShift = null;
            const form = $('#dutyShiftForm');
            form.reset();
            hydrateForms();
            form.elements.starts_at.value = localInput(start);
            form.elements.ends_at.value = localInput(end);
            const until = new Date(start); until.setMonth(until.getMonth() + 3);
            form.elements.recurrence_until.value = localInput(until).slice(0, 10);
            form.elements.recurrence.disabled = false;
            $('[data-edit-scope]', form).hidden = true;
            $('#dutyShiftDialogTitle').textContent = 'Schedule a shift';
            toggleRecurrenceFields(form);
        } else {
            dialog.querySelector('[name="starts_at"]').value = localInput(start);
            dialog.querySelector('[name="ends_at"]').value = localInput(end);
        }
        dialog.querySelector('[data-form-error]').textContent = '';
        dialog.showModal();
    }

    function editShift(shift) {
        const form = $('#dutyShiftForm');
        state.editingShift = shift;
        form.reset();
        hydrateForms();
        form.elements.label.value = shift.label;
        form.elements.team_id.value = shift.team_id;
        form.elements.starts_at.value = inputFromIso(shift.starts_at);
        form.elements.ends_at.value = inputFromIso(shift.ends_at);
        form.elements.notes.value = shift.notes || '';
        shift.assignments.filter(item => item.team_role !== 'station_duty').forEach(item => {
            if (form.elements[item.team_role]) form.elements[item.team_role].value = item.station_officer_id;
        });
        const stationDuty = shift.assignments.filter(item => item.team_role === 'station_duty');
        form.elements.station_duty.value = stationDuty[0]?.station_officer_id || '';
        form.elements.station_duty_backup.value = stationDuty[1]?.station_officer_id || '';
        syncAssignmentAvailability();
        form.elements.recurrence.value = shift.is_recurring ? 'weekly' : 'none';
        form.elements.recurrence.disabled = true;
        form.elements.recurrence_until.value = shift.recurrence_until || inputFromIso(shift.ends_at).slice(0, 10);
        $('[data-edit-scope]', form).hidden = !shift.is_recurring;
        $('#dutyShiftDialogTitle').textContent = shift.is_recurring ? 'Edit recurring shift' : 'Edit shift';
        toggleRecurrenceFields(form);
        form.querySelector('[data-form-error]').textContent = '';
        $('#dutyShiftDialog').showModal();
    }

    app.addEventListener('click', async event => {
        const action = event.target.closest('[data-roster-action]')?.dataset.rosterAction;
        if (action === 'shift') openDialog('#dutyShiftDialog');
        if (action === 'exception') openDialog('#dutyExceptionDialog');
        const close = event.target.closest('[data-dialog-close]');
        if (close) close.closest('dialog').close();
        const editId = event.target.closest('[data-edit-shift]')?.dataset.editShift;
        if (editId) {
            const shift = state.data.shifts.find(item => Number(item.id) === Number(editId));
            if (shift) editShift(shift);
            return;
        }
        const shiftId = event.target.closest('[data-delete-shift]')?.dataset.deleteShift;
        const exceptionId = event.target.closest('[data-delete-exception]')?.dataset.deleteException;
        if (!shiftId && !exceptionId) return;
        if (!window.confirm(`Remove this ${shiftId ? 'shift' : 'rest/leave period'}?`)) return;
        try {
            await request(shiftId ? `/shifts/${shiftId}` : `/exceptions/${exceptionId}`, { method: 'DELETE' });
            toast('Roster updated.'); await refresh(true);
        } catch (error) { toast(error.message, true); }
    });

    $('#dutyShiftForm [name="recurrence"]').addEventListener('change', event => toggleRecurrenceFields(event.currentTarget.form));
    $('#dutyShiftForm').addEventListener('change', event => {
        if (event.target.matches('select[name]')) syncAssignmentAvailability();
    });

    $('#dutyShiftForm').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget; const data = new FormData(form);
        const duplicateName = duplicateAssignmentName(form);
        if (duplicateName) {
            form.querySelector('[data-form-error]').textContent = `${duplicateName} is already assigned to another duty position.`;
            return;
        }
        const assignments = roles.map(([key]) => ({ team_role: key, station_officer_id: data.get(key) })).filter(item => item.station_officer_id);
        assignments.push({ team_role: 'station_duty', station_officer_id: data.get('station_duty') });
        if (data.get('station_duty_backup')) assignments.push({ team_role: 'station_duty', station_officer_id: data.get('station_duty_backup') });
        const editing = state.editingShift;
        const payload = { team_id: data.get('team_id'), label: data.get('label'), starts_at: data.get('starts_at'), ends_at: data.get('ends_at'), notes: data.get('notes'), recurrence: data.get('recurrence') || (editing?.is_recurring ? 'weekly' : 'none'), recurrence_until: data.get('recurrence_until'), edit_scope: data.get('edit_scope') || 'this', assignments };
        try { await request(editing ? `/shifts/${editing.id}` : '/shifts', { method: editing ? 'PUT' : 'POST', body: JSON.stringify(payload) }); form.closest('dialog').close(); form.reset(); state.editingShift = null; toast(editing ? 'Duty schedule updated.' : (payload.recurrence === 'weekly' ? 'Weekly duty schedule created.' : 'Duty shift scheduled.')); await refresh(true); }
        catch (error) { form.querySelector('[data-form-error]').textContent = error.message; }
    });

    $('#dutyExceptionForm').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget; const data = Object.fromEntries(new FormData(form));
        try { await request('/exceptions', { method: 'POST', body: JSON.stringify(data) }); form.closest('dialog').close(); form.reset(); toast('Availability saved.'); await refresh(true); }
        catch (error) { form.querySelector('[data-form-error]').textContent = error.message; }
    });

    const tick = () => { $('#dutyRosterClock').textContent = new Intl.DateTimeFormat('en-PH', { timeZone: config.timezone, weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', second: '2-digit' }).format(new Date()); };
    tick(); setInterval(tick, 1000); refresh(); state.timer = setInterval(() => refresh(true), 30000);
}
