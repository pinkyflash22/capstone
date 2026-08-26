import '../css/routing-panel.css';

const CARD_SELECTOR = '#routeInfoCard, #userRouteInfoCard, [data-pyro-routing-panel]';
const managedCards = new Map();
let launcher = null;
let backdrop = null;
let currentCard = null;

function ensureControls() {
    if (launcher) return;

    launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'pyro-routing-launcher';
    launcher.dataset.pyroRoutingLauncher = 'true';
    launcher.setAttribute('aria-label', 'Open routing panel');
    launcher.setAttribute('aria-expanded', 'false');
    launcher.innerHTML = '<i class="fas fa-route" aria-hidden="true"></i><span>Routes</span><b data-pyro-routing-count hidden>0</b>';

    backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'pyro-routing-backdrop';
    backdrop.hidden = true;
    backdrop.setAttribute('aria-label', 'Close routing panel');

    launcher.addEventListener('click', () => setOpen(!currentCard?.classList.contains('is-pyro-routing-open')));
    backdrop.addEventListener('click', () => setOpen(false));
    document.body.append(backdrop, launcher);
}

function activeRouteCount(card) {
    if (card.id === 'userRouteInfoCard') {
        return card.querySelectorAll('#userActiveRoutesList > div').length || 1;
    }
    if (card.querySelector('#activeRoutesList')) {
        return card.querySelectorAll('#activeRoutesList > div').length || 1;
    }
    return card.dataset.pyroRoutingEmpty === 'true' ? 0 : 1;
}

function updateLauncher() {
    ensureControls();
    const realAvailable = [...managedCards.entries()]
        .filter(([card, state]) => state.available && card.dataset.pyroRoutingEmpty !== 'true')
        .sort((left, right) => right[1].updatedAt - left[1].updatedAt);
    const emptyAvailable = [...managedCards.entries()].filter(([, state]) => state.available);
    const candidate = realAvailable[0] || emptyAvailable[0];
    currentCard = candidate?.[0] || null;

    launcher.hidden = !currentCard;
    const count = currentCard ? activeRouteCount(currentCard) : 0;
    const badge = launcher.querySelector('[data-pyro-routing-count]');
    badge.textContent = String(count);
    badge.hidden = count === 0;
    launcher.classList.toggle('has-active-route', count > 0);
}

function setOpen(open, card = currentCard) {
    if (!card) return;
    managedCards.forEach((state, managedCard) => {
        const isOpen = Boolean(open && managedCard === card);
        managedCard.classList.toggle('is-pyro-routing-open', isOpen);
        managedCard.dataset.pyroRoutingOpen = isOpen ? 'true' : 'false';
        managedCard.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        managedCard.setAttribute('aria-modal', isOpen ? 'true' : 'false');
    });
    launcher?.setAttribute('aria-expanded', open ? 'true' : 'false');
    launcher?.setAttribute('aria-label', open ? 'Close routing panel' : 'Open routing panel');
    if (backdrop) backdrop.hidden = !open;
    document.body.classList.toggle('pyro-routing-panel-open', Boolean(open));
    if (open) window.setTimeout(() => card.querySelector('[data-pyro-routing-close]')?.focus(), 0);
}

function syncAvailability(card) {
    const state = managedCards.get(card);
    if (!state) return;
    state.available = card.style.display !== 'none';
    state.updatedAt = Date.now();
    card.dataset.pyroRoutingAvailable = state.available ? 'true' : 'false';
    if (!state.available && card.classList.contains('is-pyro-routing-open')) setOpen(false, card);
    updateLauncher();
}

function registerCard(card) {
    if (!card || managedCards.has(card)) return;
    ensureControls();
    card.classList.add('pyro-routing-panel');
    card.dataset.pyroRoutingOpen = 'false';
    card.setAttribute('role', 'dialog');
    card.setAttribute('aria-label', card.dataset.pyroRoutingLabel || 'Routing details');

    if (!card.querySelector('[data-pyro-routing-close]')) {
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'pyro-routing-panel__close';
        close.dataset.pyroRoutingClose = 'true';
        close.setAttribute('aria-label', 'Close routing panel');
        close.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        close.addEventListener('click', () => setOpen(false, card));
        card.prepend(close);
    }

    const observer = new MutationObserver(() => syncAvailability(card));
    observer.observe(card, { attributes: true, attributeFilter: ['style'] });
    managedCards.set(card, { available: false, updatedAt: Date.now(), observer });

    // Route cards are commonly created inside map/dashboard stacking contexts.
    // Portaling them beside the backdrop keeps the mobile sheet above that
    // backdrop and makes its controls reachable in installed PWAs.
    if (card.parentElement !== document.body) {
        document.body.appendChild(card);
        card.dataset.pyroRoutingPortaled = 'true';
    }
    syncAvailability(card);
}

function createEmptyPanel(mapElement) {
    if (!mapElement || document.querySelector('[data-pyro-routing-empty="true"]')) return;
    const panel = document.createElement('section');
    panel.dataset.pyroRoutingPanel = 'true';
    panel.dataset.pyroRoutingEmpty = 'true';
    panel.dataset.pyroRoutingLabel = 'Routing connections';
    panel.className = 'pyro-routing-empty-panel';
    panel.innerHTML = `
        <div class="pyro-routing-empty-panel__icon"><i class="fas fa-route" aria-hidden="true"></i></div>
        <h3>Routing connections</h3>
        <p>No emergency route is connected to this map right now. Active and available routes will appear here automatically.</p>
    `;
    document.body.appendChild(panel);
    registerCard(panel);
}

function scan(root = document) {
    if (root.matches?.(CARD_SELECTOR)) registerCard(root);
    root.querySelectorAll?.(CARD_SELECTOR).forEach(registerCard);
}

function init() {
    scan();
    const mapElement = document.querySelector('#map, #usermap, #barangayDashboardMap, [data-map-role]');
    if (mapElement && !document.querySelector(CARD_SELECTOR)) createEmptyPanel(mapElement);
    new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) scan(node);
    }))).observe(document.body, { childList: true, subtree: true });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && currentCard?.classList.contains('is-pyro-routing-open')) setOpen(false);
    });
}

window.PyroRoutingPanel = { open: () => setOpen(true), close: () => setOpen(false), register: registerCard };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
else init();
