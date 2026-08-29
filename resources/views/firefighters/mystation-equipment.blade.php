@php
    $apparatusUnits = collect($apparatusUnits ?? []);
    $today = now()->startOfDay();
    $maintenanceWindow = $today->copy()->addDays(30);
    $apparatusUnits = $apparatusUnits->sortBy(function ($unit) use ($today) {
        $isOverdue = $unit->next_maintenance_due_at?->copy()->startOfDay()->lt($today) ?? false;

        return match (true) {
            $unit->maintenance_status === 'in_maintenance' => '0-' . $unit->unit_number,
            $isOverdue => '1-' . $unit->unit_number,
            $unit->maintenance_status === 'due' => '2-' . $unit->unit_number,
            default => '3-' . $unit->unit_number,
        };
    })->values();
    $apparatusAttention = $apparatusUnits->filter(function ($unit) use ($today) {
        $isOverdue = $unit->next_maintenance_due_at?->copy()->startOfDay()->lt($today) ?? false;

        return $isOverdue || in_array($unit->maintenance_status, ['due', 'in_maintenance'], true);
    })->count();
    $apparatusDueSoon = $apparatusUnits->filter(function ($unit) use ($today, $maintenanceWindow) {
        $dueDate = $unit->next_maintenance_due_at?->copy()->startOfDay();

        return $unit->maintenance_status === 'operational'
            && $dueDate
            && $dueDate->betweenIncluded($today, $maintenanceWindow);
    })->count();
    $readyQuantity = $equipmentAssignments->sum(fn ($assignment) => (int) ($assignment->available_quantity ?? 0));
    $maintenanceQuantity = $equipmentAssignments->sum(fn ($assignment) => (int) ($assignment->maintenance_quantity ?? 0));
    $damagedQuantity = $equipmentAssignments->sum(fn ($assignment) => (int) ($assignment->damaged_quantity ?? 0));
    $totalQuantity = $readyQuantity + $maintenanceQuantity + $damagedQuantity;
@endphp

<section
    class="section-card equipment-readiness-card"
    data-equipment-summary
    data-equipment-lines="{{ $equipmentAssignments->count() }}"
    data-equipment-ready="{{ $readyQuantity }}"
    data-equipment-maintenance="{{ $maintenanceQuantity }}"
    data-equipment-damaged="{{ $damagedQuantity }}"
>
    <div class="section-header station-section-header-compact">
        <div>
            <div class="section-title"><i class="fa-solid fa-truck-medical"></i> Vehicles and Equipment</div>
            <div class="section-subtitle">Readiness from current station assignments</div>
        </div>
        <small class="station-readiness-refresh-note"><i class="fa-solid fa-arrows-rotate"></i> Updates every 30 seconds</small>
    </div>
    <div class="section-body">
        <div class="readiness-strip" aria-label="Equipment readiness summary">
            <div class="readiness-chip readiness-chip-ready">
                <strong>{{ $readyQuantity }}</strong>
                <span>Ready</span>
            </div>
            <div class="readiness-chip readiness-chip-maintenance">
                <strong>{{ $maintenanceQuantity }}</strong>
                <span>Maintenance</span>
            </div>
            <div class="readiness-chip readiness-chip-damaged">
                <strong>{{ $damagedQuantity }}</strong>
                <span>Damaged</span>
            </div>
            <div class="readiness-chip">
                <strong>{{ $totalQuantity }}</strong>
                <span>Total units</span>
            </div>
        </div>

        <section class="maintenance-check-panel" aria-labelledby="apparatusMaintenanceHeading">
            <div class="maintenance-check-heading">
                <div>
                    <span class="maintenance-check-kicker"><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance check</span>
                    <h3 id="apparatusMaintenanceHeading">Registered vehicles and apparatus</h3>
                    <p>Check service state and maintenance dates for vehicles registered to your station.</p>
                </div>
                <div class="maintenance-check-metrics" aria-label="Vehicle maintenance summary">
                    <span><strong>{{ $apparatusUnits->count() }}</strong> Registered</span>
                    <span class="{{ $apparatusAttention > 0 ? 'is-alert' : '' }}"><strong>{{ $apparatusAttention }}</strong> Needs attention</span>
                    <span class="{{ $apparatusDueSoon > 0 ? 'is-warning' : '' }}"><strong>{{ $apparatusDueSoon }}</strong> Due in 30 days</span>
                </div>
            </div>

            @if($apparatusUnits->isEmpty())
                <div class="maintenance-empty">
                    <i class="fa-solid fa-truck"></i>
                    <div><strong>No registered apparatus</strong><span>Vehicle maintenance dates will appear after Chief Command registers station apparatus.</span></div>
                </div>
            @else
                <div class="maintenance-filter" role="group" aria-label="Filter vehicle maintenance records">
                    <button type="button" class="is-active" data-maintenance-filter="all">All {{ $apparatusUnits->count() }}</button>
                    <button type="button" data-maintenance-filter="attention">Needs attention {{ $apparatusAttention }}</button>
                    <button type="button" data-maintenance-filter="operational">Operational {{ $apparatusUnits->count() - $apparatusAttention }}</button>
                </div>

                <div class="maintenance-unit-list" data-maintenance-unit-list>
                    @foreach($apparatusUnits as $unit)
                        @php
                            $dueDate = $unit->next_maintenance_due_at?->copy()->startOfDay();
                            $isOverdue = $dueDate?->lt($today) ?? false;
                            $isDueSoon = $unit->maintenance_status === 'operational'
                                && $dueDate
                                && $dueDate->betweenIncluded($today, $maintenanceWindow);
                            $needsAttention = $isOverdue || in_array($unit->maintenance_status, ['due', 'in_maintenance'], true);
                            [$maintenanceClass, $maintenanceLabel, $maintenanceIcon] = match (true) {
                                $unit->maintenance_status === 'in_maintenance' => ['in-service', 'In maintenance', 'fa-gears'],
                                $isOverdue => ['overdue', 'Service overdue', 'fa-triangle-exclamation'],
                                $unit->maintenance_status === 'due' => ['due', 'Maintenance due', 'fa-clock'],
                                $isDueSoon => ['upcoming', 'Due soon', 'fa-calendar-day'],
                                default => ['operational', 'Operational', 'fa-circle-check'],
                            };
                            $unitSearch = strtolower(collect([
                                $unit->unit_number,
                                $unit->plate_number,
                                $unit->apparatus_type,
                                $unit->equipment?->equipment_name,
                                $maintenanceLabel,
                            ])->filter()->join(' '));
                        @endphp
                        <article
                            class="maintenance-unit-card maintenance-unit-card--{{ $maintenanceClass }}"
                            data-maintenance-unit
                            data-maintenance-state="{{ $needsAttention ? 'attention' : 'operational' }}"
                            data-maintenance-search="{{ $unitSearch }}"
                        >
                            <div class="maintenance-unit-identity">
                                <span class="maintenance-unit-icon"><i class="fa-solid fa-truck-front"></i></span>
                                <div>
                                    <div class="maintenance-unit-title-row">
                                        <h4>{{ $unit->unit_number }}</h4>
                                        <span class="maintenance-state maintenance-state--{{ $maintenanceClass }}"><i class="fa-solid {{ $maintenanceIcon }}"></i> {{ $maintenanceLabel }}</span>
                                    </div>
                                    <p>{{ $unit->equipment?->equipment_name ?? ucwords(str_replace('_', ' ', $unit->apparatus_type)) }}{{ $unit->plate_number ? ' · ' . $unit->plate_number : '' }}</p>
                                </div>
                            </div>
                            <div class="maintenance-date-grid">
                                <div><span>Last maintenance</span><strong>{{ $unit->last_maintenance_at?->format('M d, Y') ?? 'Not recorded' }}</strong></div>
                                <div><span>Next service due</span><strong>{{ $unit->next_maintenance_due_at?->format('M d, Y') ?? 'Not scheduled' }}</strong></div>
                                <div><span>Availability</span><strong>{{ ucwords(str_replace('_', ' ', $unit->status ?: 'unknown')) }}</strong></div>
                            </div>
                            @if($unit->notes)
                                <p class="maintenance-unit-note"><i class="fa-regular fa-note-sticky"></i> {{ \Illuminate\Support\Str::limit($unit->notes, 140) }}</p>
                            @endif
                        </article>
                    @endforeach
                    <div class="maintenance-filter-empty" data-maintenance-filter-empty hidden>No vehicles match this maintenance filter.</div>
                </div>
            @endif
        </section>

        @if($equipmentAssignments->isEmpty())
            <div class="empty-box">
                <i class="fa-solid fa-box-open"></i>
                <div>No station equipment is listed yet.</div>
            </div>
        @else
            <div class="equipment-inventory-heading">
                <div><strong>Other equipment readiness</strong><span>Check station inventory currently ready, under maintenance, or damaged.</span></div>
            </div>
            <div class="equipment-grid">
                @foreach($equipmentAssignments->sortByDesc(fn ($assignment) => (int) ($assignment->maintenance_quantity ?? 0) + (int) ($assignment->damaged_quantity ?? 0)) as $assignment)
                    <div class="equipment-item">
                        <div class="equipment-item-main">
                            <span class="equipment-icon"><i class="fa-solid fa-toolbox"></i></span>
                            <div>
                                <div class="equipment-name">{{ $assignment->equipment?->equipment_name ?? 'Unnamed equipment' }}</div>
                                <div class="equipment-category">{{ $assignment->equipment?->category ?? 'Uncategorized equipment' }}</div>
                            </div>
                        </div>
                        <div class="equipment-qty-row">
                            <div class="qty-pill qty-available">Ready {{ $assignment->available_quantity ?? 0 }}</div>
                            <div class="qty-pill qty-maintenance">Maintenance {{ $assignment->maintenance_quantity ?? 0 }}</div>
                            <div class="qty-pill qty-damaged">Damaged {{ $assignment->damaged_quantity ?? 0 }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
