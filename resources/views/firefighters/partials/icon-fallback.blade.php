<style>
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:FILL@1&display=swap');

.ff-google-symbol {
    font-family: "Material Symbols Rounded" !important;
    font-weight: normal;
    font-style: normal;
    font-size: 1.15em;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    direction: ltr;
    -webkit-font-smoothing: antialiased;
    font-variation-settings:
        "FILL" 1,
        "wght" 500,
        "GRAD" 0,
        "opsz" 24;
}
</style>

<script>
function getFirefighterMaterialSymbol(iconName) {
    const icons = {
        'fa-address-card': 'badge',
        'fa-align-left': 'notes',
        'fa-arrow-down-wide-short': 'sort',
        'fa-arrow-right': 'arrow_forward',
        'fa-arrow-right-from-bracket': 'logout',
        'fa-arrow-up': 'north',
        'fa-award': 'military_tech',
        'fa-band-aid': 'healing',
        'fa-bars': 'menu',
        'fa-bell': 'notifications',
        'fa-bolt': 'bolt',
        'fa-boxes': 'inventory_2',
        'fa-box-open': 'inventory',
        'fa-briefcase': 'work',
        'fa-building': 'apartment',
        'fa-bullhorn': 'campaign',
        'fa-calendar': 'calendar_month',
        'fa-calendar-check': 'event_available',
        'fa-calendar-week': 'calendar_view_week',
        'fa-camera': 'photo_camera',
        'fa-car': 'directions_car',
        'fa-certificate': 'workspace_premium',
        'fa-chalkboard-user': 'co_present',
        'fa-charging-station': 'ev_station',
        'fa-chart-line': 'show_chart',
        'fa-check': 'check',
        'fa-check-circle': 'check_circle',
        'fa-check-double': 'done_all',
        'fa-chevron-down': 'expand_more',
        'fa-chevron-left': 'chevron_left',
        'fa-chevron-right': 'chevron_right',
        'fa-circle': 'radio_button_checked',
        'fa-circle-check': 'task_alt',
        'fa-circle-info': 'info',
        'fa-city': 'location_city',
        'fa-clipboard-check': 'fact_check',
        'fa-clipboard-list': 'assignment',
        'fa-clock': 'schedule',
        'fa-clock-rotate-left': 'history',
        'fa-coins': 'payments',
        'fa-comment': 'chat_bubble',
        'fa-compass': 'explore',
        'fa-copy': 'content_copy',
        'fa-desktop': 'desktop_windows',
        'fa-exclamation': 'priority_high',
        'fa-exclamation-circle': 'error',
        'fa-exclamation-triangle': 'warning',
        'fa-expand': 'open_in_full',
        'fa-eye': 'visibility',
        'fa-file-alt': 'description',
        'fa-file-circle-check': 'task',
        'fa-file-invoice': 'receipt_long',
        'fa-fire': 'local_fire_department',
        'fa-fire-extinguisher': 'fire_extinguisher',
        'fa-fire-station': 'local_fire_department',
        'fa-flag': 'flag',
        'fa-folder': 'folder',
        'fa-folder-check': 'folder_open',
        'fa-folder-closed': 'folder',
        'fa-folder-open': 'folder_open',
        'fa-gauge-high': 'speed',
        'fa-globe': 'language',
        'fa-graduation-cap': 'school',
        'fa-grip-lines': 'drag_indicator',
        'fa-hand-holding-heart': 'volunteer_activism',
        'fa-hashtag': 'tag',
        'fa-heartbeat': 'monitor_heart',
        'fa-history': 'history',
        'fa-home': 'home',
        'fa-hourglass-half': 'hourglass_bottom',
        'fa-id-badge': 'badge',
        'fa-image': 'image',
        'fa-industry': 'factory',
        'fa-kit-medical': 'medical_services',
        'fa-ladder-water': 'construction',
        'fa-layer-group': 'layers',
        'fa-life-ring': 'health_and_safety',
        'fa-list': 'list',
        'fa-list-check': 'checklist',
        'fa-location-crosshairs': 'my_location',
        'fa-location-dot': 'location_on',
        'fa-location-marker-alt': 'location_on',
        'fa-magnifying-glass': 'search',
        'fa-map': 'map',
        'fa-map-location-dot': 'location_on',
        'fa-map-marked-alt': 'map',
        'fa-map-marker-alt': 'location_on',
        'fa-mask': 'masks',
        'fa-message': 'chat',
        'fa-mug-hot': 'coffee',
        'fa-note-sticky': 'sticky_note_2',
        'fa-paper-plane': 'send',
        'fa-pen': 'edit',
        'fa-pen-to-square': 'edit_square',
        'fa-people-group': 'groups',
        'fa-person-walking': 'directions_walk',
        'fa-phone': 'call',
        'fa-phone-alt': 'call',
        'fa-phone-volume': 'call',
        'fa-plug': 'power',
        'fa-plus': 'add',
        'fa-plus-circle': 'add_circle',
        'fa-radio': 'radio',
        'fa-radiation': 'warning',
        'fa-road': 'route',
        'fa-rotate': 'refresh',
        'fa-rotate-right': 'refresh',
        'fa-route': 'route',
        'fa-search': 'search',
        'fa-shield': 'shield',
        'fa-sign-out-alt': 'logout',
        'fa-skull': 'warning',
        'fa-spinner': 'progress_activity',
        'fa-square-poll-vertical': 'poll',
        'fa-stop': 'stop',
        'fa-stopwatch': 'timer',
        'fa-tag': 'sell',
        'fa-tachometer-alt': 'dashboard',
        'fa-thumbs-up': 'thumb_up',
        'fa-times': 'close',
        'fa-toolbox': 'inventory',
        'fa-tools': 'build',
        'fa-trash': 'delete',
        'fa-trash-can': 'delete',
        'fa-tree': 'park',
        'fa-triangle-exclamation': 'warning',
        'fa-truck': 'local_shipping',
        'fa-truck-fast': 'local_shipping',
        'fa-truck-fire': 'local_shipping',
        'fa-truck-medical': 'emergency',
        'fa-upload': 'upload',
        'fa-user': 'person',
        'fa-user-astronaut': 'person',
        'fa-user-check': 'verified_user',
        'fa-user-secret': 'person',
        'fa-user-shield': 'admin_panel_settings',
        'fa-users': 'groups',
        'fa-users-slash': 'group_off',
        'fa-video': 'videocam',
        'fa-water': 'water_drop',
        'fa-whatsapp': 'chat',
        'fa-xmark': 'close'
    };

    return icons[iconName] || null;
}

function getFirefighterInlineIconSvg(iconName) {
    const icons = {
        'fa-address-card': '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" fill="none"/><circle cx="8" cy="11" r="2" fill="none"/><path d="M5.5 16a3.5 3.5 0 0 1 5 0M13 10h5M13 14h5"/></svg>',
        'fa-align-left': '<svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h10M4 18h16"/></svg>',
        'fa-arrow-down-wide-short': '<svg viewBox="0 0 24 24"><path d="M4 7h10M4 11h7M4 15h4"/><path d="M16 6v10"/><path d="M13 13l3 3 3-3"/></svg>',
        'fa-arrow-right': '<svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>',
        'fa-arrow-right-from-bracket': '<svg viewBox="0 0 24 24"><path d="M14 7l5 5-5 5"/><path d="M19 12H9"/><path d="M11 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h5"/></svg>',
        'fa-arrow-up': '<svg viewBox="0 0 24 24"><path d="M12 19V5"/><path d="M6 11l6-6 6 6"/></svg>',
        'fa-award': '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" fill="none"/><path d="M9 12l-1 7 4-2 4 2-1-7"/></svg>',
        'fa-band-aid': '<svg viewBox="0 0 24 24"><path d="M8 8l8 8"/><rect x="5" y="9" width="14" height="6" rx="3" transform="rotate(45 12 12)" fill="none"/><path d="M10 12h.01M12 14h.01M14 16h.01"/></svg>',
        'fa-bars': '<svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
        'fa-bell': '<svg viewBox="0 0 24 24"><path d="M15 18H9"/><path d="M18 16H6l1.5-2V10a4.5 4.5 0 1 1 9 0v4z"/></svg>',
        'fa-bolt': '<svg viewBox="0 0 24 24"><path d="M13 3L6 13h5l-1 8 8-11h-5z"/></svg>',
        'fa-boxes': '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="7" height="7" fill="none"/><rect x="13" y="4" width="7" height="7" fill="none"/><rect x="8.5" y="13" width="7" height="7" fill="none"/></svg>',
        'fa-box-open': '<svg viewBox="0 0 24 24"><path d="M4 10l8-4 8 4"/><path d="M5 10v8h14v-8"/><path d="M12 6v12"/></svg>',
        'fa-briefcase': '<svg viewBox="0 0 24 24"><rect x="4" y="8" width="16" height="11" rx="2" fill="none"/><path d="M9 8V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M4 12h16"/></svg>',
        'fa-building': '<svg viewBox="0 0 24 24"><path d="M5 20V5l7-2v17"/><path d="M12 20V9h7v11"/><path d="M8 8h1M8 11h1M8 14h1M15 12h1M15 15h1"/></svg>',
        'fa-bullhorn': '<svg viewBox="0 0 24 24"><path d="M4 11v2"/><path d="M6 10h3l7-4v12l-7-4H6z"/><path d="M9 14l1.5 4"/></svg>',
        'fa-calendar': '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2" fill="none"/><path d="M8 3v4M16 3v4M4 9h16"/></svg>',
        'fa-calendar-check': '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2" fill="none"/><path d="M8 3v4M16 3v4M4 9h16"/><path d="M9 14l2 2 4-4"/></svg>',
        'fa-calendar-week': '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2" fill="none"/><path d="M8 3v4M16 3v4M4 9h16M7 12h10M7 15h6"/></svg>',
        'fa-camera': '<svg viewBox="0 0 24 24"><path d="M5 8h14v10H5z"/><path d="M9 8l1.5-2h3L15 8"/><circle cx="12" cy="13" r="3" fill="none"/></svg>',
        'fa-car': '<svg viewBox="0 0 24 24"><path d="M5 16l1.5-4h11L19 16"/><path d="M4 16h16v3H4z"/><circle cx="7" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>',
        'fa-certificate': '<svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="4" fill="none"/><path d="M10 13l-2 7 4-2 4 2-2-7"/></svg>',
        'fa-chalkboard-user': '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="14" height="10" rx="1" fill="none"/><path d="M6 18h8M10 14v4"/><circle cx="19" cy="9" r="2" fill="none"/><path d="M16.5 15a3 3 0 0 1 5 0"/></svg>',
        'fa-charging-station': '<svg viewBox="0 0 24 24"><rect x="6" y="4" width="8" height="16" rx="1" fill="none"/><path d="M10 8l-2 4h3l-1 4"/><path d="M17 8h1a2 2 0 0 1 2 2v4"/></svg>',
        'fa-chart-line': '<svg viewBox="0 0 24 24"><path d="M4 19h16"/><path d="M5 15l4-4 4 2 6-6"/></svg>',
        'fa-check': '<svg viewBox="0 0 24 24"><path d="M5 12l4 4L19 6"/></svg>',
        'fa-check-circle': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M8 12l2.5 2.5L16 9"/></svg>',
        'fa-check-double': '<svg viewBox="0 0 24 24"><path d="M3 12l3 3 5-5"/><path d="M10 12l3 3 8-8"/></svg>',
        'fa-chevron-down': '<svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>',
        'fa-chevron-left': '<svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></svg>',
        'fa-chevron-right': '<svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>',
        'fa-circle': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7" fill="currentColor" stroke="none"/></svg>',
        'fa-circle-check': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M8 12l2.5 2.5L16 9"/></svg>',
        'fa-circle-info': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M12 10v5"/><circle cx="12" cy="7.5" r="0.8" fill="currentColor" stroke="none"/></svg>',
        'fa-city': '<svg viewBox="0 0 24 24"><path d="M4 20V9l5-2v13"/><path d="M9 20V5l6-2v17"/><path d="M15 20v-8l5-2v10"/></svg>',
        'fa-clipboard-list': '<svg viewBox="0 0 24 24"><rect x="6" y="4" width="12" height="16" rx="2" fill="none"/><path d="M9 4.5h6M9 9h6M9 13h6M9 17h4"/></svg>',
        'fa-clock': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M12 7v5l3 2"/></svg>',
        'fa-clock-rotate-left': '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 5v4h4"/><path d="M12 8v5l3 2"/></svg>',
        'fa-coins': '<svg viewBox="0 0 24 24"><ellipse cx="12" cy="7" rx="6" ry="2.5" fill="none"/><path d="M6 7v6c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5V7"/><path d="M6 11c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5"/></svg>',
        'fa-comment': '<svg viewBox="0 0 24 24"><path d="M5 6h14v10H9l-4 3z"/></svg>',
        'fa-compass': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M14.5 9.5l-2 5-5 2 2-5z"/></svg>',
        'fa-copy': '<svg viewBox="0 0 24 24"><rect x="8" y="8" width="11" height="12" rx="2" fill="none"/><path d="M5 16V6a2 2 0 0 1 2-2h8"/></svg>',
        'fa-exclamation': '<svg viewBox="0 0 24 24"><path d="M12 6v8"/><circle cx="12" cy="17.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-exclamation-circle': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M12 7v6"/><circle cx="12" cy="16.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-exclamation-triangle': '<svg viewBox="0 0 24 24"><path d="M12 4l8 15H4z" fill="none"/><path d="M12 9v4"/><circle cx="12" cy="16.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-expand': '<svg viewBox="0 0 24 24"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M21 21h-5v-5"/></svg>',
        'fa-eye': '<svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5z"/><circle cx="12" cy="12" r="2.5" fill="none"/></svg>',
        'fa-file-alt': '<svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M9 11h6M9 15h6"/></svg>',
        'fa-fire': '<svg viewBox="0 0 24 24"><path d="M12 4c1.5 3-1 4.5 1 7 1.4-1 2.5-2.7 2.5-4.5 3 2 5 5.1 5 8.4A8.5 8.5 0 0 1 4 14c0-2.9 1.5-5.5 4-7 .2 2 1 3 2.5 4 .2-2.4.6-4.3 1.5-7z"/></svg>',
        'fa-fire-extinguisher': '<svg viewBox="0 0 24 24"><path d="M10 6h5"/><path d="M15 6a2 2 0 0 1 2 2v1"/><path d="M9 9h6v10H9z"/><path d="M11 4h2v2h-2z"/><path d="M17 9h1.5"/></svg>',
        'fa-fire-station': '<svg viewBox="0 0 24 24"><path d="M5 20V9l7-5 7 5v11"/><path d="M9 20v-5h6v5"/><path d="M10 10h4"/></svg>',
        'fa-flag': '<svg viewBox="0 0 24 24"><path d="M6 3v18"/><path d="M6 4h10l-2 4 2 4H6"/></svg>',
        'fa-folder': '<svg viewBox="0 0 24 24"><path d="M3 7h6l2 2h10v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
        'fa-gauge-high': '<svg viewBox="0 0 24 24"><path d="M5 16a7 7 0 1 1 14 0"/><path d="M12 12l4-3"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-graduation-cap': '<svg viewBox="0 0 24 24"><path d="M3 10l9-4 9 4-9 4z"/><path d="M7 12v4c2 2 8 2 10 0v-4"/><path d="M21 10v5"/></svg>',
        'fa-hand-holding-heart': '<svg viewBox="0 0 24 24"><path d="M3 15h5l2 2h6a2 2 0 0 0 0-4h-4"/><path d="M11 9l1-1a2.5 2.5 0 0 1 4 3l-4 4-4-4a2.5 2.5 0 0 1 4-3z"/></svg>',
        'fa-hashtag': '<svg viewBox="0 0 24 24"><path d="M8 4L6 20M16 4l-2 16M4 9h16M3 15h16"/></svg>',
        'fa-heartbeat': '<svg viewBox="0 0 24 24"><path d="M3 13h4l2-4 3 8 2-4h7"/></svg>',
        'fa-history': '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 5v4h4"/><path d="M12 8v5l3 2"/></svg>',
        'fa-home': '<svg viewBox="0 0 24 24"><path d="M4 11l8-6 8 6"/><path d="M6 10v10h12V10"/><path d="M10 20v-6h4v6"/></svg>',
        'fa-image': '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2" fill="none"/><circle cx="9" cy="10" r="1.5" fill="currentColor" stroke="none"/><path d="M20 16l-5-5-6 6-2-2-3 3"/></svg>',
        'fa-industry': '<svg viewBox="0 0 24 24"><path d="M3 20V8l6 3V8l6 3V5l6 3v12z"/><path d="M7 20v-4h3v4M14 20v-4h3v4"/></svg>',
        'fa-kit-medical': '<svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="12" rx="2" fill="none"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M12 10v6M9 13h6"/></svg>',
        'fa-ladder-water': '<svg viewBox="0 0 24 24"><path d="M6 19L16 5"/><path d="M10 19L20 5"/><path d="M8 16h4M11 12h4M14 8h4"/><path d="M4 19h4"/></svg>',
        'fa-layer-group': '<svg viewBox="0 0 24 24"><path d="M12 5l8 4-8 4-8-4z"/><path d="M4 13l8 4 8-4"/><path d="M4 17l8 4 8-4"/></svg>',
        'fa-list': '<svg viewBox="0 0 24 24"><path d="M8 7h12M8 12h12M8 17h12"/><circle cx="4.5" cy="7" r="1" fill="currentColor" stroke="none"/><circle cx="4.5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="4.5" cy="17" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-location-crosshairs': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" fill="none"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>',
        'fa-location-dot': '<svg viewBox="0 0 24 24"><path d="M12 21s6-5.5 6-11a6 6 0 1 0-12 0c0 5.5 6 11 6 11z"/><circle cx="12" cy="10" r="2.2" fill="none"/></svg>',
        'fa-magnifying-glass': '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" fill="none"/><path d="M20 20l-4.5-4.5"/></svg>',
        'fa-map': '<svg viewBox="0 0 24 24"><path d="M4 6l5-2 6 2 5-2v14l-5 2-6-2-5 2z"/><path d="M9 4v14M15 6v14"/></svg>',
        'fa-map-location-dot': '<svg viewBox="0 0 24 24"><path d="M4 6l5-2 6 2 5-2v14l-5 2-6-2-5 2z"/><path d="M9 4v14M15 6v14"/><path d="M15 14s2.5-2.3 2.5-4.6A2.5 2.5 0 1 0 12.5 9.4c0 2.3 2.5 4.6 2.5 4.6z"/></svg>',
        'fa-map-marker-alt': '<svg viewBox="0 0 24 24"><path d="M12 21s6-5.5 6-11a6 6 0 1 0-12 0c0 5.5 6 11 6 11z"/><circle cx="12" cy="10" r="2.2" fill="none"/></svg>',
        'fa-mask': '<svg viewBox="0 0 24 24"><path d="M5 8c0 6 3 10 7 10s7-4 7-10"/><path d="M7 10h2M15 10h2"/><path d="M9 14h6"/><path d="M6 8l-2-2M18 8l2-2"/></svg>',
        'fa-message': '<svg viewBox="0 0 24 24"><path d="M5 6h14v10H9l-4 3z"/></svg>',
        'fa-note-sticky': '<svg viewBox="0 0 24 24"><path d="M6 4h12v10l-4 4H6z"/><path d="M14 18v-4h4"/></svg>',
        'fa-paper-plane': '<svg viewBox="0 0 24 24"><path d="M3 11l18-8-6 18-2-7z"/><path d="M13 14l-2-2"/></svg>',
        'fa-pen': '<svg viewBox="0 0 24 24"><path d="M4 20l4-1 10-10-3-3L5 16z"/><path d="M14 6l3 3"/></svg>',
        'fa-pen-to-square': '<svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16z"/><path d="M14 6l4 4"/><path d="M4 4h8"/></svg>',
        'fa-people-group': '<svg viewBox="0 0 24 24"><circle cx="8" cy="9" r="2" fill="none"/><circle cx="16" cy="9" r="2" fill="none"/><circle cx="12" cy="7" r="2" fill="none"/><path d="M4 18a4 4 0 0 1 8 0M12 18a4 4 0 0 1 8 0M8 16a4 4 0 0 1 8 0"/></svg>',
        'fa-phone': '<svg viewBox="0 0 24 24"><path d="M7 4h3l1 4-2 2a14 14 0 0 0 5 5l2-2 4 1v3a2 2 0 0 1-2 2A15 15 0 0 1 5 6a2 2 0 0 1 2-2z"/></svg>',
        'fa-phone-volume': '<svg viewBox="0 0 24 24"><path d="M7 4h3l1 4-2 2a14 14 0 0 0 5 5l2-2 4 1v3a2 2 0 0 1-2 2A15 15 0 0 1 5 6a2 2 0 0 1 2-2z"/><path d="M16 7a4 4 0 0 1 0 6"/><path d="M18 5a7 7 0 0 1 0 10"/></svg>',
        'fa-plug': '<svg viewBox="0 0 24 24"><path d="M9 3v6M15 3v6"/><path d="M7 9h10v2a5 5 0 0 1-5 5 5 5 0 0 1-5-5z"/><path d="M12 16v5"/></svg>',
        'fa-plus': '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>',
        'fa-plus-circle': '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none"/><path d="M12 8v8M8 12h8"/></svg>',
        'fa-radio': '<svg viewBox="0 0 24 24"><rect x="4" y="7" width="16" height="11" rx="2" fill="none"/><circle cx="9" cy="12.5" r="2" fill="none"/><path d="M7 4l10 3M14 10h3M14 13h3"/></svg>',
        'fa-road': '<svg viewBox="0 0 24 24"><path d="M9 3L7 21M15 3l2 18"/><path d="M4 21h16"/><path d="M12 7v2M12 12v2M12 17v2"/></svg>',
        'fa-rotate': '<svg viewBox="0 0 24 24"><path d="M20 7v5h-5"/><path d="M7 17a7 7 0 0 0 11-2"/><path d="M17 7A7 7 0 0 0 6 9"/></svg>',
        'fa-rotate-right': '<svg viewBox="0 0 24 24"><path d="M20 7v5h-5"/><path d="M7 17a7 7 0 0 0 11-2"/><path d="M17 7A7 7 0 0 0 6 9"/></svg>',
        'fa-route': '<svg viewBox="0 0 24 24"><circle cx="6" cy="18" r="2" fill="currentColor" stroke="none"/><circle cx="18" cy="6" r="2" fill="currentColor" stroke="none"/><path d="M8 18c5 0 2-6 7-6h1"/></svg>',
        'fa-search': '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" fill="none"/><path d="M20 20l-4.5-4.5"/></svg>',
        'fa-sign-out-alt': '<svg viewBox="0 0 24 24"><path d="M14 7l5 5-5 5"/><path d="M19 12H9"/><path d="M11 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h5"/></svg>',
        'fa-skull': '<svg viewBox="0 0 24 24"><path d="M8 18v2M12 18v2M16 18v2"/><path d="M8 16c-2-1-3-3-3-6a7 7 0 1 1 14 0c0 3-1 5-3 6z"/><circle cx="9" cy="11" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="11" r="1" fill="currentColor" stroke="none"/><path d="M11 14h2"/></svg>',
        'fa-spinner': '<svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"/></svg>',
        'fa-square-poll-vertical': '<svg viewBox="0 0 24 24"><rect x="5" y="5" width="14" height="14" rx="2" fill="none"/><path d="M9 15V11M12 15V8M15 15v-5"/></svg>',
        'fa-tag': '<svg viewBox="0 0 24 24"><path d="M4 12l8-8h6l2 2v6l-8 8z"/><circle cx="15.5" cy="8.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-tachometer-alt': '<svg viewBox="0 0 24 24"><path d="M5 16a7 7 0 1 1 14 0"/><path d="M12 12l4-3"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-times': '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>',
        'fa-tools': '<svg viewBox="0 0 24 24"><path d="M14 6l4 4"/><path d="M13 7l5-5"/><path d="M4 20l8-8"/><path d="M7 11l6 6"/></svg>',
        'fa-toolbox': '<svg viewBox="0 0 24 24"><path d="M9 7V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M4 8h16v10H4z"/><path d="M4 12h16"/><path d="M11 12v2h2v-2"/></svg>',
        'fa-trash': '<svg viewBox="0 0 24 24"><path d="M5 7h14"/><path d="M9 7V5h6v2"/><path d="M7 7l1 12h8l1-12"/><path d="M10 11v5M14 11v5"/></svg>',
        'fa-trash-can': '<svg viewBox="0 0 24 24"><path d="M5 7h14"/><path d="M9 7V5h6v2"/><path d="M7 7l1 12h8l1-12"/><path d="M10 11v5M14 11v5"/></svg>',
        'fa-tree': '<svg viewBox="0 0 24 24"><path d="M12 4l4 5h-2l3 4h-3l2 3H8l2-3H7l3-4H8z"/><path d="M12 16v4"/></svg>',
        'fa-triangle-exclamation': '<svg viewBox="0 0 24 24"><path d="M12 4l8 15H4z" fill="none"/><path d="M12 9v4"/><circle cx="12" cy="16.5" r="1" fill="currentColor" stroke="none"/></svg>',
        'fa-truck': '<svg viewBox="0 0 24 24"><path d="M3 7h10v8H3z"/><path d="M13 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="2" fill="none"/><circle cx="17" cy="17" r="2" fill="none"/></svg>',
        'fa-truck-fast': '<svg viewBox="0 0 24 24"><path d="M3 8h10v7H3z"/><path d="M13 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="2" fill="none"/><circle cx="17" cy="17" r="2" fill="none"/><path d="M1 10h4M1 13h3"/></svg>',
        'fa-truck-medical': '<svg viewBox="0 0 24 24"><path d="M3 7h10v8H3z"/><path d="M13 10h4l3 3v2h-7z"/><path d="M8 9v4M6 11h4"/><circle cx="7" cy="17" r="2" fill="none"/><circle cx="17" cy="17" r="2" fill="none"/></svg>',
        'fa-upload': '<svg viewBox="0 0 24 24"><path d="M12 16V5"/><path d="M7 10l5-5 5 5"/><path d="M5 19h14"/></svg>',
        'fa-user': '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" fill="none"/><path d="M6 19a6 6 0 0 1 12 0"/></svg>',
        'fa-user-astronaut': '<svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="4" fill="none"/><path d="M8 17a4 4 0 0 1 8 0"/><path d="M8 5l-2-2M16 5l2-2"/></svg>',
        'fa-users': '<svg viewBox="0 0 24 24"><circle cx="9" cy="9" r="2.5" fill="none"/><circle cx="16.5" cy="10" r="2" fill="none"/><path d="M4.5 18a4.5 4.5 0 0 1 9 0"/><path d="M14 18a3.5 3.5 0 0 1 6 0"/></svg>',
        'fa-users-slash': '<svg viewBox="0 0 24 24"><circle cx="9" cy="9" r="2.5" fill="none"/><path d="M4.5 18a4.5 4.5 0 0 1 4.5-4.5"/><circle cx="16.5" cy="10" r="2" fill="none"/><path d="M14 18a3.5 3.5 0 0 1 3.5-3.5"/><path d="M4 4l16 16"/></svg>',
        'fa-user-secret': '<svg viewBox="0 0 24 24"><path d="M7 9c2-3 8-3 10 0"/><circle cx="12" cy="10" r="2.5" fill="none"/><path d="M6 19a6 6 0 0 1 12 0"/><path d="M9 8l-1-2M15 8l1-2"/></svg>',
        'fa-video': '<svg viewBox="0 0 24 24"><rect x="4" y="7" width="11" height="10" rx="2" fill="none"/><path d="M15 10l5-3v10l-5-3"/></svg>',
        'fa-water': '<svg viewBox="0 0 24 24"><path d="M12 4c3 4 5 6.5 5 9a5 5 0 1 1-10 0c0-2.5 2-5 5-9z"/></svg>',
        'fa-whatsapp': '<svg viewBox="0 0 24 24"><path d="M20 11.5A8.5 8.5 0 0 1 7 18l-3 1 1-3A8.5 8.5 0 1 1 20 11.5z"/><path d="M9 8c0 4 4 7 7 7"/><path d="M10 11l1-1M14 15l1-1"/></svg>',
        'fa-xmark': '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>'
    };

    return icons[iconName] || '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" fill="none"/></svg>';
}

function replaceFirefighterFaIcons(root = document) {
    const icons = root.querySelectorAll('i[class*="fa-"]:not([data-inline-fa-processed])');

    icons.forEach((icon) => {
        if (
            icon.closest('.custom-fa-icon') ||
            icon.closest('.leaflet-marker-icon') ||
            icon.closest('.leaflet-div-icon')
        ) {
            icon.setAttribute('data-inline-fa-processed', '1');
            return;
        }

        const ignored = ['fa-solid', 'fa-regular', 'fa-brands', 'fa-spin', 'fa-pulse', 'fa-fw'];
        const iconClass = Array.from(icon.classList).find((cls) => cls.startsWith('fa-') && !ignored.includes(cls));
        const materialSymbol = iconClass ? getFirefighterMaterialSymbol(iconClass) : null;
        const svg = materialSymbol ? null : (iconClass ? getFirefighterInlineIconSvg(iconClass) : null);

        if (!materialSymbol && !svg) {
            icon.setAttribute('data-inline-fa-processed', '1');
            return;
        }

        const replacement = document.createElement('span');
        replacement.className = Array.from(icon.classList)
            .filter((cls) => !['fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands', 'fa-pulse', 'fa-spin', iconClass].includes(cls))
            .join(' ');
        replacement.className = `${replacement.className} inline-fa-icon`.trim();

        if (icon.classList.contains('fa-spinner') || icon.classList.contains('fa-rotate-right') || icon.classList.contains('fa-rotate')) {
            replacement.classList.add('icon-spin');
        }

        if (icon.classList.contains('fa-pulse')) {
            replacement.classList.add('icon-pulse');
        }

        replacement.setAttribute('aria-hidden', icon.getAttribute('aria-hidden') || 'true');

        if (materialSymbol) {
            replacement.className = `${replacement.className} ff-google-symbol`.trim();
            replacement.textContent = materialSymbol;
        } else {
            replacement.innerHTML = svg;
        }

        const style = icon.getAttribute('style');
        if (style) {
            replacement.setAttribute('style', style);
        }

        icon.setAttribute('data-inline-fa-processed', '1');
        icon.replaceWith(replacement);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    replaceFirefighterFaIcons();

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) {
                    return;
                }

                if (node.matches && node.matches('i[class*="fa-"]')) {
                    replaceFirefighterFaIcons(node.parentNode || document);
                    return;
                }

                if (node.querySelectorAll) {
                    replaceFirefighterFaIcons(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});
</script>
