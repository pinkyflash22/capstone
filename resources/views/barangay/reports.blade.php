@extends('barangay.layouts.app')

@section('active_nav', 'reports')
@section('page_title', 'Fire Reports')
@section('page_subtitle', 'Barangay fire record register and reporting workflow')
@section('hero_title', 'Manage barangay fire records from one reporting desk.')
@section('hero_copy', 'Use this page only for barangay fire records. Submit a new report, review previous entries, and maintain the linked incident details without leaving the register.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-fire-flame-curved"></i> Fire records only</span>
    <span class="hero-badge"><i class="fa-solid fa-folder-open"></i> CRUD-ready register</span>
    <span class="hero-badge"><i class="fa-solid fa-location-crosshairs"></i> Current location capture</span>
@endsection

@section('content')
    @if (session('success'))
        <div class="panel-card rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="panel-card rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Unable to save the fire record.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="panel-eyebrow">Barangay Fire Record Register</div>
                <h3 class="panel-title">Recorded fire reports for this barangay hall</h3>
                <p class="panel-copy">This list is limited to barangay-side fire records. Creating, editing, and deleting here also updates the linked incident and marker data.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong class="block text-lg text-slate-900">{{ $fireRecords->count() }}</strong>
                    Showing records
                    @if(($fireRecordTotalCount ?? $fireRecords->count()) !== $fireRecords->count())
                        <span class="block text-xs text-amber-700">out of {{ $fireRecordTotalCount }} total</span>
                    @endif
                </div>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-orange-600 px-5 py-3 font-semibold text-white shadow-lg shadow-orange-200 transition hover:bg-orange-700" data-open-modal="createFireReportModal">
                    <i class="fa-solid fa-plus"></i>
                    Report Fire
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('barangay.reports') }}" class="mt-6 fire-report-filterbar">
            <div class="fire-report-filterbar__grid">
                <div class="fire-report-form__field">
                    <label for="fire_record_search">Search</label>
                    <input id="fire_record_search" name="search" type="text" value="{{ $fireRecordFilters['search'] ?? '' }}" placeholder="Search title, cause, location, or notes">
                </div>
                <div class="fire-report-form__field">
                    <label for="fire_record_classification">Classification</label>
                    <select id="fire_record_classification" name="classification">
                        <option value="">All classifications</option>
                        <option value="accidental" @selected(($fireRecordFilters['classification'] ?? '') === 'accidental')>Accidental</option>
                        <option value="intentional" @selected(($fireRecordFilters['classification'] ?? '') === 'intentional')>Intentional</option>
                        <option value="undetermined" @selected(($fireRecordFilters['classification'] ?? '') === 'undetermined')>Undetermined</option>
                    </select>
                </div>
                <div class="fire-report-form__field">
                    <label for="fire_record_date_from">Date From</label>
                    <input id="fire_record_date_from" name="date_from" type="date" value="{{ $fireRecordFilters['date_from'] ?? '' }}">
                </div>
                <div class="fire-report-form__field">
                    <label for="fire_record_date_to">Date To</label>
                    <input id="fire_record_date_to" name="date_to" type="date" value="{{ $fireRecordFilters['date_to'] ?? '' }}">
                </div>
            </div>
            <div class="fire-report-filterbar__actions">
                <a href="{{ route('barangay.reports') }}" class="fire-report-secondary-btn">Reset</a>
                <button type="submit" class="fire-report-primary-btn">Apply Filters</button>
            </div>
        </form>

        @if($fireRecords->isEmpty())
            <div class="empty-state mt-6">No barangay fire records are linked to this hall yet.</div>
        @else
            <div class="table-wrap mt-6">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Classification</th>
                            <th>Cause</th>
                            <th>Location</th>
                            <th>Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fireRecords as $record)
                            @php
                                $marker = $record->incident?->marker;
                            @endphp
                            <tr>
                                <td>{{ optional($record->incident_date)->format('M d, Y') ?? 'N/A' }}</td>
                                <td>{{ $marker?->title ?? 'Barangay Fire Record' }}</td>
                                <td>{{ ucfirst($record->fire_classification ?? 'undetermined') }}</td>
                                <td>{{ $record->fire_cause ?? 'Pending assessment' }}</td>
                                <td>{{ $marker?->description ?? 'No location description' }}</td>
                                <td>{{ $record->incident_count ?? 1 }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-orange-300 hover:text-orange-700"
                                            data-open-edit-fire-report
                                            data-record-id="{{ $record->barangay_fire_record_id }}"
                                            data-title="{{ $marker?->title ?? 'Barangay Fire Record' }}"
                                            data-incident-date="{{ optional($record->incident_date)->toDateString() ?? now()->toDateString() }}"
                                            data-incident-count="{{ $record->incident_count ?? 1 }}"
                                            data-fire-classification="{{ $record->fire_classification ?? 'undetermined' }}"
                                            data-fire-cause="{{ $record->fire_cause ?? '' }}"
                                            data-location-description="{{ $marker?->description ?? '' }}"
                                            data-lat="{{ $marker?->lat ?? '' }}"
                                            data-lng="{{ $marker?->lng ?? '' }}"
                                            data-notes="{{ $record->notes ?? '' }}"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('barangay.reports.fire.destroy', $record->barangay_fire_record_id) }}" onsubmit="return confirm('Delete this fire record and its linked incident?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 transition hover:border-red-300 hover:bg-red-100">
                                                <i class="fa-solid fa-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div id="createFireReportModal" class="fire-report-modal hidden">
        <div class="fire-report-modal__backdrop" data-close-modal></div>
        <div class="fire-report-modal__dialog">
            <div class="fire-report-modal__header">
                <div>
                    <div class="panel-eyebrow">New Fire Record</div>
                    <h3 class="panel-title">Submit a barangay fire report</h3>
                </div>
                <button type="button" class="fire-report-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('barangay.reports.fire.store') }}" class="fire-report-form">
                @csrf
                @include('barangay.partials.fire-report-form-fields', ['prefix' => 'create', 'record' => null])
                <div class="fire-report-modal__actions">
                    <button type="button" class="fire-report-secondary-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="fire-report-primary-btn">Save Fire Report</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editFireReportModal" class="fire-report-modal hidden">
        <div class="fire-report-modal__backdrop" data-close-modal></div>
        <div class="fire-report-modal__dialog">
            <div class="fire-report-modal__header">
                <div>
                    <div class="panel-eyebrow">Update Fire Record</div>
                    <h3 class="panel-title">Edit barangay fire report</h3>
                </div>
                <button type="button" class="fire-report-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="" id="editFireReportForm" class="fire-report-form">
                @csrf
                @method('PUT')
                @include('barangay.partials.fire-report-form-fields', ['prefix' => 'edit', 'record' => null])
                <div class="fire-report-modal__actions">
                    <button type="button" class="fire-report-secondary-btn" data-close-modal>Cancel</button>
                    <button type="submit" class="fire-report-primary-btn">Update Fire Report</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .fire-report-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
    }

    .fire-report-modal.hidden {
        display: none;
    }

    .fire-report-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(4px);
    }

    .fire-report-modal__dialog {
        position: relative;
        width: min(960px, 100%);
        max-height: calc(100vh - 2rem);
        overflow: auto;
        border-radius: 28px;
        background: #fffdf9;
        border: 1px solid rgba(251, 191, 36, 0.18);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.18);
        padding: 1.5rem;
    }

    .fire-report-modal__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .fire-report-modal__close {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: white;
        color: #475569;
    }

    .fire-report-form {
        display: grid;
        gap: 1.25rem;
    }

    .fire-report-form__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .fire-report-form__field--full {
        grid-column: 1 / -1;
    }

    .fire-report-form__field label {
        display: block;
        margin-bottom: 0.4rem;
        font-size: 0.92rem;
        font-weight: 600;
        color: #334155;
    }

    .fire-report-form__field input,
    .fire-report-form__field select,
    .fire-report-form__field textarea {
        width: 100%;
        border-radius: 16px;
        border: 1px solid rgba(203, 213, 225, 0.95);
        background: white;
        padding: 0.85rem 1rem;
        color: #0f172a;
    }

    .fire-report-form__field textarea {
        min-height: 110px;
        resize: vertical;
    }

    .fire-report-location-panel {
        border-radius: 22px;
        border: 1px solid rgba(251, 191, 36, 0.22);
        background: linear-gradient(180deg, #fffaf0 0%, #ffffff 100%);
        padding: 1rem;
    }

    .fire-report-location-panel__header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .fire-report-location-btn,
    .fire-report-primary-btn,
    .fire-report-secondary-btn {
        border-radius: 16px;
        padding: 0.85rem 1.1rem;
        font-weight: 600;
    }

    .fire-report-location-btn,
    .fire-report-secondary-btn {
        border: 1px solid rgba(251, 191, 36, 0.25);
        background: #fff7ed;
        color: #c2410c;
    }

    .fire-report-primary-btn {
        border: none;
        background: #ea580c;
        color: white;
        box-shadow: 0 16px 34px rgba(234, 88, 12, 0.24);
    }

    .fire-report-modal__actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .fire-report-filterbar {
        margin-top: 1.5rem;
        border-radius: 22px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
        padding: 1rem;
    }

    .fire-report-filterbar__grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 1rem;
    }

    .fire-report-filterbar__actions {
        margin-top: 1rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        .fire-report-form__grid {
            grid-template-columns: 1fr;
        }

        .fire-report-filterbar__grid {
            grid-template-columns: 1fr;
        }

        .fire-report-modal {
            padding: 0.75rem;
        }

        .fire-report-modal__dialog {
            padding: 1rem;
        }

        .fire-report-modal__actions {
            flex-direction: column-reverse;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const createModal = document.getElementById('createFireReportModal');
        const editModal = document.getElementById('editFireReportModal');
        const editForm = document.getElementById('editFireReportForm');

        function openModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            body.classList.add('overflow-hidden');
        }

        function closeModal(modal) {
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-open-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(document.getElementById(button.dataset.openModal));
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(button.closest('.fire-report-modal'));
            });
        });

        document.querySelectorAll('[data-open-edit-fire-report]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!editModal || !editForm) {
                    return;
                }

                editForm.action = "{{ url('/barangay/reports/fire') }}/" + button.dataset.recordId;

                const fieldMap = {
                    title: button.dataset.title,
                    incident_date: button.dataset.incidentDate,
                    incident_count: button.dataset.incidentCount,
                    fire_classification: button.dataset.fireClassification,
                    fire_cause: button.dataset.fireCause,
                    location_description: button.dataset.locationDescription,
                    lat: button.dataset.lat,
                    lng: button.dataset.lng,
                    notes: button.dataset.notes,
                };

                Object.entries(fieldMap).forEach(function ([key, value]) {
                    const input = document.getElementById('edit_' + key);
                    if (input) {
                        input.value = value ?? '';
                    }
                });

                openModal(editModal);
            });
        });

        function attachCurrentLocation(buttonId, latId, lngId) {
            const button = document.getElementById(buttonId);
            const latInput = document.getElementById(latId);
            const lngInput = document.getElementById(lngId);

            if (!button || !latInput || !lngInput) {
                return;
            }

            button.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    return;
                }

                button.disabled = true;
                const previousLabel = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Getting location...';

                navigator.geolocation.getCurrentPosition(function (position) {
                    latInput.value = Number(position.coords.latitude).toFixed(7);
                    lngInput.value = Number(position.coords.longitude).toFixed(7);
                    button.disabled = false;
                    button.innerHTML = previousLabel;
                }, function () {
                    button.disabled = false;
                    button.innerHTML = previousLabel;
                });
            });
        }

        attachCurrentLocation('create_useCurrentLocationBtn', 'create_lat', 'create_lng');
        attachCurrentLocation('edit_useCurrentLocationBtn', 'edit_lat', 'edit_lng');

        @if ($errors->any())
            openModal(createModal);
        @endif
    });
</script>
@endpush
