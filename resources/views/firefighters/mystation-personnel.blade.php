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
                            $phoneHref = $phone ? preg_replace('/\D+/', '', $phone) : null;
                        @endphp
                        <details class="officer-card">
                            <summary class="officer-summary">
                                <span class="officer-avatar" aria-hidden="true">{{ $initials }}</span>
                                <span class="officer-main">
                                    <span class="officer-topline">
                                        <span class="officer-name">{{ $officerName }}</span>
                                        <span class="status-pill officer-status {{ $statusClass }}">{{ ucwords(str_replace('-', ' ', $status)) }}</span>
                                    </span>
                                    <span class="officer-meta">{{ $rank }} <span aria-hidden="true">&bull;</span> {{ $role }}</span>
                                </span>
                                <span class="officer-chevron" aria-hidden="true">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </span>
                            </summary>
                            <div class="officer-details">
                                @if($phone)
                                    <a class="officer-contact" href="tel:{{ $phoneHref }}" aria-label="Call {{ $officerName }} at {{ $phone }}">
                                        <i class="fa-solid fa-phone"></i>
                                        <span>{{ $phone }}</span>
                                    </a>
                                @elseif($email)
                                    <a class="officer-contact" href="mailto:{{ $email }}" aria-label="Email {{ $officerName }} at {{ $email }}">
                                        <i class="fa-solid fa-envelope"></i>
                                        <span>{{ $email }}</span>
                                    </a>
                                @else
                                    <span class="officer-contact">
                                        <i class="fa-solid fa-phone-slash"></i>
                                        <span>No contact listed</span>
                                    </span>
                                @endif
                            </div>
                        </details>
                    @endforeach
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

<div class="crew-member-modal" id="crewMemberDetailModal" aria-hidden="true">
    <button type="button" class="crew-member-modal__backdrop" data-crew-member-close aria-label="Close firefighter details"></button>
    <section class="crew-member-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="crewMemberDetailName">
        <button type="button" class="crew-member-modal__close" data-crew-member-close aria-label="Close firefighter details">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="crew-member-modal__identity">
            <div class="crew-member-modal__photo" data-crew-modal-photo></div>
            <div>
                <span class="crew-member-modal__eyebrow">Response crew</span>
                <h3 id="crewMemberDetailName" data-crew-modal-name>Firefighter</h3>
                <p data-crew-modal-position>Position</p>
            </div>
        </div>
        <dl class="crew-member-modal__details">
            <div><dt>Rank</dt><dd data-crew-modal-rank>Not recorded</dd></div>
            <div><dt>Role</dt><dd data-crew-modal-role>Firefighter</dd></div>
            <div><dt>Status</dt><dd data-crew-modal-status>Available</dd></div>
            <div><dt>Contact</dt><dd data-crew-modal-contact>Not provided</dd></div>
        </dl>
        <a class="crew-member-modal__email" data-crew-modal-email hidden>
            <i class="fa-solid fa-envelope"></i><span></span>
        </a>
    </section>
</div>
