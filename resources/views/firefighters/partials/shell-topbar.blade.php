<nav class="ff-topbar-shell">
    <div class="flex items-center gap-4">
        <button id="ffOpenSidebar" class="lg:hidden text-slate-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <div class="hidden lg:block">
            <h1 class="text-xl font-bold text-slate-800">{{ $title ?? 'Responder Command' }}</h1>
            <p class="text-xs text-slate-500 font-medium">{{ $subtitle ?? ('Ready for Duty • ' . now()->format('l, F j')) }}</p>
        </div>
    </div>

    <div class="flex items-center gap-4 sm:gap-5">
        <div class="hidden sm:flex ff-topbar-status-pill">
            <span>On Duty</span>
        </div>

        <div class="user-dropdown">
            <button id="ffUserDropdownBtn" class="user-dropdown-toggle" type="button" aria-expanded="false" aria-controls="ffUserDropdownMenu">
                <div class="ff-topbar-profile-avatar">
                    {{ substr(auth()->user()->first_name ?? 'F', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                </div>
                <span class="ff-topbar-profile-copy hidden sm:flex">
                    <span class="ff-topbar-profile-name">{{ auth()->user()->first_name ?? auth()->user()->name ?? 'Firefighter' }}</span>
                    <span class="ff-topbar-profile-role">Firefighter</span>
                </span>
                <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
            </button>

            <div id="ffUserDropdownMenu" class="user-dropdown-menu">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? auth()->user()->name ?? '' }}</p>
                    <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                </div>
                <div class="p-2">
                    <a href="{{ route('firefighter.my-profile') }}" class="user-dropdown-item">
                        <i class="far fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="#" class="user-dropdown-item" onclick="openMyLogsModal(); return false;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>My Logs</span>
                    </a>
                    <div class="h-px bg-slate-100 my-2"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('ffSidebar');
    const openBtn = document.getElementById('ffOpenSidebar');
    const backdrop = document.getElementById('ffSidebarBackdrop');
    const dropdownBtn = document.getElementById('ffUserDropdownBtn');
    const dropdownMenu = document.getElementById('ffUserDropdownMenu');

    if (openBtn && sidebar && backdrop) {
        openBtn.addEventListener('click', function () {
            sidebar.classList.add('active');
            backdrop.classList.remove('hidden');
        });

        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('active');
            backdrop.classList.add('hidden');
        });
    }

    if (dropdownBtn && dropdownMenu) {
        dropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            dropdownBtn.setAttribute('aria-expanded', dropdownMenu.classList.contains('show') ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!dropdownBtn.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>

@include('firefighters.partials.icon-fallback')
