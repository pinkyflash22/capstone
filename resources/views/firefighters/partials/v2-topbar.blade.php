<div class="ff2-topbar">
    <div>
        <h1>{{ $title ?? 'Responder Command V2' }}</h1>
        <p>{{ $subtitle ?? 'Firefighter pages rebuilt in a safer parallel module' }}</p>
    </div>

    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <span class="ff2-status">On Duty</span>
        <a href="{{ route('firefighter.dashboard') }}" class="ff2-button ff2-button--ghost">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Current Module</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" data-firefighter-logout-form>
            @csrf
            <button type="submit" class="ff2-button ff2-button--primary">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>
