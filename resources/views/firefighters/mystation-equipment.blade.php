<section class="section-card">
    <div class="section-header">
        <div>
            <div class="section-title"><i class="fa-solid fa-toolbox"></i> Station Equipment</div>
            <div class="section-subtitle">Equipment records are loaded from the station-equipment assignments linked to this station.</div>
        </div>
    </div>
    <div class="section-body">
        @if($equipmentAssignments->isEmpty())
            <div class="empty-box">
                <i class="fa-solid fa-box-open"></i>
                <div>No equipment assignments are linked to this station yet.</div>
            </div>
        @else
            <div class="equipment-grid">
                @foreach($equipmentAssignments as $assignment)
                    <div class="equipment-item">
                        <div>
                            <div class="equipment-name">{{ $assignment->equipment?->equipment_name ?? 'Unnamed equipment' }}</div>
                            <div class="equipment-category">{{ $assignment->equipment?->category?->category_name ?? 'Uncategorized equipment' }}</div>
                        </div>
                        <div class="qty-pill qty-available">Available: {{ $assignment->available_quantity ?? 0 }}</div>
                        <div class="qty-pill qty-maintenance">Maintenance: {{ $assignment->maintenance_quantity ?? 0 }}</div>
                        <div class="qty-pill qty-damaged">Damaged: {{ $assignment->damaged_quantity ?? 0 }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
