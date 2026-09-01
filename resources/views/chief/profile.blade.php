<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · Commander Profile</title>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root{--chief-orange:#c84f24;--chief-amber:#e89a24;--chief-ink:#172033;--chief-copy:#64748b;--chief-line:#e8edf3}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,ui-sans-serif,system-ui,sans-serif;color:var(--chief-ink);background:linear-gradient(180deg,#fffaf2 0,#f8fafc 52%,#f4f7fb 100%)}.main-content{min-height:100vh;margin-left:280px;padding:20px 28px 42px}.profile-topbar{position:sticky;top:14px;z-index:40;display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:70px;padding:12px 18px;margin-bottom:20px;border:1px solid rgba(232,154,36,.15);border-radius:22px;background:rgba(255,255,255,.88);box-shadow:0 12px 32px rgba(15,23,42,.07);backdrop-filter:blur(14px)}.profile-topbar__title{margin:0;font-size:1.05rem;font-weight:850}.profile-topbar__subtitle{margin:3px 0 0;color:var(--chief-copy);font-size:.78rem}.profile-identity-pill{display:flex;align-items:center;gap:10px;min-width:0;padding:7px 12px 7px 7px;border:1px solid #fde7bd;border-radius:999px;background:#fffaf0}.profile-mini-avatar,.profile-avatar{display:grid;place-items:center;overflow:hidden;flex:0 0 auto;color:#fff;font-weight:850;background:linear-gradient(135deg,var(--chief-amber),var(--chief-orange))}.profile-mini-avatar{width:36px;height:36px;border-radius:50%;font-size:.72rem}.profile-mini-avatar img,.profile-avatar img{width:100%;height:100%;object-fit:cover}.profile-shell{width:min(1280px,100%);margin:0 auto;display:grid;gap:20px}.profile-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:26px;padding:30px;border:1px solid rgba(232,154,36,.16);border-radius:30px;background:radial-gradient(circle at 92% 10%,rgba(232,154,36,.22),transparent 24%),linear-gradient(135deg,#fff8ec 0,#fffdf9 56%,#fff 100%);box-shadow:0 22px 50px rgba(87,61,24,.08)}.profile-hero__identity{display:flex;align-items:center;gap:20px;min-width:0}.profile-avatar{width:94px;height:94px;border:6px solid rgba(255,255,255,.9);border-radius:28px;box-shadow:0 15px 34px rgba(200,79,36,.22);font-size:1.65rem}.profile-eyebrow{display:inline-flex;align-items:center;gap:8px;color:#a84620;font-size:.7rem;font-weight:850;letter-spacing:.14em;text-transform:uppercase}.profile-hero h1{margin:8px 0 4px;font-size:clamp(1.7rem,3vw,2.7rem);line-height:1;letter-spacing:-.045em}.profile-hero p{margin:0;color:var(--chief-copy);line-height:1.6}.profile-status{align-self:start;display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1px solid #bce9ce;border-radius:999px;color:#187447;background:#effbf4;font-size:.75rem;font-weight:800;text-transform:capitalize}.profile-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.profile-stat{padding:18px;border:1px solid var(--chief-line);border-radius:21px;background:rgba(255,255,255,.92);box-shadow:0 10px 28px rgba(15,23,42,.05)}.profile-stat span{display:block;color:var(--chief-copy);font-size:.7rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.profile-stat strong{display:block;margin-top:7px;font-size:1.32rem;line-height:1.2}.profile-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(285px,.7fr);gap:20px;align-items:start}.profile-card{border:1px solid var(--chief-line);border-radius:25px;background:rgba(255,255,255,.95);box-shadow:0 14px 35px rgba(15,23,42,.055)}.profile-card__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:21px 22px;border-bottom:1px solid var(--chief-line)}.profile-card__head h2{margin:0;font-size:1.05rem}.profile-card__head p{margin:5px 0 0;color:var(--chief-copy);font-size:.82rem;line-height:1.5}.profile-card__icon{display:grid;place-items:center;width:40px;height:40px;border-radius:14px;color:#b74620;background:#fff1e6}.profile-card__body{padding:22px}.profile-form-section+.profile-form-section{margin-top:24px;padding-top:23px;border-top:1px solid var(--chief-line)}.profile-form-section h3{margin:0 0 15px;font-size:.82rem;letter-spacing:.03em}.profile-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.profile-field--wide{grid-column:1/-1}.profile-field label{display:block;margin-bottom:7px;color:#475569;font-size:.75rem;font-weight:750}.profile-field input,.profile-field select{width:100%;min-height:45px;padding:10px 12px;border:1px solid #dbe3ec;border-radius:13px;outline:none;color:var(--chief-ink);background:#fbfdff;transition:.2s}.profile-field input:focus,.profile-field select:focus{border-color:#e89a24;box-shadow:0 0 0 4px rgba(232,154,36,.12);background:#fff}.profile-actions{display:flex;justify-content:flex-end;padding-top:20px}.profile-save{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:45px;padding:0 20px;border:0;border-radius:999px;color:#fff;background:linear-gradient(135deg,var(--chief-orange),#a93d1a);box-shadow:0 12px 24px rgba(200,79,36,.2);font-weight:800;cursor:pointer}.station-list{display:grid;gap:0}.station-detail{display:grid;grid-template-columns:38px minmax(0,1fr);gap:12px;padding:15px 0}.station-detail+.station-detail{border-top:1px solid var(--chief-line)}.station-detail i{display:grid;place-items:center;width:38px;height:38px;border-radius:13px;color:#b74620;background:#fff4e8}.station-detail span{display:block;color:var(--chief-copy);font-size:.69rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.station-detail strong{display:block;margin-top:4px;font-size:.88rem;line-height:1.45;overflow-wrap:anywhere}.activity-list{display:grid}.activity-row{display:grid;grid-template-columns:42px minmax(0,1fr) auto;gap:13px;align-items:center;padding:15px 0}.activity-row+.activity-row{border-top:1px solid var(--chief-line)}.activity-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;color:#b74620;background:#fff3e8}.activity-row strong{display:block;font-size:.86rem;text-transform:capitalize}.activity-row p{margin:4px 0 0;color:var(--chief-copy);font-size:.74rem}.activity-time{color:#64748b;font-size:.72rem;text-align:right}.empty-state{padding:35px 15px;color:var(--chief-copy);text-align:center}.profile-pagination{padding:0 22px 22px}.profile-alert{padding:13px 16px;border-radius:15px;font-size:.84rem}.profile-alert--success{border:1px solid #bce9ce;color:#187447;background:#effbf4}.profile-alert--error{border:1px solid #fecaca;color:#b91c1c;background:#fef2f2}.chief-profile-error{margin:5px 0 0;color:#dc2626;font-size:.72rem}@media(max-width:1024px){.main-content{margin-left:0}.profile-grid{grid-template-columns:1fr}}@media(max-width:700px){.main-content{padding:10px 12px calc(92px + env(safe-area-inset-bottom))}.profile-topbar{top:8px;min-height:60px;border-radius:18px}.profile-topbar__copy,.profile-identity-pill__copy{display:none}.profile-hero{grid-template-columns:1fr;padding:22px 18px;border-radius:24px}.profile-hero__identity{align-items:flex-start}.profile-avatar{width:72px;height:72px;border-radius:22px}.profile-status{position:absolute;top:16px;right:16px;font-size:0;width:30px;height:30px;padding:0;justify-content:center}.profile-status i{font-size:.72rem}.profile-stats{grid-template-columns:1fr 1fr}.profile-stat:last-child{grid-column:1/-1}.profile-fields{grid-template-columns:1fr}.profile-field--wide{grid-column:auto}.profile-card__body{padding:18px}.profile-save{width:100%}.activity-row{grid-template-columns:38px minmax(0,1fr)}.activity-time{grid-column:2;text-align:left}}
        /* Match the shared Fire Risk Forecast command theme. */
        .main-content{padding:20px 24px 32px;background:linear-gradient(180deg,#fffdf8 0%,#f8fafc 100%)}
        .station-badge{max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6b7280;font-size:14px;background:#f3f4f6;padding:6px 12px;border-radius:20px}
        .profile-shell{width:100%;max-width:1480px;gap:24px}
        .profile-hero{grid-template-columns:minmax(0,1.45fr) minmax(280px,.95fr);gap:20px;padding:28px 30px;border-radius:30px;background:linear-gradient(135deg,#fff7ed 0%,#fffbeb 44%,#fff 100%);border-color:rgba(245,158,11,.12);box-shadow:0 20px 40px rgba(15,23,42,.05)}
        .profile-eyebrow{min-height:36px;padding:0 14px;border-radius:999px;background:rgba(255,255,255,.76);color:#9a3412;border:1px solid rgba(245,158,11,.14);font-size:.78rem;letter-spacing:.06em}
        .profile-hero h1{margin:16px 0 0;color:#0f172a;font-size:clamp(2rem,3vw,2.8rem);font-weight:900}
        .profile-hero p{margin-top:12px;font-size:.98rem;line-height:1.65}
        .profile-card{border-radius:30px;border-color:rgba(226,232,240,.92);box-shadow:0 20px 40px rgba(15,23,42,.05)}
        .profile-card__head{padding:22px 24px 18px;background:linear-gradient(180deg,#fffaf4 0%,#fff 100%)}
        .profile-card__head h2{color:#0f172a;font-size:1.2rem;font-weight:900}
        .profile-stat{border-radius:22px;border-color:rgba(245,158,11,.12);background:rgba(255,255,255,.82)}
        @media(max-width:1024px){.main-content{margin-left:0;width:100%;padding:20px 24px 96px}.profile-hero{grid-template-columns:1fr}}
        @media(max-width:700px){.main-content{padding:6px 0 calc(96px + env(safe-area-inset-bottom))}.profile-shell{padding:0 12px}.profile-hero{padding:22px 18px;border-radius:24px}.profile-card{border-radius:24px}.profile-card__head{padding:18px}}
        .profile-hero{grid-template-columns:minmax(0,1fr) minmax(270px,340px);align-items:center}
        .profile-avatar-shell{position:relative;flex:0 0 auto}.profile-avatar-change{position:absolute;right:-5px;bottom:-5px;display:grid;place-items:center;width:38px;height:38px;border:4px solid #fff;border-radius:13px;background:linear-gradient(135deg,#c84f24,#e89a24);color:#fff;box-shadow:0 8px 18px rgba(200,79,36,.25);cursor:pointer;transition:transform .18s ease}.profile-avatar-change:hover{transform:translateY(-2px)}
        .profile-photo-editor{display:grid;grid-template-columns:42px minmax(0,1fr);gap:11px;padding:15px;border:1px solid rgba(245,158,11,.22);border-radius:19px;background:rgba(255,255,255,.78);box-shadow:0 10px 25px rgba(120,75,20,.05)}.profile-photo-editor__icon{display:grid;place-items:center;width:42px;height:42px;border-radius:13px;background:#ffedd5;color:#c2410c}.profile-photo-editor__copy{min-width:0}.profile-photo-editor__copy strong{display:block;color:#1e293b;font-size:.82rem}.profile-photo-editor__copy p{margin:3px 0 0;font-size:.68rem;line-height:1.42}.profile-photo-editor__actions{grid-column:1/-1;display:flex;align-items:center;gap:8px}.profile-photo-select,.profile-photo-clear{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:37px;padding:0 13px;border-radius:11px;font-size:.72rem;font-weight:850;cursor:pointer}.profile-photo-select{border:0;background:#fff1e6;color:#a43f1d}.profile-photo-clear{border:1px solid #e2e8f0;background:#fff;color:#64748b}.profile-photo-clear[hidden]{display:none}.profile-photo-name{grid-column:1/-1;min-width:0;color:#64748b;font-size:.68rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}#chief_profile_image{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
        .profile-section-heading{display:flex;align-items:center;gap:9px;margin:0 0 16px!important;color:#334155!important;font-size:.78rem!important;text-transform:uppercase;letter-spacing:.07em!important}.profile-section-heading i{display:grid;place-items:center;width:28px;height:28px;border-radius:9px;background:#fff1e6;color:#c2410c;font-size:.72rem}.profile-field label{display:flex;align-items:center;gap:7px}.profile-field label i{width:16px;color:#d97706;font-size:.7rem;text-align:center}
        @media(max-width:1024px){.profile-hero{grid-template-columns:1fr}.profile-photo-editor{max-width:500px}}
        @media(max-width:700px){.profile-hero__identity{display:grid;grid-template-columns:auto minmax(0,1fr);gap:14px}.profile-photo-editor{grid-template-columns:36px minmax(0,1fr);padding:12px;border-radius:16px}.profile-photo-editor__icon{width:36px;height:36px}.profile-photo-editor__actions{display:grid;grid-template-columns:1fr auto}.profile-photo-select{width:100%}.profile-photo-name{white-space:normal;overflow-wrap:anywhere}.profile-hero h1{font-size:1.55rem;line-height:1.08}.profile-hero p{font-size:.78rem}.profile-status{top:12px;right:12px}}
    </style>
</head>
@php
    $profileUser=$user??auth()->user();$profileAddress=$profileUser?->address;$profileBarangay=$profileAddress?->barangay;$profileCity=$profileBarangay?->city;
    $profileName=trim(collect([$profileUser->first_name,$profileUser->middle_name,$profileUser->last_name])->filter()->implode(' '))?:'Station Commander';
    $profileInitials=strtoupper(substr($profileUser->first_name?:'C',0,1).substr($profileUser->last_name?:'P',0,1));$profileImageUrl=$profileUser->profile_thumbnail_url??$profileUser->profile_image_url??null;
    $selectedCityId=old('city_id',$profileCity?->city_id);$barangayOptions=$selectedCityId?(optional($cities->firstWhere('city_id',(int)$selectedCityId))->barangays??collect()):collect();$station=$stationOfficer?->station;
    $stationAddress=collect([$station?->address?->street,$station?->address?->barangay?->barangay_name,$station?->address?->barangay?->city?->city_name])->filter()->implode(', ');
@endphp
<body class="chief-legacy-layout">
@include('chief.partials.sidebar',['active'=>'profile'])
<main class="main-content">
    <nav class="lector-top-bar">
        <div class="flex items-center gap-4"><button id="openSidebar" type="button" class="lg:hidden text-slate-600" aria-label="Open navigation"><i class="fa-solid fa-bars text-xl"></i></button><div class="hidden lg:block"><h1 class="text-xl font-bold text-slate-800">Commander Profile</h1><p class="text-xs text-slate-500 font-medium">Account, station assignment, and recent activity · {{ now()->format('l, F j') }}</p></div></div>
        <div class="flex items-center gap-6"><span class="station-badge">{{ $profileStats['station_name'] }}</span><div class="user-dropdown"><button id="userDropdownBtn" type="button" class="user-dropdown-toggle" aria-expanded="false" aria-controls="userDropdownMenu"><div id="chiefProfileTopbarInitials" class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center overflow-hidden text-white text-[10px] font-bold shadow-sm shadow-amber-200">@if($profileImageUrl)<img src="{{ $profileImageUrl }}" alt="" class="w-full h-full object-cover">@else{{ $profileInitials }}@endif</div><span id="chiefProfileTopbarName" class="text-sm font-bold text-slate-700 hidden sm:block">{{ $profileUser->first_name }}</span><i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i></button><div id="userDropdownMenu" class="user-dropdown-menu"><div class="p-4 border-b border-slate-100 bg-slate-50/50"><p class="text-sm font-bold text-slate-800">{{ $profileName }}</p><p id="chiefProfileDropdownEmail" class="text-[10px] text-slate-500 font-medium">{{ $profileUser->email }}</p></div><div class="p-2"><a href="{{ route('station-admin.profile') }}" class="user-dropdown-item"><i class="fa-regular fa-user"></i><span>Commander Profile</span></a><a href="#" class="user-dropdown-item chief-open-logs"><i class="fa-solid fa-clock-rotate-left"></i><span>My Logs</span></a><div class="h-px bg-slate-100 my-2"></div><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="user-dropdown-item text-red-600 w-full text-left"><i class="fa-solid fa-sign-out-alt"></i><span>Logout</span></button></form></div></div></div></div>
    </nav>
    <div class="profile-shell chief-theme-shell">
        @if(session('success'))<div class="profile-alert profile-alert--success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="profile-alert profile-alert--error"><strong>Please review the highlighted fields.</strong><ul class="mt-1 pl-5 list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="profile-hero">
            <div class="profile-hero__identity">
                <div class="profile-avatar-shell">
                    <div id="chiefProfileCardInitials" class="profile-avatar">
                        @if($profileImageUrl)<img src="{{ $profileImageUrl }}" alt="{{ $profileName }}">@else<span data-profile-initials>{{ $profileInitials }}</span>@endif
                    </div>
                    <label class="profile-avatar-change" for="chief_profile_image" title="Change profile photo" aria-label="Change profile photo"><i class="fa-solid fa-camera"></i></label>
                </div>
                <div><span class="profile-eyebrow"><i class="fa-solid fa-shield-halved"></i> BFP command account</span><h1 id="chiefProfileCardName">{{ $profileName }}</h1><p><span id="chiefProfileCardEmail">{{ $profileUser->email }}</span> &middot; {{ $profileStats['station_name'] }}</p></div>
            </div>
            <aside class="profile-photo-editor" aria-label="Profile photo settings">
                <span class="profile-photo-editor__icon"><i class="fa-solid fa-user-shield"></i></span>
                <div class="profile-photo-editor__copy"><strong>Commander photo</strong><p>Use a clear square portrait. JPG, PNG, or WebP up to 5 MB.</p></div>
                <div class="profile-photo-editor__actions">
                    <label class="profile-photo-select" for="chief_profile_image"><i class="fa-solid fa-image"></i> Choose photo</label>
                    <button id="chiefProfilePhotoClear" class="profile-photo-clear" type="button" hidden><i class="fa-solid fa-rotate-left"></i> Discard</button>
                </div>
                <span id="chiefProfilePhotoName" class="profile-photo-name" aria-live="polite">Your current profile photo is shown.</span>
                <input id="chief_profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp" form="chiefProfileForm">
            </aside>
            <span class="profile-status"><i class="fa-solid fa-circle-check"></i> {{ str_replace('_',' ',$profileStats['duty_status']) }}</span>
        </section>
        <section class="profile-stats" aria-label="Profile summary"><article class="profile-stat"><span>Rank / position</span><strong>{{ $profileStats['rank'] }}</strong></article><article class="profile-stat"><span>Station personnel</span><strong>{{ number_format($profileStats['station_personnel']) }}</strong></article><article class="profile-stat"><span>Recorded activities</span><strong>{{ number_format($profileStats['activity_count']) }}</strong></article></section>
        <div class="profile-grid">
            <section class="profile-card"><header class="profile-card__head"><div><h2>Edit account information</h2><p>These values are read from and saved directly to your commander account.</p></div><span class="profile-card__icon"><i class="fa-solid fa-user-pen"></i></span></header>
                <form id="chiefProfileForm" method="POST" action="{{ route('station-admin.profile.update') }}" enctype="multipart/form-data" class="profile-card__body">@csrf @method('PUT')
                    <div class="profile-form-section"><h3 class="profile-section-heading"><i class="fa-solid fa-id-card"></i> Personal details</h3><div class="profile-fields"><div class="profile-field"><label for="chief_first_name">First name</label><input id="chief_first_name" name="first_name" value="{{ old('first_name',$profileUser->first_name) }}" required></div><div class="profile-field"><label for="chief_middle_name">Middle name</label><input id="chief_middle_name" name="middle_name" value="{{ old('middle_name',$profileUser->middle_name) }}"></div><div class="profile-field"><label for="chief_last_name">Last name</label><input id="chief_last_name" name="last_name" value="{{ old('last_name',$profileUser->last_name) }}" required></div><div class="profile-field"><label for="chief_contact_number">Contact number</label><input id="chief_contact_number" name="contact_number" value="{{ old('contact_number',$profileUser->contact_number) }}" inputmode="tel"></div><div class="profile-field profile-field--wide"><label for="chief_email">Email address</label><input id="chief_email" name="email" type="email" value="{{ old('email',$profileUser->email) }}" required></div></div></div>
                    <div class="profile-form-section"><h3 class="profile-section-heading"><i class="fa-solid fa-location-dot"></i> Residential address</h3><div class="profile-fields"><div class="profile-field"><label for="chief_city_id">City / municipality</label><select id="chief_city_id" name="city_id" required><option value="">Select city</option>@foreach($cities as $city)<option value="{{ $city->city_id }}" @selected((string)old('city_id',$profileCity?->city_id)===(string)$city->city_id)>{{ $city->city_name }}</option>@endforeach</select></div><div class="profile-field"><label for="chief_barangay_id">Barangay</label><select id="chief_barangay_id" name="barangay_id" required><option value="">Select barangay</option>@foreach($barangayOptions as $barangay)<option value="{{ $barangay->barangay_id }}" @selected((string)old('barangay_id',$profileBarangay?->barangay_id)===(string)$barangay->barangay_id)>{{ $barangay->barangay_name }}</option>@endforeach</select></div><div class="profile-field profile-field--wide"><label for="chief_street">Street address</label><input id="chief_street" name="street" value="{{ old('street',$profileAddress?->street) }}" required></div></div></div>
                    <div class="profile-actions"><button id="chiefProfileSaveBtn" class="profile-save" type="submit"><i class="fa-solid fa-floppy-disk"></i><span>Save profile</span></button></div>
                </form>
            </section>
            <aside class="profile-card"><header class="profile-card__head"><div><h2>Station assignment</h2><p>Live assignment information from the station roster.</p></div><span class="profile-card__icon"><i class="fa-solid fa-building-shield"></i></span></header><div class="profile-card__body station-list"><div class="station-detail"><i class="fa-solid fa-building"></i><div><span>Station</span><strong>{{ $station?->station_name??'No station assigned' }}</strong></div></div><div class="station-detail"><i class="fa-solid fa-hashtag"></i><div><span>Station code</span><strong>{{ $station?->station_code??'Not recorded' }}</strong></div></div><div class="station-detail"><i class="fa-solid fa-location-dot"></i><div><span>Address</span><strong>{{ $stationAddress?:'Not recorded' }}</strong></div></div><div class="station-detail"><i class="fa-solid fa-phone"></i><div><span>Station contact</span><strong>{{ $station?->contact_number?:'Not recorded' }}</strong></div></div><div class="station-detail"><i class="fa-regular fa-calendar"></i><div><span>Member since</span><strong>{{ $profileUser->created_at?->format('F j, Y')??'Not recorded' }}</strong></div></div></div></aside>
        </div>
        <section class="profile-card"><header class="profile-card__head"><div><h2>Recent account activity</h2><p>Paginated security and command actions recorded in the database.</p></div><span class="profile-card__icon"><i class="fa-solid fa-clock-rotate-left"></i></span></header><div class="profile-card__body activity-list">@forelse($activityLogs as $log)<article class="activity-row"><span class="activity-icon"><i class="fa-solid fa-bolt"></i></span><div><strong>{{ str_replace(['_','-'],' ',$log->action) }}</strong><p>{{ $log->ip_address?:'IP not recorded' }} · {{ $log->device_info }}</p></div><time class="activity-time" datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('M d, Y') }}<br>{{ $log->created_at?->format('g:i A') }}</time></article>@empty<div class="empty-state"><i class="fa-regular fa-folder-open text-2xl"></i><p>No account activities have been recorded yet.</p></div>@endforelse</div>@if($activityLogs->hasPages())<div class="profile-pagination">{{ $activityLogs->onEachSide(1)->links() }}</div>@endif</section>
    </div>
</main>
@include('chief.partials.logs-modal') @include('chief.partials.logs-script') @include('chief.partials.logout-confirmation')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('chief_profile_image');
    const discard = document.getElementById('chiefProfilePhotoClear');
    const fileName = document.getElementById('chiefProfilePhotoName');
    const initials = @json($profileInitials);
    let currentPhotoUrl = @json($profileImageUrl);
    let previewUrl = null;

    const renderPhoto = (container, url) => {
        if (!container) return;
        container.replaceChildren();
        if (url) {
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Commander profile photo';
            image.className = 'w-full h-full object-cover';
            container.appendChild(image);
            return;
        }
        const fallback = document.createElement('span');
        fallback.textContent = initials;
        fallback.dataset.profileInitials = '';
        container.appendChild(fallback);
    };

    const applyPhoto = url => {
        renderPhoto(document.getElementById('chiefProfileCardInitials'), url);
        renderPhoto(document.getElementById('chiefProfileTopbarInitials'), url);
    };

    const releasePreview = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    };

    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
            input.value = '';
            applyPhoto(currentPhotoUrl);
            fileName.textContent = file.size > 5 * 1024 * 1024 ? 'Photo exceeds the 5 MB limit.' : 'Choose a JPG, PNG, or WebP image.';
            discard.hidden = true;
            window.PyroNotify?.notify(fileName.textContent, 'error');
            return;
        }
        releasePreview();
        previewUrl = URL.createObjectURL(file);
        applyPhoto(previewUrl);
        fileName.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(1)} MB`;
        discard.hidden = false;
    });

    discard?.addEventListener('click', () => {
        input.value = '';
        releasePreview();
        applyPhoto(currentPhotoUrl);
        fileName.textContent = 'Photo change discarded.';
        discard.hidden = true;
    });

    window.chiefApplyProfilePhoto = url => {
        currentPhotoUrl = url || currentPhotoUrl;
        releasePreview();
        applyPhoto(currentPhotoUrl);
        if (input) input.value = '';
        if (discard) discard.hidden = true;
        if (fileName) fileName.textContent = 'Profile photo updated.';
    };
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const form = document.getElementById('chiefProfileForm');
    const city = document.getElementById('chief_city_id');
    const barangay = document.getElementById('chief_barangay_id');
    const save = document.getElementById('chiefProfileSaveBtn');
    const initialBarangay = @json((string) old('barangay_id', $profileBarangay?->barangay_id));

    document.getElementById('openSidebar')?.addEventListener('click', () => {
        sidebar?.classList.add('open');
        backdrop?.classList.remove('hidden');
    });
    backdrop?.addEventListener('click', () => {
        sidebar?.classList.remove('open');
        backdrop.classList.add('hidden');
    });

    async function loadBarangays(cityId, selected = '') {
        barangay.innerHTML = '<option value="">Select barangay</option>';
        if (!cityId) return;
        try {
            const response = await fetch(`/api/barangays/${cityId}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Unable to load barangays.');
            (await response.json()).forEach(row => barangay.add(new Option(row.barangay_name, row.barangay_id, false, String(row.barangay_id) === String(selected))));
        } catch (error) {
            window.PyroNotify?.notify(error.message, 'error');
        }
    }

    city?.addEventListener('change', () => loadBarangays(city.value));
    if (city?.value) loadBarangays(city.value, initialBarangay);

    form?.addEventListener('submit', async event => {
        event.preventDefault();
        form.querySelectorAll('.chief-profile-error').forEach(node => node.remove());
        save.disabled = true;
        save.querySelector('span').textContent = form.elements.namedItem('profile_image')?.files?.length ? 'Uploading and saving…' : 'Saving…';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new FormData(form),
            });
            const payload = await response.json();
            if (!response.ok) {
                if (payload.errors) Object.entries(payload.errors).forEach(([name, messages]) => {
                    const field = form.elements.namedItem(name);
                    if (field) {
                        const error = document.createElement('p');
                        error.className = 'chief-profile-error';
                        error.textContent = messages[0];
                        field.after(error);
                    }
                });
                throw new Error(payload.message || 'Please review the profile fields.');
            }
            document.getElementById('chiefProfileCardName').textContent = payload.user.full_name;
            document.getElementById('chiefProfileCardEmail').textContent = payload.user.email;
            document.getElementById('chiefProfileTopbarName').textContent = payload.user.first_name;
            document.getElementById('chiefProfileDropdownEmail').textContent = payload.user.email;
            const savedPhoto = payload.user.thumbnail_url || payload.user.image_url;
            if (savedPhoto) window.chiefApplyProfilePhoto?.(savedPhoto);
            window.PyroNotify?.notify(payload.message || 'Profile updated.', 'success');
        } catch (error) {
            window.PyroNotify?.notify(error.message || 'Unable to update profile.', 'error');
        } finally {
            save.disabled = false;
            save.querySelector('span').textContent = 'Save profile';
        }
    });
});
</script>
<script>document.addEventListener('DOMContentLoaded',()=>{const button=document.getElementById('userDropdownBtn'),menu=document.getElementById('userDropdownMenu');if(!button||!menu)return;button.addEventListener('click',event=>{event.stopPropagation();const open=menu.classList.toggle('show');button.setAttribute('aria-expanded',open?'true':'false')});document.addEventListener('click',event=>{if(!button.contains(event.target)&&!menu.contains(event.target)){menu.classList.remove('show');button.setAttribute('aria-expanded','false')}})});</script>
</body></html>
