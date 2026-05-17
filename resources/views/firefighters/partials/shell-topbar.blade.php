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

    <div class="flex items-center gap-6">
        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-green-50 rounded-full border border-green-100">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span class="text-[11px] font-bold text-green-700 uppercase tracking-wider">On Duty</span>
        </div>

        <div class="user-dropdown">
            <button id="ffUserDropdownBtn" class="user-dropdown-toggle">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-red-200">
                    {{ substr(auth()->user()->first_name ?? 'F', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                </div>
                <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name ?? auth()->user()->name ?? 'Firefighter' }}</span>
                <i class="fas fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
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
        });

        document.addEventListener('click', function (e) {
            if (!dropdownBtn.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
});
</script>

@include('firefighters.partials.icon-fallback')
