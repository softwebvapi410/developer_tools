// Page router, side panel, breadcrumb, domain sync

// ═══════════════════════════════════════
//  PAGE ROUTER + SIDE PANEL
// ═══════════════════════════════════════

const PAGE_META = {
    audit:       { label: 'Analyze Site',         icon: 'search' },
    dns:         { label: 'DNS Lookup',            icon: 'terminal' },
    mx:          { label: 'Check MX / Email',      icon: 'mail-check' },
    whois:       { label: 'WHOIS Lookup',          icon: 'id-card' },
    browser:     { label: 'Browser & UA',          icon: 'monitor' },
    ip:          { label: 'IP Address Info',       icon: 'wifi' },
    downloader:  { label: 'Website Downloader',    icon: 'download' },
    caseconvert: { label: 'Text Case Converter',   icon: 'case-sensitive' },
    qr:          { label: 'QR Code Generator',     icon: 'qr-code' },
    codeformat:  { label: 'Code Formatter',          icon: 'code-2' },
    numgen:      { label: 'Number Generator',        icon: 'hash' },
    colorgen:    { label: 'Colour Generator',        icon: 'palette' },
};

let currentPage = 'audit';

function stripToDomain(val) {
    // Remove protocol, www optionally, trailing slashes, paths, query strings
    return val
        .replace(/^https?:\/\//i, '')
        .replace(/^www\./i, '')
        .split('/')[0]
        .split('?')[0]
        .split('#')[0]
        .trim()
        .toLowerCase();
}

// ── Global domain state ──
let _syncLock = false; // prevent re-entrant loops

function syncDomainInput(inputId) {
    if (_syncLock) return;
    const el = document.getElementById(inputId);
    if (!el) return;

    // Strip protocol/path from the typed value
    let val = el.value;
    if (val.includes('://')) {
        val = stripToDomain(val);
        _syncLock = true;
        el.value = val;
        el.setSelectionRange(val.length, val.length);
        _syncLock = false;
    }

    val = el.value.trim();
    if (!val) return;

    // Push to ALL other domain inputs unconditionally
    _syncLock = true;
    const ALL_DOMAIN_INPUTS = ['targetUrl', 'dnsInput', 'mxInput', 'whoisInput'];
    ALL_DOMAIN_INPUTS.forEach(id => {
        if (id === inputId) return;
        const other = document.getElementById(id);
        if (other) other.value = val;
    });
    _syncLock = false;
}

function navigateTo(page, pushState = true) {
    if (!PAGE_META[page]) page = 'audit';
    currentPage = page;

    // Hide all sections, show target
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const section = document.getElementById('page-' + page);
    if (section) section.classList.add('active');

    // Update breadcrumb
    const bcName = document.getElementById('bcPageName');
    if (bcName) bcName.textContent = PAGE_META[page].label;

    // Update sidebar active state
    document.querySelectorAll('.sp-item[data-page]').forEach(a => {
        a.classList.toggle('active', a.getAttribute('data-page') === page);
    });

    // Push browser history with ?page=xxx
    if (pushState) {
        const url = new URL(window.location.href);
        if (page === 'audit') {
            url.searchParams.delete('page');
        } else {
            url.searchParams.set('page', page);
        }
        history.pushState({ page }, '', url.toString());
    }

    // Re-init browser info when navigating to that page
    if (page === 'browser') {
        loadBrowserInfo();
        loadMyUA();
    }
    // Load IP info when navigating to ip page
    if (page === 'ip') {
        loadIpInfo();
    }
    if (page === 'colorgen') {
        setTimeout(() => { cgDrawCanvas(); cgSyncAll(); }, 50);
    }
    // Always sync domain across all inputs when navigating
    const _domainInputIds = ['targetUrl','dnsInput','mxInput','whoisInput'];
    const currentDomain = (() => {
        for (const id of _domainInputIds) {
            const el = document.getElementById(id);
            if (el && el.value.trim()) return stripToDomain(el.value.trim());
        }
        return '';
    })();
    if (currentDomain) {
        _domainInputIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = currentDomain;
        });
    }

    closeSidePanel();
    window.scrollTo({ top: 0, behavior: 'smooth' });
    refreshIcons();
    return false; // prevent default link navigation
}

function toggleSidePanel() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('sidePanelOverlay');
    const isOpen = panel.classList.contains('open');
    if (isOpen) { closeSidePanel(); } else {
        panel.classList.add('open');
        overlay.classList.add('open');
        refreshIcons();
    }
}
function closeSidePanel() {
    document.getElementById('sidePanel').classList.remove('open');
    document.getElementById('sidePanelOverlay').classList.remove('open');
}

// Handle browser back/forward
window.addEventListener('popstate', (e) => {
    const page = (e.state && e.state.page) || getPageFromUrl();
    navigateTo(page, false);
});

function getPageFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const p = params.get('page');
    return PAGE_META[p] ? p : 'audit';
}

// ── Auto-init ──
document.addEventListener('DOMContentLoaded', () => {
    // Init router from URL
    const initPage = getPageFromUrl();
    navigateTo(initPage, false);
    // Browser info loads when browser page is active
    if (initPage === 'browser') {
        loadBrowserInfo();
        loadMyUA();
    }
    if (initPage === 'ip') {
        loadIpInfo();
    }
    // Init QR & Case converter defaults
    qrSetType('url');
    qrSetSecurity('WPA');
    // Init colour generator
    if (typeof cgInit === 'function') cgInit();
    refreshIcons();
});

document.addEventListener('touchstart', () => {}, { passive: true });

