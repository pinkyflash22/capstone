<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>PyroSense · Training</title>
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    <style>
        /* Copy all styles from certifications page here - same sidebar structure */
        * { font-family: 'Inter', sans-serif; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100%; background: linear-gradient(180deg, #1e3a5f 0%, #0f2b45 100%); color: white; z-index: 50; transition: transform 0.3s; overflow-y: auto; }
        .sidebar.open { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } }
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .logo-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-text h2 { font-size: 20px; font-weight: 700; margin: 0; }
        .logo-text p { font-size: 11px; opacity: 0.7; margin: 0; }
        .user-info { display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 16px; margin-top: 16px; }
        .user-avatar { width: 48px; height: 48px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; }
        .user-details h4 { font-size: 14px; font-weight: 600; margin: 0; }
        .user-details span { font-size: 11px; opacity: 0.8; display: flex; align-items: center; gap: 4px; }
        .sidebar-nav { padding: 20px; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.5; margin-bottom: 12px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; color: white; text-decoration: none; transition: all 0.2s; margin-bottom: 4px; }
        .nav-item:hover { background: rgba(255,255,255,0.1); }
        .nav-item.active { background: #3b82f6; }
        .nav-item i { width: 20px; }
        .logout-container { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; }
        .logout-btn { width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: rgba(255,255,255,0.05); border: none; border-radius: 12px; color: white; cursor: pointer; }
        .main-content { margin-left: 280px; padding: 30px 40px; max-width: 1400px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 20px; } }
        .welcome-banner { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 16px; padding: 30px; color: white; margin-bottom: 30px; }
        .welcome-title { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(59,130,246,0.1); border-color: #3b82f6; }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .stat-title { color: #6b7280; font-size: 14px; font-weight: 500; text-transform: uppercase; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-icon.blue { background: #dbeafe; color: #3b82f6; }
        .stat-icon.green { background: #dcfce7; color: #22c55e; }
        .stat-icon.purple { background: #f3e8ff; color: #a855f7; }
        .stat-icon.orange { background: #ffedd5; color: #f97316; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 30px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { font-size: 20px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px; }
        .view-all { color: #3b82f6; text-decoration: none; font-size: 14px; font-weight: 500; cursor: pointer; }
        .training-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e5e7eb; transition: all 0.2s; margin-bottom: 16px; display: flex; gap: 16px; }
        .training-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .training-date { text-align: center; min-width: 80px; padding: 12px; background: #f3f4f6; border-radius: 12px; }
        .training-day { font-size: 28px; font-weight: 700; color: #1f2937; line-height: 1; }
        .training-month { font-size: 11px; color: #6b7280; text-transform: uppercase; }
        .training-info { flex: 1; }
        .training-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .training-meta { display: flex; gap: 16px; margin: 8px 0; font-size: 13px; color: #6b7280; }
        .training-meta i { width: 16px; color: #3b82f6; }
        .training-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-upcoming { background: #dbeafe; color: #1e40af; }
        .badge-ongoing { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .status-indicator { display: flex; align-items: center; gap: 8px; background: #f3f4f6; padding: 6px 12px; border-radius: 30px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; }
        .status-dot.online { background: #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.7; } }
        .btn { padding: 10px 16px; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: none; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline { background: white; border: 1px solid #e5e7eb; color: #4b5563; }
        .btn-outline:hover { background: #f9fafb; }
        @media (max-width: 768px) {
            .main-content { padding: 12px 14px calc(112px + env(safe-area-inset-bottom, 0px)); }
            .grid-4, .grid-2 { grid-template-columns: 1fr; gap: 14px; }
            .welcome-banner { padding: 20px 18px; border-radius: 22px; }
            .welcome-title { font-size: 1.9rem; }
            .section-header { flex-direction: column; align-items: stretch; gap: 12px; }
            .section-header > * { width: 100%; }
            .training-card { flex-direction: column; border-radius: 18px; }
            .training-date { display: flex; align-items: center; justify-content: space-between; }
            .view-all { display: inline-flex; justify-content: center; }
            .modal { align-items: flex-end; padding: 10px; }
            .modal-content {
                width: 100%;
                max-width: 100%;
                max-height: min(84dvh, 760px);
                border-radius: 24px 24px 18px 18px;
                overflow-y: auto;
            }
            .modal-content::before {
                content: "";
                display: block;
                width: 52px;
                height: 5px;
                border-radius: 999px;
                background: #cbd5e1;
                margin: 0 auto 14px;
            }
        }
        @media (max-width: 430px) {
            .main-content { padding-left: 12px; padding-right: 12px; }
            .welcome-banner { padding: 18px 16px; }
            .stat-card { padding: 16px; }
        }
        .toast { position: fixed; bottom: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; z-index: 9999; display: none; }
        .toast-success { background: #22c55e; }
        .stat-trend { font-size: 12px; color: #6b7280; margin-top: 8px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; padding: 24px; width: 500px; max-width: min(100%, calc(100vw - 24px)); max-height: calc(100dvh - 24px); overflow-y: auto; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'training'])
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'Training',
            'subtitle' => 'Programs, drills, and development sessions'
        ])

        <nav class="bg-white shadow-sm sticky top-0 z-30" style="margin-bottom: 30px; border-radius: 12px;">...</nav>
        <div class="welcome-banner"><h1 class="welcome-title">Training & Development</h1><p class="welcome-subtitle">Enhance your skills with scheduled training sessions</p></div>
        <div class="grid-4"><div class="stat-card"><div class="stat-header"><span class="stat-title">Upcoming</span><div class="stat-icon blue"><i class="fa-solid fa-calendar"></i></div></div><div class="stat-value" id="upcomingCount">0</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-title">In Progress</span><div class="stat-icon orange"><i class="fa-solid fa-spinner"></i></div></div><div class="stat-value" id="ongoingCount">0</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-title">Completed</span><div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div></div><div class="stat-value" id="completedCount">0</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-title">Hours Trained</span><div class="stat-icon purple"><i class="fa-solid fa-clock"></i></div></div><div class="stat-value" id="hoursCount">0</div></div></div>
        <div class="section-header"><h2><i class="fa-solid fa-chalkboard-user"></i> Training Sessions</h2><button class="view-all" onclick="openRegisterModal()">+ Register for Training</button></div>
        <div id="trainingList"></div>
        <div class="section-header mt-4"><h2><i class="fa-solid fa-history"></i> Training History</h2></div>
        <div id="historyList" class="bg-white rounded-xl shadow p-5"></div>
    </main>
    <div id="registerModal" class="modal"><div class="modal-content"><h3>Register for Training</h3><form id="registerForm"><div class="form-group"><label class="form-label">Training Title</label><input type="text" class="form-input" id="regTitle" required></div><div class="form-group"><label class="form-label">Training Date</label><input type="date" class="form-input" id="regDate" required></div><div class="form-group"><label class="form-label">Location</label><input type="text" class="form-input" id="regLocation" required></div><div class="form-group"><label class="form-label">Duration (hours)</label><input type="number" class="form-input" id="regDuration" required></div><div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" onclick="closeRegisterModal()">Cancel</button><button type="submit" class="btn btn-primary">Register</button></div></form></div></div>
    <script>
        let trainings = [
            { id: 1, title: 'Advanced Firefighting Techniques', date: '2024-03-20', location: 'Training Ground', duration: 8, status: 'upcoming', registered: true },
            { id: 2, title: 'First Aid & CPR', date: '2024-03-10', location: 'Station 7', duration: 4, status: 'ongoing', registered: true },
            { id: 3, title: 'Hazmat Awareness', date: '2024-02-15', location: 'Training Center', duration: 6, status: 'completed', registered: true }
        ];
        function renderTraining() { const container = document.getElementById('trainingList'); let html = ''; trainings.filter(t => t.status !== 'completed').forEach(t => { const statusText = t.status === 'upcoming' ? 'Upcoming' : 'In Progress'; const statusClass = t.status === 'upcoming' ? 'badge-upcoming' : 'badge-ongoing'; const date = new Date(t.date); html += `<div class="training-card"><div class="training-date"><div class="training-day">${date.getDate()}</div><div class="training-month">${date.toLocaleDateString('en-US', { month: 'short' })}</div></div><div class="training-info"><div class="training-title">${escapeHtml(t.title)}</div><div class="training-meta"><span><i class="fa-solid fa-location-dot"></i> ${escapeHtml(t.location)}</span><span><i class="fa-regular fa-clock"></i> ${t.duration} hours</span></div><div class="training-badge ${statusClass}">${statusText}</div></div><div><button class="btn btn-outline" onclick="viewTraining(${t.id})"><i class="fa-regular fa-eye"></i> Details</button></div></div>`; }); container.innerHTML = html || '<div class="text-center py-8 text-gray-500">No upcoming training sessions</div>'; updateStats(); }
        function updateStats() { const upcoming = trainings.filter(t => t.status === 'upcoming').length; const ongoing = trainings.filter(t => t.status === 'ongoing').length; const completed = trainings.filter(t => t.status === 'completed').length; const hours = trainings.filter(t => t.status === 'completed').reduce((sum, t) => sum + t.duration, 0); document.getElementById('upcomingCount').textContent = upcoming; document.getElementById('ongoingCount').textContent = ongoing; document.getElementById('completedCount').textContent = completed; document.getElementById('hoursCount').textContent = hours; const historyContainer = document.getElementById('historyList'); historyContainer.innerHTML = `<div class="space-y-3">${trainings.filter(t => t.status === 'completed').map(t => `<div class="flex justify-between items-center p-3 border-b"><div><div class="font-medium">${escapeHtml(t.title)}</div><div class="text-sm text-gray-500">${formatDate(t.date)} · ${t.location}</div></div><span class="text-sm text-gray-500">${t.duration} hours</span></div>`).join('')}</div>`; }
        function openRegisterModal() { document.getElementById('registerModal').classList.add('active'); }
        function closeRegisterModal() { document.getElementById('registerModal').classList.remove('active'); document.getElementById('registerForm').reset(); }
        document.getElementById('registerForm')?.addEventListener('submit', function(e) { e.preventDefault(); const newTraining = { id: trainings.length + 1, title: document.getElementById('regTitle').value, date: document.getElementById('regDate').value, location: document.getElementById('regLocation').value, duration: parseInt(document.getElementById('regDuration').value), status: 'upcoming', registered: true }; trainings.push(newTraining); closeRegisterModal(); renderTraining(); showToast('Registered for training successfully!', 'success'); });
        function viewTraining(id) { const t = trainings.find(t => t.id === id); if (t) alert(`Training: ${t.title}\nDate: ${formatDate(t.date)}\nLocation: ${t.location}\nDuration: ${t.duration} hours\nStatus: ${t.status}`); }
        function formatDate(dateStr) { const d = new Date(dateStr); return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }); }
        function escapeHtml(text) { if (!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        function showToast(message, type) { window.PyroNotify?.notify(message, type); }
        document.addEventListener('DOMContentLoaded', function() { renderTraining(); const sidebar = document.querySelector('.sidebar'); const openBtn = document.getElementById('openSidebar'); const backdrop = document.getElementById('sidebarBackdrop'); if (openBtn) openBtn.addEventListener('click', () => { sidebar.classList.add('open'); backdrop.classList.remove('hidden'); }); if (backdrop) backdrop.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.classList.add('hidden'); }); });
        window.openRegisterModal = openRegisterModal; window.closeRegisterModal = closeRegisterModal; window.viewTraining = viewTraining;
    </script>
    @include('partials.pwa-body')
</body>
</html>
