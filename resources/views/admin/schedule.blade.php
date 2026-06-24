<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PyroSense · Admin Schedule</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffcf5 0%, #f8fafc 100%);
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px 24px 32px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        .schedule-shell {
            max-width: 1480px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 158, 11, 0.08);
            height: 72px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 0 0 20px;
            border-radius: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .schedule-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.9fr);
            gap: 20px;
            padding: 28px 30px;
            border-radius: 30px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 44%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .schedule-hero h1 {
            margin: 0;
            font-size: clamp(2rem, 3vw, 2.8rem);
            line-height: 1;
            color: #0f172a;
            font-weight: 900;
        }

        .schedule-hero p {
            margin: 12px 0 0;
            color: #64748b;
            line-height: 1.65;
            font-size: 0.98rem;
            max-width: 62ch;
        }

        .schedule-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .schedule-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #334155;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .schedule-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .schedule-summary-card,
        .calendar-card,
        .agenda-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.06);
        }

        .schedule-summary-card {
            padding: 20px 18px;
        }

        .schedule-summary-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
        }

        .schedule-summary-value {
            margin-top: 12px;
            font-size: 1.8rem;
            font-weight: 900;
            color: #0f172a;
        }

        .schedule-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(320px, 0.85fr);
            gap: 20px;
        }

        .calendar-card {
            padding: 22px;
        }

        .calendar-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .calendar-toolbar-title h2 {
            margin: 0;
            color: #0f172a;
            font-size: 1.5rem;
            font-weight: 900;
        }

        .calendar-toolbar-title p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .calendar-toolbar-actions,
        .calendar-toolbar-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .calendar-btn,
        .filter-select {
            border: 1px solid #e2e8f0;
            background: white;
            color: #334155;
            border-radius: 14px;
            padding: 10px 14px;
            font-weight: 700;
        }

        .calendar-btn.primary {
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
            border-color: transparent;
            color: white;
        }

        .calendar-grid-wrap {
            overflow: hidden;
            border-radius: 22px;
            border: 1px solid #e2e8f0;
        }

        .calendar-weekdays,
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .calendar-weekday {
            padding: 14px 10px;
            text-align: center;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            background: #fffdf8;
            border-bottom: 1px solid #e2e8f0;
        }

        .calendar-day {
            min-height: 148px;
            padding: 12px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            background: white;
            text-align: left;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .calendar-day:hover {
            background: #fffaf0;
        }

        .calendar-day.muted {
            background: #f8fafc;
            color: #94a3b8;
        }

        .calendar-day.today {
            box-shadow: inset 0 0 0 2px rgba(245, 158, 11, 0.45);
        }

        .calendar-day.selected {
            background: #fff7ed;
        }

        .calendar-day-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .calendar-day-number {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .calendar-day-tag {
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #f59e0b;
            color: white;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .calendar-event-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .calendar-event-pill {
            border-radius: 14px;
            padding: 8px 10px;
            border: 1px solid transparent;
            font-size: 0.74rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .calendar-event-pill.operations { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .calendar-event-pill.drill { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .calendar-event-pill.community { background: #f5f3ff; color: #6d28d9; border-color: #ddd6fe; }
        .calendar-event-pill.inspection { background: #ecfeff; color: #0f766e; border-color: #a5f3fc; }
        .calendar-event-pill.command { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .calendar-event-pill.logistics { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }

        .agenda-card {
            padding: 22px;
        }

        .agenda-header h3,
        .upcoming-card h4,
        .selected-day-card h4 {
            margin: 0;
            color: #0f172a;
        }

        .agenda-header p,
        .selected-day-card p {
            color: #64748b;
            font-size: 0.92rem;
        }

        .agenda-body {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 18px;
        }

        .selected-day-card,
        .upcoming-card,
        .agenda-event,
        .agenda-empty {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: #fffdf8;
            padding: 16px;
        }

        .eyebrow {
            font-size: 0.72rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .agenda-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .agenda-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .agenda-time {
            font-size: 0.84rem;
            font-weight: 800;
            color: #0f172a;
        }

        .agenda-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .upcoming-item {
            display: grid;
            grid-template-columns: 56px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
        }

        .upcoming-item + .upcoming-item {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        .upcoming-date {
            border-radius: 16px;
            background: #fff7ed;
            color: #9a3412;
            text-align: center;
            padding: 10px 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .upcoming-date strong {
            display: block;
            font-size: 1.2rem;
            color: #0f172a;
        }

        @media (max-width: 1180px) {
            .schedule-hero,
            .schedule-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .schedule-summary-grid {
                grid-template-columns: 1fr;
            }

            .calendar-weekdays,
            .calendar-grid {
                min-width: 760px;
            }
        }
    </style>
</head>
<body>
    @include('admin.partials.sidebar', ['active' => 'schedule'])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <main class="main-content">
        <nav class="top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Station Activity Schedule</h1>
                    <p class="text-xs text-slate-500 font-medium">Synced live with chief-created schedule events</p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-amber-50 rounded-full border border-amber-100">
                <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Admin Overview</span>
            </div>
        </nav>

        <div class="schedule-shell">
            <section class="schedule-hero">
                <div>
                    <h1>Track every station’s scheduled activities in one calendar.</h1>
                    <p>This admin calendar reads from the same `event_activities` records used by the chief schedule, so any station schedule created there appears here automatically.</p>
                    <div class="schedule-badges">
                        <span class="schedule-badge"><i class="fa-solid fa-link"></i> Same event source as chief</span>
                        <span class="schedule-badge"><i class="fa-solid fa-building"></i> Cross-station visibility</span>
                        <span class="schedule-badge"><i class="fa-solid fa-filter"></i> Station filter included</span>
                    </div>
                </div>

                <div class="schedule-summary-grid">
                    <div class="schedule-summary-card">
                        <div class="schedule-summary-label">Current Window</div>
                        <div class="schedule-summary-value" id="scheduleMonthSummary">{{ now()->format('F Y') }}</div>
                    </div>
                    <div class="schedule-summary-card">
                        <div class="schedule-summary-label">Visible Activities</div>
                        <div class="schedule-summary-value" id="scheduleEventCount">0</div>
                    </div>
                    <div class="schedule-summary-card">
                        <div class="schedule-summary-label">Next Highlight</div>
                        <div class="schedule-summary-value" id="scheduleNextHighlight" style="font-size:1.1rem;">Loading...</div>
                    </div>
                </div>
            </section>

            <section class="schedule-layout">
                <div class="calendar-card">
                    <div class="calendar-toolbar">
                        <div class="calendar-toolbar-title">
                            <h2 id="calendarMonthLabel">{{ now()->format('F Y') }}</h2>
                            <p>Review station events by month and filter the board to a single station when needed.</p>
                        </div>

                        <div class="calendar-toolbar-filters">
                            <select id="stationFilter" class="filter-select">
                                <option value="">All Stations</option>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->station_id }}">{{ $station->station_name }}</option>
                                @endforeach
                            </select>
                            <div class="calendar-toolbar-actions">
                                <button type="button" class="calendar-btn" id="prevMonthBtn"><i class="fa-solid fa-chevron-left"></i></button>
                                <button type="button" class="calendar-btn primary" id="todayMonthBtn">Today</button>
                                <button type="button" class="calendar-btn" id="nextMonthBtn"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="calendar-grid-wrap">
                        <div class="calendar-weekdays">
                            <div class="calendar-weekday">Sun</div>
                            <div class="calendar-weekday">Mon</div>
                            <div class="calendar-weekday">Tue</div>
                            <div class="calendar-weekday">Wed</div>
                            <div class="calendar-weekday">Thu</div>
                            <div class="calendar-weekday">Fri</div>
                            <div class="calendar-weekday">Sat</div>
                        </div>
                        <div id="adminScheduleCalendarGrid" class="calendar-grid"></div>
                    </div>
                </div>

                <aside class="agenda-card">
                    <div class="agenda-header">
                        <h3>Day Agenda</h3>
                        <p>Selected day details across the currently visible station scope.</p>
                    </div>

                    <div class="agenda-body">
                        <div class="selected-day-card">
                            <div class="eyebrow">Selected Day</div>
                            <h4 id="selectedDayLabel">{{ now()->format('F d, Y') }}</h4>
                            <p id="selectedDaySummary">Choose a date to inspect scheduled activities.</p>
                        </div>

                        <div id="selectedDayAgenda" class="agenda-list"></div>

                        <div class="upcoming-card">
                            <h4>Upcoming Activity Queue</h4>
                            <div id="upcomingScheduleList"></div>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const seedEvents = @json($scheduleEvents ?? []);
            const monthLabel = document.getElementById('calendarMonthLabel');
            const monthSummary = document.getElementById('scheduleMonthSummary');
            const eventCount = document.getElementById('scheduleEventCount');
            const nextHighlight = document.getElementById('scheduleNextHighlight');
            const grid = document.getElementById('adminScheduleCalendarGrid');
            const stationFilter = document.getElementById('stationFilter');
            const selectedDayLabel = document.getElementById('selectedDayLabel');
            const selectedDaySummary = document.getElementById('selectedDaySummary');
            const selectedDayAgenda = document.getElementById('selectedDayAgenda');
            const upcomingScheduleList = document.getElementById('upcomingScheduleList');

            const state = {
                viewDate: new Date(),
                selectedDateKey: formatDateKey(new Date()),
                selectedStationId: '',
            };

            function formatDateKey(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function formatLongDate(date) {
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            function formatMonthYear(date) {
                return date.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });
            }

            function getVisibleEvents() {
                return seedEvents.filter(function (event) {
                    return !state.selectedStationId || String(event.station_id) === String(state.selectedStationId);
                });
            }

            function getEventsForDate(dateKey) {
                return getVisibleEvents()
                    .filter(event => event.event_date === dateKey)
                    .sort((a, b) => `${a.event_date} ${a.start_time}`.localeCompare(`${b.event_date} ${b.start_time}`));
            }

            function getUpcomingEvents() {
                const todayKey = formatDateKey(new Date());

                return getVisibleEvents()
                    .filter(event => event.event_date >= todayKey)
                    .sort((a, b) => `${a.event_date} ${a.start_time}`.localeCompare(`${b.event_date} ${b.start_time}`))
                    .slice(0, 5);
            }

            function buildAgendaCard(event) {
                return `
                    <article class="agenda-event">
                        <div class="agenda-meta">
                            <div class="agenda-time">${event.schedule || `${event.start_time} - ${event.end_time}`}</div>
                            <span class="agenda-type ${event.activity_type || 'operations'}">
                                <i class="fa-solid fa-circle"></i>
                                ${event.activity_type || 'operations'}
                            </span>
                        </div>
                        <h5>${event.event}</h5>
                        <p><i class="fa-regular fa-building"></i> ${event.station_name || 'Unassigned Station'}</p>
                        <p><i class="fa-regular fa-location-dot"></i> ${event.barangay || 'No location set'}</p>
                        ${event.notes ? `<p>${event.notes}</p>` : ''}
                    </article>
                `;
            }

            function renderSelectedDay() {
                const selectedDate = new Date(`${state.selectedDateKey}T00:00:00`);
                const events = getEventsForDate(state.selectedDateKey);

                selectedDayLabel.textContent = formatLongDate(selectedDate);
                selectedDaySummary.textContent = events.length
                    ? `${events.length} scheduled activit${events.length === 1 ? 'y' : 'ies'} on this day.`
                    : 'No scheduled activities for this day in the current filter.';

                if (!events.length) {
                    selectedDayAgenda.innerHTML = `
                        <div class="agenda-empty">
                            <i class="fa-regular fa-calendar-days text-xl mb-3"></i>
                            <div class="text-sm font-semibold text-slate-600">Open day</div>
                            <div class="text-xs mt-1">No events are visible for the selected date and station filter.</div>
                        </div>
                    `;
                    return;
                }

                selectedDayAgenda.innerHTML = events.map(buildAgendaCard).join('');
            }

            function renderUpcoming() {
                const upcoming = getUpcomingEvents();
                const visibleEvents = getVisibleEvents();

                upcomingScheduleList.innerHTML = upcoming.length
                    ? upcoming.map(function (event) {
                        const date = new Date(`${event.event_date}T00:00:00`);
                        return `
                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    ${date.toLocaleDateString('en-US', { month: 'short' })}
                                    <strong>${date.getDate()}</strong>
                                </div>
                                <div class="upcoming-copy">
                                    <strong>${event.event}</strong>
                                    <p>${event.station_name || 'Unassigned Station'}</p>
                                    <p>${event.schedule || `${event.start_time} - ${event.end_time}`} · ${event.barangay || 'No location set'}</p>
                                </div>
                            </div>
                        `;
                    }).join('')
                    : '<div class="agenda-empty text-sm text-slate-500">No upcoming events in the current filter.</div>';

                eventCount.textContent = String(visibleEvents.length);

                const next = upcoming[0];
                nextHighlight.textContent = next
                    ? `${new Date(`${next.event_date}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} · ${next.event}`
                    : 'No events';
            }

            function renderCalendar() {
                const year = state.viewDate.getFullYear();
                const month = state.viewDate.getMonth();
                const firstOfMonth = new Date(year, month, 1);
                const firstCell = new Date(firstOfMonth);
                firstCell.setDate(firstCell.getDate() - firstOfMonth.getDay());
                const todayKey = formatDateKey(new Date());

                monthLabel.textContent = formatMonthYear(firstOfMonth);
                monthSummary.textContent = formatMonthYear(firstOfMonth);
                grid.innerHTML = '';

                for (let i = 0; i < 42; i += 1) {
                    const cellDate = new Date(firstCell);
                    cellDate.setDate(firstCell.getDate() + i);
                    const dateKey = formatDateKey(cellDate);
                    const events = getEventsForDate(dateKey);
                    const isCurrentMonth = cellDate.getMonth() === month;
                    const isToday = dateKey === todayKey;
                    const isSelected = dateKey === state.selectedDateKey;

                    const cell = document.createElement('button');
                    cell.type = 'button';
                    cell.className = `calendar-day${isCurrentMonth ? '' : ' muted'}${isToday ? ' today' : ''}${isSelected ? ' selected' : ''}`;
                    cell.innerHTML = `
                        <div class="calendar-day-head">
                            <span class="calendar-day-number">${cellDate.getDate()}</span>
                            ${events.length ? `<span class="calendar-day-tag">${events.length}</span>` : ''}
                        </div>
                        <div class="calendar-event-stack">
                            ${events.slice(0, 2).map(event => `
                                <div class="calendar-event-pill ${event.activity_type || 'operations'}">
                                    <div>${event.station_name || 'Station'}</div>
                                    <div>${event.event}</div>
                                </div>
                            `).join('')}
                        </div>
                    `;

                    cell.addEventListener('click', function () {
                        state.selectedDateKey = dateKey;
                        renderCalendar();
                        renderSelectedDay();
                    });

                    grid.appendChild(cell);
                }
            }

            document.getElementById('prevMonthBtn')?.addEventListener('click', function () {
                state.viewDate = new Date(state.viewDate.getFullYear(), state.viewDate.getMonth() - 1, 1);
                renderCalendar();
            });

            document.getElementById('nextMonthBtn')?.addEventListener('click', function () {
                state.viewDate = new Date(state.viewDate.getFullYear(), state.viewDate.getMonth() + 1, 1);
                renderCalendar();
            });

            document.getElementById('todayMonthBtn')?.addEventListener('click', function () {
                const today = new Date();
                state.viewDate = new Date(today.getFullYear(), today.getMonth(), 1);
                state.selectedDateKey = formatDateKey(today);
                renderCalendar();
                renderSelectedDay();
            });

            stationFilter?.addEventListener('change', function () {
                state.selectedStationId = stationFilter.value;
                renderUpcoming();
                renderCalendar();
                renderSelectedDay();
            });

            renderUpcoming();
            renderCalendar();
            renderSelectedDay();
        });
    </script>
</body>
</html>
