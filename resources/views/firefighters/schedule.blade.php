<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense · My Schedule</title>
    
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #1e3a5f 0%, #0f2b45 100%);
            color: white;
            z-index: 50;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .logo-text h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        
        .logo-text p {
            font-size: 11px;
            opacity: 0.7;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 16px;
            margin-top: 16px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .user-details h4 {
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }
        
        .user-details span {
            font-size: 11px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .sidebar-nav {
            padding: 20px;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            margin-bottom: 12px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-item.active {
            background: #3b82f6;
        }
        
        .nav-item i {
            width: 20px;
            font-size: 16px;
        }
        
        .nav-badge {
            margin-left: auto;
            background: #ef4444;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .logout-container {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        
        .logout-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.05);
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            background: rgba(239,68,68,0.2);
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            padding: 30px 40px;
            max-width: 1400px;
        }
        
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-radius: 16px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .welcome-subtitle {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59,130,246,0.1);
            border-color: #3b82f6;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .stat-title {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.blue { background: #dbeafe; color: #3b82f6; }
        .stat-icon.green { background: #dcfce7; color: #22c55e; }
        .stat-icon.purple { background: #f3e8ff; color: #a855f7; }
        .stat-icon.orange { background: #ffedd5; color: #f97316; }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .view-all {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Schedule Specific Styles */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .month-nav {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .month-nav button {
            background: white;
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .month-nav button:hover {
            background: #f3f4f6;
        }
        
        .current-month {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .view-buttons {
            display: flex;
            gap: 8px;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .view-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .calendar-grid {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .weekday {
            padding: 16px;
            text-align: center;
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        
        .calendar-day {
            min-height: 120px;
            padding: 12px;
            border-right: 1px solid #f3f4f6;
            border-bottom: 1px solid #f3f4f6;
            position: relative;
        }
        
        .calendar-day:nth-child(7n) {
            border-right: none;
        }
        
        .day-number {
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .calendar-day.today {
            background: #eff6ff;
        }
        
        .calendar-day.today .day-number {
            color: #3b82f6;
            font-weight: 700;
        }
        
        .calendar-day.other-month {
            background: #f9fafb;
        }
        
        .calendar-day.other-month .day-number {
            color: #9ca3af;
        }
        
        .event-item {
            background: #dbeafe;
            border-left: 3px solid #3b82f6;
            padding: 6px 8px;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .event-item:hover {
            transform: translateX(2px);
        }
        
        .event-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 2px;
        }
        
        .event-time {
            font-size: 9px;
            color: #6b7280;
        }
        
        .event-item.training {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        
        .event-item.training .event-title {
            color: #92400e;
        }
        
        .event-item.certification {
            background: #dcfce7;
            border-left-color: #22c55e;
        }
        
        .event-item.certification .event-title {
            color: #166534;
        }
        
        .add-event-btn {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .calendar-day:hover .add-event-btn {
            opacity: 1;
        }
        
        /* Upcoming Events List */
        .events-list {
            background: white;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .event-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        
        .event-card:hover {
            background: #f9fafb;
        }
        
        .event-date {
            text-align: center;
            min-width: 60px;
        }
        
        .event-day {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }
        
        .event-month {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .event-info {
            flex: 1;
        }
        
        .event-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .event-location {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .event-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .event-badge.training {
            background: #fef3c7;
            color: #92400e;
        }
        
        .event-badge.certification {
            background: #dcfce7;
            color: #166534;
        }
        
        .event-badge.meeting {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 24px;
            width: 500px;
            max-width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-outline {
            background: white;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }
        
        .btn-outline:hover {
            background: #f9fafb;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 30px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .status-dot.online { background: #22c55e; animation: pulse 2s infinite; }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
        }
        
        @media (max-width: 768px) {
            .grid-4, .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .weekday {
                font-size: 10px;
                padding: 8px;
            }
            
            .calendar-day {
                min-height: 80px;
                padding: 6px;
            }
            
            .day-number {
                font-size: 11px;
            }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'schedule'])
    <!-- MAIN CONTENT -->
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'My Schedule',
            'subtitle' => 'Duty calendar, shifts, and station commitments'
        ])

        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm sticky top-0 z-30" style="margin-bottom: 30px; border-radius: 12px;">
            <div class="px-4 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-3">
                        <button id="openSidebar" class="lg:hidden text-gray-700 focus:outline-none">
                            <i class="fa-solid fa-bars text-2xl"></i>
                        </button>
                        <div class="status-indicator">
                            <span class="status-dot online"></span>
                            <span class="text-sm">On Duty</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <span class="hidden sm:inline text-sm text-gray-600">Station 7</span>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                            <i class="fa-solid fa-circle"></i>
                            <span class="hidden sm:inline">On Duty</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1 class="welcome-title">My Schedule</h1>
            <p class="welcome-subtitle">
                Manage your duty shifts, training sessions, and important events
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid-4">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Upcoming Events</span>
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-value" id="upcomingCount">0</div>
                <div class="stat-trend">Next 30 days</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Training Sessions</span>
                    <div class="stat-icon green">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>
                <div class="stat-value" id="trainingCount">0</div>
                <div class="stat-trend">Scheduled this month</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Duty Shifts</span>
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value" id="shiftCount">0</div>
                <div class="stat-trend">This week</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Certifications Due</span>
                    <div class="stat-icon orange">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                </div>
                <div class="stat-value" id="certDueCount">0</div>
                <div class="stat-trend">Expiring soon</div>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="bg-white rounded-xl shadow p-5 mb-6">
            <div class="calendar-header">
                <div class="month-nav">
                    <button id="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="current-month" id="currentMonthYear">March 2024</span>
                    <button id="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="view-buttons">
                    <button class="view-btn active" data-view="month">Month</button>
                    <button class="view-btn" data-view="week">Week</button>
                    <button class="view-btn" data-view="list">List</button>
                </div>
            </div>

            <!-- Month View -->
            <div id="monthView">
                <div class="calendar-grid">
                    <div class="weekdays">
                        <div class="weekday">Sun</div>
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                        <div class="weekday">Sat</div>
                    </div>
                    <div class="calendar-days" id="calendarDays"></div>
                </div>
            </div>

            <!-- Week View (hidden by default) -->
            <div id="weekView" style="display: none;">
                <div class="calendar-grid" id="weekViewGrid"></div>
            </div>

            <!-- List View (hidden by default) -->
            <div id="listView" style="display: none;">
                <div class="events-list" id="eventsList"></div>
            </div>
        </div>

        <!-- Upcoming Events Section -->
        <div class="grid-2">
            <div>
                <div class="section-header">
                    <h2><i class="fa-solid fa-clock"></i> Upcoming Events</h2>
                    <button class="view-all" onclick="openAddEventModal()">+ Add Event</button>
                </div>
                <div class="events-list" id="upcomingEventsList"></div>
            </div>

            <div>
                <div class="section-header">
                    <h2><i class="fa-solid fa-chalkboard-user"></i> Training Reminders</h2>
                    <a href="{{ route('firefighter.training') }}" class="view-all">View All</a>
                </div>
                <div class="events-list" id="trainingRemindersList"></div>
            </div>
        </div>
    </main>

    <!-- Add Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px;">Add New Event</h3>
            <form id="eventForm">
                <div class="form-group">
                    <label class="form-label">Event Title</label>
                    <input type="text" class="form-input" id="eventTitle" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Event Type</label>
                    <select class="form-select" id="eventType">
                        <option value="training">Training</option>
                        <option value="certification">Certification</option>
                        <option value="meeting">Meeting</option>
                        <option value="shift">Duty Shift</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" id="eventDate" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" class="form-input" id="eventStartTime">
                </div>
                <div class="form-group">
                    <label class="form-label">End Time</label>
                    <input type="time" class="form-input" id="eventEndTime">
                </div>
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" id="eventLocation" placeholder="Station 7, Training Ground, etc.">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" id="eventDescription" rows="3" placeholder="Additional details..."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Event</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        // Sample events data
        let events = [
            { id: 1, title: 'Fire Safety Training', type: 'training', date: '2024-03-15', startTime: '09:00', endTime: '17:00', location: 'Training Ground', description: 'Annual fire safety refresher course' },
            { id: 2, title: 'CPR Certification', type: 'certification', date: '2024-03-20', startTime: '08:00', endTime: '12:00', location: 'Station 7', description: 'CPR recertification' },
            { id: 3, title: 'Station Meeting', type: 'meeting', date: '2024-03-10', startTime: '14:00', endTime: '15:30', location: 'Conference Room', description: 'Weekly station briefing' },
            { id: 4, title: 'Night Shift', type: 'shift', date: '2024-03-12', startTime: '20:00', endTime: '08:00', location: 'Station 7', description: 'Overnight duty' },
            { id: 5, title: 'Hazmat Training', type: 'training', date: '2024-03-25', startTime: '09:00', endTime: '16:00', location: 'Training Center', description: 'Hazardous materials handling' }
        ];

        let currentDate = new Date();
        let currentView = 'month';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar();
            updateStats();
            renderUpcomingEvents();
            
            // Sidebar toggle
            const sidebar = document.querySelector('.sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            
            if (openBtn) {
                openBtn.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                });
            }
            
            if (backdrop) {
                backdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }
            
            // View buttons
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentView = this.dataset.view;
                    renderCalendar();
                });
            });
            
            // Month navigation
            document.getElementById('prevMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
            
            document.getElementById('nextMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
            
            // Event form submission
            document.getElementById('eventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                addEvent();
            });
        });
        
        function renderCalendar() {
            if (currentView === 'month') {
                document.getElementById('monthView').style.display = 'block';
                document.getElementById('weekView').style.display = 'none';
                document.getElementById('listView').style.display = 'none';
                renderMonthView();
            } else if (currentView === 'week') {
                document.getElementById('monthView').style.display = 'none';
                document.getElementById('weekView').style.display = 'block';
                document.getElementById('listView').style.display = 'none';
                renderWeekView();
            } else {
                document.getElementById('monthView').style.display = 'none';
                document.getElementById('weekView').style.display = 'none';
                document.getElementById('listView').style.display = 'block';
                renderListView();
            }
        }
        
        function renderMonthView() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startDayOfWeek = firstDay.getDay();
            
            const today = new Date();
            const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;
            
            document.getElementById('currentMonthYear').textContent = 
                firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            let calendarHtml = '';
            let dayCount = 1;
            
            // Previous month days
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = startDayOfWeek - 1; i >= 0; i--) {
                const prevDay = prevMonthLastDay - i;
                calendarHtml += `<div class="calendar-day other-month">
                    <div class="day-number">${prevDay}</div>
                </div>`;
                dayCount++;
            }
            
            // Current month days
            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const dayEvents = events.filter(e => e.date === dateStr);
                const isToday = isCurrentMonth && d === today.getDate();
                
                let eventsHtml = '';
                dayEvents.forEach(event => {
                    eventsHtml += `
                        <div class="event-item ${event.type}" onclick="viewEvent(${event.id})">
                            <div class="event-title">${escapeHtml(event.title)}</div>
                            ${event.startTime ? `<div class="event-time">${event.startTime}</div>` : ''}
                        </div>
                    `;
                });
                
                calendarHtml += `
                    <div class="calendar-day ${isToday ? 'today' : ''}">
                        <div class="day-number">${d}</div>
                        ${eventsHtml}
                        <div class="add-event-btn" onclick="openAddEventModal('${dateStr}')">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                    </div>
                `;
            }
            
            // Next month days
            const totalCells = Math.ceil(dayCount / 7) * 7;
            let nextDay = 1;
            while (dayCount <= totalCells) {
                calendarHtml += `<div class="calendar-day other-month">
                    <div class="day-number">${nextDay}</div>
                </div>`;
                dayCount++;
                nextDay++;
            }
            
            document.getElementById('calendarDays').innerHTML = calendarHtml;
        }
        
        function renderWeekView() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const currentDay = currentDate.getDate();
            const firstDayOfWeek = new Date(year, month, currentDay - currentDate.getDay());
            
            let weekHtml = '<div class="weekdays">';
            const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            weekDays.forEach(day => {
                weekHtml += `<div class="weekday">${day}</div>`;
            });
            weekHtml += '</div><div class="calendar-days">';
            
            for (let i = 0; i < 7; i++) {
                const date = new Date(firstDayOfWeek);
                date.setDate(firstDayOfWeek.getDate() + i);
                const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
                const dayEvents = events.filter(e => e.date === dateStr);
                const isToday = date.toDateString() === new Date().toDateString();
                
                let eventsHtml = '';
                dayEvents.forEach(event => {
                    eventsHtml += `
                        <div class="event-item ${event.type}" style="margin-bottom: 6px;" onclick="viewEvent(${event.id})">
                            <div class="event-title">${escapeHtml(event.title)}</div>
                            ${event.startTime ? `<div class="event-time">${event.startTime}</div>` : ''}
                        </div>
                    `;
                });
                
                weekHtml += `
                    <div class="calendar-day ${isToday ? 'today' : ''}" style="min-height: 150px;">
                        <div class="day-number">${date.getDate()}</div>
                        ${eventsHtml}
                    </div>
                `;
            }
            
            weekHtml += '</div>';
            document.getElementById('weekViewGrid').innerHTML = weekHtml;
            document.getElementById('currentMonthYear').textContent = 
                `Week of ${firstDayOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${new Date(firstDayOfWeek.getTime() + 6*24*60*60*1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
        }
        
        function renderListView() {
            const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
            let listHtml = '';
            
            sortedEvents.forEach(event => {
                const eventDate = new Date(event.date);
                const typeLabels = {
                    training: 'Training',
                    certification: 'Certification',
                    meeting: 'Meeting',
                    shift: 'Duty Shift'
                };
                
                listHtml += `
                    <div class="event-card" onclick="viewEvent(${event.id})">
                        <div class="event-date">
                            <div class="event-day">${eventDate.getDate()}</div>
                            <div class="event-month">${eventDate.toLocaleDateString('en-US', { month: 'short' })}</div>
                        </div>
                        <div class="event-info">
                            <div class="event-name">${escapeHtml(event.title)}</div>
                            <div class="event-location">
                                <i class="fa-solid fa-location-dot"></i> ${escapeHtml(event.location || 'Station 7')}
                            </div>
                        </div>
                        <div class="event-badge ${event.type}">${typeLabels[event.type]}</div>
                    </div>
                `;
            });
            
            if (sortedEvents.length === 0) {
                listHtml = '<div class="empty-incidents text-center py-8"><i class="fa-regular fa-calendar text-4xl mb-2 opacity-50"></i><p>No events scheduled</p></div>';
            }
            
            document.getElementById('eventsList').innerHTML = listHtml;
            document.getElementById('currentMonthYear').textContent = 'All Events';
        }
        
        function renderUpcomingEvents() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const upcomingEvents = events
                .filter(e => new Date(e.date) >= today)
                .sort((a, b) => new Date(a.date) - new Date(b.date))
                .slice(0, 5);
            
            let eventsHtml = '';
            upcomingEvents.forEach(event => {
                const eventDate = new Date(event.date);
                const typeLabels = {
                    training: 'Training',
                    certification: 'Certification',
                    meeting: 'Meeting',
                    shift: 'Duty Shift'
                };
                
                eventsHtml += `
                    <div class="event-card" onclick="viewEvent(${event.id})">
                        <div class="event-date">
                            <div class="event-day">${eventDate.getDate()}</div>
                            <div class="event-month">${eventDate.toLocaleDateString('en-US', { month: 'short' })}</div>
                        </div>
                        <div class="event-info">
                            <div class="event-name">${escapeHtml(event.title)}</div>
                            <div class="event-location">
                                <i class="fa-solid fa-location-dot"></i> ${escapeHtml(event.location || 'Station 7')}
                            </div>
                        </div>
                        <div class="event-badge ${event.type}">${typeLabels[event.type]}</div>
                    </div>
                `;
            });
            
            if (upcomingEvents.length === 0) {
                eventsHtml = '<div class="text-center py-8 text-gray-500"><i class="fa-regular fa-calendar-check text-3xl mb-2"></i><p>No upcoming events</p></div>';
            }
            
            document.getElementById('upcomingEventsList').innerHTML = eventsHtml;
            
            // Training reminders
            const trainingEvents = events.filter(e => e.type === 'training' && new Date(e.date) >= today).slice(0, 3);
            let trainingHtml = '';
            trainingEvents.forEach(event => {
                const eventDate = new Date(event.date);
                trainingHtml += `
                    <div class="event-card" onclick="viewEvent(${event.id})">
                        <div class="event-date">
                            <div class="event-day">${eventDate.getDate()}</div>
                            <div class="event-month">${eventDate.toLocaleDateString('en-US', { month: 'short' })}</div>
                        </div>
                        <div class="event-info">
                            <div class="event-name">${escapeHtml(event.title)}</div>
                            <div class="event-location">
                                <i class="fa-solid fa-location-dot"></i> ${escapeHtml(event.location || 'Training Ground')}
                            </div>
                        </div>
                        <div class="event-badge training">Training</div>
                    </div>
                `;
            });
            
            if (trainingEvents.length === 0) {
                trainingHtml = '<div class="text-center py-8 text-gray-500"><i class="fa-solid fa-chalkboard-user text-3xl mb-2"></i><p>No upcoming training</p></div>';
            }
            
            document.getElementById('trainingRemindersList').innerHTML = trainingHtml;
        }
        
        function updateStats() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const nextMonth = new Date(today);
            nextMonth.setDate(today.getDate() + 30);
            
            const upcoming = events.filter(e => new Date(e.date) >= today && new Date(e.date) <= nextMonth);
            const trainings = events.filter(e => e.type === 'training' && new Date(e.date) >= today);
            const shifts = events.filter(e => e.type === 'shift' && new Date(e.date) >= today);
            const certsDue = events.filter(e => e.type === 'certification' && new Date(e.date) >= today && new Date(e.date) <= nextMonth);
            
            document.getElementById('upcomingCount').textContent = upcoming.length;
            document.getElementById('trainingCount').textContent = trainings.length;
            document.getElementById('shiftCount').textContent = shifts.length;
            document.getElementById('certDueCount').textContent = certsDue.length;
        }
        
        function openAddEventModal(date = null) {
            if (date) {
                document.getElementById('eventDate').value = date;
            }
            document.getElementById('eventModal').classList.add('active');
        }
        
        function closeEventModal() {
            document.getElementById('eventModal').classList.remove('active');
            document.getElementById('eventForm').reset();
        }
        
        function addEvent() {
            const newEvent = {
                id: events.length + 1,
                title: document.getElementById('eventTitle').value,
                type: document.getElementById('eventType').value,
                date: document.getElementById('eventDate').value,
                startTime: document.getElementById('eventStartTime').value,
                endTime: document.getElementById('eventEndTime').value,
                location: document.getElementById('eventLocation').value,
                description: document.getElementById('eventDescription').value
            };
            
            events.push(newEvent);
            closeEventModal();
            renderCalendar();
            updateStats();
            renderUpcomingEvents();
            showToast('Event added successfully!', 'success');
        }
        
        function viewEvent(id) {
            const event = events.find(e => e.id === id);
            if (event) {
                alert(`Event: ${event.title}\nDate: ${event.date}\nLocation: ${event.location || 'Station 7'}\nDescription: ${event.description || 'No description'}`);
            }
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast toast-${type}`;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        
        window.openAddEventModal = openAddEventModal;
        window.closeEventModal = closeEventModal;
        window.viewEvent = viewEvent;
    </script>

    <style>
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            z-index: 9999;
            display: none;
        }
        .toast-success { background: #22c55e; }
        .toast-error { background: #ef4444; }
        .toast-info { background: #3b82f6; }
        .stat-trend { font-size: 12px; color: #6b7280; margin-top: 8px; }
        .empty-incidents { text-align: center; padding: 40px; color: #9ca3af; }
    </style>
    @include('partials.pwa-body')
</body>
</html>

