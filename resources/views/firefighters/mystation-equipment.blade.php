@php
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

        @if($equipmentAssignments->isEmpty())
            <div class="empty-box">
                <i class="fa-solid fa-box-open"></i>
                <div>No station equipment is listed yet.</div>
            </div>
        @else
            <div class="equipment-grid">
                @foreach($equipmentAssignments as $assignment)
                    <div class="equipment-item">
                        <div class="equipment-item-main">
                            <span class="equipment-icon"><i class="fa-solid fa-toolbox"></i></span>
                            <div>
                                <div class="equipment-name">{{ $assignment->equipment?->equipment_name ?? 'Unnamed equipment' }}</div>
                                <div class="equipment-category">{{ $assignment->equipment?->category?->category_name ?? 'Uncategorized equipment' }}</div>
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
