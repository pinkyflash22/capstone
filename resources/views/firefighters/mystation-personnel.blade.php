@php
    $availableOfficers = $stationOfficers->filter(fn ($officer) => strtolower($officer->status ?? 'available') === 'available')->count();
    $assignedOfficers = $stationOfficers->filter(fn ($officer) => in_array(strtolower($officer->status ?? ''), ['busy', 'on-scene', 'responding', 'dispatched', 'assigned'], true))->count();
    $crewCount = $crewMembers->count();
@endphp

<section class="station-ops-grid">
    <section class="section-card station-officers-section">
        <div class="section-header station-section-header-compact">
            <div>
                <div class="section-title">
                    <i class="fa-solid fa-id-card"></i>
                    Station Officers <span class="station-officers-count">&middot; {{ $stationOfficers->count() }}</span>
                </div>
            </div>
            <small class="station-readiness-refresh-note"><i class="fa-solid fa-arrows-rotate"></i> Updates every 30 seconds</small>
        </div>
        <div
            class="section-body"
            data-station-officers-roster
            data-officers-total="{{ $stationOfficers->count() }}"
            data-officers-available="{{ $availableOfficers }}"
            data-officers-assigned="{{ $assignedOfficers }}"
        >
            @if($stationOfficers->isEmpty())
                <div class="empty-box">
                    <i class="fa-solid fa-users"></i>
                    <div>No station officers found.</div>
                </div>
            @else
                @if($stationOfficers->count() >= 8)
                    <div class="station-roster-search">
                        <label for="stationRosterSearch" class="sr-only">Search station roster</label>
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input
                            id="stationRosterSearch"
                            type="search"
                            inputmode="search"
                            autocomplete="off"
                            placeholder="Search by name, rank, role, or status"
                            data-station-roster-search
                        >
                        <span data-station-roster-search-count aria-live="polite">{{ $stationOfficers->count() }} shown</span>
                    </div>
                @endif
                <div class="officer-list" aria-label="Station officers">
                    @foreach($stationOfficers as $officer)
                        @php
                            $status = strtolower($officer->status ?? 'available');
                            $statusClass = match($status) {
                                'available' => 'status-available',
                                'busy', 'on-scene' => 'status-busy',
                                'responding', 'dispatched' => 'status-dispatched',
                                default => 'status-default',
                            };
                            $officerName = trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? '')) ?: 'Unknown Officer';
                            $nameParts = preg_split('/\s+/', trim($officerName)) ?: [];
                            $initials = collect($nameParts)
                                ->filter()
                                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                ->take(2)
                                ->join('') ?: 'FO';
                            $rank = $officer->rank ?? 'Rank not set';
                            $role = ucfirst($officer->user->role->role ?? 'station officer');
                            $phone = $officer->user->contact_number ?? null;
                            $email = $officer->user->email ?? null;
                            $profileImage = $officer->user->profile_thumbnail_url ?: $officer->user->profile_image_url;
                        @endphp
                        <button
                            type="button"
                            class="officer-card station-person-card"
                            data-crew-member-card
                            data-member-group="Station officer"
                            data-member-name="{{ $officerName }}"
                            data-member-position="{{ $rank }}"
                            data-member-role="{{ $role }}"
                            data-member-rank="{{ $rank }}"
                            data-member-status="{{ ucwords(str_replace('-', ' ', $status)) }}"
                            data-member-email="{{ $email }}"
                            data-member-contact="{{ $phone }}"
                            data-member-image="{{ $profileImage }}"
                            data-member-initials="{{ $initials }}"
                            data-roster-search-text="{{ strtolower(implode(' ', array_filter([$officerName, $rank, $role, $status, $email]))) }}"
                            aria-haspopup="dialog"
                            aria-controls="crewMemberDetailModal"
                        >
                            <span class="officer-avatar" aria-hidden="true">
                                @if($profileImage)<img src="{{ $profileImage }}" alt="" loading="lazy">@else<span>{{ $initials }}</span>@endif
                            </span>
                            <span class="officer-main">
                                <span class="officer-topline"><span class="officer-name">{{ $officerName }}</span></span>
                                <span class="officer-meta">{{ $rank }} <span aria-hidden="true">&bull;</span> {{ $role }}</span>
                            </span>
                            <span class="station-person-trailing">
                                <span class="status-pill officer-status {{ $statusClass }}">{{ ucwords(str_replace('-', ' ', $status)) }}</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </span>
                        </button>
                    @endforeach
                </div>
                <div class="station-roster-empty" data-station-roster-empty hidden>
                    <i class="fa-solid fa-user-slash"></i>
                    <span>No personnel match this search.</span>
                </div>
            @endif
        </div>
    </section>

    <aside class="section-card today-station-card" aria-label="Today at the station">
        <div class="section-header station-section-header-compact">
            <div>
                <div class="section-title"><i class="fa-solid fa-calendar"></i> Today at the Station</div>
            </div>
        </div>
        <div class="today-status-list">
            <div class="today-status-item">
                <span class="today-status-icon"><i class="fa-solid fa-user-check"></i></span>
                <div>
                    <strong>{{ $availableOfficers }} available</strong>
                    <small>{{ $stationOfficers->count() }} rostered officers</small>
                </div>
            </div>
            <div class="today-status-item">
                <span class="today-status-icon"><i class="fa-solid fa-people-group"></i></span>
                <div>
                    <strong>{{ $crewCount }} crew {{ $crewCount === 1 ? 'member' : 'members' }}</strong>
                    <small>{{ $crewCount ? 'Current team roster loaded' : 'No crew team loaded' }}</small>
                </div>
            </div>
            <div class="today-status-item">
                <span class="today-status-icon"><i class="fa-solid fa-truck-fast"></i></span>
                <div>
                    <strong>{{ $assignedOfficers }} assigned</strong>
                    <small>Responder status from station roster</small>
                </div>
            </div>
        </div>
    </aside>

    <section class="section-card crew-section-card">
        <div class="section-header station-section-header-compact">
            <div>
                <div class="section-title"><i class="fa-solid fa-people-group"></i> Crew Members</div>
            </div>
        </div>
        <div class="section-body">
            @if($crewMembers->isEmpty())
                <div class="empty-box">
                    <i class="fa-solid fa-user"></i>
                    <div>No crew members assigned yet.</div>
                </div>
            @else
                <div class="crew-list crew-card-row" aria-label="Current response team members">
                    @foreach($crewMembers as $member)
                        @php
                            $memberNameParts = preg_split('/\s+/', trim($member->name)) ?: [];
                            $memberInitials = collect($memberNameParts)
                                ->filter()
                                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                ->take(2)
                                ->join('') ?: 'FF';
                            $memberPosition = $member->role_label ?: 'Response Crew';
                            $memberStatus = ucwords(str_replace('-', ' ', strtolower($member->status ?: 'available')));
                        @endphp
                        <button
                            type="button"
                            class="crew-member-card"
                            data-crew-member-card
                            data-member-group="Response crew"
                            data-member-name="{{ $member->name }}"
                            data-member-position="{{ $memberPosition }}"
                            data-member-role="{{ ucfirst($member->role_name ?: 'firefighter') }}"
                            data-member-rank="{{ $member->rank }}"
                            data-member-status="{{ $memberStatus }}"
                            data-member-email="{{ $member->email }}"
                            data-member-contact="{{ $member->contact_number }}"
                            data-member-image="{{ $member->profile_image_url }}"
                            data-member-initials="{{ $memberInitials }}"
                            aria-haspopup="dialog"
                            aria-controls="crewMemberDetailModal"
                        >
                            <span class="crew-member-photo" aria-hidden="true">
                                @if($member->profile_image_url)
                                    <img src="{{ $member->profile_image_url }}" alt="" loading="lazy">
                                @else
                                    <span>{{ $memberInitials }}</span>
                                @endif
                            </span>
                            <span class="crew-member-copy">
                                <strong class="crew-name">{{ $member->name }}</strong>
                                <span class="crew-role">{{ $memberPosition }}</span>
                            </span>
                            <span class="crew-member-open" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</section>
