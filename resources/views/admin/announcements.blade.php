{{-- resources/views/admin/announcements.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense · Announcements</title>
    <style>
        .layout {
    display: flex;
    min-height: 100vh;
}

    </style>
    @vite(['resources/css/chief/dashboard.css'])
    @vite(['resources/css/admin/announcements.css'])
</head>
<body class="chief-dashboard-page">
    <!-- Side Navigation -->
    @include('admin.partials.sidebar', ['active' => 'announcements'])
    {{-- <aside class="sidebar">
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

                <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->name ?? 'JD', 0, 2) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->name ?? 'John Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role ?? 'Chief Administrator' }}</span>
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
                <a href="{{ route('admin.dashboard') }}" class="nav-item ">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.announcements') }}" class="nav-item active">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
                <a href="{{ route('admin.maps') }}" class="nav-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Incident Map</span>
                </a>
                <a href="{{ route('admin.management') }}" class="nav-item">
                    <i class="fa-solid fa-table"></i>
                    <span>Management</span>
                </a>
                <a href="#" class="nav-item ">
                    <i class="fa-solid fa-toolbox"></i>
                    <span>Resources</span>
                </a>
                {{-- <div class="nav-section-title">Main</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-item">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.announcements') }}" class="nav-item active">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Announcements</span>
                    @if(App\Models\Post::whereDate('created_at', today())->count() > 0)
                        <span class="nav-badge">New</span>
                    @endif
                </a>
                <a href="{{ route('admin.maps') }}" class="nav-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Incident Map</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fa-solid fa-truck"></i>
                    <span>Active Units</span>
                    <span class="nav-badge secondary">6</span>
                </a> --}}
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
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
                <div class="nav-section-title">Resources</div>
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

        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; border-top: 1px solid #e5e7eb; background: white;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="width: 100%; padding: 12px; display: flex; align-items: center; gap: 12px; background: none; border: none; color: #4b5563; border-radius: 10px; cursor: pointer;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside> --}}

    <!-- Main Content -->
    <main class="main-content">
        @include('admin.partials.topbar', ['title' => 'Announcements', 'subtitle' => 'Command updates and public coordination. ' . now()->format('l, F j')])
        <div class="dashboard-container chief-dashboard-container">
        <div class="chief-dashboard-welcome bg-amber-50 border-l-4 border-amber-500 rounded-2xl p-8 mb-8 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-amber-900 mb-2">Command Announcements</h1>
                <p class="text-amber-700 font-medium">Post updates, announcements, and engage with your team.</p>
            </div>
            <div class="hidden md:flex items-center gap-4 bg-white/50 p-2 rounded-xl border border-amber-100">
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Broadcast Status</p>
                    <p class="text-xs font-bold text-green-600">READY</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
            </div>
        </div>

        <div class="top-bar">
            <div class="page-title">
                <h1>Announcements</h1>
                <p>Post updates, announcements, and engage with your team</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-outline" onclick="window.announcementSystem?.loadPosts(true)">
                    <i class="fa-regular fa-rotate-right"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Create Post Card -->
        <div class="create-post-card">
            <div class="post-input-area">
                <div class="post-avatar">
                    <span>{{ substr(auth()->user()->name ?? 'JD', 0, 2) }}</span>
                </div>
                <div class="post-input-wrapper">
                    <textarea 
                        class="post-input" 
                        rows="3" 
                        placeholder="What's happening in your station? Make an announcement..."
                        id="postContent"
                        maxlength="5000"
                    ></textarea>
                </div>
            </div>

            <div class="post-attachments">
                <label class="attachment-btn image" for="imageUpload">
                    <i class="fa-regular fa-image"></i>
                    Photo
                    <input type="file" id="imageUpload" accept="image/*" multiple style="display: none;">
                </label>
                <label class="attachment-btn video" for="videoUpload">
                    <i class="fa-regular fa-video"></i>
                    Video
                    <input type="file" id="videoUpload" accept="video/*" style="display: none;" disabled>
                </label>
                <button class="attachment-btn poll" onclick="window.announcementSystem?.showNotification('Poll feature coming soon!', 'info')" disabled>
                    <i class="fa-regular fa-square-poll-vertical"></i>
                    Poll
                </button>
            </div>

            <div class="image-preview" id="imagePreview"></div>

            <div class="post-actions">
                <button class="btn btn-outline" onclick="window.announcementSystem?.clearPost()">
                    <i class="fa-regular fa-trash-can"></i>
                    Clear
                </button>
                <button class="btn btn-primary" onclick="window.announcementSystem?.createPost()">
                    <i class="fa-regular fa-paper-plane"></i>
                    Post Announcement
                </button>
            </div>
        </div>

        <!-- Feed -->
        <div class="feed" id="feed"></div>

        <!-- Loading Spinner -->     
        <div id="loadingSpinner" class="loading-spinner" style="display: none;"></div>
        </div>
    </main>

    <!-- Right Sidebar -->
    {{-- <div class="layout">
        <aside class="right-sidebar">
            <div class="trending-section">
                <div class="section-header">
                    <h3>Quick Stats</h3>
                </div>
                <div class="stats-card">
                    <div class="stat-item">
                        <div class="stat-label">Total Announcements</div>
                        <div class="stat-value">{{ App\Models\Post::count() }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Recent Activity</div>
                        <div class="stat-small">
                            <i class="fa-regular fa-message"></i>
                            {{ App\Models\Comment::whereDate('created_at', today())->count() }} comments today
                        </div>
                    </div>
                </div>
            </div>

            <div class="trending-section">
                <div class="section-header">
                    <h3>Online Personnel</h3>
                    <a href="#">See all</a>
                </div>
                <div class="online-users">
                    @foreach(App\Models\User::where('last_activity', '>', now()->subMinutes(5))->take(3)->get() as $user)
                    <div class="online-user">
                        <div class="online-user-avatar"></div>
                        <div class="online-user-info">
                            <h4>{{ $user->name }}</h4>
                            <p>{{ $user->role ?? 'Personnel' }} · Online</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div> --}}

    @vite(['resources/js/admin/announcements.js'])
</body>
</html>
