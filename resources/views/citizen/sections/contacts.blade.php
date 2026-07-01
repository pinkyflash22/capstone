@php
    $stationCollection = collect($stations ?? [])->values();
    $stationCount = $stationCollection->count();
@endphp

<style>
    #section-contacts.contacts-page {
        --contacts-red: #c63d26;
        --contacts-red-dark: #9f2f1d;
        --contacts-orange: #e39b17;
        --contacts-ink: #132033;
        --contacts-muted: #64748b;
        width: min(100%, 1240px);
        margin-inline: auto;
        padding: clamp(0.85rem, 1.45vw, 1.35rem);
        padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
        color: var(--contacts-ink);
    }

    #section-contacts .contacts-shell {
        display: grid;
        gap: clamp(1rem, 2vw, 1.35rem);
    }

    #section-contacts .contacts-hero,
    #section-contacts .contacts-card,
    #section-contacts .contacts-panel {
        border: 1px solid rgba(120, 53, 15, 0.11);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 40px rgba(88, 43, 20, 0.075);
    }

    #section-contacts .contacts-hero {
        position: relative;
        overflow: hidden;
        border-color: rgba(198, 61, 38, 0.16);
        border-radius: 2rem;
        background:
            radial-gradient(circle at 92% 12%, rgba(227, 155, 23, 0.24), transparent 16rem),
            linear-gradient(135deg, rgba(255, 248, 237, 0.98), rgba(255, 255, 255, 0.96));
        padding: clamp(1.1rem, 3vw, 1.75rem);
    }

    #section-contacts .contacts-hero-grid {
        position: relative;
        display: grid;
        gap: 1rem;
    }

    #section-contacts .contacts-eyebrow {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        background: rgba(198, 61, 38, 0.1);
        color: var(--contacts-red-dark);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        padding: 0.45rem 0.7rem;
    }

    #section-contacts .contacts-title {
        margin-top: 0.8rem;
        color: var(--contacts-ink);
        font-size: clamp(1.85rem, 5vw, 2.85rem);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.055em;
    }

    #section-contacts .contacts-subtitle {
        max-width: 44rem;
        margin-top: 0.75rem;
        color: var(--contacts-muted);
        font-size: clamp(0.94rem, 2.4vw, 1.03rem);
        line-height: 1.7;
    }

    #section-contacts .contacts-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    #section-contacts .contacts-pill,
    #section-contacts .contacts-primary-btn {
        display: inline-flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 0.62rem;
        border-radius: 1rem;
        font-size: 0.9rem;
        font-weight: 850;
        text-decoration: none;
    }

    #section-contacts .contacts-pill {
        border: 1px solid rgba(245, 158, 11, 0.2);
        background: linear-gradient(135deg, #fffbeb, #fff7ed);
        color: #b45309;
        padding: 0.72rem 1rem;
    }

    #section-contacts .contacts-primary-btn {
        border: 0;
        background: linear-gradient(135deg, var(--contacts-red), var(--contacts-orange));
        color: #fff;
        padding: 0.82rem 1.1rem;
        box-shadow: 0 16px 34px rgba(198, 61, 38, 0.24);
    }

    #section-contacts .contacts-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }

    #section-contacts .contacts-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.6rem;
        padding: 1.1rem;
    }

    #section-contacts .contacts-card::after {
        content: '';
        position: absolute;
        inset: auto -2rem -3rem auto;
        width: 8rem;
        height: 8rem;
        border-radius: 999px;
        background: var(--contacts-glow, rgba(198, 61, 38, 0.08));
    }

    #section-contacts .contacts-card-top {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 0.8rem;
    }

    #section-contacts .contacts-card-label {
        color: var(--contacts-muted);
        font-size: 0.82rem;
        font-weight: 750;
    }

    #section-contacts .contacts-card-value {
        margin-top: 0.42rem;
        color: var(--contacts-ink);
        font-size: clamp(2rem, 6vw, 2.65rem);
        font-weight: 950;
        letter-spacing: -0.055em;
        line-height: 1;
    }

    #section-contacts .contacts-card-icon {
        display: inline-flex;
        width: 3rem;
        height: 3rem;
        flex: 0 0 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 1.05rem;
        background: var(--contacts-icon-bg, #fff7ed);
        color: var(--contacts-icon-color, #c2410c);
        font-size: 1.1rem;
    }

    #section-contacts .contacts-card-copy {
        position: relative;
        z-index: 1;
        margin-top: 0.9rem;
        color: var(--contacts-muted);
        font-size: 0.78rem;
        line-height: 1.55;
    }

    #section-contacts .contacts-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
    }

    #section-contacts .contacts-panel {
        overflow: hidden;
        border-radius: 1.75rem;
    }

    #section-contacts .contacts-panel-head {
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(135deg, rgba(255, 248, 237, 0.74), rgba(255, 255, 255, 0.96));
        padding: 1.1rem;
    }

    #section-contacts .contacts-panel-title {
        margin-top: 0.55rem;
        color: var(--contacts-ink);
        font-size: clamp(1.1rem, 3vw, 1.35rem);
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    #section-contacts .contacts-panel-copy {
        margin-top: 0.35rem;
        color: var(--contacts-muted);
        font-size: 0.88rem;
        line-height: 1.62;
    }

    #section-contacts .contacts-list {
        display: grid;
        gap: 0.75rem;
        padding: 1rem;
    }

    #section-contacts .contact-row,
    #section-contacts .support-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.2rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 237, 0.78));
        padding: 0.95rem;
    }

    #section-contacts .contact-info {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 0.8rem;
    }

    #section-contacts .contact-icon {
        display: inline-flex;
        width: 2.75rem;
        height: 2.75rem;
        flex: 0 0 2.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        background: var(--contact-bg, #fef2f2);
        color: var(--contact-color, #dc2626);
        font-size: 1rem;
    }

    #section-contacts .contact-title {
        color: var(--contacts-ink);
        font-size: 0.95rem;
        font-weight: 850;
        line-height: 1.25;
    }

    #section-contacts .contact-subtitle {
        margin-top: 0.12rem;
        color: var(--contacts-muted);
        font-size: 0.78rem;
        line-height: 1.35;
    }

    #section-contacts .contact-call {
        display: inline-flex;
        min-height: 2.4rem;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--contacts-red), var(--contacts-orange));
        color: #fff;
        padding: 0.55rem 0.82rem;
        font-size: 0.82rem;
        font-weight: 900;
        text-decoration: none;
        box-shadow: 0 10px 22px rgba(198, 61, 38, 0.18);
    }

    #section-contacts .station-list {
        max-height: 30rem;
        overflow-y: auto;
    }

    #section-contacts .support-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
        padding: 1rem;
    }

    #section-contacts .support-card {
        display: grid;
        justify-items: start;
        align-content: start;
    }

    #section-contacts .contacts-empty {
        border: 1px dashed rgba(148, 163, 184, 0.32);
        border-radius: 1.2rem;
        padding: 2.5rem 1rem;
        color: var(--contacts-muted);
        text-align: center;
    }

    #section-contacts .contacts-mobile-bottom-space {
        display: none;
    }

    @media (min-width: 900px) {
        #section-contacts .contacts-hero-grid {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }

        #section-contacts .contacts-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        #section-contacts .contacts-panel--wide {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 520px) {
        #section-contacts.contacts-page {
            width: 100%;
            padding: 1rem;
            padding-bottom: calc(7.5rem + env(safe-area-inset-bottom, 0px));
        }

        #section-contacts .contacts-shell {
            gap: 1rem;
        }

        #section-contacts .contacts-hero {
            border-radius: 1.4rem;
            box-shadow: none;
            padding: 1.35rem 1rem 1.05rem;
        }

        #section-contacts .contacts-title {
            max-width: 17rem;
            font-size: 2rem;
            line-height: 1.05;
        }

        #section-contacts .contacts-subtitle {
            max-width: 18rem;
            font-size: 0.93rem;
            line-height: 1.58;
        }

        #section-contacts .contacts-hero-actions,
        #section-contacts .contacts-primary-btn,
        #section-contacts .contacts-pill {
            width: 100%;
        }

        #section-contacts .contacts-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.55rem;
        }

        #section-contacts .contacts-card {
            min-height: 8.25rem;
            border-radius: 1rem;
            padding: 0.72rem;
            box-shadow: none;
        }

        #section-contacts .contacts-card-top {
            display: grid;
            gap: 0.55rem;
        }

        #section-contacts .contacts-card-icon {
            order: -1;
            width: 2.25rem;
            height: 2.25rem;
            flex-basis: 2.25rem;
            border-radius: 0.78rem;
            font-size: 0.95rem;
        }

        #section-contacts .contacts-card-label {
            font-size: 0.68rem;
            line-height: 1.18;
        }

        #section-contacts .contacts-card-value {
            margin-top: 0.28rem;
            font-size: 1.7rem;
        }

        #section-contacts .contacts-card-copy {
            display: none;
        }

        #section-contacts .contacts-panel {
            border-radius: 1.2rem;
            box-shadow: none;
        }

        #section-contacts .contacts-panel-head {
            background: transparent;
            padding: 1rem 0.95rem;
        }

        #section-contacts .contacts-list,
        #section-contacts .support-grid {
            padding: 0.75rem;
        }

        #section-contacts .contact-row {
            border-radius: 1rem;
            padding: 0.78rem;
        }

        #section-contacts .contact-icon {
            width: 2.45rem;
            height: 2.45rem;
            flex-basis: 2.45rem;
            border-radius: 0.82rem;
        }

        #section-contacts .contact-title {
            font-size: 0.86rem;
        }

        #section-contacts .contact-subtitle {
            font-size: 0.72rem;
        }

        #section-contacts .contact-call {
            min-height: 2.3rem;
            padding: 0.5rem 0.68rem;
            font-size: 0.72rem;
        }

        #section-contacts .support-grid {
            grid-template-columns: 1fr;
        }

        #section-contacts .station-list {
            max-height: none;
            padding-bottom: calc(4rem + env(safe-area-inset-bottom, 0px));
        }

        #section-contacts .contacts-mobile-bottom-space {
            display: block;
            height: calc(2.25rem + env(safe-area-inset-bottom, 0px));
        }
    }
</style>

<div id="section-contacts" class="content-section contacts-page">
    <div class="contacts-shell">
        <section class="contacts-hero">
            <div class="contacts-hero-grid">
                <div>
                    <span class="contacts-eyebrow"><i class="fa-solid fa-phone-volume"></i> Emergency directory</span>
                    <h1 class="contacts-title">Emergency Contacts</h1>
                    <p class="contacts-subtitle">Fast access to fire response, public emergency numbers, and community support contacts.</p>
                </div>

                <div class="contacts-hero-actions">
                    <span class="contacts-pill">
                        <i class="fa-solid fa-fire-extinguisher"></i>
                        <span>{{ $stationCount }} Fire {{ Str::plural('Station', $stationCount) }}</span>
                    </span>
                    <a href="tel:911" class="contacts-primary-btn">
                        <i class="fa-solid fa-phone"></i>
                        <span>Call 911</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="contacts-stats" aria-label="Emergency contacts summary">
            <div class="contacts-card" style="--contacts-glow:rgba(239,68,68,0.12);--contacts-icon-bg:#fef2f2;--contacts-icon-color:#dc2626;">
                <div class="contacts-card-top">
                    <div>
                        <p class="contacts-card-label">Emergency</p>
                        <p class="contacts-card-value">911</p>
                    </div>
                    <div class="contacts-card-icon"><i class="fa-solid fa-phone-volume"></i></div>
                </div>
                <p class="contacts-card-copy">Police, fire, and medical emergency hotline.</p>
            </div>

            <div class="contacts-card" style="--contacts-glow:rgba(245,158,11,0.14);--contacts-icon-bg:#fffbeb;--contacts-icon-color:#d97706;">
                <div class="contacts-card-top">
                    <div>
                        <p class="contacts-card-label">Stations</p>
                        <p class="contacts-card-value">{{ $stationCount }}</p>
                    </div>
                    <div class="contacts-card-icon"><i class="fa-solid fa-building-shield"></i></div>
                </div>
                <p class="contacts-card-copy">Fire stations available from the system records.</p>
            </div>

            <div class="contacts-card" style="--contacts-glow:rgba(5,150,105,0.12);--contacts-icon-bg:#ecfdf5;--contacts-icon-color:#059669;">
                <div class="contacts-card-top">
                    <div>
                        <p class="contacts-card-label">Support</p>
                        <p class="contacts-card-value">3</p>
                    </div>
                    <div class="contacts-card-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                </div>
                <p class="contacts-card-copy">Medical, hazmat, and disaster relief contacts.</p>
            </div>
        </section>

        <div class="contacts-grid">
            <section class="contacts-panel">
                <div class="contacts-panel-head">
                    <span class="contacts-eyebrow"><i class="fa-solid fa-bolt"></i> Primary emergency</span>
                    <h2 class="contacts-panel-title">Quick Dial</h2>
                    <p class="contacts-panel-copy">Use these numbers for immediate or fire-related assistance.</p>
                </div>

                <div class="contacts-list">
                    <div class="contact-row">
                        <div class="contact-info">
                            <span class="contact-icon" style="--contact-bg:#fef2f2;--contact-color:#dc2626;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>
                            <div>
                                <p class="contact-title">Emergency</p>
                                <p class="contact-subtitle">Police, Fire, Medical</p>
                            </div>
                        </div>
                        <a href="tel:911" class="contact-call"><i class="fa-solid fa-phone"></i> 911</a>
                    </div>

                    <div class="contact-row">
                        <div class="contact-info">
                            <span class="contact-icon" style="--contact-bg:#eff6ff;--contact-color:#2563eb;">
                                <i class="fa-solid fa-fire-extinguisher"></i>
                            </span>
                            <div>
                                <p class="contact-title">Non-Emergency</p>
                                <p class="contact-subtitle">Fire Department</p>
                            </div>
                        </div>
                        <a href="tel:555-0123" class="contact-call"><i class="fa-solid fa-phone"></i> 555-0123</a>
                    </div>
                </div>
            </section>

            <section class="contacts-panel">
                <div class="contacts-panel-head">
                    <span class="contacts-eyebrow"><i class="fa-solid fa-building-shield"></i> Fire stations</span>
                    <h2 class="contacts-panel-title">Nearby Stations</h2>
                    <p class="contacts-panel-copy">Call your listed fire stations directly from your phone.</p>
                </div>

                <div class="contacts-list station-list">
                    @forelse ($stationCollection as $st)
                        <div class="contact-row">
                            <div class="contact-info">
                                <span class="contact-icon" style="--contact-bg:#fff7ed;--contact-color:#c2410c;">
                                    <i class="fa-solid fa-fire-flame-curved"></i>
                                </span>
                                <div>
                                    <p class="contact-title">{{ $st->station_name }}</p>
                                    <p class="contact-subtitle">{{ $st->address_id ?? 'Station address not available' }}</p>
                                </div>
                            </div>
                            @if($st->contact_number)
                                <a href="tel:{{ $st->contact_number }}" class="contact-call">
                                    <i class="fa-solid fa-phone"></i>
                                    {{ $st->contact_number }}
                                </a>
                            @else
                                <span class="contact-subtitle">No number</span>
                            @endif
                        </div>
                    @empty
                        <div class="contacts-empty">
                            <i class="fa-solid fa-building-shield text-2xl mb-2"></i>
                            <p>No fire station contacts available.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="contacts-panel contacts-panel--wide">
                <div class="contacts-panel-head">
                    <span class="contacts-eyebrow"><i class="fa-solid fa-people-group"></i> Community support</span>
                    <h2 class="contacts-panel-title">Support Contacts</h2>
                    <p class="contacts-panel-copy">Additional emergency support numbers for medical, hazmat, and disaster response.</p>
                </div>

                <div class="support-grid">
                    <div class="support-card">
                        <div class="contact-info">
                            <span class="contact-icon" style="--contact-bg:#eff6ff;--contact-color:#2563eb;">
                                <i class="fa-solid fa-hospital"></i>
                            </span>
                            <div>
                                <p class="contact-title">Medical Center</p>
                                <p class="contact-subtitle">24/7 Emergency</p>
                            </div>
                        </div>
                        <a href="tel:555-0200" class="contact-call"><i class="fa-solid fa-phone"></i> 555-0200</a>
                    </div>

                    <div class="support-card">
                        <div class="contact-info">
                            <span class="contact-icon" style="--contact-bg:#fef2f2;--contact-color:#dc2626;">
                                <i class="fa-solid fa-truck-droplet"></i>
                            </span>
                            <div>
                                <p class="contact-title">Hazmat Team</p>
                                <p class="contact-subtitle">Chemical Emergencies</p>
                            </div>
                        </div>
                        <a href="tel:555-0300" class="contact-call"><i class="fa-solid fa-phone"></i> 555-0300</a>
                    </div>

                    <div class="support-card">
                        <div class="contact-info">
                            <span class="contact-icon" style="--contact-bg:#f5f3ff;--contact-color:#7c3aed;">
                                <i class="fa-solid fa-hand-holding-heart"></i>
                            </span>
                            <div>
                                <p class="contact-title">Disaster Relief</p>
                                <p class="contact-subtitle">Red Cross</p>
                            </div>
                        </div>
                        <a href="tel:555-0400" class="contact-call"><i class="fa-solid fa-phone"></i> 555-0400</a>
                    </div>
                </div>
            </section>
        </div>

        <div class="contacts-mobile-bottom-space" aria-hidden="true"></div>
    </div>
</div>
