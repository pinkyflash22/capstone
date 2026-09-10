<?php

namespace App\Http\Controllers;

use App\Models\LandingMedia;
use App\Support\MediaPath;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LandingMediaController extends Controller
{
    public function index()
    {
        $schemaReady = Schema::hasTable('landing_media');
        $slides = $schemaReady
            ? LandingMedia::query()->with('creator:user_id,first_name,last_name')->orderBy('sort_order')->orderByDesc('id')->get()
            : collect();

        return view('landing-media.index', compact('slides', 'schemaReady'));
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('landing_media'), 503, 'Landing media storage is not configured yet.');
        $validated = $this->validatePayload($request, true);
        $mediaPath = $this->storeUpload($request->file('media'), 'landing/media');
        $posterPath = $request->hasFile('poster')
            ? $this->storeUpload($request->file('poster'), 'landing/posters')
            : null;

        try {
            $slide = LandingMedia::create([
                ...$this->metadata($validated),
                'media_path' => $mediaPath,
                'poster_path' => $posterPath,
                'created_by' => $request->user()?->user_id,
            ]);
        } catch (\Throwable $exception) {
            MediaPath::delete($mediaPath);
            MediaPath::delete($posterPath);
            throw $exception;
        }

        UserActivityLogger::log('created landing media slide', $request->user()?->user_id, $request);

        return back()->with('success', "{$slide->title} was added to the landing slider.");
    }

    public function update(Request $request, LandingMedia $landingMedia)
    {
        $validated = $this->validatePayload($request, false);
        if (! $request->hasFile('media') && $validated['type'] !== $landingMedia->type) {
            throw ValidationException::withMessages([
                'media' => 'Upload replacement media when changing the slide type.',
            ]);
        }
        $oldMediaPath = $landingMedia->media_path;
        $oldPosterPath = $landingMedia->poster_path;
        $mediaPath = $request->hasFile('media')
            ? $this->storeUpload($request->file('media'), 'landing/media')
            : $oldMediaPath;
        $posterPath = $request->hasFile('poster')
            ? $this->storeUpload($request->file('poster'), 'landing/posters')
            : ($request->boolean('remove_poster') ? null : $oldPosterPath);

        $landingMedia->update([
            ...$this->metadata($validated),
            'media_path' => $mediaPath,
            'poster_path' => $posterPath,
        ]);

        if ($mediaPath !== $oldMediaPath) MediaPath::delete($oldMediaPath);
        if ($posterPath !== $oldPosterPath) MediaPath::delete($oldPosterPath);

        UserActivityLogger::log('updated landing media slide', $request->user()?->user_id, $request);

        return back()->with('success', "{$landingMedia->title} was updated.");
    }

    public function toggle(Request $request, LandingMedia $landingMedia)
    {
        $landingMedia->update(['is_active' => ! $landingMedia->is_active]);
        UserActivityLogger::log('changed landing media visibility', $request->user()?->user_id, $request);

        return back()->with('success', $landingMedia->is_active ? 'Slide published.' : 'Slide hidden.');
    }

    public function destroy(Request $request, LandingMedia $landingMedia)
    {
        $title = $landingMedia->title;
        $mediaPath = $landingMedia->media_path;
        $posterPath = $landingMedia->poster_path;
        $landingMedia->delete();
        MediaPath::delete($mediaPath);
        MediaPath::delete($posterPath);
        UserActivityLogger::log('deleted landing media slide', $request->user()?->user_id, $request);

        return back()->with('success', "{$title} was removed.");
    }

    private function validatePayload(Request $request, bool $mediaRequired): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['image', 'video'])],
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:320'],
            'media' => [$mediaRequired ? 'required' : 'nullable', 'file', 'max:102400', function ($attribute, $file, $fail) use ($request) {
                $allowed = $request->input('type') === 'video'
                    ? ['video/mp4', 'video/webm', 'video/quicktime']
                    : ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if ($file && ! in_array($file->getMimeType(), $allowed, true)) {
                    $fail('Choose a JPG, PNG, WEBP, GIF, MP4, WEBM, or MOV file matching the selected slide type.');
                }
                if ($file && $request->input('type') === 'image' && $file->getSize() > 8 * 1024 * 1024) {
                    $fail('Landing images may not be larger than 8 MB.');
                }
            }],
            'poster' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:8192'],
            'remove_poster' => ['nullable', 'boolean'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
                if (filled($value) && ! str_starts_with($value, '/') && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail('The button URL must be an absolute URL or a local path beginning with /.');
                }
            }],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function metadata(array $validated): array
    {
        return [
            'type' => $validated['type'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'button_label' => $validated['button_label'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function storeUpload($file, string $directory): string
    {
        $path = $file->storeAs($directory, Str::random(40).'.'.$file->getClientOriginalExtension(), 'supabase');
        abort_if(! $path, 500, 'The media file could not be stored.');

        return MediaPath::storeForDisk($path, 'supabase');
    }
}
