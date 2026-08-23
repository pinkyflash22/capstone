<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense · My Certifications</title>
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    <style>
        /* Copy all styles from schedule page here - same sidebar, main content, etc. */
        * { font-family: 'Inter', sans-serif; }
        
        /* Sidebar Styles (same as schedule page) */
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
        
        .sidebar.open { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } }
        
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .logo-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-text h2 { font-size: 20px; font-weight: 700; margin: 0; }
        .logo-text p { font-size: 11px; opacity: 0.7; margin: 0; }
        
        .user-info { display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 16px; margin-top: 16px; }
        .user-avatar { width: 48px; height: 48px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; }
        .user-details h4 { font-size: 14px; font-weight: 600; margin: 0 0 4px 0; }
        .user-details span { font-size: 11px; opacity: 0.8; display: flex; align-items: center; gap: 4px; }
        
        .sidebar-nav { padding: 20px; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.5; margin-bottom: 12px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; color: white; text-decoration: none; transition: all 0.2s; margin-bottom: 4px; }
        .nav-item:hover { background: rgba(255,255,255,0.1); }
        .nav-item.active { background: #3b82f6; }
        .nav-item i { width: 20px; font-size: 16px; }
        
        .logout-container { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; }
        .logout-btn { width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: rgba(255,255,255,0.05); border: none; border-radius: 12px; color: white; cursor: pointer; }
        
        .main-content { margin-left: 280px; padding: 30px 40px; max-width: 1400px; }
        @media (max-width: 1024px) { .main-content { margin-left: 0; padding: 20px; } }
        
        .welcome-banner { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 16px; padding: 30px; color: white; margin-bottom: 30px; }
        .welcome-title { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .welcome-subtitle { opacity: 0.9; font-size: 16px; }
        
        .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(59,130,246,0.1); border-color: #3b82f6; }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .stat-title { color: #6b7280; font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-icon.blue { background: #dbeafe; color: #3b82f6; }
        .stat-icon.green { background: #dcfce7; color: #22c55e; }
        .stat-icon.red { background: #fee2e2; color: #ef4444; }
        .stat-icon.purple { background: #f3e8ff; color: #a855f7; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 30px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { font-size: 20px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px; }
        .view-all { color: #3b82f6; text-decoration: none; font-size: 14px; font-weight: 500; }
        
        /* Certification Card Styles */
        .cert-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e5e7eb; transition: all 0.2s; margin-bottom: 16px; }
        .cert-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .cert-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .cert-icon { width: 56px; height: 56px; background: #dbeafe; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #3b82f6; }
        .cert-info { flex: 1; }
        .cert-name { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .cert-issuer { font-size: 13px; color: #6b7280; display: flex; align-items: center; gap: 4px; }
        .cert-details { display: flex; gap: 24px; margin-bottom: 16px; padding: 12px 0; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; }
        .cert-detail { flex: 1; }
        .cert-detail-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .cert-detail-value { font-size: 14px; font-weight: 600; color: #1f2937; }
        .cert-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-expiring { background: #fef3c7; color: #92400e; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        
        .progress-bar { height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; margin-top: 8px; }
        .progress-fill { height: 100%; background: #22c55e; border-radius: 3px; transition: width 0.3s; }
        
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
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; gap: 14px; }
            .welcome-banner { padding: 20px 18px; border-radius: 22px; }
            .welcome-title { font-size: 1.9rem; }
            .stat-card, .cert-card { border-radius: 18px; }
            .section-header { flex-direction: column; align-items: stretch; gap: 12px; }
            .section-header > * { width: 100%; }
            .cert-header,
            .cert-details { flex-direction: column; align-items: stretch; gap: 12px; }
            .cert-detail { width: 100%; }
            .view-all { display: inline-flex; justify-content: center; }
        }
        @media (max-width: 430px) {
            .main-content { padding-left: 12px; padding-right: 12px; }
            .welcome-banner { padding: 18px 16px; }
            .stat-card { padding: 16px; }
        }
        
        .toast { position: fixed; bottom: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; z-index: 9999; display: none; }
        .toast-success { background: #22c55e; }
        .stat-trend { font-size: 12px; color: #6b7280; margin-top: 8px; }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'certifications'])

    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'Certifications',
            'subtitle' => 'Track active credentials and renewal timelines'
        ])

        <nav class="bg-white shadow-sm sticky top-0 z-30" style="margin-bottom: 30px; border-radius: 12px;">
            <div class="px-4 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center gap-3">
                        <button id="openSidebar" class="lg:hidden text-gray-700 focus:outline-none"><i class="fa-solid fa-bars text-2xl"></i></button>
                        <div class="status-indicator"><span class="status-dot online"></span><span class="text-sm">On Duty</span></div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="hidden sm:inline text-sm text-gray-600">Station 7</span>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2"><i class="fa-solid fa-circle"></i><span class="hidden sm:inline">On Duty</span></button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="welcome-banner">
            <h1 class="welcome-title">My Certifications</h1>
            <p class="welcome-subtitle">Track your professional certifications and credentials</p>
        </div>

        <div class="grid-4">
            <div class="stat-card"><div class="stat-header"><span class="stat-title">Active</span><div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div></div><div class="stat-value" id="activeCount">0</div><div class="stat-trend">Current certifications</div></div>
            <div class="stat-card"><div class="stat-header"><span class="stat-title">Expiring Soon</span><div class="stat-icon orange"><i class="fa-solid fa-clock"></i></div></div><div class="stat-value" id="expiringCount">0</div><div class="stat-trend">Within 30 days</div></div>
            <div class="stat-card"><div class="stat-header"><span class="stat-title">Expired</span><div class="stat-icon red"><i class="fa-solid fa-exclamation-triangle"></i></div></div><div class="stat-value" id="expiredCount">0</div><div class="stat-trend">Need renewal</div></div>
            <div class="stat-card"><div class="stat-header"><span class="stat-title">Total Earned</span><div class="stat-icon purple"><i class="fa-solid fa-award"></i></div></div><div class="stat-value" id="totalCount">0</div><div class="stat-trend">Lifetime achievements</div></div>
        </div>

        <div class="section-header"><h2><i class="fa-solid fa-certificate"></i> My Certifications</h2><button class="view-all" onclick="showUploadModal()"><i class="fa-solid fa-upload"></i> Upload New</button></div>
        <div id="certificationsList" class="grid-3"></div>

        <div class="section-header mt-4"><h2><i class="fa-solid fa-history"></i> Certification History</h2></div>
        <div id="historyList" class="bg-white rounded-xl shadow p-5"></div>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal"><div class="modal-content"><h3 style="margin-bottom: 20px;">Upload Certification</h3><form id="uploadForm"><div class="form-group"><label class="form-label">Certification Name</label><input type="text" class="form-input" id="certName" required></div><div class="form-group"><label class="form-label">Issued By</label><input type="text" class="form-input" id="certIssuer" required></div><div class="form-group"><label class="form-label">Issue Date</label><input type="date" class="form-input" id="certIssueDate" required></div><div class="form-group"><label class="form-label">Expiry Date</label><input type="date" class="form-input" id="certExpiryDate"></div><div class="form-group"><label class="form-label">Certificate Number</label><input type="text" class="form-input" id="certNumber" placeholder="Optional"></div><div class="form-group"><label class="form-label">Upload File</label><input type="file" class="form-input" id="certFile" accept=".pdf,.jpg,.png"></div><div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" onclick="closeUploadModal()">Cancel</button><button type="submit" class="btn btn-primary">Upload</button></div></form></div></div>

    <script>
        let certifications = [
            { id: 1, name: 'Firefighter I', issuer: 'BFP', issueDate: '2022-01-15', expiryDate: '2025-01-15', number: 'FF-001-2022', status: 'active' },
            { id: 2, name: 'Basic Life Support (BLS)', issuer: 'Philippine Red Cross', issueDate: '2023-06-10', expiryDate: '2025-06-10', number: 'BLS-2023-789', status: 'active' },
            { id: 3, name: 'Hazmat Operations', issuer: 'BFP', issueDate: '2021-03-20', expiryDate: '2024-03-20', number: 'HZ-456-2021', status: 'expiring' },
            { id: 4, name: 'Emergency Medical Responder', issuer: 'DOH', issueDate: '2020-11-05', expiryDate: '2023-11-05', number: 'EMR-2020-234', status: 'expired' }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            renderCertifications();
            updateStats();
            
            const sidebar = document.querySelector('.sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (openBtn) openBtn.addEventListener('click', () => { sidebar.classList.add('open'); backdrop.classList.remove('hidden'); });
            if (backdrop) backdrop.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.classList.add('hidden'); });
            
            document.getElementById('uploadForm').addEventListener('submit', function(e) { e.preventDefault(); uploadCertification(); });
        });
        
        function renderCertifications() {
            const container = document.getElementById('certificationsList');
            let html = '';
            certifications.forEach(cert => {
                const today = new Date();
                const expiryDate = new Date(cert.expiryDate);
                const daysUntilExpiry = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                let statusClass = 'status-active';
                let statusText = 'Active';
                if (daysUntilExpiry < 0) { statusClass = 'status-expired'; statusText = 'Expired'; }
                else if (daysUntilExpiry <= 30) { statusClass = 'status-expiring'; statusText = `Expires in ${daysUntilExpiry} days`; }
                
                html += `<div class="cert-card"><div class="cert-header"><div class="cert-icon"><i class="fa-solid fa-certificate"></i></div><div class="cert-info"><div class="cert-name">${escapeHtml(cert.name)}</div><div class="cert-issuer"><i class="fa-solid fa-building"></i> ${escapeHtml(cert.issuer)}</div></div><div class="cert-status ${statusClass}">${statusText}</div></div>
                <div class="cert-details"><div class="cert-detail"><div class="cert-detail-label">Issue Date</div><div class="cert-detail-value">${formatDate(cert.issueDate)}</div></div>
                <div class="cert-detail"><div class="cert-detail-label">Expiry Date</div><div class="cert-detail-value">${formatDate(cert.expiryDate)}</div></div>
                <div class="cert-detail"><div class="cert-detail-label">Certificate #</div><div class="cert-detail-value">${escapeHtml(cert.number) || 'N/A'}</div></div></div>`;
                
                if (daysUntilExpiry > 0 && daysUntilExpiry <= 30) {
                    const progress = ((365 - daysUntilExpiry) / 365) * 100;
                    html += `<div class="progress-bar"><div class="progress-fill" style="width: ${progress}%"></div></div><div class="stat-trend mt-2"><i class="fa-solid fa-bell"></i> Renewal reminder</div>`;
                }
                html += `<div class="mt-3 flex gap-2"><button class="btn btn-outline btn-sm" onclick="viewCert(${cert.id})"><i class="fa-regular fa-eye"></i> View</button>`;
                if (daysUntilExpiry < 0 || daysUntilExpiry <= 30) html += `<button class="btn btn-primary btn-sm" onclick="renewCert(${cert.id})"><i class="fa-solid fa-rotate"></i> Renew</button>`;
                html += `</div></div>`;
            });
            container.innerHTML = html || '<div class="text-center py-8 text-gray-500">No certifications found</div>';
            
            // History
            const historyContainer = document.getElementById('historyList');
            const historyHtml = `<div class="space-y-3">${certifications.map(cert => `<div class="flex justify-between items-center p-3 border-b"><div><div class="font-medium">${escapeHtml(cert.name)}</div><div class="text-sm text-gray-500">${escapeHtml(cert.issuer)} · ${formatDate(cert.issueDate)}</div></div><span class="text-sm text-gray-500">${cert.number || 'N/A'}</span></div>`).join('')}</div>`;
            historyContainer.innerHTML = historyHtml;
        }
        
        function updateStats() {
            const today = new Date();
            const active = certifications.filter(c => new Date(c.expiryDate) > today).length;
            const expiring = certifications.filter(c => { const diff = Math.ceil((new Date(c.expiryDate) - today) / (1000 * 60 * 60 * 24)); return diff > 0 && diff <= 30; }).length;
            const expired = certifications.filter(c => new Date(c.expiryDate) < today).length;
            document.getElementById('activeCount').textContent = active;
            document.getElementById('expiringCount').textContent = expiring;
            document.getElementById('expiredCount').textContent = expired;
            document.getElementById('totalCount').textContent = certifications.length;
        }
        
        function showUploadModal() { document.getElementById('uploadModal').classList.add('active'); }
        function closeUploadModal() { document.getElementById('uploadModal').classList.remove('active'); document.getElementById('uploadForm').reset(); }
        
        function uploadCertification() {
            const newCert = { id: certifications.length + 1, name: document.getElementById('certName').value, issuer: document.getElementById('certIssuer').value, issueDate: document.getElementById('certIssueDate').value, expiryDate: document.getElementById('certExpiryDate').value || '2099-12-31', number: document.getElementById('certNumber').value, status: 'active' };
            certifications.push(newCert);
            closeUploadModal();
            renderCertifications();
            updateStats();
            showToast('Certification uploaded successfully!', 'success');
        }
        
        function viewCert(id) { const cert = certifications.find(c => c.id === id); if (cert) alert(`Certification: ${cert.name}\nIssuer: ${cert.issuer}\nIssue Date: ${formatDate(cert.issueDate)}\nExpiry Date: ${formatDate(cert.expiryDate)}\nNumber: ${cert.number || 'N/A'}`); }
        function renewCert(id) { alert('Renewal request submitted. A representative will contact you soon.'); }
        
        function formatDate(dateStr) { if (!dateStr) return 'N/A'; const d = new Date(dateStr); return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }); }
        function escapeHtml(text) { if (!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        function showToast(message, type) { window.PyroNotify?.notify(message, type); }
        
        window.showUploadModal = showUploadModal; window.closeUploadModal = closeUploadModal; window.viewCert = viewCert; window.renewCert = renewCert;
    </script>
    <style>.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 12px; }.modal.active { display: flex; }.modal-content { background: white; border-radius: 16px; padding: 24px; width: 500px; max-width: min(100%, calc(100vw - 24px)); max-height: calc(100dvh - 24px); overflow-y: auto; }.form-group { margin-bottom: 16px; }.form-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }.form-input, .form-select { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; } @media (max-width: 768px) { .modal { align-items: flex-end; padding: 10px; } .modal-content { width: 100%; max-width: 100%; max-height: min(84dvh, 760px); border-radius: 24px 24px 18px 18px; } .modal-content::before { content:''; display:block; width:52px; height:5px; border-radius:999px; background:#cbd5e1; margin:0 auto 14px; } }</style>
    @include('partials.pwa-body')
</body>
</html>

