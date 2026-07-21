<section class="station-grid">
    <div class="section-card">
        <div class="section-header">
        <div>
            <div class="section-title"><i class="fa-solid fa-people-group"></i> Crew Members</div>
        </div>
        </div>
        <div class="section-body">
            @if($crewMembers->isEmpty())
                <div class="empty-box">
                    <i class="fa-solid fa-user-slash"></i>
                    <div>No crew members yet.</div>
                </div>
            @else
                <div class="crew-list">
                    @foreach($crewMembers as $member)
                        <div class="crew-item">
                            <div class="crew-avatar">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                            <div>
                                <div class="crew-name">{{ $member->name }}</div>
                                <div class="crew-role">{{ $member->role_label }}{{ $member->role_name ? ' | ' . ucfirst($member->role_name) : '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <section class="section-card">
        <div class="section-header">
        <div>
            <div class="section-title"><i class="fa-solid fa-id-card-clip"></i> Station Officers</div>
        </div>
        </div>
        <div class="section-body table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Officer</th>
                        <th>Rank</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stationOfficers as $officer)
                        @php
                            $status = strtolower($officer->status ?? 'available');
                            $statusClass = match($status) {
                                'available' => 'status-available',
                                'busy', 'on-scene' => 'status-busy',
                                'responding', 'dispatched' => 'status-dispatched',
                                default => 'status-default',
                            };
                        @endphp
                        <tr>
                            <td data-label="Officer">
                                <div class="table-title">{{ trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? '')) ?: 'Unknown Officer' }}</div>
                            </td>
                            <td data-label="Rank">{{ $officer->rank ?? 'N/A' }}</td>
                            <td data-label="Role">{{ ucfirst($officer->user->role->role ?? 'station officer') }}</td>
                            <td data-label="Contact">{{ $officer->user->contact_number ?? ($officer->user->email ?? 'N/A') }}</td>
                            <td data-label="Status"><span class="status-pill {{ $statusClass }}">{{ ucwords(str_replace('-', ' ', $status)) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-box">
                                    <i class="fa-solid fa-users-slash"></i>
                                    <div>No station officers found.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
