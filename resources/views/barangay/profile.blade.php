@extends('barangay.layouts.app')

@php
    $profileUser = $user ?? auth()->user();
    $profileAddress = $profileUser?->address;
    $profileBarangay = $profileAddress?->barangay;
    $profileCity = $profileBarangay?->city;
    $profileName = trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? ''));
    $profileName = $profileName !== '' ? $profileName : ($profileUser->name ?? 'Barangay Officer');
    $profileInitials = strtoupper(substr($profileUser->first_name ?? 'B', 0, 1) . substr($profileUser->last_name ?? ($profileUser->first_name ?? 'D'), 0, 1));
    $selectedCityId = old('city_id', $profileCity?->city_id);
    $barangayOptions = collect();

    if ($selectedCityId) {
        $barangayOptions = optional($cities->firstWhere('city_id', (int) $selectedCityId))->barangays ?? collect();
    }
@endphp

@section('page_title', 'My Profile')
@section('page_subtitle', 'Barangay account details and address information')
@section('hero_title', 'Keep your barangay profile current.')
@section('hero_copy', 'Update your contact information and address so barangay coordination records stay accurate across the portal.')
@section('active_nav', 'profile')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-regular fa-user"></i> Barangay Account</span>
    <span class="hero-badge"><i class="fa-regular fa-building"></i> {{ $barangay?->barangay_name ?? 'Barangay Desk' }}</span>
@endsection

@section('top_bar_actions')
    <a href="{{ route('barangay.reports') }}" class="portal-report-fire-btn" data-barangay-quick-report data-report-url="{{ route('barangay.reports.quick') }}">
        <i class="fa-solid fa-fire-flame-curved"></i>
        <span>Report Fire</span>
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="panel-card mb-6 rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="panel-card mb-6 rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Please fix the highlighted profile fields.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel-grid two">
        <section class="panel-card">
            <div class="flex flex-col items-center text-center">
                <div id="barangayProfileCardInitials" class="w-24 h-24 rounded-full bg-orange-100 text-orange-600 text-3xl font-bold flex items-center justify-center mb-4">
                    {{ $profileInitials }}
                </div>
                <h2 id="barangayProfileCardName" class="text-2xl font-black text-slate-900">{{ $profileName }}</h2>
                <p id="barangayProfileCardEmail" class="text-sm text-slate-500 mt-1">{{ $profileUser->email }}</p>
                <span class="tag medium mt-4">{{ $profileUser->role->role ?? 'Barangay' }}</span>
            </div>

            <div class="list-stack mt-8">
                <div class="list-item">
                    <div class="tiny-label">Member Since</div>
                    <strong>{{ $profileUser->created_at?->format('F j, Y') }}</strong>
                </div>
                <div class="list-item">
                    <div class="tiny-label">Last Login</div>
                    <strong>{{ $profileUser->last_login_at ?? 'Today' }}</strong>
                </div>
                <div class="list-item">
                    <div class="tiny-label">Current Address</div>
                    <strong id="barangayProfileCurrentAddress">
                        {{ $profileAddress?->street ? $profileAddress->street . ', ' : '' }}{{ $profileBarangay?->barangay_name ?? 'No barangay yet' }}, {{ $profileCity?->city_name ?? 'No city yet' }}
                    </strong>
                </div>
                <div class="list-item">
                    <div class="tiny-label">Contact Number</div>
                    <strong id="barangayProfileContact">{{ $profileUser->contact_number ?: 'No contact number yet' }}</strong>
                </div>
            </div>
        </section>

        <section class="panel-card">
            <form id="barangayProfileForm" method="POST" action="{{ route('barangay.profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <p class="panel-eyebrow">Personal Information</p>
                    <h2 class="panel-title">Barangay Profile Settings</h2>
                    <p class="panel-copy">These details are used throughout the barangay portal and shared records.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="barangay_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input id="barangay_first_name" name="first_name" type="text" value="{{ old('first_name', $profileUser->first_name) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="barangay_middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input id="barangay_middle_name" name="middle_name" type="text" value="{{ old('middle_name', $profileUser->middle_name) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="barangay_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input id="barangay_last_name" name="last_name" type="text" value="{{ old('last_name', $profileUser->last_name) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="barangay_contact_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input id="barangay_contact_number" name="contact_number" type="text" value="{{ old('contact_number', $profileUser->contact_number) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label for="barangay_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input id="barangay_email" name="email" type="email" value="{{ old('email', $profileUser->email) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>

                <div>
                    <p class="panel-eyebrow">Address Information</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <div>
                            <label for="barangay_city_id" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <select id="barangay_city_id" name="city_id" class="w-full border rounded-lg px-3 py-2">
                                <option value="">Select City</option>
                                @foreach ($cities as $cityOption)
                                    <option value="{{ $cityOption->city_id }}" @selected((string) old('city_id', $profileCity?->city_id) === (string) $cityOption->city_id)>
                                        {{ $cityOption->city_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="barangay_barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                            <select id="barangay_barangay_id" name="barangay_id" class="w-full border rounded-lg px-3 py-2">
                                <option value="">Select Barangay</option>
                                @foreach ($barangayOptions as $barangayOption)
                                    <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $profileBarangay?->barangay_id) === (string) $barangayOption->barangay_id)>
                                        {{ $barangayOption->barangay_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="barangay_street" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                            <input id="barangay_street" name="street" type="text" value="{{ old('street', $profileAddress?->street) }}" class="w-full border rounded-lg px-3 py-2" placeholder="Enter street address">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button id="barangayProfileSaveBtn" type="submit" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div id="barangayProfileToast" class="fixed right-6 bottom-6 bg-green-600 text-white px-4 py-3 rounded-2xl shadow-xl opacity-0 pointer-events-none translate-y-2 transition-all duration-200 z-[6000] text-sm font-semibold"></div>

    <div id="barangayProfileLoadingOverlay" class="fixed inset-0 bg-slate-950/35 backdrop-blur-sm hidden items-center justify-center z-[120]">
        <div class="bg-white rounded-2xl shadow-xl px-6 py-5 flex items-center gap-3 text-slate-700">
            <span class="text-orange-600"><i class="fa-solid fa-spinner fa-spin"></i></span>
            <div>
                <p class="font-semibold">Saving profile</p>
                <p class="text-sm text-slate-500">Updating your information...</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const profileForm = document.getElementById('barangayProfileForm');
        const citySelect = document.getElementById('barangay_city_id');
        const barangaySelect = document.getElementById('barangay_barangay_id');
        const selectedBarangayId = @json((string) old('barangay_id', $profileBarangay?->barangay_id));
        const loadingOverlay = document.getElementById('barangayProfileLoadingOverlay');
        const saveBtn = document.getElementById('barangayProfileSaveBtn');
        const toast = document.getElementById('barangayProfileToast');

        if (!profileForm || !citySelect || !barangaySelect) {
            return;
        }

        function showProfileToast(message, isError = false) {
            if (!toast) {
                return;
            }

            toast.textContent = message;
            toast.classList.remove('opacity-0', 'translate-y-2');
            toast.classList.add('opacity-100', 'translate-y-0');
            toast.style.background = isError ? 'linear-gradient(135deg, #b91c1c 0%, #dc2626 100%)' : 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)';

            window.clearTimeout(window.barangayProfileToastTimer);
            window.barangayProfileToastTimer = window.setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-2');
            }, 2800);
        }

        function clearProfileErrors() {
            profileForm.querySelectorAll('.border-red-400').forEach((field) => {
                field.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
            });

            profileForm.querySelectorAll('.barangay-profile-error').forEach((node) => node.remove());
        }

        function applyFieldErrors(errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const input = profileForm.querySelector(`[name="${field}"]`);
                if (!input) {
                    return;
                }

                input.classList.add('border-red-400', 'ring-2', 'ring-red-100');
                const error = document.createElement('p');
                error.className = 'barangay-profile-error mt-1 text-sm text-red-600';
                error.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.insertAdjacentElement('afterend', error);
            });
        }

        function updateBarangayProfileUI(user) {
            const fullName = user.full_name || [user.first_name, user.last_name].filter(Boolean).join(' ').trim();
            const currentAddress = `${user.street ? `${user.street}, ` : ''}${user.barangay || 'No barangay yet'}, ${user.city || 'No city yet'}`;

            const textMap = {
                barangayProfileCardInitials: user.initials,
                barangayProfileCardName: fullName,
                barangayProfileCardEmail: user.email,
                barangayProfileCurrentAddress: currentAddress,
                barangayProfileContact: user.contact_number || 'No contact number yet',
            };

            Object.entries(textMap).forEach(([id, value]) => {
                const node = document.getElementById(id);
                if (node) {
                    node.textContent = value ?? '';
                }
            });
        }

        async function loadBarangays(cityId, preferredBarangayId = '') {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

            if (!cityId) {
                return;
            }

            try {
                const response = await fetch(`/api/barangays/${cityId}`);
                const barangays = await response.json();

                barangays.forEach((barangay) => {
                    const option = document.createElement('option');
                    option.value = barangay.barangay_id;
                    option.textContent = barangay.barangay_name;

                    if (preferredBarangayId && String(preferredBarangayId) === String(barangay.barangay_id)) {
                        option.selected = true;
                    }

                    barangaySelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading barangays:', error);
            }
        }

        citySelect.addEventListener('change', function () {
            loadBarangays(this.value);
        });

        if (citySelect.value) {
            loadBarangays(citySelect.value, selectedBarangayId);
        }

        profileForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearProfileErrors();

            loadingOverlay?.classList.remove('hidden');
            loadingOverlay?.classList.add('flex');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const formData = new FormData(profileForm);

                const response = await fetch(profileForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: formData,
                });

                const payload = await response.json();

                if (!response.ok) {
                    if (response.status === 422 && payload.errors) {
                        applyFieldErrors(payload.errors);
                        showProfileToast('Please check the profile fields.', true);
                        return;
                    }

                    throw new Error(payload.message || 'Unable to update profile.');
                }

                updateBarangayProfileUI(payload.user || {});
                showProfileToast(payload.message || 'Profile updated successfully.');
            } catch (error) {
                console.error('Barangay profile update failed:', error);
                showProfileToast(error.message || 'Failed to update profile.', true);
            } finally {
                loadingOverlay?.classList.add('hidden');
                loadingOverlay?.classList.remove('flex');
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
            }
        });
    });
</script>
@endpush
