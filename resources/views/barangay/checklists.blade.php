@extends('barangay.layouts.app')

@section('active_nav', 'checklists')
@section('page_title', 'Checklist Center')
@section('page_subtitle', 'Central checklist view for barangay predictions, actions, and compliance tracking')
@section('hero_title', 'Keep every prediction-driven barangay checklist in one operational workspace.')
@section('hero_copy', 'This page is the main central point for AI-generated recommendations, patrol follow-ups, awareness actions, and compliance tracking for your barangay.')

@section('hero_badges')
    <span class="hero-badge"><i class="bi bi-check2-square"></i> Central checklist display</span>
    <span class="hero-badge"><i class="bi bi-flag"></i> Priority-based monitoring</span>
    <span class="hero-badge"><i class="bi bi-hourglass-split"></i> Status and compliance tracking</span>
@endsection

@section('content')
    <style>
        .checklist-center-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .checklist-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .checklist-summary-card,
        .checklist-panel-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .checklist-summary-card {
            padding: 22px;
        }

        .checklist-panel-card {
            padding: 24px;
        }

        .checklist-summary-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .checklist-summary-icon {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff7ed;
            color: #ea580c;
            font-size: 1.15rem;
        }

        .checklist-summary-label {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .checklist-summary-value {
            margin-top: 16px;
            color: #0f172a;
            font-size: clamp(1.85rem, 2.2vw, 2.5rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .checklist-summary-copy {
            margin-top: 10px;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .checklist-panel-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .checklist-panel-heading h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 900;
        }

        .checklist-panel-heading p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.55;
            max-width: 68ch;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
        }

        .checklist-table th,
        .checklist-table td {
            padding: 14px 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid rgba(241, 245, 249, 1);
        }

        .checklist-table th {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .checklist-table td {
            color: #334155;
            font-size: 0.88rem;
        }

        .checklist-table strong {
            color: #0f172a;
            font-weight: 800;
        }

        .checklist-inline-note {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .checklist-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .checklist-lane {
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #f8fafc;
            padding: 18px;
        }

        .checklist-lane h4 {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
        }

        .checklist-lane p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.84rem;
            line-height: 1.5;
        }

        .checklist-lane-list {
            margin-top: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .checklist-lane-item {
            border-radius: 18px;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            padding: 14px;
        }

        .checklist-lane-item strong {
            display: block;
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .checklist-lane-item span {
            display: block;
            margin-top: 6px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        @media (max-width: 1200px) {
            .checklist-summary-grid,
            .checklist-columns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .checklist-summary-grid,
            .checklist-columns {
                grid-template-columns: 1fr;
            }

            .checklist-panel-card,
            .checklist-summary-card {
                padding: 20px;
                border-radius: 24px;
            }

            .checklist-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .mitigation-form-grid {
                grid-template-columns: 1fr;
            }
        }

        .checklist-refresh-note {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
        }

    </style>

    <div class="checklist-refresh-note">
        <i class="bi bi-arrow-repeat"></i>
        <span>Checklist data refreshes automatically every 10 seconds.</span>
    </div>

    <div id="barangayChecklistContent" data-checklist-content-url="{{ route('barangay.checklists.content') }}">
        @include('barangay.partials.checklists-content')
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('barangayChecklistContent');
            const contentUrl = container?.getAttribute('data-checklist-content-url');

            if (!container || !contentUrl) {
                return;
            }

            let isRefreshing = false;

            const refreshChecklistContent = async () => {
                if (isRefreshing) {
                    return;
                }

                isRefreshing = true;

                try {
                    const response = await fetch(contentUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`Checklist refresh failed with status ${response.status}`);
                    }

                    const html = await response.text();
                    container.innerHTML = html;
                } catch (error) {
                    console.error('Unable to refresh barangay checklist content:', error);
                } finally {
                    isRefreshing = false;
                }
            };

            window.setInterval(refreshChecklistContent, 10000);
        });
    </script>
@endpush
