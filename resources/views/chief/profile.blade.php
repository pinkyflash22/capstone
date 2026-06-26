<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PyroSense · My Profile</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
        }

        .main-content {
            margin-left: 280px;
            padding: 20px 24px 28px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        .lector-top-bar {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 158, 11, 0.08);
            height: 72px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 0 0 20px;
            border-radius: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #fef3c7;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(245, 158, 11, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s ease;
        }

        .user-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        #toast {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            color: #fff;
            padding: 0.85rem 1.1rem;
            border-radius: 0.9rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: all 0.25s ease;
            z-index: 6000;
            font-size: 0.95rem;
            font-weight: 600;
        }

        #toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="chief-legacy-layout">
@php
    $profileUser = $user ?? auth()->user();
    $profileAddress = $profileUser?->address;
    $profileBarangay = $profileAddress?->barangay;
    $profileCity = $profileBarangay?->city;
    $profileName = trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? ''));
    $profileName = $profileName !== '' ? $profileName : ($profileUser->name ?? 'Chief');
    $profileInitials = strtoupper(substr($profileUser->first_name ?? 'C', 0, 1) . substr($profileUser->last_name ?? ($profileUser->first_name ?? 'P'), 0, 1));
    $selectedCityId = old('city_id', $profileCity?->city_id);
    $barangayOptions = collect();

    if ($selectedCityId) {
        $barangayOptions = optional($cities->firstWhere('city_id', (int) $selectedCityId))->barangays ?? collect();
    }
@endphp

    @include('chief.partials.sidebar', ['active' => 'profile'])

    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">My Profile</h1>
                    <p class="text-xs text-slate-500 font-medium">Commander account details and profile settings</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle">
                        <div id="chiefProfileTopbarInitials" class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ $profileInitials }}
                        </div>
                        <span id="chiefProfileTopbarName" class="text-sm font-bold text-slate-700 hidden sm:block">{{ $profileUser->first_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                    </button>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <p id="chiefProfileDropdownName" class="text-sm font-bold text-slate-800">{{ $profileUser->first_name }} {{ $profileUser->last_name }}</p>
                            <p id="chiefProfileDropdownEmail" class="text-[10px] text-slate-500 font-medium">{{ $profileUser->email }}</p>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('station-admin.profile') }}" class="user-dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                <span>Commander Profile</span>
                            </a>
                            <a href="#" class="user-dropdown-item chief-open-logs">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>My Logs</span>
                            </a>
                            <div class="h-px bg-slate-100 my-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

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
                    <div class="bg-white rounded-3xl shadow-lg p-6 text-center border border-slate-100">
                        <div id="chiefProfileCardInitials" class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-3xl font-bold mx-auto mb-3">
                            {{ $profileInitials }}
                        </div>
                        <h2 id="chiefProfileCardName" class="text-xl font-bold text-gray-800">{{ $profileName }}</h2>
                        <p id="chiefProfileCardEmail" class="text-sm text-gray-500 mb-2">{{ $profileUser->email }}</p>
                        <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-medium">Station Commander</span>

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
                                <p id="chiefProfileRole" class="text-sm font-medium">{{ $profileUser->role->role ?? 'Chief' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Current Address</p>
                                <p id="chiefProfileCurrentAddress" class="text-sm font-medium">
                                    {{ $profileAddress?->street ? $profileAddress->street . ', ' : '' }}
                                    {{ $profileBarangay?->barangay_name ?? 'No barangay yet' }},
                                    {{ $profileCity?->city_name ?? 'No city yet' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <form id="chiefProfileForm" method="POST" action="{{ route('station-admin.profile.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white rounded-3xl shadow-lg p-6 border border-slate-100">
                            <h2 class="font-bold text-gray-800 mb-4">Personal Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="chief_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input id="chief_first_name" name="first_name" type="text" value="{{ old('first_name', $profileUser->first_name) }}" class="w-full border rounded-lg px-3 py-2">
                                </div>
                                <div>
                                    <label for="chief_middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input id="chief_middle_name" name="middle_name" type="text" value="{{ old('middle_name', $profileUser->middle_name) }}" class="w-full border rounded-lg px-3 py-2">
                                </div>
                                <div>
                                    <label for="chief_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input id="chief_last_name" name="last_name" type="text" value="{{ old('last_name', $profileUser->last_name) }}" class="w-full border rounded-lg px-3 py-2">
                                </div>
                                <div>
                                    <label for="chief_contact_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input id="chief_contact_number" name="contact_number" type="text" value="{{ old('contact_number', $profileUser->contact_number) }}" class="w-full border rounded-lg px-3 py-2">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="chief_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input id="chief_email" name="email" type="email" value="{{ old('email', $profileUser->email) }}" class="w-full border rounded-lg px-3 py-2">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl shadow-lg p-6 border border-slate-100">
                            <h2 class="font-bold text-gray-800 mb-4">Address Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="chief_city_id" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                    <select id="chief_city_id" name="city_id" class="w-full border rounded-lg px-3 py-2">
                                        <option value="">Select City</option>
                                        @foreach ($cities as $cityOption)
                                            <option value="{{ $cityOption->city_id }}" @selected((string) old('city_id', $profileCity?->city_id) === (string) $cityOption->city_id)>
                                                {{ $cityOption->city_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="chief_barangay_id" class="block text-sm font-medium text-gray-700 mb-1">Barangay</label>
                                    <select id="chief_barangay_id" name="barangay_id" class="w-full border rounded-lg px-3 py-2">
                                        <option value="">Select Barangay</option>
                                        @foreach ($barangayOptions as $barangayOption)
                                            <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $profileBarangay?->barangay_id) === (string) $barangayOption->barangay_id)>
                                                {{ $barangayOption->barangay_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="chief_street" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                                    <input id="chief_street" name="street" type="text" value="{{ old('street', $profileAddress?->street) }}" class="w-full border rounded-lg px-3 py-2" placeholder="Enter street address">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button id="chiefProfileSaveBtn" type="submit" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div id="toast"></div>

    <div id="chiefProfileLoadingOverlay" class="fixed inset-0 bg-slate-950/35 backdrop-blur-sm hidden items-center justify-center z-[120]">
        <div class="bg-white rounded-2xl shadow-xl px-6 py-5 flex items-center gap-3 text-slate-700">
            <span class="text-orange-600"><i class="fa-solid fa-spinner fa-spin"></i></span>
            <div>
                <p class="font-semibold">Saving profile</p>
                <p class="text-sm text-slate-500">Updating your information...</p>
            </div>
        </div>
    </div>

    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    sidebar?.classList.add('open');
                    backdrop?.classList.remove('hidden');
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    sidebar?.classList.remove('open');
                    backdrop.classList.add('hidden');
                });
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function (event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });

                userDropdownMenu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const profileForm = document.getElementById('chiefProfileForm');
        const citySelect = document.getElementById('chief_city_id');
        const barangaySelect = document.getElementById('chief_barangay_id');
        const selectedBarangayId = @json((string) old('barangay_id', $profileBarangay?->barangay_id));
        const loadingOverlay = document.getElementById('chiefProfileLoadingOverlay');
        const saveBtn = document.getElementById('chiefProfileSaveBtn');
        const toast = document.getElementById('toast');

        if (!profileForm || !citySelect || !barangaySelect) {
            return;
        }

        function showProfileToast(message, isError = false) {
            if (!toast) {
                return;
            }

            toast.textContent = message;
            toast.style.background = isError ? 'linear-gradient(135deg, #b91c1c 0%, #dc2626 100%)' : 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)';
            toast.classList.add('show');

            window.clearTimeout(window.chiefProfileToastTimer);
            window.chiefProfileToastTimer = window.setTimeout(() => {
                toast.classList.remove('show');
            }, 2800);
        }

        function clearProfileErrors() {
            profileForm.querySelectorAll('.border-red-400').forEach((field) => {
                field.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
            });

            profileForm.querySelectorAll('.chief-profile-error').forEach((node) => node.remove());
        }

        function applyFieldErrors(errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const input = profileForm.querySelector(`[name="${field}"]`);
                if (!input) {
                    return;
                }

                input.classList.add('border-red-400', 'ring-2', 'ring-red-100');
                const error = document.createElement('p');
                error.className = 'chief-profile-error mt-1 text-sm text-red-600';
                error.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.insertAdjacentElement('afterend', error);
            });
        }

        function updateChiefProfileUI(user) {
            const fullName = user.full_name || [user.first_name, user.last_name].filter(Boolean).join(' ').trim();
            const currentAddress = `${user.street ? `${user.street}, ` : ''}${user.barangay || 'No barangay yet'}, ${user.city || 'No city yet'}`;

            const textMap = {
                chiefProfileCardInitials: user.initials,
                chiefProfileCardName: fullName,
                chiefProfileCardEmail: user.email,
                chiefProfileRole: user.role,
                chiefProfileCurrentAddress: currentAddress,
                chiefProfileTopbarInitials: user.initials,
                chiefProfileTopbarName: user.first_name || fullName,
                chiefProfileDropdownName: fullName,
                chiefProfileDropdownEmail: user.email,
            };

            Object.entries(textMap).forEach(([id, value]) => {
                const node = document.getElementById(id);
                if (node) {
                    node.textContent = value ?? '';
                }
            });
        }

        async function loadChiefBarangays(cityId, preferredBarangayId = '') {
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
                console.error('Error loading chief barangays:', error);
            }
        }

        citySelect.addEventListener('change', function () {
            loadChiefBarangays(this.value);
        });

        if (citySelect.value) {
            loadChiefBarangays(citySelect.value, selectedBarangayId);
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

                updateChiefProfileUI(payload.user || {});
                showProfileToast(payload.message || 'Profile updated successfully.');
            } catch (error) {
                console.error('Chief profile update failed:', error);
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
    @include('chief.partials.logout-confirmation')
</body>
</html>
