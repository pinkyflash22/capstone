{{-- resources/views/admin/analytics.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <title>PyroSense · AI Predictive Analytics</title>
    
    @vite(['resources/css/chief/dashboard.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            margin: 0;
            display: flex;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 50;
            overflow-y: auto;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 24px 20px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i { font-size: 24px; }
        .logo-text h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .logo-text p { font-size: 12px; opacity: 0.8; }

        .user-info {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #4b5563;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        .nav-item:hover { background: #f3f4f6; }
        .nav-item.active { background: #fee2e2; color: #dc2626; font-weight: 500; }

        .main-content {
            margin-left: 280px;
            flex: 1;
            background-color: #f3f4f6;
            min-height: 100vh;
        }

        .top-nav {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 30;
            padding: 16px 24px;
        }

        .top-nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .station-badge {
            background: #fef2f2;
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .emergency-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .emergency-btn:hover {
            background: #b91c1c;
        }

        .content-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .risk-high { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; }
        .risk-medium { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .risk-low { background: linear-gradient(135deg, #10b981, #059669); color: white; }

        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #dc2626;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            display: none;
        }
        .loading-overlay.active { display: flex; }

        .form-select {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            width: 100%;
        }

        .recommendation-card {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        .recommendation-card.critical { background: #fef2f2; border-left: 4px solid #dc2626; }
        .recommendation-card.high { background: #fffbeb; border-left: 4px solid #f59e0b; }
        .recommendation-card.medium { background: #f0fdf4; border-left: 4px solid #10b981; }
        .recommendation-card.low { background: #f8fafc; border-left: 4px solid #94a3b8; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            #menuToggle { display: block !important; }
        }
    </style>
</head>
<body class="chief-dashboard-page">
    @include('admin.partials.sidebar', ['active' => 'analytics'])
    {{-- <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-area">
                <div class="logo-icon"><i class="fa-solid fa-fire-extinguisher"></i></div>
                <div class="logo-text"><h2>PyroSense</h2><p>BFP Command Center</p></div>
            </div>
            <div class="user-info">
                <div><strong>{{ auth()->user()->first_name ?? 'Admin' }} {{ auth()->user()->last_name ?? '' }}</strong><br>
                <small>{{ auth()->user()->role->role ?? 'Administrator' }}</small></div>
            </div>
        </div>
        <nav style="padding: 20px;">
            <a href="#" class="nav-item active"><i class="fa-solid fa-chart-line"></i> Predictive Analytics</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-truck"></i> Active Units</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-map-location-dot"></i> Incident Map</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
        </nav>
    </aside> --}}

    <main class="main-content">
        <div class="top-nav">
            <div class="top-nav-container">
                <button id="menuToggle" style="background:none;border:none;font-size:24px;display:none;cursor:pointer;">☰</button>
                <div></div>
                <div style="display:flex;gap:16px;align-items:center;">
                    <span class="station-badge" id="selectedStationBadge">Select Station</span>
                    <button class="emergency-btn" onclick="emergencyCall()"><i class="fa-solid fa-phone-alt"></i> Emergency</button>
                </div>
            </div>
        </div>

        <div class="content-container">
            <div style="margin-bottom:24px;">
                <h1 style="font-size:28px;font-weight:700;">🤖 AI-Powered Predictive Analytics</h1>
                <p style="color:#6b7280;">Real-time AI analysis using your actual incident data</p>
            </div>

            <!-- Station Selector -->
            <div class="stat-card" style="margin-bottom:24px;">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-user"></i> Select Location for Analysis</h3>
                <div style="display:grid;grid-template-columns:1fr auto;gap:16px;">
                    <select id="stationSelect" class="form-select">
                        <option value="">Loading locations...</option>
                    </select>
                    <button id="analyzeBtn" style="background:#dc2626;color:white;padding:10px 24px;border:none;border-radius:8px;font-weight:600;cursor:pointer;">
                        <i class="fa-solid fa-chart-line"></i> Run AI Analysis
                    </button>
                </div>
                <div style="margin-top:16px;">
                    <select id="periodSelect" class="form-select" style="width:200px;">
                        <option value="7">Last 7 days</option>
                        <option value="30" selected>Last 30 days</option>
                        <option value="90">Last 3 months</option>
                        <option value="365">Last year</option>
                    </select>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:24px;">
                <!-- Risk Score Card -->
                <div class="stat-card" id="riskCard">
                    <h3><i class="fa-solid fa-gauge-high"></i> AI Risk Assessment</h3>
                    <div style="text-align:center;padding:20px;">
                        <div style="font-size:48px;font-weight:800;" id="riskScore">--</div>
                        <div id="riskLabel" style="font-size:14px;margin-top:8px;">Loading...</div>
                        <div id="confidenceScore" style="font-size:12px;margin-top:4px;">AI Confidence: --%</div>
                    </div>
                    <div id="riskFactors" style="margin-top:16px;"></div>
                </div>

                <!-- Incident Stats -->
                <div class="stat-card">
                    <h3><i class="fa-solid fa-fire"></i> Real Incident Statistics</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
                        <div><small>Total Incidents</small><div style="font-size:28px;font-weight:700;" id="totalIncidents">--</div></div>
                        <div><small>Fire Incidents</small><div style="font-size:28px;font-weight:700;" id="fireIncidents">--</div></div>
                        <div><small>Avg Response Time</small><div style="font-size:20px;font-weight:700;" id="avgResponse">--</div></div>
                        <div><small>Peak Hour</small><div style="font-size:20px;font-weight:700;" id="peakHour">--</div></div>
                    </div>
                    <div id="incidentTypes" style="margin-top:16px;font-size:13px;"></div>
                </div>

                <!-- Resources -->
                <div class="stat-card">
                    <h3><i class="fa-solid fa-truck"></i> Resources & Readiness</h3>
                    <div style="margin-top:16px;">
                        <div><span>Available Personnel</span> <span id="availablePersonnel" style="float:right;font-weight:700;">--</span></div>
                        <div style="background:#e5e7eb;height:8px;border-radius:4px;margin:8px 0;"><div id="personnelBar" style="background:#10b981;height:8px;border-radius:4px;width:0%"></div></div>
                        <div><span>Available Equipment</span> <span id="availableEquipment" style="float:right;font-weight:700;">--</span></div>
                        <div style="background:#e5e7eb;height:8px;border-radius:4px;margin:8px 0;"><div id="equipmentBar" style="background:#3b82f6;height:8px;border-radius:4px;width:0%"></div></div>
                        <div><span>Response Units</span> <span id="responseUnits" style="float:right;font-weight:700;">--</span></div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;">
                <div class="stat-card">
                    <h3><i class="fa-solid fa-chart-line"></i> Hourly Incident Pattern</h3>
                    <canvas id="hourlyChart" height="200"></canvas>
                </div>
                <div class="stat-card">
                    <h3><i class="fa-solid fa-calendar-week"></i> Daily Trend (Last 7 Days)</h3>
                    <canvas id="dailyChart" height="200"></canvas>
                </div>
            </div>

            <!-- AI Recommendations -->
            <div class="stat-card" style="margin-top:24px;">
                <h3><i class="fa-solid fa-lightbulb"></i> AI-Powered Recommendations</h3>
                <div id="recommendations" style="margin-top:16px;"></div>
                <div id="aiExplanation" style="margin-top:20px;padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;color:#475569;"></div>
            </div>
        </div>
    </main>

    <div id="loadingOverlay" class="loading-overlay">
        <div style="text-align:center;">
            <div class="loading-spinner"></div>
            <p style="margin-top:16px;">🤖 AI is analyzing real incident data...</p>
        </div>
    </div>

    <script>
        let hourlyChart, dailyChart;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function loadStations() {
            try {
                const response = await fetch('/admin/api/stations-for-analytics', {
                    headers: { 
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
                const result = await response.json();
                
                let stations = [];
                if (Array.isArray(result)) {
                    stations = result;
                } else if (result.data && Array.isArray(result.data)) {
                    stations = result.data;
                } else if (result.success && result.data && Array.isArray(result.data)) {
                    stations = result.data;
                }
                
                const select = document.getElementById('stationSelect');
                
                if (stations.length === 0) {
                    select.innerHTML = '<option value="">No locations found</option>';
                    return;
                }
                
                select.innerHTML = '<option value="all">📍 All Locations</option>';
                stations.forEach(station => {
                    const stationId = station.station_id || station.id;
                    const stationName = station.station_name || station.name || station.title || 'Unnamed';
                    select.innerHTML += `<option value="${stationId}">${stationName}</option>`;
                });
                
                // Auto-analyze first location
                if (stations.length > 0) {
                    const firstId = stations[0].station_id || stations[0].id;
                    select.value = firstId;
                    runAIAnalysis(firstId);
                }
            } catch (error) {
                console.error('Error loading locations:', error);
                document.getElementById('stationSelect').innerHTML = '<option value="">Error loading locations</option>';
            }
        }

        async function runAIAnalysis(locationId) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            const periodDays = document.getElementById('periodSelect').value;

            try {
                const response = await fetch('/admin/api/ai-predictive-analysis', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        location_id: locationId,
                        days: periodDays
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('AI Analysis Result:', result);
                
                if (result.success) {
                    updateDashboardWithAIData(result);
                    const locationName = result.location_name || 'Selected Location';
                    document.getElementById('selectedStationBadge').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${locationName}`;
                } else {
                    alert('Error: ' + (result.message || 'Analysis failed'));
                }
            } catch (error) {
                console.error('Analysis error:', error);
                alert('Failed to run AI analysis: ' + error.message);
            } finally {
                overlay.classList.remove('active');
            }
        }

        function updateDashboardWithAIData(data) {
            // Risk assessment
            const riskScore = data.risk_score || 0;
            const riskCard = document.getElementById('riskCard');
            riskCard.className = 'stat-card ' + (riskScore >= 70 ? 'risk-high' : (riskScore >= 40 ? 'risk-medium' : 'risk-low'));
            document.getElementById('riskScore').innerHTML = riskScore + '/100';
            document.getElementById('riskLabel').innerHTML = data.risk_level || 'Not Available';
            document.getElementById('confidenceScore').innerHTML = `AI Confidence: ${data.confidence_score || 85}%`;
            
            let riskFactorsHtml = '<strong>AI-Identified Risk Factors:</strong><ul style="margin-top:8px;margin-left:16px;">';
            const riskFactors = data.risk_factors || ['Insufficient data for detailed analysis'];
            riskFactors.forEach(factor => {
                riskFactorsHtml += `<li style="font-size:13px;margin-bottom:4px;">• ${factor}</li>`;
            });
            riskFactorsHtml += '</ul>';
            document.getElementById('riskFactors').innerHTML = riskFactorsHtml;

            // Incident stats from real data
            document.getElementById('totalIncidents').innerHTML = data.total_incidents || 0;
            document.getElementById('fireIncidents').innerHTML = data.fire_incidents || 0;
            document.getElementById('avgResponse').innerHTML = (data.avg_response_time || 'N/A') + (data.avg_response_time ? ' min' : '');
            document.getElementById('peakHour').innerHTML = data.peak_hour ? data.peak_hour + ':00' : 'N/A';
            
            // Incident types breakdown
            if (data.incidents_by_type && Object.keys(data.incidents_by_type).length > 0) {
                let typesHtml = '<strong>Incidents by Type:</strong><div style="margin-top:8px;">';
                for (const [type, count] of Object.entries(data.incidents_by_type)) {
                    typesHtml += `<span style="display:inline-block;background:#f1f5f9;padding:2px 8px;border-radius:12px;margin:2px;font-size:11px;">${type}: ${count}</span>`;
                }
                typesHtml += '</div>';
                document.getElementById('incidentTypes').innerHTML = typesHtml;
            }

            // Resources
            const totalPersonnel = data.total_personnel || 1;
            const availablePersonnel = data.available_personnel || 0;
            const personnelPercent = totalPersonnel > 0 ? Math.min(100, (availablePersonnel / totalPersonnel) * 100) : 0;
            document.getElementById('availablePersonnel').innerHTML = `${availablePersonnel}/${totalPersonnel}`;
            document.getElementById('personnelBar').style.width = personnelPercent + '%';
            
            const totalEquipment = data.total_equipment || 1;
            const availableEquipment = data.available_equipment || 0;
            const equipPercent = totalEquipment > 0 ? Math.min(100, (availableEquipment / totalEquipment) * 100) : 0;
            document.getElementById('availableEquipment').innerHTML = `${availableEquipment}/${totalEquipment}`;
            document.getElementById('equipmentBar').style.width = equipPercent + '%';
            
            document.getElementById('responseUnits').innerHTML = data.response_units || 'N/A';

            // Update charts with real data
            if (hourlyChart) hourlyChart.destroy();
            if (dailyChart) dailyChart.destroy();

            // Hourly chart
            const hourlyLabels = data.hourly_labels || Array.from({length: 24}, (_, i) => i + ':00');
            const hourlyData = data.hourly_data || new Array(24).fill(0);
            
            const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
            hourlyChart = new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        label: 'Incidents by Hour',
                        data: hourlyData,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#dc2626',
                        pointBorderColor: '#fff',
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Number of Incidents' } },
                        x: { title: { display: true, text: 'Hour of Day' } }
                    }
                }
            });

            // Daily chart
            const dailyData = data.daily_data || [0, 0, 0, 0, 0, 0, 0];
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            dailyChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    datasets: [{
                        label: 'Incidents',
                        data: dailyData,
                        backgroundColor: '#ef4444',
                        borderRadius: 6,
                        barPercentage: 0.7
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Number of Incidents' } }
                    }
                }
            });

            // Display AI recommendations
            const recommendations = data.recommendations || [];
            let recHtml = '<div style="display:grid;gap:12px;">';
            
            if (recommendations.length === 0) {
                recHtml += `
                    <div class="recommendation-card low">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:28px;height:28px;background:#10b981;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">✓</div>
                            <div>
                                <strong style="font-size:15px;">All Systems Operational</strong><br>
                                <small>No urgent recommendations at this time. Continue regular monitoring.</small>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                recommendations.forEach((rec, idx) => {
                    const priority = (rec.priority || '').toLowerCase();
                    let priorityClass = 'medium';
                    if (priority === 'critical' || priority === 'high') priorityClass = 'critical';
                    else if (priority === 'low') priorityClass = 'low';
                    
                    recHtml += `
                        <div class="recommendation-card ${priorityClass}">
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <div style="width:28px;height:28px;background:${priorityClass === 'critical' ? '#dc2626' : (priorityClass === 'low' ? '#94a3b8' : '#f59e0b')};color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">${idx+1}</div>
                                <div style="flex:1;">
                                    <strong style="font-size:15px;">${rec.title || 'Recommendation'}</strong><br>
                                    <small style="color:#4b5563;">${rec.description || (typeof rec === 'string' ? rec : 'Action recommended based on data patterns')}</small>
                                    ${rec.priority ? `<span style="font-size:11px;color:${priorityClass === 'critical' ? '#dc2626' : '#f59e0b'};font-weight:600;margin-top:6px;display:inline-block;">${rec.priority.toUpperCase()} PRIORITY</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            recHtml += '</div>';
            document.getElementById('recommendations').innerHTML = recHtml;
            
            // AI Explanation
            if (data.ai_explanation) {
                document.getElementById('aiExplanation').innerHTML = `
                    <strong><i class="fa-solid fa-brain"></i> AI Analysis Summary:</strong><br>
                    ${data.ai_explanation}
                `;
            }
        }

        function emergencyCall() {
            if (confirm('Call emergency services (911)?')) {
                window.location.href = 'tel:911';
            }
        }

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                document.querySelector('.sidebar').classList.toggle('open');
            });
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('menuToggle');
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('open')) {
                if (!sidebar.contains(event.target) && event.target !== toggle && (!toggle || !toggle.contains(event.target))) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Event listeners
        const analyzeBtn = document.getElementById('analyzeBtn');
        if (analyzeBtn) {
            analyzeBtn.addEventListener('click', () => {
                const stationSelect = document.getElementById('stationSelect');
                const locationId = stationSelect.value;
                if (locationId) {
                    runAIAnalysis(locationId);
                } else {
                    alert('Please select a location first');
                }
            });
        }

        const stationSelect = document.getElementById('stationSelect');
        if (stationSelect) {
            stationSelect.addEventListener('change', () => {
                const locationId = stationSelect.value;
                if (locationId) {
                    runAIAnalysis(locationId);
                }
            });
        }

        const periodSelect = document.getElementById('periodSelect');
        if (periodSelect) {
            periodSelect.addEventListener('change', () => {
                const locationId = stationSelect?.value;
                if (locationId) {
                    runAIAnalysis(locationId);
                }
            });
        }

        // Initial load
        loadStations();
    </script>
</body>
</html>
