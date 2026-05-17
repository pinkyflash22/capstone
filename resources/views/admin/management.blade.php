<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense · Management</title>
    
    @vite(['resources/css/admin/announcements.css', 'resources/css/admin/management.css'])
</head>
<body>
    <!-- Side Navigation (same as announcements) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
                <div class="logo-area">
                    <div class="logo-icon">
                        <i class="fa-solid fa-fire-extinguisher"></i>
                    </div>
                    <div class="logo-text">
                        <h2>PyroSense</h2>
                        <p>command center</p>
                    </div>
                </div>

                <button id="closeSidebar" class="absolute top-5 right-4 lg:hidden text-white/80 hover:text-white">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>

                    <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Chief Administrator' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> {{ now()->format('H:i · d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.announcements') }}" class="nav-item">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
                <a href="{{ route('admin.maps') }}" class="nav-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Incident Map</span>
                </a>
                <a href="#" class="nav-item active">
                    <i class="fa-solid fa-table"></i>
                    <span>Management</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
                <a href="" class="nav-item">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Predictive Analytics</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-bell"></i>
                    <span>Alerts</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">RESOURCES</div>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-user"></i>
                    <span>Personnel</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-truck"></i>
                    <span>Fleet</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-water"></i>
                    <span>Hydrants</span>
                </a>
            </div>
        </nav>

        <!-- Logout at bottom -->
        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; border-top: 1px solid #e5e7eb; background: white;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="width: 100%; padding: 12px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: #4b5563; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- BACKDROP for mobile -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Mobile menu button (only visible on mobile) -->
        <div class="lg:hidden p-4 bg-white border-b">
            <button id="openSidebar" class="text-gray-700 focus:outline-none">
                <i class="fa-solid fa-bars text-2xl"></i>
            </button>
        </div>

        <!-- MANAGEMENT CONTENT -->
        <div class="management-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fa-solid fa-table"></i>
                    Data Management
                </h1>
                <div class="date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

            <!-- Alert Banner -->
            <div class="alert-banner">
                <i class="fa-solid fa-circle-info"></i>
                <div class="content">
                    <strong>Management Dashboard:</strong> View and manage all your data in one place. Use the tabs below to switch between different data types.
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="incidents">
                    <i class="fa-solid fa-fire"></i>
                    Incidents
                </button>
                <button class="tab-btn" data-tab="reports">
                    <i class="fa-solid fa-file-lines"></i>
                    Reports
                </button>
                <button class="tab-btn" data-tab="events">
                    <i class="fa-solid fa-calendar"></i>
                    Events
                </button>
                <button class="tab-btn" data-tab="personnel">
                    <i class="fa-solid fa-users"></i>
                    Personnel
                </button>
                <button class="tab-btn" data-tab="equipment">
                    <i class="fa-solid fa-toolbox"></i>
                    Equipment
                </button>
                <button class="tab-btn" data-tab="trainings">
                    <i class="fa-solid fa-graduation-cap"></i>
                    Trainings
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Incidents</h3>
                        <div class="stat-number" id="totalIncidents">{{$totalIncidents->count()}}</div>
                        <div class="stat-change positive" id="activeIncidents">0 active</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reports</h3>
                        <div class="stat-number" id="totalReports">0</div>
                        <div class="stat-change" id="pendingReports">0 pending</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Events</h3>
                        <div class="stat-number" id="upcomingEvents">0</div>
                        <div class="stat-change">upcoming</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Personnel</h3>
                        {{-- <div class="stat-number" id="totalPersonnel">{{$personnels ->where('role_id', 2)->count()}}</div> --}}
                        <div class="stat-change positive">active</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search...">
                </div>
                <div class="filter-actions">
                    <select class="filter-select" id="filterSelect">
                        <option value="">All Status</option>
                    </select>
                    <select class="filter-select" id="dateFilter">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                    <button class="btn-filter" id="refreshBtn">
                        <i class="fa-solid fa-rotate"></i>
                        Refresh
                    </button>
                    <button class="btn-primary" id="addNewBtn">
                        <i class="fa-solid fa-plus"></i>
                        Add New
                    </button>
                    <button class="btn-outline" id="exportBtn">
                        <i class="fa-solid fa-download"></i>
                        Export
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="loading-state" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Loading data...</p>
            </div>

            <!-- Data Table -->
            <div class="table-card" id="dataTable">
                <div class="table-header">
                    <h2>
                        <i class="fa-solid fa-list"></i>
                        <span id="tableTitle">Incidents</span>
                    </h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead id="tableHeader">
                            <tr>
                                {{-- <th>ID</th> --}}
                                {{-- <th>Title</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Date</th>
                                <th>Actions</th> --}}
                            </tr>
                        </thead>
                       <script>
                            // Pass data to JavaScript
                               window.rolesData = @json($roles);
                            window.incidentDb = @json($incidents);
                            window.personnelDb = @json($personnel);
                            window.equipmentDb = @json($equipment);
                            window.stationDb = @json($stations);
                            window.csrfToken = '{{ csrf_token() }}';
                        </script>
                            <tbody id="tableBody">
                            
                           {{-- @if(!$totalIncidents)
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <h3>No data available</h3>
                                    <p>Click refresh to load data or add new entries.</p>
                                </td>
                            </tr>
                           @endif --}}
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination" class="pagination">
                    <div class="pagination-info">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn" disabled><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>View Details</h2>
                <button class="modal-close" id="viewModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="management.closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Form Modal -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Item</h2>
                <button class="modal-close" id="formModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="dataForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i>
                            Title
                        </label>
                        <input type="text" class="form-input" placeholder="Enter title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                                                  <i class="fa-solid fa-align-left"></i>
                            Description
                        </label>
                        <textarea class="form-textarea" placeholder="Enter description" rows="4"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-tag"></i>
                                Status
                            </label>
                            <select class="form-select">
                                <option>Active</option>
                                <option>Pending</option>
                                <option>Completed</option>
                                <option>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-calendar"></i>
                                Date
                            </label>
                            <input type="date" class="form-input">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="cancelFormBtn">Cancel</button>
                <button class="btn-primary" id="saveFormBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button class="modal-close" id="deleteModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="text-align: center; margin: 20px 0;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #dc2626; margin-bottom: 16px;"></i>
                    <br>
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="cancelDeleteBtn">Cancel</button>
                <button class="btn-primary" style="background: #dc2626;" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
    {{-- equipment modal --}}
     <div id="ecatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Equipment Category</h2>
                <button class="modal-close" id="ecatModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="ecatDataForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i>
                            Title
                        </label>
                        <input type="text" name="category_name" class="form-input" placeholder="Enter title">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="ecatCancelBtn">Cancel</button>
                <button class="btn-primary" id="ecatSaveFormBtn">Save Changes</button>
            </div>
        </div>
    </div>
    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const openBtn = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                    document.body.classList.add('menu-open');
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }
        });
    </script>

    @vite(['resources/js/admin/management.js'])
</body>
</html>
