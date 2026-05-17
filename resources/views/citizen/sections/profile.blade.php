@php
    $profileUser = $citizenUser ?? auth()->user();
    $profileAddress = $profileUser?->address;
    $profileBarangay = $profileAddress?->barangay;
    $profileCity = $profileBarangay?->city;
    $profileName = trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? ''));
    $profileName = $profileName !== '' ? $profileName : ($profileUser->name ?? 'Citizen');
    $profileInitials = strtoupper(substr($profileUser->first_name ?? 'C', 0, 1) . substr($profileUser->last_name ?? ($profileUser->first_name ?? 'P'), 0, 1));
    $selectedCityId = old('city_id', $profileCity?->city_id);
    $barangayOptions = collect();

    if ($selectedCityId) {
        $barangayOptions = optional($cities->firstWhere('city_id', (int) $selectedCityId))->barangays ?? collect();
    }
@endphp

<div id="section-profile" class="content-section">
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-6">My Profile</h1>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Please fix the highlighted profile fields.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <div id="citizenProfileCardInitials" class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-3xl font-bold mx-auto mb-3">
                    {{ $profileInitials }}
                </div>
                <h2 id="citizenProfileCardName" class="text-xl font-bold text-gray-800">{{ $profileName }}</h2>
                <p id="citizenProfileCardEmail" class="text-sm text-gray-500 mb-2">{{ $profileUser->email }}</p>
                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">Verified Citizen</span>

                <hr class="my-4">

                <div class="text-left space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Member Since</p>
                        <p class="text-sm font-medium">{{ $profileUser->created_at?->format('F j, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Last Login</p>
                        <p class="text-sm font-medium">{{ $profileUser->last_login_at ?? 'Today' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Account Type</p>
                        <p id="citizenProfileRole" class="text-sm font-medium">{{ $profileUser->role->role ?? 'Citizen' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Current Address</p>
                        <p id="citizenProfileCurrentAddress" class="text-sm font-medium">
                            {{ $profileAddress?->street ? $profileAddress->street . ', ' : '' }}
                            {{ $profileBarangay?->barangay_name ?? 'No barangay yet' }},
                            {{ $profileCity?->city_name ?? 'No city yet' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <form id="citizenProfileForm" method="POST" action="{{ route('citizen.profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="font-bold text-gray-800 mb-4">Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="citizen_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input id="citizen_first_name" name="first_name" type="text" value="{{ old('first_name', $profileUser->first_name) }}" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="citizen_middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                            <input id="citizen_middle_name" name="middle_name" type="text" value="{{ old('middle_name', $profileUser->middle_name) }}" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="citizen_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input id="citizen_last_name" name="last_name" type="text" value="{{ old('last_name', $profileUser->last_name) }}" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label for="citizen_contact_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input id="citizen_contact_number" name="contact_number" type="text" value="{{ old('contact_number', $profileUser->contact_number) }}" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div class="md:col-span-2">
                            <label for="citizen_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input id="citizen_email" name="email" type="email" value="{{ old('email', $profileUser->email) }}" class="w-full border rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="font-bold text-gray-800 mb-4">Address Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="citizen_city_id" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <select id="citizen_city_id" name="city_id" class="w-full border rounded-lg px-3 py-2">
                                <option value="">Select City</option>
                                @foreach ($cities as $cityOption)
                                    <option value="{{ $cityOption->city_id }}" @selected((string) old('city_id', $profileCity?->city_id) === (string) $cityOption->city_id)>
                                        {{ $cityOption->city_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="citizen_barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                            <select id="citizen_barangay_id" name="barangay_id" class="w-full border rounded-lg px-3 py-2">
                                <option value="">Select Barangay</option>
                                @foreach ($barangayOptions as $barangayOption)
                                    <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $profileBarangay?->barangay_id) === (string) $barangayOption->barangay_id)>
                                        {{ $barangayOption->barangay_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="citizen_street" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                            <input id="citizen_street" name="street" type="text" value="{{ old('street', $profileAddress?->street) }}" class="w-full border rounded-lg px-3 py-2" placeholder="Enter street address">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button id="citizenProfileSaveBtn" type="submit" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="citizenProfileLoadingOverlay" class="fixed inset-0 bg-slate-950/35 backdrop-blur-sm hidden items-center justify-center z-[120]">
    <div class="bg-white rounded-2xl shadow-xl px-6 py-5 flex items-center gap-3 text-slate-700">
        <span class="inline-fa-icon icon-spin text-orange-600" style="width: 1.25rem; height: 1.25rem;">
            <svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"></path></svg>
        </span>
        <div>
            <p class="font-semibold">Saving profile</p>
            <p class="text-sm text-slate-500">Updating your information...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('citizenProfileForm');
    const citySelect = document.getElementById('citizen_city_id');
    const barangaySelect = document.getElementById('citizen_barangay_id');
    const selectedBarangayId = @json((string) old('barangay_id', $profileBarangay?->barangay_id));
    const loadingOverlay = document.getElementById('citizenProfileLoadingOverlay');
    const saveBtn = document.getElementById('citizenProfileSaveBtn');
    const toast = document.getElementById('toast');

    if (!profileForm || !citySelect || !barangaySelect) {
        return;
    }

    function showProfileToast(message, isError = false) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.style.background = isError ? 'linear-gradient(135deg, #b91c1c 0%, #dc2626 100%)' : '';
        toast.classList.add('show');

        window.clearTimeout(window.citizenProfileToastTimer);
        window.citizenProfileToastTimer = window.setTimeout(() => {
            toast.classList.remove('show');
            if (!isError) {
                toast.style.background = '';
            }
        }, 2800);
    }

    function clearProfileErrors() {
        profileForm.querySelectorAll('.border-red-400').forEach((field) => {
            field.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
        });

        profileForm.querySelectorAll('.citizen-profile-error').forEach((node) => node.remove());
    }

    function applyFieldErrors(errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            const input = profileForm.querySelector(`[name="${field}"]`);
            if (!input) {
                return;
            }

            input.classList.add('border-red-400', 'ring-2', 'ring-red-100');
            const error = document.createElement('p');
            error.className = 'citizen-profile-error mt-1 text-sm text-red-600';
            error.textContent = Array.isArray(messages) ? messages[0] : messages;
            input.insertAdjacentElement('afterend', error);
        });
    }

    function updateCitizenProfileUI(user) {
        const fullName = user.full_name || [user.first_name, user.last_name].filter(Boolean).join(' ').trim();
        const currentAddress = `${user.street ? `${user.street}, ` : ''}${user.barangay || 'No barangay yet'}, ${user.city || 'No city yet'}`;

        const map = {
            citizenProfileCardInitials: user.initials,
            citizenProfileCardName: fullName,
            citizenProfileCardEmail: user.email,
            citizenProfileRole: user.role,
            citizenProfileCurrentAddress: currentAddress,
        };

        Object.entries(map).forEach(([id, value]) => {
            const node = document.getElementById(id);
            if (node) {
                node.textContent = value ?? '';
            }
        });

        document.querySelectorAll('#userDropdownBtn .w-8.h-8, .px-4.py-4.border-b .w-12.h-12').forEach((node) => {
            node.textContent = user.initials;
        });

        document.querySelectorAll('#userDropdownBtn + span, #userDropdownMenu .user-dropdown-header p.text-sm.font-bold.text-gray-800').forEach((node) => {
            node.textContent = fullName;
        });

        const topbarName = document.querySelector('#userDropdownBtn span.hidden.md\\:inline');
        if (topbarName) {
            topbarName.textContent = fullName;
        }

        const sidebarName = document.querySelector('.px-4.py-4.border-b p.font-semibold.text-gray-800');
        if (sidebarName) {
            sidebarName.textContent = fullName;
        }

        const sidebarEmail = document.querySelector('.px-4.py-4.border-b p.text-xs.text-gray-500');
        if (sidebarEmail) {
            sidebarEmail.innerHTML = `<span class="inline-fa-icon" style="margin-right:0.25rem;"><svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2" fill="none"></rect><path d="M5 8l7 5 7-5"></path></svg></span>${user.email}`;
        }
    }

    async function loadCitizenBarangays(cityId, preferredBarangayId = '') {
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
            console.error('Error loading citizen barangays:', error);
        }
    }

    citySelect.addEventListener('change', function () {
        loadCitizenBarangays(this.value);
    });

    if (citySelect.value) {
        loadCitizenBarangays(citySelect.value, selectedBarangayId);
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

            updateCitizenProfileUI(payload.user || {});
            showProfileToast(payload.message || 'Profile updated successfully.');
        } catch (error) {
            console.error('Citizen profile update failed:', error);
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
