<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Spam Markers - PyroSense</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
</head>
<body class="chief-legacy-layout bg-gray-50 min-h-screen flex">
    @include($isProvincial ? 'admin.partials.sidebar' : 'chief.partials.sidebar', ['active' => 'spam-markers'])

    <main class="main-content flex-1 min-w-0">
        <div class="p-4 lg:p-8 max-w-7xl mx-auto">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-red-600">Map moderation</p>
                    <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">Spam markers</h1>
                    <p class="text-sm text-slate-500 mt-1">Review markers hidden from the operational map and restore legitimate reports.</p>
                </div>
                <a href="{{ $isProvincial ? route('admin.maps') : route('station-admin.maps') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Back to map
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <section class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900">Hidden marker queue</h2>
                        <p class="text-xs text-slate-500">{{ $markers->total() }} marker{{ $markers->total() === 1 ? '' : 's' }} currently marked as spam.</p>
                    </div>
                </div>

                @if ($markers->isEmpty())
                    <div class="p-10 text-center">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h3 class="font-bold text-slate-900">No spam markers to review</h3>
                        <p class="text-sm text-slate-500 mt-1">Markers marked as spam will appear here before they can be restored.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Marker</th>
                                    <th class="px-5 py-3">Type</th>
                                    <th class="px-5 py-3">Coordinates</th>
                                    <th class="px-5 py-3">Related records</th>
                                    <th class="px-5 py-3">Hidden</th>
                                    <th class="px-5 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($markers as $marker)
                                    @php
                                        $related = collect([
                                            $marker->incidents->isNotEmpty() ? $marker->incidents->count().' incident'.($marker->incidents->count() === 1 ? '' : 's') : null,
                                            $marker->stations->isNotEmpty() ? $marker->stations->count().' station'.($marker->stations->count() === 1 ? '' : 's') : null,
                                            $marker->hydrants->isNotEmpty() ? $marker->hydrants->count().' hydrant'.($marker->hydrants->count() === 1 ? '' : 's') : null,
                                            $marker->riskAreas->isNotEmpty() ? $marker->riskAreas->count().' risk/hazard record'.($marker->riskAreas->count() === 1 ? '' : 's') : null,
                                        ])->filter()->join(', ');
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-900">#{{ $marker->marker_id }} {{ $marker->title }}</div>
                                            <div class="mt-1 max-w-md truncate text-xs text-slate-500">{{ $marker->description ?: 'No description provided.' }}</div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">
                                                {{ $marker->category ?: 'uncategorized' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs text-slate-600">
                                            {{ number_format((float) $marker->lat, 5) }}, {{ number_format((float) $marker->lng, 5) }}
                                        </td>
                                        <td class="px-5 py-4 text-slate-600">
                                            {{ $related ?: 'Marker only' }}
                                        </td>
                                        <td class="px-5 py-4 text-slate-600">
                                            {{ optional($marker->spammed_at ?? $marker->updated_at)->diffForHumans() }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <form method="POST" action="{{ route('map.markers.restore', $marker->marker_id) }}" onsubmit="return confirm('Restore this marker to the map?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                                    Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $markers->links() }}
                    </div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
