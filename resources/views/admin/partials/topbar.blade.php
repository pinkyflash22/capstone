@php
    $title = $title ?? "Commander's Dashboard";
    $subtitle = $subtitle ?? ('Strategic Overview. ' . now()->format('l, F j'));
    $alertsCount = isset($alerts) ? $alerts->count() : 0;
@endphp

<nav class="lector-top-bar">
    <div class="flex items-center gap-4">
        <button id="openSidebar" class="lg:hidden text-slate-600">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        <div class="hidden lg:block">
            <h1 class="text-xl font-bold text-slate-800">{{ $title }}</h1>
            <p class="text-xs text-slate-500 font-medium">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="flex items-center gap-6">
        <div class="hidden md:block relative">
            <button class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition relative">
                <i class="fa-regular fa-bell"></i>
                @if($alertsCount > 0)
                    <span class="absolute top-2 right-2 w-2 h-2 bg-amber-500 rounded-full"></span>
                @endif
            </button>
        </div>

        <div class="hidden md:block user-dropdown">
            <button id="userDropdownBtn" class="user-dropdown-toggle">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                    {{ substr(auth()->user()->first_name ?? 'A', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}
                </div>
                <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
            </button>

            <div id="userDropdownMenu" class="user-dropdown-menu">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                    <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                </div>
                <div class="p-2">
                    <a href="{{ route('admin.dashboard') }}" class="user-dropdown-item">
                        <i class="fa-regular fa-user"></i>
                        <span>Admin Dashboard</span>
                    </a>
                    <a href="{{ route('admin.analytics') }}" class="user-dropdown-item">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                    <div class="h-px bg-slate-100 my-2"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
