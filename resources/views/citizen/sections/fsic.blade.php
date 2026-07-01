@php
    $fsicCollection = collect($fsicRequests ?? [])->values();
    $fsicPendingTotal = $fsicCollection->where('status', 'pending')->count();
    $fsicApprovedTotal = $fsicCollection->where('status', 'approved')->count();
    $fsicInspectionTotal = $fsicCollection->filter(fn ($request) => in_array($request->status, ['for_inspection', 'inspected'], true))->count();
@endphp

{{-- FSIC REQUEST SECTION --}}
<style>
    #section-fsic.fsic-page {
        --fsic-red: #c63d26;
        --fsic-red-dark: #9f2f1d;
        --fsic-orange: #e39b17;
        --fsic-ink: #132033;
        --fsic-muted: #64748b;
        width: min(100%, 1240px);
        margin-inline: auto;
        padding: clamp(0.85rem, 1.45vw, 1.35rem);
        padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
        color: var(--fsic-ink);
    }

    #section-fsic .fsic-shell {
        display: grid;
        gap: clamp(1rem, 2vw, 1.35rem);
    }

    #section-fsic .fsic-hero,
    #section-fsic .fsic-card,
    #section-fsic .fsic-panel {
        border: 1px solid rgba(120, 53, 15, 0.11);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 40px rgba(88, 43, 20, 0.075);
    }

    #section-fsic .fsic-hero {
        position: relative;
        overflow: hidden;
        border-color: rgba(198, 61, 38, 0.16);
        border-radius: 2rem;
        background:
            radial-gradient(circle at 92% 12%, rgba(227, 155, 23, 0.24), transparent 16rem),
            linear-gradient(135deg, rgba(255, 248, 237, 0.98), rgba(255, 255, 255, 0.96));
        padding: clamp(1.1rem, 3vw, 1.75rem);
    }

    #section-fsic .fsic-hero-grid {
        position: relative;
        display: grid;
        gap: 1rem;
    }

    #section-fsic .fsic-eyebrow {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        background: rgba(198, 61, 38, 0.1);
        color: var(--fsic-red-dark);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        padding: 0.45rem 0.7rem;
    }

    #section-fsic .fsic-title {
        margin-top: 0.8rem;
        color: var(--fsic-ink);
        font-size: clamp(1.85rem, 5vw, 2.85rem);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.055em;
    }

    #section-fsic .fsic-subtitle {
        max-width: 44rem;
        margin-top: 0.75rem;
        color: var(--fsic-muted);
        font-size: clamp(0.94rem, 2.4vw, 1.03rem);
        line-height: 1.7;
    }

    #section-fsic .fsic-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    #section-fsic .fsic-pill {
        display: inline-flex;
        min-height: 44px;
        align-items: center;
        gap: 0.55rem;
        border-radius: 999px;
        border: 1px solid rgba(245, 158, 11, 0.2);
        background: linear-gradient(135deg, #fffbeb, #fff7ed);
        color: #b45309;
        padding: 0.72rem 1rem;
        font-size: 0.88rem;
        font-weight: 850;
    }

    #section-fsic .fsic-primary-btn {
        display: inline-flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 0.62rem;
        border: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, var(--fsic-red), var(--fsic-orange));
        color: #fff;
        padding: 0.82rem 1.1rem;
        font-size: 0.92rem;
        font-weight: 850;
        box-shadow: 0 16px 34px rgba(198, 61, 38, 0.24);
    }

    #section-fsic .fsic-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }

    #section-fsic .fsic-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.6rem;
        padding: 1.1rem;
    }

    #section-fsic .fsic-card::after {
        content: '';
        position: absolute;
        inset: auto -2rem -3rem auto;
        width: 8rem;
        height: 8rem;
        border-radius: 999px;
        background: var(--fsic-glow, rgba(198, 61, 38, 0.08));
    }

    #section-fsic .fsic-card-top {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 0.8rem;
    }

    #section-fsic .fsic-card-label {
        color: var(--fsic-muted);
        font-size: 0.82rem;
        font-weight: 750;
    }

    #section-fsic .fsic-card-value {
        margin-top: 0.42rem;
        color: var(--fsic-ink);
        font-size: clamp(2rem, 6vw, 2.65rem);
        font-weight: 950;
        letter-spacing: -0.055em;
        line-height: 1;
    }

    #section-fsic .fsic-card-icon {
        display: inline-flex;
        width: 3rem;
        height: 3rem;
        flex: 0 0 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 1.05rem;
        background: var(--fsic-icon-bg, #fff7ed);
        color: var(--fsic-icon-color, #c2410c);
        font-size: 1.1rem;
    }

    #section-fsic .fsic-card-copy {
        position: relative;
        z-index: 1;
        margin-top: 0.9rem;
        color: var(--fsic-muted);
        font-size: 0.78rem;
        line-height: 1.55;
    }

    #section-fsic .fsic-panel {
        overflow: hidden;
        border-radius: 1.75rem;
    }

    #section-fsic .fsic-panel-head {
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(135deg, rgba(255, 248, 237, 0.74), rgba(255, 255, 255, 0.96));
        padding: 1.1rem;
    }

    #section-fsic .fsic-panel-title {
        margin-top: 0.55rem;
        color: var(--fsic-ink);
        font-size: clamp(1.1rem, 3vw, 1.35rem);
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    #section-fsic .fsic-panel-copy {
        margin-top: 0.35rem;
        color: var(--fsic-muted);
        font-size: 0.88rem;
        line-height: 1.62;
    }

    #section-fsic .fsic-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #section-fsic .fsic-mobile-bottom-space {
        display: none;
    }

    #section-fsic .fsic-empty-cell {
        padding-block: 3.5rem !important;
    }

    #section-fsic .fsic-table {
        width: 100%;
        min-width: 760px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.9rem;
    }

    #section-fsic .fsic-table th {
        background: #fff7ed;
        color: #9a3412;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    #section-fsic .fsic-table td,
    #section-fsic .fsic-table th {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    #section-fsic .fsic-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    #section-fsic .fsic-action {
        border-radius: 999px;
        background: #fff7ed;
        padding: 0.45rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 800;
        color: #c2410c;
    }

    #section-fsic .fsic-action--green {
        background: #ecfdf5;
        color: #047857;
    }

    #section-fsic .fsic-action--blue {
        background: #eff6ff;
        color: #1d4ed8;
    }

    #section-fsic .fsic-action--red {
        background: #fef2f2;
        color: #b91c1c;
    }

    @media (min-width: 760px) {
        #section-fsic .fsic-hero-grid {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }
    }

    @media (max-width: 520px) {
        #section-fsic.fsic-page {
            width: 100%;
            padding: 1rem;
            padding-bottom: calc(7.5rem + env(safe-area-inset-bottom, 0px));
        }

        #section-fsic .fsic-shell {
            gap: 1rem;
        }

        #section-fsic .fsic-hero {
            border-radius: 1.4rem;
            box-shadow: none;
            padding: 1.35rem 1rem 1.05rem;
        }

        #section-fsic .fsic-title {
            max-width: 17rem;
            font-size: 2rem;
            line-height: 1.05;
        }

        #section-fsic .fsic-subtitle {
            max-width: 18rem;
            font-size: 0.93rem;
            line-height: 1.58;
        }

        #section-fsic .fsic-hero-actions,
        #section-fsic .fsic-primary-btn,
        #section-fsic .fsic-pill {
            width: 100%;
        }

        #section-fsic .fsic-primary-btn,
        #section-fsic .fsic-pill {
            justify-content: center;
            border-radius: 1rem;
        }

        #section-fsic .fsic-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.55rem;
        }

        #section-fsic .fsic-card {
            min-height: 8.25rem;
            border-radius: 1rem;
            padding: 0.72rem;
            box-shadow: none;
        }

        #section-fsic .fsic-card-top {
            display: grid;
            gap: 0.55rem;
        }

        #section-fsic .fsic-card-icon {
            order: -1;
            width: 2.25rem;
            height: 2.25rem;
            flex-basis: 2.25rem;
            border-radius: 0.78rem;
            font-size: 0.95rem;
        }

        #section-fsic .fsic-card-label {
            font-size: 0.68rem;
            line-height: 1.18;
        }

        #section-fsic .fsic-card-value {
            margin-top: 0.28rem;
            font-size: 1.7rem;
        }

        #section-fsic .fsic-card-copy {
            display: none;
        }

        #section-fsic .fsic-panel {
            border-radius: 1.2rem;
            box-shadow: none;
        }

        #section-fsic .fsic-panel-head {
            background: transparent;
            padding: 1rem 0.95rem;
        }

        #section-fsic .fsic-table {
            min-width: 0;
            table-layout: fixed;
            font-size: 0.84rem;
        }

        #section-fsic .fsic-mobile-hide {
            display: none;
        }

        #section-fsic .fsic-table th:nth-child(1),
        #section-fsic .fsic-table td:nth-child(1) {
            width: 42%;
        }

        #section-fsic .fsic-table th:nth-child(5),
        #section-fsic .fsic-table td:nth-child(5) {
            width: 28%;
        }

        #section-fsic .fsic-table th:nth-child(6),
        #section-fsic .fsic-table td:nth-child(6) {
            width: 30%;
        }

        #section-fsic .fsic-table-wrap {
            min-height: 18rem;
            padding-bottom: calc(6.5rem + env(safe-area-inset-bottom, 0px));
        }

        #section-fsic .fsic-table td,
        #section-fsic .fsic-table th {
            padding: 0.78rem 0.55rem;
            white-space: normal;
            vertical-align: middle;
        }

        #section-fsic .fsic-table th {
            font-size: 0.61rem;
            letter-spacing: 0.055em;
        }

        #section-fsic .fsic-table td {
            font-size: 0.76rem;
        }

        #section-fsic .fsic-table td:first-child {
            overflow-wrap: anywhere;
            font-size: 0.74rem;
            line-height: 1.25;
        }

        #section-fsic .fsic-table td:nth-child(5) span {
            display: inline-flex;
            max-width: 100%;
            align-items: center;
            justify-content: center;
            padding: 0.35rem 0.42rem;
            font-size: 0.63rem;
            line-height: 1.1;
            white-space: normal;
            text-align: center;
        }

        #section-fsic .fsic-actions {
            justify-content: flex-end;
            gap: 0.3rem;
        }

        #section-fsic .fsic-action {
            padding: 0.38rem 0.48rem;
            font-size: 0.68rem;
        }

        #section-fsic .fsic-empty-cell {
            padding-block: 4.75rem !important;
        }

        #section-fsic .fsic-mobile-bottom-space {
            display: block;
            height: calc(2.25rem + env(safe-area-inset-bottom, 0px));
        }
    }
</style>

<div id="section-fsic" class="content-section fsic-page">
    <div class="fsic-shell">
        <section class="fsic-hero">
            <div class="fsic-hero-grid">
                <div>
                    <span class="fsic-eyebrow"><i class="fa-solid fa-certificate"></i> Citizen requests</span>
                    <h1 class="fsic-title">Fire Safety Inspection Certificate</h1>
                    <p class="fsic-subtitle">Request, track, and manage your FSIC applications from your citizen portal.</p>
                </div>

                <div class="fsic-hero-actions">
                    <span class="fsic-pill">
                        <i class="fa-solid fa-file-circle-exclamation"></i>
                        <span><span id="fsicPendingCount">{{ $fsicPendingTotal }}</span> Pending Requests</span>
                    </span>

                    <button type="button" onclick="openNewFsicModal()" class="fsic-primary-btn">
                        <i class="fa-solid fa-plus"></i>
                        <span>New Request</span>
                    </button>
                </div>
            </div>
        </section>

        <section class="fsic-stats" aria-label="FSIC request summary">
            <div class="fsic-card" style="--fsic-glow:rgba(245,158,11,0.14);--fsic-icon-bg:#fffbeb;--fsic-icon-color:#d97706;">
                <div class="fsic-card-top">
                    <div>
                        <p class="fsic-card-label">Pending</p>
                        <p class="fsic-card-value">{{ $fsicPendingTotal }}</p>
                    </div>
                    <div class="fsic-card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                </div>
                <p class="fsic-card-copy">Requests waiting for review or initial processing.</p>
            </div>

            <div class="fsic-card" style="--fsic-glow:rgba(37,99,235,0.1);--fsic-icon-bg:#eff6ff;--fsic-icon-color:#2563eb;">
                <div class="fsic-card-top">
                    <div>
                        <p class="fsic-card-label">Inspection</p>
                        <p class="fsic-card-value">{{ $fsicInspectionTotal }}</p>
                    </div>
                    <div class="fsic-card-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                </div>
                <p class="fsic-card-copy">Requests currently scheduled or marked inspected.</p>
            </div>

            <div class="fsic-card" style="--fsic-glow:rgba(5,150,105,0.12);--fsic-icon-bg:#ecfdf5;--fsic-icon-color:#059669;">
                <div class="fsic-card-top">
                    <div>
                        <p class="fsic-card-label">Approved</p>
                        <p class="fsic-card-value">{{ $fsicApprovedTotal }}</p>
                    </div>
                    <div class="fsic-card-icon"><i class="fa-solid fa-circle-check"></i></div>
                </div>
                <p class="fsic-card-copy">Approved certificates ready for next actions.</p>
            </div>
        </section>

        <section class="fsic-panel">
            <div class="fsic-panel-head">
                <span class="fsic-eyebrow"><i class="fa-solid fa-list-check"></i> Applications</span>
                <h2 class="fsic-panel-title">My FSIC Requests</h2>
                <p class="fsic-panel-copy">Review your applications, track status updates, and continue pending request actions.</p>
            </div>

            <div class="fsic-table-wrap">
            <table class="fsic-table text-left">
                <thead>
                    <tr>
                        <th class="px-6 py-3">Request Code</th>
                        <th class="px-6 py-3 fsic-mobile-hide">Date</th>
                        <th class="px-6 py-3 fsic-mobile-hide">Business Name</th>
                        <th class="px-6 py-3 fsic-mobile-hide">Owner</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>

                <tbody id="fsicRequestsTableBody">
                    @forelse($fsicRequests ?? [] as $request)
                    <tr class="border-b hover:bg-gray-50" id="request-row-{{ $request->id }}">
                        <td class="px-6 py-4 font-medium">
                            {{ $request->request_code }}
                        </td>
                        <td class="px-6 py-4 fsic-mobile-hide">
                            {{ $request->date_requested->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 font-medium fsic-mobile-hide">
                            {{ $request->business_name }}
                        </td>
                        <td class="px-6 py-4 fsic-mobile-hide">
                            {{ $request->owner_name }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'for_inspection' => 'bg-blue-100 text-blue-700',
                                    'inspected' => 'bg-purple-100 text-purple-700',
                                    'approved' => 'bg-green-100 text-green-700',
                                    'rejected' => 'bg-red-100 text-red-700'
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100' }}">
                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="fsic-actions">
                                <button class="fsic-action" onclick="viewFsicDetails({{ $request->id }})">
                                    View
                                </button>
                                @if($request->status == 'approved' && optional($request->approval)->date_issued === null)
                                <button class="fsic-action fsic-action--green" onclick="claimFsicRequest({{ $request->id }})">
                                    Claim
                                </button>
                                @endif
                                
                                @if($request->status == 'pending')
                                <button class="fsic-action fsic-action--blue" onclick="editFsicRequest({{ $request->id }})">
                                    Edit
                                </button>
                                <button class="fsic-action fsic-action--red" onclick="cancelFsicRequest({{ $request->id }})">
                                    Cancel
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="fsic-empty-cell text-center py-10 text-gray-500">
                                No FSIC requests yet. Click "New Request" to apply.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </section>
        <div class="fsic-mobile-bottom-space" aria-hidden="true"></div>
    </div>
</div>

{{-- EDIT FSIC REQUEST MODAL --}}
<div id="editFsicModal" class="modal">
    <div class="modal-content" style="max-width: 850px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header flex justify-between items-center p-4 border-b sticky top-0 bg-white z-10">
            <h2 class="text-xl font-bold">Edit FSIC Request</h2>
            <button onclick="closeEditFsicModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <form id="editFsicForm">
            <input type="hidden" name="request_id" id="edit_request_id">
            <div class="modal-body p-4">
                <!-- Business Information -->
                <h3 class="font-semibold text-gray-800 mb-3 pb-2 border-b">
                    <i class="fa-solid fa-building text-orange-600 mr-2"></i>Business Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Business Name</label>
                        <input type="text" name="business_name" id="edit_business_name" class="w-full border rounded-lg p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Owner/Applicant Name</label>
                        <input type="text" name="owner_name" id="edit_owner_name" class="w-full border rounded-lg p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                        <select name="business_type" id="edit_business_type" class="w-full border rounded-lg p-2">
                            <option value="">Select...</option>
                            <option value="retail">Retail / Store</option>
                            <option value="restaurant">Restaurant / Food Service</option>
                            <option value="office">Office</option>
                            <option value="warehouse">Warehouse / Storage</option>
                            <option value="hotel">Hotel / Inn</option>
                            <option value="school">School / Educational</option>
                            <option value="hospital">Hospital / Clinic</option>
                            <option value="manufacturing">Manufacturing / Factory</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Location Details -->
                <h3 class="font-semibold text-gray-800 mb-3 pb-2 border-b mt-4">
                    <i class="fa-solid fa-location-dot text-orange-600 mr-2"></i>Location Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Building/Street Address</label>
                        <input type="text" name="building_address" id="edit_building_address" class="w-full border rounded-lg p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Province</label>
                        <input type="text" name="province" id="edit_province" class="w-full border rounded-lg p-2 bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">City</label>
                        <input type="text" name="city" id="edit_city" class="w-full border rounded-lg p-2 bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Barangay</label>
                        <input type="text" name="barangay" id="edit_barangay" class="w-full border rounded-lg p-2 bg-gray-100" readonly>
                    </div>
                </div>

                <!-- Registration Numbers -->
                <h3 class="font-semibold text-gray-800 mb-3 pb-2 border-b">
                    <i class="fa-solid fa-file-invoice text-orange-600 mr-2"></i>Registration Numbers
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">DTI / SEC Registration No.</label>
                        <input type="text" name="dti_sec_registration_no" id="edit_dti_sec_registration_no" class="w-full border rounded-lg p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mayor's Permit No.</label>
                        <input type="text" name="mayors_permit_no" id="edit_mayors_permit_no" class="w-full border rounded-lg p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">TIN Number</label>
                        <input type="text" name="tin_number" id="edit_tin_number" class="w-full border rounded-lg p-2">
                    </div>
                </div>
            </div>

            <div class="modal-footer p-4 border-t flex justify-end gap-2">
                <button type="button" class="px-4 py-2 bg-gray-300 rounded-lg" onclick="closeEditFsicModal()">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg">
                    <i class="fa-regular fa-floppy-disk mr-1"></i> Update Request
                </button>
            </div>
        </form>
    </div>
</div>

{{-- CANCEL CONFIRMATION MODAL --}}
<div id="cancelConfirmModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header flex justify-between items-center p-4 border-b">
            <h2 class="text-xl font-bold text-red-600">Cancel Request</h2>
            <button onclick="closeCancelConfirmModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        <div class="modal-body p-4">
            <div class="text-center">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-5xl mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Are you sure?</h3>
                <p class="text-gray-600 mb-4">This action cannot be undone. The request will be permanently deleted.</p>
                <p class="text-sm text-gray-500" id="cancelRequestCode"></p>
            </div>
        </div>
        <div class="modal-footer p-4 border-t flex justify-end gap-2">
            <button type="button" class="px-4 py-2 bg-gray-300 rounded-lg" onclick="closeCancelConfirmModal()">No, Go Back</button>
            <button type="button" id="confirmCancelBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg">
                Yes, Cancel Request
            </button>
        </div>
    </div>
</div>

{{-- NEW FSIC REQUEST MODAL WITH PROGRESS BAR --}}

{{-- NEW FSIC REQUEST MODAL WITH PROGRESS BAR --}}
<div id="newFsicModal" class="modal">
    <div class="modal-content" style="max-width: 850px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header flex justify-between items-center p-4 border-b sticky top-0 bg-white z-10">
            <h2 class="text-xl font-bold">New FSIC Request</h2>
            <button onclick="closeNewFsicModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <!-- Progress Bar -->
        <div class="px-4 pt-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 text-center">
                    <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-sm font-bold step-1 active-step bg-orange-600 text-white">1</div>
                    <span class="text-xs mt-1 block">Input Details</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-200 step-1-line"></div>
                <div class="flex-1 text-center">
                    <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-sm font-bold step-2 bg-gray-200 text-gray-500">2</div>
                    <span class="text-xs mt-1 block">Review & Submit</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-200 step-2-line"></div>
                <div class="flex-1 text-center">
                    <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center text-sm font-bold step-3 bg-gray-200 text-gray-500">3</div>
                    <span class="text-xs mt-1 block">Confirmation</span>
                </div>
            </div>
        </div>

        <form id="fsicRequestForm">
            <div class="modal-body p-4">
                <!-- Hidden user_id field -->
                <input type="hidden" name="user_id" value="{{ Auth::user()->user_id ?? '' }}">

                <!-- STEP 1: INPUT DETAILS -->
                <div id="step1" class="form-step">
                    <!-- Business Information -->
                    <h3 class="font-semibold text-gray-800 mb-3 pb-2 border-b">
                        <i class="fa-solid fa-building text-orange-600 mr-2"></i>Business Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Business Name</label>
                            <input type="text" name="business_name" class="w-full border rounded-lg p-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Owner/Applicant Name</label>
                            <input type="text" name="owner_name" class="w-full border rounded-lg p-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                            <select name="business_type" class="w-full border rounded-lg p-2">
                                <option value="">Select...</option>
                                <option value="retail">Retail / Store</option>
                                <option value="restaurant">Restaurant / Food Service</option>
                                <option value="office">Office</option>
                                <option value="warehouse">Warehouse / Storage</option>
                                <option value="hotel">Hotel / Inn</option>
                                <option value="school">School / Educational</option>
                                <option value="hospital">Hospital / Clinic</option>
                                <option value="manufacturing">Manufacturing / Factory</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Location Details with Dynamic Dropdowns -->
                    <h3 class="font-semibold text-gray-800 mb-3 pb-2 border-b mt-4">
                        <i class="fa-solid fa-location-dot text-orange-600 mr-2"></i>Location Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Building/Street Address</label>
                            <input type="text" name="building_address" class="w-full border rounded-lg p-2" required placeholder="House/Block/Lot number, Street, Subdivision">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Province</label>
                            <select name="province_id" id="provinceSelect" class="w-full border rounded-lg p-2" required onchange="loadCities()">
                                <option value="">Select Province</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->province_id }}">{{ $province->province_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">City/Municipality</label>
                            <select name="city_id" id="citySelect" class="w-full border rounded-lg p-2" required onchange="loadBarangays()" disabled>
                                <option value="">Select City First</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required">Barangay</label>
                            <select name="barangay_id" id="barangaySelect" class="w-full border rounded-lg p-2" required disabled>
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                    </div>

                    <!-- Registration Numbers -->
                    <h3 class="font-semibold text-gray-800 mb-3 pb-2 border-b">
                        <i class="fa-solid fa-file-invoice text-orange-600 mr-2"></i>Registration Numbers
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">DTI / SEC Registration No.</label>
                            <input type="text" name="dti_sec_registration_no" class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mayor's Permit No.</label>
                            <input type="text" name="mayors_permit_no" class="w-full border rounded-lg p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">TIN Number</label>
                            <input type="text" name="tin_number" class="w-full border rounded-lg p-2">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: REVIEW & SUBMIT -->
                <div id="step2" class="form-step" style="display: none;">
                    <div class="bg-blue-50 p-4 rounded-lg mb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-clipboard-list text-blue-600 text-xl"></i>
                            <p class="text-sm text-gray-700">Please review all information before submitting.</p>
                        </div>
                    </div>

                    <div id="reviewContent" class="space-y-4">
                        <!-- Review content will be populated dynamically -->
                    </div>
                </div>

                <!-- STEP 3: CONFIRMATION / SUCCESS MESSAGE -->
                <div id="step3" class="form-step" style="display: none;">
                    <div class="text-center py-8">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-check-circle text-green-600 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Request Submitted Successfully!</h3>
                        <p class="text-gray-600 mb-2">Your FSIC request has been received.</p>
                        <div class="bg-yellow-50 p-4 rounded-lg mt-4 text-left">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fa-solid fa-clock text-yellow-600"></i>
                                <span class="font-semibold text-gray-700">What's Next?</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-1 ml-6 list-disc">
                                <li>Your request is now <span class="font-semibold text-yellow-600">Pending Review</span></li>
                                <li>A fire safety inspector will be assigned to your request</li>
                                <li>You will receive an update once inspection is scheduled</li>
                                <li>Check your dashboard for status updates</li>
                            </ul>
                        </div>
                        <p class="text-sm text-gray-400 mt-4">Redirecting to dashboard in <span id="countdown">3</span> seconds...</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer p-4 border-t flex justify-between">
                <div>
                    <button type="button" id="prevBtn" class="px-4 py-2 bg-gray-300 rounded-lg" style="display: none;" onclick="changeStep(-1)">
                        <i class="fa-solid fa-chevron-left mr-1"></i> Previous
                    </button>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="px-4 py-2 bg-gray-300 rounded-lg" onclick="closeNewFsicModal()">Cancel</button>
                    <button type="button" id="nextBtn" class="px-4 py-2 bg-orange-600 text-white rounded-lg" onclick="changeStep(1)">
                        Next <i class="fa-solid fa-chevron-right ml-1"></i>
                    </button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg" style="display: none;">
                        <i class="fa-regular fa-paper-plane mr-1"></i> Submit Request
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- FSIC DETAILS MODAL --}}
<div id="fsicDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header flex justify-between items-center p-4 border-b">
            <h2 class="text-xl font-bold">FSIC Request Details</h2>
            <button onclick="closeFsicDetailsModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <div class="modal-body p-4" id="fsicDetailsContent">
            <div class="text-center py-8 text-gray-500">
                Loading...
            </div>
        </div>

        <div class="modal-footer p-4 border-t flex justify-end">
            <button class="px-4 py-2 bg-gray-300 rounded-lg" onclick="closeFsicDetailsModal()">Close</button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}
.modal.show {
    display: flex;
}
.modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 850px;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
}
.required:after {
    content: " *";
    color: red;
}
.form-step {
    transition: all 0.3s ease;
}
.active-step {
    background-color: #ea580c !important;
    color: white !important;
}
</style>

<script>
let currentStep = 1;
const totalSteps = 3;
let countdownInterval = null;
let currentEditId = null;
let currentCancelId = null;

function getFsicStatusClass(status) {
    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-700',
        for_inspection: 'bg-blue-100 text-blue-700',
        inspected: 'bg-purple-100 text-purple-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700'
    };

    return statusColors[status] || 'bg-gray-100 text-gray-700';
}

function formatFsicStatus(status) {
    return String(status || 'pending').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatFsicDate(dateValue) {
    if (!dateValue) return 'N/A';
    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) return dateValue;
    return date.toLocaleDateString([], { month: 'short', day: '2-digit', year: 'numeric' });
}

function getFsicRowMarkup(request) {
    const canEdit = String(request.status) === 'pending';
    const canClaim = String(request.status) === 'approved' && !request?.approval?.date_issued;

    return `
        <tr class="border-b hover:bg-gray-50" id="request-row-${request.id}">
            <td class="px-6 py-4 font-medium">${escapeHtml(request.request_code || 'N/A')}</td>
            <td class="px-6 py-4 fsic-mobile-hide">${formatFsicDate(request.date_requested)}</td>
            <td class="px-6 py-4 font-medium fsic-mobile-hide">${escapeHtml(request.business_name || 'N/A')}</td>
            <td class="px-6 py-4 fsic-mobile-hide">${escapeHtml(request.owner_name || 'N/A')}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full ${getFsicStatusClass(request.status)}">
                    ${escapeHtml(formatFsicStatus(request.status))}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="fsic-actions">
                    <button type="button" class="fsic-action" onclick="viewFsicDetails(${request.id})">View</button>
                    ${canClaim ? `
                        <button type="button" class="fsic-action fsic-action--green" onclick="claimFsicRequest(${request.id})">Claim</button>
                    ` : ''}
                    ${canEdit ? `
                        <button type="button" class="fsic-action fsic-action--blue" onclick="editFsicRequest(${request.id})">Edit</button>
                        <button type="button" class="fsic-action fsic-action--red" onclick="cancelFsicRequest(${request.id})">Cancel</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `;
}

function updateFsicPendingCount() {
    const pendingCountNode = document.getElementById('fsicPendingCount');
    if (!pendingCountNode) return;

    const pendingRows = Array.from(document.querySelectorAll('#fsicRequestsTableBody tr[id^="request-row-"]'))
        .filter((row) => row.textContent.toLowerCase().includes('pending'));

    pendingCountNode.textContent = pendingRows.length;
}

function upsertFsicRow(request, mode = 'prepend') {
    const tableBody = document.getElementById('fsicRequestsTableBody');
    if (!tableBody || !request?.id) return;

    const emptyStateRow = tableBody.querySelector('td[colspan="6"]')?.closest('tr');
    if (emptyStateRow) {
        emptyStateRow.remove();
    }

    const existingRow = document.getElementById(`request-row-${request.id}`);
    const markup = getFsicRowMarkup(request);

    if (existingRow) {
        existingRow.outerHTML = markup;
    } else if (mode === 'prepend') {
        tableBody.insertAdjacentHTML('afterbegin', markup);
    } else {
        tableBody.insertAdjacentHTML('beforeend', markup);
    }

    updateFsicPendingCount();
}

function ensureFsicEmptyState() {
    const tableBody = document.getElementById('fsicRequestsTableBody');
    if (!tableBody) return;

    const hasRows = tableBody.querySelector('tr[id^="request-row-"]');
    if (hasRows) {
        updateFsicPendingCount();
        return;
    }

        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="fsic-empty-cell text-center py-10 text-gray-500">
                    No FSIC requests yet. Click "New Request" to apply.
                </td>
            </tr>
        `;

    updateFsicPendingCount();
}

/* =========================
   EDIT FSIC REQUEST FUNCTIONS
========================= */
window.editFsicRequest = async function(id) {
    try {
        const response = await fetch(`/citizen/api/fsic/${id}`);
        const data = await response.json();
        
        if (data) {
            document.getElementById('edit_request_id').value = id;
            document.getElementById('edit_business_name').value = data.business_name || '';
            document.getElementById('edit_owner_name').value = data.owner_name || '';
            document.getElementById('edit_business_type').value = data.business_type || '';
            document.getElementById('edit_building_address').value = data.building_address || '';
            document.getElementById('edit_province').value = data.province || '';
            document.getElementById('edit_city').value = data.city || '';
            document.getElementById('edit_barangay').value = data.barangay || '';
            document.getElementById('edit_dti_sec_registration_no').value = data.dti_sec_registration_no || '';
            document.getElementById('edit_mayors_permit_no').value = data.mayors_permit_no || '';
            document.getElementById('edit_tin_number').value = data.tin_number || '';
            
            document.getElementById('editFsicModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    } catch (error) {
        console.error('Error fetching request:', error);
        alert('Failed to load request details');
    }
};

window.closeEditFsicModal = function() {
    document.getElementById('editFsicModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    currentEditId = null;
};

// Submit edit form
document.getElementById('editFsicForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('edit_request_id').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    const updateData = {
        business_name: document.getElementById('edit_business_name').value,
        owner_name: document.getElementById('edit_owner_name').value,
        business_type: document.getElementById('edit_business_type').value,
        building_address: document.getElementById('edit_building_address').value,
        dti_sec_registration_no: document.getElementById('edit_dti_sec_registration_no').value,
        mayors_permit_no: document.getElementById('edit_mayors_permit_no').value,
        tin_number: document.getElementById('edit_tin_number').value
    };
    
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Updating...';
    submitBtn.disabled = true;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/citizen/api/fsic/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(updateData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            const updatedRequest = {
                id: Number(id),
                status: 'pending',
                ...result.data,
                business_name: updateData.business_name,
                owner_name: updateData.owner_name,
                business_type: updateData.business_type,
                building_address: updateData.building_address,
                dti_sec_registration_no: updateData.dti_sec_registration_no,
                mayors_permit_no: updateData.mayors_permit_no,
                tin_number: updateData.tin_number,
            };

            upsertFsicRow(updatedRequest, 'append');
            closeEditFsicModal();
            window.showCitizenToast?.('FSIC request updated successfully.');
        } else {
            window.showCitizenToast?.(result.message || 'Failed to update request', true);
        }
    } catch (error) {
        console.error('Error:', error);
        window.showCitizenToast?.('An error occurred while updating', true);
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

/* =========================
   CANCEL FSIC REQUEST FUNCTIONS
========================= */
window.cancelFsicRequest = async function(id) {
    try {
        // Fetch request details to show request code in modal
        const response = await fetch(`/citizen/api/fsic/${id}`);
        const data = await response.json();
        
        if (data) {
            currentCancelId = id;
            document.getElementById('cancelRequestCode').innerHTML = `Request Code: <strong>${data.request_code}</strong>`;
            document.getElementById('cancelConfirmModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    } catch (error) {
        console.error('Error fetching request:', error);
        alert('Failed to load request details');
    }
};

window.closeCancelConfirmModal = function() {
    document.getElementById('cancelConfirmModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    currentCancelId = null;
};

// Confirm cancel button
document.getElementById('confirmCancelBtn')?.addEventListener('click', async function() {
    if (!currentCancelId) return;
    
    const confirmBtn = this;
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Cancelling...';
    confirmBtn.disabled = true;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/citizen/api/fsic/${currentCancelId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            document.getElementById(`request-row-${currentCancelId}`)?.remove();
            closeCancelConfirmModal();
            ensureFsicEmptyState();
            window.showCitizenToast?.('Request cancelled successfully.');
        } else {
            window.showCitizenToast?.(result.message || 'Failed to cancel request', true);
        }
    } catch (error) {
        console.error('Error:', error);
        window.showCitizenToast?.('An error occurred while cancelling', true);
    } finally {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    }
});

/* =========================
   PROGRESS BAR FUNCTIONS
========================= */
function updateProgressBar() {
    for (let i = 1; i <= totalSteps; i++) {
        const circle = document.querySelector(`.step-${i}`);
        if (circle) {
            if (i < currentStep) {
                circle.classList.remove('bg-gray-200', 'text-gray-500', 'active-step');
                circle.classList.add('bg-green-600', 'text-white');
                circle.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
            } else if (i === currentStep) {
                circle.classList.remove('bg-gray-200', 'text-gray-500', 'bg-green-600');
                circle.classList.add('active-step', 'bg-orange-600', 'text-white');
                circle.innerHTML = i;
            } else {
                circle.classList.remove('bg-green-600', 'active-step', 'bg-orange-600');
                circle.classList.add('bg-gray-200', 'text-gray-500');
                circle.innerHTML = i;
            }
        }
        
        if (i < totalSteps) {
            const line = document.querySelector(`.step-${i}-line`);
            if (line) {
                if (i < currentStep) {
                    line.classList.remove('bg-gray-200');
                    line.classList.add('bg-green-600');
                } else {
                    line.classList.remove('bg-green-600');
                    line.classList.add('bg-gray-200');
                }
            }
        }
    }
    
    for (let i = 1; i <= totalSteps; i++) {
        const stepDiv = document.getElementById(`step${i}`);
        if (stepDiv) {
            stepDiv.style.display = i === currentStep ? 'block' : 'none';
        }
    }
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    if (prevBtn) {
        prevBtn.style.display = currentStep > 1 && currentStep < totalSteps ? 'block' : 'none';
    }
    
    if (nextBtn) {
        nextBtn.style.display = currentStep === 1 ? 'block' : 'none';
    }
    
    if (submitBtn) {
        submitBtn.style.display = currentStep === 2 ? 'block' : 'none';
    }
    
    if (currentStep === 2) {
        populateReviewContent();
    }
}

function changeStep(direction) {
    if (direction === 1 && currentStep < totalSteps) {
        if (!validateCurrentStep()) {
            return;
        }
        currentStep++;
        updateProgressBar();
    } else if (direction === -1 && currentStep > 1) {
        currentStep--;
        updateProgressBar();
    }
}

function validateCurrentStep() {
    if (currentStep === 1) {
        const form = document.getElementById('fsicRequestForm');
        if (!form) {
            return false;
        }

        const requiredFields = ['business_name', 'owner_name', 'building_address', 'province_id', 'city_id', 'barangay_id'];
        let isValid = true;
        let missingFields = [];
        
        requiredFields.forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            const value = typeof input?.value === 'string' ? input.value.trim() : '';

            if (input && !value) {
                isValid = false;
                missingFields.push(field.replace('_', ' '));
                input.classList.add('border-red-500');
            } else if (input) {
                input.classList.remove('border-red-500');
            }
        });
        
        if (!isValid) {
            alert(`Please fill in: ${missingFields.join(', ')}`);
            return false;
        }
    }
    return true;
}

function populateReviewContent() {
    const form = document.getElementById('fsicRequestForm');
    if (!form) {
        return;
    }

    const getValue = (name) => {
        const input = form.querySelector(`[name="${name}"]`);
        return input?.value || 'Not provided';
    };
    
    const getSelectText = (selectId) => {
        const select = document.getElementById(selectId);
        return select?.options[select.selectedIndex]?.text || 'Not selected';
    };
    
    const reviewHtml = `
        <div class="border rounded-lg p-3">
            <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-building text-orange-600"></i> Business Information
            </h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <p><span class="text-gray-500">Business Name:</span></p>
                <p class="font-medium">${getValue('business_name')}</p>
                <p><span class="text-gray-500">Owner Name:</span></p>
                <p class="font-medium">${getValue('owner_name')}</p>
                <p><span class="text-gray-500">Business Type:</span></p>
                <p class="font-medium">${getValue('business_type') || 'Not specified'}</p>
            </div>
        </div>
        
        <div class="border rounded-lg p-3">
            <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-location-dot text-orange-600"></i> Location Details
            </h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <p><span class="text-gray-500">Address:</span></p>
                <p class="font-medium">${getValue('building_address')}</p>
                <p><span class="text-gray-500">Province:</span></p>
                <p class="font-medium">${getSelectText('provinceSelect')}</p>
                <p><span class="text-gray-500">City/Municipality:</span></p>
                <p class="font-medium">${getSelectText('citySelect')}</p>
                <p><span class="text-gray-500">Barangay:</span></p>
                <p class="font-medium">${getSelectText('barangaySelect')}</p>
            </div>
        </div>
        
        <div class="border rounded-lg p-3">
            <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-orange-600"></i> Registration Numbers
            </h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <p><span class="text-gray-500">DTI/SEC No.:</span></p>
                <p class="font-medium">${getValue('dti_sec_registration_no') || 'Not provided'}</p>
                <p><span class="text-gray-500">Mayor's Permit No.:</span></p>
                <p class="font-medium">${getValue('mayors_permit_no') || 'Not provided'}</p>
                <p><span class="text-gray-500">TIN No.:</span></p>
                <p class="font-medium">${getValue('tin_number') || 'Not provided'}</p>
            </div>
        </div>
    `;
    
    document.getElementById('reviewContent').innerHTML = reviewHtml;
}

function startCountdown() {
    let seconds = 3;
    const countdownElement = document.getElementById('countdown');
    
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    countdownInterval = setInterval(() => {
        seconds--;
        if (countdownElement) {
            countdownElement.textContent = seconds;
        }
        
        if (seconds <= 0) {
            clearInterval(countdownInterval);
            closeNewFsicModal();
        }
    }, 1000);
}

/* =========================
   LOCATION DROPDOWN FUNCTIONS
========================= */
async function loadCities() {
    const provinceId = document.getElementById('provinceSelect').value;
    const citySelect = document.getElementById('citySelect');
    const barangaySelect = document.getElementById('barangaySelect');
    
    if (!provinceId) {
        citySelect.innerHTML = '<option value="">Select City First</option>';
        citySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        return;
    }
    
    try {
        const response = await fetch(`/api/cities/${provinceId}`);
        const cities = await response.json();
        
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        cities.forEach(city => {
            citySelect.innerHTML += `<option value="${city.city_id}">${city.city_name}</option>`;
        });
        citySelect.disabled = false;
        
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        
    } catch (error) {
        console.error('Error loading cities:', error);
        citySelect.innerHTML = '<option value="">Error loading cities</option>';
    }
}

async function loadBarangays() {
    const cityId = document.getElementById('citySelect').value;
    const barangaySelect = document.getElementById('barangaySelect');
    
    if (!cityId) {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        return;
    }
    
    try {
        const response = await fetch(`/api/barangays/${cityId}`);
        const barangays = await response.json();
        
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangays.forEach(barangay => {
            barangaySelect.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
        });
        barangaySelect.disabled = false;
        
    } catch (error) {
        console.error('Error loading barangays:', error);
        barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
    }
}

/* =========================
   MODAL FUNCTIONS
========================= */
function openNewFsicModal() {
    const modal = document.getElementById('newFsicModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    currentStep = 1;
    updateProgressBar();
    resetFsicForm();
}

function closeNewFsicModal() {
    const modal = document.getElementById('newFsicModal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
    resetFsicForm();
    
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
}

function resetFsicForm() {
    const form = document.getElementById('fsicRequestForm');
    if (form) {
        form.reset();
    }
    document.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
    });
    
    const citySelect = document.getElementById('citySelect');
    const barangaySelect = document.getElementById('barangaySelect');
    if (citySelect) {
        citySelect.innerHTML = '<option value="">Select City First</option>';
        citySelect.disabled = true;
    }
    if (barangaySelect) {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
    }
}

/* =========================
   SUBMIT FSIC REQUEST
========================= */
window.submitFsicRequest = async function(event) {
    if (event) {
        event.preventDefault();
    }
    
    const form = document.getElementById('fsicRequestForm');
    if (!form) {
        console.error('Form not found!');
        alert('Form not found!');
        return;
    }
    
    // Get form values
    const business_name = form.querySelector('[name="business_name"]')?.value;
    const owner_name = form.querySelector('[name="owner_name"]')?.value;
    const business_type = form.querySelector('[name="business_type"]')?.value;
    const building_address = form.querySelector('[name="building_address"]')?.value;
    const dti_sec_registration_no = form.querySelector('[name="dti_sec_registration_no"]')?.value;
    const mayors_permit_no = form.querySelector('[name="mayors_permit_no"]')?.value;
    const tin_number = form.querySelector('[name="tin_number"]')?.value;
    
    // Get location dropdown values
    const provinceSelect = document.getElementById('provinceSelect');
    const citySelect = document.getElementById('citySelect');
    const barangaySelect = document.getElementById('barangaySelect');
    
    const province_id = provinceSelect?.value;
    const city_id = citySelect?.value;
    const barangay_id = barangaySelect?.value;
    
    // Get the text names for display
    const province_name = provinceSelect?.options[provinceSelect.selectedIndex]?.text;
    const city_name = citySelect?.options[citySelect.selectedIndex]?.text;
    const barangay_name = barangaySelect?.options[barangaySelect.selectedIndex]?.text;
    
    // Validate required fields
    if (!business_name) {
        alert('Please enter Business Name');
        return;
    }
    if (!owner_name) {
        alert('Please enter Owner Name');
        return;
    }
    if (!building_address) {
        alert('Please enter Building Address');
        return;
    }
    if (!province_id) {
        alert('Please select Province');
        return;
    }
    if (!city_id) {
        alert('Please select City');
        return;
    }
    if (!barangay_id) {
        alert('Please select Barangay');
        return;
    }
    
    // Generate request code
    const requestCode = 'FSIC-' + new Date().getFullYear() + '-' + 
        Math.random().toString(36).substring(2, 8).toUpperCase();
    
    // Prepare data matching your database schema
    const postData = {
        user_id: {{ Auth::user()->user_id ?? 'null' }},
        request_code: requestCode,
        date_requested: new Date().toISOString().split('T')[0],
        business_name: business_name,
        owner_name: owner_name,
        business_type: business_type || null,
        building_address: building_address,
        barangay: barangay_name,
        city: city_name,
        province: province_name,
        dti_sec_registration_no: dti_sec_registration_no || null,
        mayors_permit_no: mayors_permit_no || null,
        tin_number: tin_number || null,
        status: 'pending'
    };
    
    console.log('Submitting data:', postData);
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn?.innerHTML || 'Submit';
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Submitting...';
        submitBtn.disabled = true;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/citizen/api/fsic/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(postData)
        });
        
        const result = await response.json();
        console.log('Response:', result);
        
        if (response.ok && result.success) {
            upsertFsicRow(result.data, 'prepend');
            currentStep = 3;
            updateProgressBar();
            window.showCitizenToast?.('FSIC request submitted successfully.');
            startCountdown();
        } else {
            let errorMessage = result.message || 'Failed to submit request.';
            if (result.errors) {
                errorMessage += '\n\n' + Object.values(result.errors).flat().join('\n');
            }
            window.showCitizenToast?.(errorMessage, true, 4200);
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        window.showCitizenToast?.('An error occurred: ' + error.message, true, 4200);
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }
};

/* =========================
   VIEW FSIC DETAILS
========================= */
// window.viewFsicDetails = async function(id) {
//     const modal = document.getElementById('fsicDetailsModal');
//     modal.classList.add('show');
//     document.body.style.overflow = 'hidden';
    
//     const contentDiv = document.getElementById('fsicDetailsContent');
//     contentDiv.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-orange-600"></i><p class="mt-2">Loading details...</p></div>';
    
//     try {
//         const response = await fetch(`/citizen/api/fsic/${id}`);
//         const data = await response.json();
        
//         const statusColors = {
//             'pending': 'bg-yellow-100 text-yellow-700',
//             'for_inspection': 'bg-blue-100 text-blue-700',
//             'inspected': 'bg-purple-100 text-purple-700',
//             'approved': 'bg-green-100 text-green-700',
//             'rejected': 'bg-red-100 text-red-700'
//         };
        
//         const content = `
//             <div class="grid grid-cols-2 gap-3 mb-4">
//                 <div class="bg-gray-50 p-3 rounded-lg">
//                     <p class="text-xs text-gray-500">Request Code</p>
//                     <p class="font-bold text-sm">${data.request_code}</p>
//                 </div>
//                 <div class="bg-gray-50 p-3 rounded-lg">
//                     <p class="text-xs text-gray-500">Date Requested</p>
//                     <p class="font-bold text-sm">${new Date(data.date_requested).toLocaleDateString()}</p>
//                 </div>
//                 <div class="bg-gray-50 p-3 rounded-lg">
//                     <p class="text-xs text-gray-500">Status</p>
//                     <p><span class="px-2 py-1 text-xs rounded-full ${statusColors[data.status]}">${data.status?.replace('_', ' ').toUpperCase()}</span></p>
//                 </div>
//             </div>
            
//             <div class="border-t pt-3">
//                 <h4 class="font-semibold text-gray-800 mb-2">Business Information</h4>
//                 <div class="grid grid-cols-2 gap-2 text-sm">
//                     <p><span class="text-gray-500">Business Name:</span> ${data.business_name}</p>
//                     <p><span class="text-gray-500">Owner:</span> ${data.owner_name}</p>
//                     <p class="col-span-2"><span class="text-gray-500">Address:</span> ${data.building_address}, ${data.barangay}, ${data.city}, ${data.province}</p>
//                 </div>
//             </div>
//         `;
        
//         contentDiv.innerHTML = content;
        
//     } catch (error) {
//         console.error('Error:', error);
//         contentDiv.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load details. Please try again.</div>';
//     }
// };
/* =========================
   VIEW FSIC DETAILS - FIXED VERSION
========================= */
window.viewFsicDetails = async function(id) {
    console.log('Viewing details for ID:', id);
    
    const modal = document.getElementById('fsicDetailsModal');
    const contentDiv = document.getElementById('fsicDetailsContent');
    
    if (!modal) {
        console.error('Modal not found!');
        alert('Modal not found');
        return;
    }
    
    // Show modal and loading state
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    contentDiv.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-2xl text-orange-600"></i><p class="mt-2">Loading details...</p></div>';
    
    try {
        const response = await fetch(`/citizen/api/fsic/${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Data received:', data);
        
        const statusColors = {
            'pending': 'bg-yellow-100 text-yellow-700',
            'for_inspection': 'bg-blue-100 text-blue-700',
            'inspected': 'bg-purple-100 text-purple-700',
            'approved': 'bg-green-100 text-green-700',
            'rejected': 'bg-red-100 text-red-700'
        };
        
        const statusText = data.status?.replace('_', ' ').toUpperCase() || 'PENDING';
        const statusColor = statusColors[data.status] || 'bg-gray-100 text-gray-700';
        const canClaim = data.status === 'approved' && data.approval && !data.approval.date_issued;
        const shouldShowCompletionDetails = ['inspected', 'rejected', 'approved'].includes(String(data.status || '').toLowerCase());
        const inspectionChecklist = Array.isArray(data.inspection?.checklist_items) ? data.inspection.checklist_items : [];
        const inspectionSection = shouldShowCompletionDetails && data.inspection ? `
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-check text-orange-600"></i> Inspection Details
                </h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p><span class="text-gray-500">Inspection Date:</span></p>
                    <p class="font-medium">${data.inspection.inspection_date ? new Date(data.inspection.inspection_date).toLocaleString() : 'N/A'}</p>
                    <p><span class="text-gray-500">Compliance:</span></p>
                    <p class="font-medium">${Number(data.inspection.is_compliant) === 1 ? 'Compliant' : 'For compliance / with findings'}</p>
                    <p><span class="text-gray-500">Inspector:</span></p>
                    <p class="font-medium">${escapeHtml(data.inspection.inspector_name || 'N/A')}</p>
                    <p><span class="text-gray-500">Inspector Rank:</span></p>
                    <p class="font-medium">${escapeHtml(data.inspection.inspector_rank || 'N/A')}</p>
                    <p><span class="text-gray-500">Inspection Remarks:</span></p>
                    <p class="font-medium">${escapeHtml(data.inspection.inspection_remarks || 'No remarks provided')}</p>
                    <p><span class="text-gray-500">Checklist Remarks:</span></p>
                    <p class="font-medium">${escapeHtml(data.inspection.checklist_remarks || 'No remarks provided')}</p>
                </div>
                ${inspectionChecklist.length ? `
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Inspection Checklist</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            ${inspectionChecklist.map((item) => `
                                <div class="rounded-lg border px-3 py-2 text-sm flex items-center justify-between gap-3 ${item.passed === true ? 'bg-green-50 border-green-200' : item.passed === false ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200'}">
                                    <span class="text-gray-700">${escapeHtml(item.label || item.key || 'Checklist item')}</span>
                                    <span class="font-semibold ${item.passed === true ? 'text-green-700' : item.passed === false ? 'text-red-700' : 'text-gray-500'}">
                                        ${item.passed === true ? 'Passed' : item.passed === false ? 'Failed' : 'Pending'}
                                    </span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        ` : '';
        const approvalSection = data.approval ? `
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-certificate text-orange-600"></i> Approval Details
                </h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <p><span class="text-gray-500">Approval Date:</span></p>
                    <p class="font-medium">${data.approval.approval_date ? new Date(data.approval.approval_date).toLocaleString() : 'N/A'}</p>
                    <p><span class="text-gray-500">Approved By:</span></p>
                    <p class="font-medium">${escapeHtml(data.approval.approver_name || 'N/A')}</p>
                    <p><span class="text-gray-500">Approver Rank:</span></p>
                    <p class="font-medium">${escapeHtml(data.approval.approver_rank || 'N/A')}</p>
                    <p><span class="text-gray-500">Approver Role:</span></p>
                    <p class="font-medium">${escapeHtml(data.approval.approver_role || 'N/A')}</p>
                    <p><span class="text-gray-500">Station:</span></p>
                    <p class="font-medium">${escapeHtml(data.approval.station_name || 'N/A')}</p>
                    <p><span class="text-gray-500">FSIC Number:</span></p>
                    <p class="font-medium">${escapeHtml(data.approval.fsic_number || 'Pending')}</p>
                    <p><span class="text-gray-500">Date Issued:</span></p>
                    <p class="font-medium">${data.approval.date_issued ? new Date(data.approval.date_issued).toLocaleString() : 'Not yet claimed'}</p>
                    <p><span class="text-gray-500">Valid Until:</span></p>
                    <p class="font-medium">${data.approval.valid_until ? new Date(data.approval.valid_until).toLocaleDateString() : 'Will be set on claim'}</p>
                    <p><span class="text-gray-500">Approval Remarks:</span></p>
                    <p class="font-medium">${escapeHtml(data.approval.approval_remarks) || 'No remarks provided'}</p>
                </div>
                ${canClaim ? `
                    <div class="mt-4">
                        <button type="button" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm" onclick="claimFsicRequest(${id}, true)">
                            <i class="fa-solid fa-hand-holding mr-1"></i> Claim Certificate
                        </button>
                    </div>
                ` : ''}
            </div>
        ` : '';
        
        const content = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Request Code</p>
                        <p class="font-bold text-sm">${escapeHtml(data.request_code || 'N/A')}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Date Requested</p>
                        <p class="font-bold text-sm">${data.date_requested ? new Date(data.date_requested).toLocaleDateString() : 'N/A'}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Status</p>
                        <p><span class="px-2 py-1 text-xs rounded-full ${statusColor}">${statusText}</span></p>
                    </div>
                </div>
                
                <div class="border-t pt-3">
                    <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-building text-orange-600"></i> Business Information
                    </h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p><span class="text-gray-500">Business Name:</span></p>
                        <p class="font-medium">${escapeHtml(data.business_name || 'N/A')}</p>
                        <p><span class="text-gray-500">Owner:</span></p>
                        <p class="font-medium">${escapeHtml(data.owner_name || 'N/A')}</p>
                        <p><span class="text-gray-500">Business Type:</span></p>
                        <p class="font-medium">${escapeHtml(data.business_type) || 'Not specified'}</p>
                    </div>
                </div>
                
                <div class="border-t pt-3">
                    <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-orange-600"></i> Location Details
                    </h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p><span class="text-gray-500">Address:</span></p>
                        <p class="font-medium">${escapeHtml(data.building_address || 'N/A')}</p>
                        <p><span class="text-gray-500">Barangay:</span></p>
                        <p class="font-medium">${escapeHtml(data.barangay || 'N/A')}</p>
                        <p><span class="text-gray-500">City:</span></p>
                        <p class="font-medium">${escapeHtml(data.city || 'N/A')}</p>
                        <p><span class="text-gray-500">Province:</span></p>
                        <p class="font-medium">${escapeHtml(data.province || 'N/A')}</p>
                    </div>
                </div>
                
                <div class="border-t pt-3">
                    <h4 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-orange-600"></i> Registration Numbers
                    </h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p><span class="text-gray-500">DTI/SEC No.:</span></p>
                        <p class="font-medium">${escapeHtml(data.dti_sec_registration_no) || 'Not provided'}</p>
                        <p><span class="text-gray-500">Mayor's Permit No.:</span></p>
                        <p class="font-medium">${escapeHtml(data.mayors_permit_no) || 'Not provided'}</p>
                        <p><span class="text-gray-500">TIN No.:</span></p>
                        <p class="font-medium">${escapeHtml(data.tin_number) || 'Not provided'}</p>
                    </div>
                </div>
                ${approvalSection}
                ${inspectionSection}
            </div>
        `;
        
        contentDiv.innerHTML = content;
        
    } catch (error) {
        console.error('Error:', error);
        contentDiv.innerHTML = `<div class="text-center py-8 text-red-500">Failed to load details: ${error.message}</div>`;
    }
};

window.claimFsicRequest = async function(id, keepModalOpen = false) {
    if (!confirm('Claim this FSIC certificate now? The date issued will be set to today and validity will run for one year from today.')) {
        return;
    }

    try {
        const response = await fetch(`/citizen/api/fsic/${id}/claim`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!response.ok || result.success === false) {
            throw new Error(result.message || 'Failed to claim certificate.');
        }

        upsertFsicRow(result.data);
        window.showCitizenToast?.(result.message || 'FSIC certificate claimed successfully.');

        if (keepModalOpen) {
            await window.viewFsicDetails(id);
        }
    } catch (error) {
        console.error('Claim FSIC error:', error);
        window.showCitizenToast?.(error.message || 'Failed to claim certificate.', true, 4200);
    }
};
window.closeFsicDetailsModal = function() {
    const modal = document.getElementById('fsicDetailsModal');
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
};
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Make functions globally accessible
window.openNewFsicModal = openNewFsicModal;
window.closeNewFsicModal = closeNewFsicModal;
window.changeStep = changeStep;
window.loadCities = loadCities;
window.loadBarangays = loadBarangays;
window.editFsicRequest = editFsicRequest;
window.closeEditFsicModal = closeEditFsicModal;
window.cancelFsicRequest = cancelFsicRequest;
window.closeCancelConfirmModal = closeCancelConfirmModal;

document.addEventListener('DOMContentLoaded', function() {
    updateProgressBar();
    
    const form = document.getElementById('fsicRequestForm');
    if (form) {
        form.removeEventListener('submit', window.submitFsicRequest);
        form.addEventListener('submit', window.submitFsicRequest);
    }
    
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(modal => {
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            });
        }
    });
    // Add this helper function at the top of your script

    
});
</script>
