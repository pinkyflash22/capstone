<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Landing Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <style>
        :root{--lm-ink:#172033;--lm-muted:#667085;--lm-line:#e8e2db;--lm-red:#be4028;--lm-orange:#df8618}
        body{background:#f7f8fa;color:var(--lm-ink)}
        .lm-main{margin-left:280px;padding:28px;min-height:100vh}.lm-shell{max-width:1320px;margin:auto}
        .lm-top{display:flex;justify-content:space-between;gap:24px;align-items:end;padding:28px;border-radius:28px;color:#fff;background:radial-gradient(circle at 85% 10%,rgba(255,255,255,.2),transparent 28%),linear-gradient(135deg,#8f2f22,#dc711c);box-shadow:0 22px 50px rgba(127,48,28,.16)}
        .lm-kicker{display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;opacity:.82}.lm-top h1{margin:8px 0 6px;font-size:clamp(25px,3vw,38px);letter-spacing:-.04em}.lm-top p{margin:0;max-width:720px;color:rgba(255,255,255,.78)}
        .lm-count{min-width:120px;padding:15px 18px;border:1px solid rgba(255,255,255,.18);border-radius:18px;background:rgba(255,255,255,.1);backdrop-filter:blur(10px)}.lm-count strong{display:block;font-size:26px}.lm-count span{font-size:11px;opacity:.75}
        .lm-alert{margin-top:18px;padding:14px 16px;border-radius:15px;border:1px solid}.lm-alert--ok{color:#17633c;background:#edf9f2;border-color:#bde8cf}.lm-alert--error{color:#9b2929;background:#fff1f1;border-color:#facaca}.lm-alert ul{margin:7px 0 0 20px}
        .lm-grid{display:grid;grid-template-columns:minmax(300px,390px) minmax(0,1fr);gap:22px;margin-top:22px}.lm-card{background:#fff;border:1px solid var(--lm-line);border-radius:24px;box-shadow:0 14px 34px rgba(23,32,51,.05)}
        .lm-card__head{padding:20px 22px;border-bottom:1px solid #f0ece7}.lm-card__head h2{margin:0;font-size:17px}.lm-card__head p{margin:5px 0 0;color:var(--lm-muted);font-size:12px}.lm-form{padding:20px 22px;display:grid;gap:14px}.lm-field{display:grid;gap:6px}.lm-field label{font-size:11px;font-weight:850;color:#475467}.lm-field input,.lm-field select,.lm-field textarea{width:100%;border:1px solid #dcdfe5;border-radius:12px;padding:11px 12px;background:#fbfcfd;color:var(--lm-ink);font:inherit}.lm-field textarea{min-height:82px;resize:vertical}.lm-fields-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.lm-help{font-size:10px;color:#8b93a1}.lm-check{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:750}.lm-check input{width:17px;height:17px;accent-color:var(--lm-red)}
        .lm-submit{border:0;border-radius:13px;padding:12px 16px;color:#fff;background:linear-gradient(135deg,var(--lm-red),var(--lm-orange));font-weight:850;cursor:pointer;box-shadow:0 10px 22px rgba(190,64,40,.18)}
        .lm-list{padding:18px;display:grid;gap:14px}.lm-slide{display:grid;grid-template-columns:190px minmax(0,1fr);gap:16px;padding:14px;border:1px solid #ebe7e1;border-radius:19px;background:#fff}.lm-preview{position:relative;min-height:138px;border-radius:14px;overflow:hidden;background:#181a20}.lm-preview img,.lm-preview video{width:100%;height:100%;position:absolute;inset:0;object-fit:cover}.lm-type{position:absolute;left:9px;top:9px;z-index:2;padding:5px 8px;border-radius:999px;color:#fff;background:rgba(16,24,40,.76);font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.08em}.lm-slide__top{display:flex;align-items:start;justify-content:space-between;gap:12px}.lm-slide h3{margin:2px 0 5px;font-size:16px}.lm-slide p{margin:0;color:var(--lm-muted);font-size:12px;line-height:1.55}.lm-status{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:9px;font-weight:900;text-transform:uppercase}.lm-status--on{color:#17633c;background:#eaf8ef}.lm-status--off{color:#7b8493;background:#eef0f3}.lm-meta{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;color:#7a8492;font-size:10px}.lm-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:13px}.lm-btn{border:1px solid #e0dcd6;border-radius:10px;padding:8px 10px;background:#fff;color:#445064;font-size:10px;font-weight:850;cursor:pointer}.lm-btn--danger{color:#a62d2d;background:#fff6f6;border-color:#f5cccc}.lm-edit{margin-top:13px;border-top:1px solid #eee9e3;padding-top:13px}.lm-edit summary{cursor:pointer;color:var(--lm-red);font-size:11px;font-weight:850}.lm-edit .lm-form{padding:13px 0 0}.lm-empty{padding:55px 20px;text-align:center;color:var(--lm-muted)}.lm-empty i{font-size:34px;color:#d8aa82;margin-bottom:12px}.lm-schema{margin-top:22px;padding:22px;border-radius:20px;color:#8a5116;background:#fff8e8;border:1px solid #f0d29d}
        @media(max-width:1050px){.lm-main{margin-left:0;padding:18px 16px 100px}.lm-grid{grid-template-columns:1fr}.lm-form-card{order:0}.lm-list-card{order:1}}
        @media(max-width:650px){.lm-main{padding:10px 10px 94px}.lm-top{padding:22px 18px;align-items:start}.lm-count{display:none}.lm-slide{grid-template-columns:1fr}.lm-preview{min-height:190px}.lm-fields-2{grid-template-columns:1fr}.lm-card{border-radius:20px}}
    </style>
</head>
@php($isProvincialAdmin = strtolower(trim((string) (auth()->user()?->role?->role ?? ''))) === 'provincial admin')
<body class="chief-dashboard-page">
    @include($isProvincialAdmin ? 'admin.partials.sidebar' : 'chief.partials.sidebar', ['active' => 'landing-media'])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <main class="lm-main">
        <div class="lm-shell">
            <header class="lm-top">
                <div>
                    <span class="lm-kicker"><i class="fa-solid fa-photo-film"></i> Public experience</span>
                    <h1>Landing media</h1>
                    <p>Publish and order the image and featured-video slides shown in the public landing-page hero.</p>
                </div>
                <div class="lm-count"><strong>{{ $slides->count() }}</strong><span>Total slides</span></div>
            </header>

            @if(session('success'))<div class="lm-alert lm-alert--ok">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="lm-alert lm-alert--error"><strong>Please review the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @unless($schemaReady)
                <div class="lm-schema"><strong>Setup required.</strong> The landing-media table has not been created yet. Run the pending migration before uploading slides.</div>
            @endunless

            <div class="lm-grid">
                <section class="lm-card lm-form-card">
                    <div class="lm-card__head"><h2>Add a slide</h2><p>Images use up to 8 MB. Featured videos use up to 100 MB.</p></div>
                    <form class="lm-form" method="POST" action="{{ route('landing-media.store') }}" enctype="multipart/form-data" data-media-form>
                        @csrf
                        <div class="lm-fields-2">
                            <div class="lm-field"><label>Slide type</label><select name="type" data-media-type required><option value="image">Image</option><option value="video">Featured video</option></select></div>
                            <div class="lm-field"><label>Display order</label><input type="number" name="sort_order" value="{{ old('sort_order', $slides->count() + 1) }}" min="0" max="9999"></div>
                        </div>
                        <div class="lm-field"><label>Title</label><input name="title" value="{{ old('title') }}" maxlength="120" required placeholder="Community fire-safety drive"></div>
                        <div class="lm-field"><label>Supporting text</label><textarea name="subtitle" maxlength="320" placeholder="Add a short, useful description.">{{ old('subtitle') }}</textarea></div>
                        <div class="lm-field"><label data-media-label>Image file</label><input type="file" name="media" data-media-input accept="image/jpeg,image/png,image/webp,image/gif" required><span class="lm-help" data-media-help>JPG, PNG, WEBP, or GIF.</span></div>
                        <div class="lm-field" data-poster-field hidden><label>Video poster image (optional)</label><input type="file" name="poster" accept="image/jpeg,image/png,image/webp"><span class="lm-help">Shown while the video loads.</span></div>
                        <div class="lm-fields-2">
                            <div class="lm-field"><label>Button label (optional)</label><input name="button_label" value="{{ old('button_label') }}" maxlength="60" placeholder="Learn more"></div>
                            <div class="lm-field"><label>Button URL (optional)</label><input name="button_url" value="{{ old('button_url') }}" maxlength="2048" placeholder="/login or https://..."></div>
                        </div>
                        <input type="hidden" name="is_active" value="0"><label class="lm-check"><input type="checkbox" name="is_active" value="1" checked> Publish immediately</label>
                        <button class="lm-submit" type="submit" @disabled(!$schemaReady)><i class="fa-solid fa-cloud-arrow-up"></i> Add to landing slider</button>
                    </form>
                </section>

                <section class="lm-card lm-list-card">
                    <div class="lm-card__head"><h2>Slider content</h2><p>Lower order numbers appear first. Hidden slides remain editable.</p></div>
                    <div class="lm-list">
                        @forelse($slides as $slide)
                            <article class="lm-slide">
                                <div class="lm-preview">
                                    <span class="lm-type"><i class="fa-solid {{ $slide->type === 'video' ? 'fa-play' : 'fa-image' }}"></i> {{ $slide->type }}</span>
                                    @if($slide->type === 'video')
                                        <video src="{{ $slide->media_url }}" poster="{{ $slide->poster_url }}" muted playsinline controls preload="metadata"></video>
                                    @else
                                        <img src="{{ $slide->media_url }}" alt="{{ $slide->title }}">
                                    @endif
                                </div>
                                <div>
                                    <div class="lm-slide__top"><div><h3>{{ $slide->title }}</h3><p>{{ $slide->subtitle ?: 'No supporting text.' }}</p></div><span class="lm-status {{ $slide->is_active ? 'lm-status--on' : 'lm-status--off' }}">{{ $slide->is_active ? 'Published' : 'Hidden' }}</span></div>
                                    <div class="lm-meta"><span><i class="fa-solid fa-arrow-down-1-9"></i> Order {{ $slide->sort_order }}</span><span><i class="fa-regular fa-clock"></i> {{ $slide->updated_at?->diffForHumans() }}</span>@if($slide->creator)<span><i class="fa-regular fa-user"></i> {{ trim($slide->creator->first_name.' '.$slide->creator->last_name) }}</span>@endif</div>
                                    <div class="lm-actions">
                                        <form method="POST" action="{{ route('landing-media.toggle', $slide) }}">@csrf @method('PATCH')<button class="lm-btn" type="submit"><i class="fa-solid {{ $slide->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i> {{ $slide->is_active ? 'Hide' : 'Publish' }}</button></form>
                                        <form method="POST" action="{{ route('landing-media.destroy', $slide) }}" onsubmit="return confirm('Remove this slide and its uploaded media?')">@csrf @method('DELETE')<button class="lm-btn lm-btn--danger" type="submit"><i class="fa-regular fa-trash-can"></i> Remove</button></form>
                                    </div>
                                    <details class="lm-edit"><summary><i class="fa-solid fa-pen"></i> Edit slide</summary>
                                        <form class="lm-form" method="POST" action="{{ route('landing-media.update', $slide) }}" enctype="multipart/form-data" data-media-form>@csrf @method('PUT')
                                            <div class="lm-fields-2"><div class="lm-field"><label>Type</label><select name="type" data-media-type><option value="image" @selected($slide->type === 'image')>Image</option><option value="video" @selected($slide->type === 'video')>Featured video</option></select></div><div class="lm-field"><label>Order</label><input type="number" name="sort_order" value="{{ $slide->sort_order }}" min="0" max="9999"></div></div>
                                            <div class="lm-field"><label>Title</label><input name="title" value="{{ $slide->title }}" required maxlength="120"></div>
                                            <div class="lm-field"><label>Supporting text</label><textarea name="subtitle" maxlength="320">{{ $slide->subtitle }}</textarea></div>
                                            <div class="lm-field"><label data-media-label>Replace media (optional)</label><input type="file" name="media" data-media-input><span class="lm-help" data-media-help>Keep empty to retain the current file.</span></div>
                                            <div class="lm-field" data-poster-field @hidden($slide->type !== 'video')><label>Replace poster (optional)</label><input type="file" name="poster" accept="image/jpeg,image/png,image/webp"><label class="lm-check"><input type="checkbox" name="remove_poster" value="1"> Remove current poster</label></div>
                                            <div class="lm-fields-2"><div class="lm-field"><label>Button label</label><input name="button_label" value="{{ $slide->button_label }}" maxlength="60"></div><div class="lm-field"><label>Button URL</label><input name="button_url" value="{{ $slide->button_url }}" maxlength="2048"></div></div>
                                            <input type="hidden" name="is_active" value="0"><label class="lm-check"><input type="checkbox" name="is_active" value="1" @checked($slide->is_active)> Published</label>
                                            <button class="lm-submit" type="submit">Save changes</button>
                                        </form>
                                    </details>
                                </div>
                            </article>
                        @empty
                            <div class="lm-empty"><i class="fa-regular fa-images"></i><h3>No custom slides yet</h3><p>The public landing page is currently using its built-in fallback image.</p></div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-media-form]').forEach((form) => {
            const type = form.querySelector('[data-media-type]');
            const input = form.querySelector('[data-media-input]');
            const label = form.querySelector('[data-media-label]');
            const help = form.querySelector('[data-media-help]');
            const poster = form.querySelector('[data-poster-field]');
            const sync = () => {
                const video = type.value === 'video';
                input.accept = video ? 'video/mp4,video/webm,video/quicktime' : 'image/jpeg,image/png,image/webp,image/gif';
                label.textContent = input.required ? (video ? 'Video file' : 'Image file') : 'Replace media (optional)';
                help.textContent = input.required ? (video ? 'MP4, WEBM, or MOV, up to 100 MB.' : 'JPG, PNG, WEBP, or GIF, up to 8 MB.') : 'Keep empty to retain the current file.';
                poster.hidden = !video;
            };
            type.addEventListener('change', sync); sync();
        });
    </script>
</body>
</html>
