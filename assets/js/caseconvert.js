// Text Case Converter page

// ══════════════════════════════════════════════════════════════
//  TEXT CASE CONVERTER
// ══════════════════════════════════════════════════════════════
let _ccMode = 'none';

function ccSetMode(m) {
    _ccMode = m;
    document.querySelectorAll('.case-btn[id^="ccBtn_"]').forEach(b => b.classList.remove('active'));
    const el = document.getElementById('ccBtn_' + m);
    if (el) el.classList.add('active');
    ccApply();
}

function ccApply() {
    const raw = document.getElementById('ccInput').value;
    const out = document.getElementById('ccOutput');
    out.value = ccConvert(raw, _ccMode);
    ccUpdateStats(raw);
    ccBuildAllCards(raw);
}

function ccConvert(t, mode) {
    if (!t) return '';
    switch (mode) {
        case 'upper':    return t.toUpperCase();
        case 'lower':    return t.toLowerCase();
        case 'title':    return t.replace(/\w\S*/g, w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
        case 'sentence': return t.toLowerCase().replace(/(^\s*\w|[.!?]\s+\w)/g, c => c.toUpperCase());
        case 'camel': {
            const words = t.replace(/[^a-zA-Z0-9\s]/g,'').split(/\s+/).filter(Boolean);
            return words.map((w,i) => i===0 ? w.toLowerCase() : w.charAt(0).toUpperCase()+w.slice(1).toLowerCase()).join('');
        }
        case 'pascal': {
            return t.replace(/[^a-zA-Z0-9\s]/g,'').split(/\s+/).filter(Boolean)
                .map(w => w.charAt(0).toUpperCase()+w.slice(1).toLowerCase()).join('');
        }
        case 'snake':   return t.replace(/[^a-zA-Z0-9\s]/g,'').trim().replace(/\s+/g,'_').toLowerCase();
        case 'kebab':   return t.replace(/[^a-zA-Z0-9\s]/g,'').trim().replace(/\s+/g,'-').toLowerCase();
        case 'const':   return t.replace(/[^a-zA-Z0-9\s]/g,'').trim().replace(/\s+/g,'_').toUpperCase();
        case 'dot':     return t.replace(/[^a-zA-Z0-9\s]/g,'').trim().replace(/\s+/g,'.').toLowerCase();
        case 'alt':     return t.split('').map((c,i) => i%2===0 ? c.toLowerCase() : c.toUpperCase()).join('');
        case 'inv':     return t.split('').map(c => c===c.toUpperCase() ? c.toLowerCase() : c.toUpperCase()).join('');
        default:        return t;
    }
}

function ccUpdateStats(t) {
    const chars = t.length;
    const words = t.trim() ? t.trim().split(/\s+/).length : 0;
    const sentences = t.split(/[.!?]+/).filter(s => s.trim()).length;
    const lines = t.split('\n').length;
    const el = document.getElementById('ccStats');
    el.innerHTML = [
        `<span class="case-stat">${chars.toLocaleString()} char${chars!==1?'s':''}</span>`,
        `<span class="case-stat">${words.toLocaleString()} word${words!==1?'s':''}</span>`,
        `<span class="case-stat">${sentences} sentence${sentences!==1?'s':''}</span>`,
        `<span class="case-stat">${lines} line${lines!==1?'s':''}</span>`,
    ].join('');
}

function ccBuildAllCards(raw) {
    if (!raw.trim()) { document.getElementById('ccQuickCards').style.display='none'; return; }
    document.getElementById('ccQuickCards').style.display='';
    const modes = [
        {id:'upper',label:'UPPER CASE'},{id:'lower',label:'lower case'},
        {id:'title',label:'Title Case'},{id:'sentence',label:'Sentence case'},
        {id:'camel',label:'camelCase'},{id:'pascal',label:'PascalCase'},
        {id:'snake',label:'snake_case'},{id:'kebab',label:'kebab-case'},
        {id:'const',label:'CONSTANT_CASE'},{id:'dot',label:'dot.case'},
    ];
    const container = document.getElementById('ccAllCards');
    container.innerHTML = modes.map(m => {
        const converted = escapeHtml(ccConvert(raw, m.id));
        return `<div class="kw-copy-card">
            <div style="font-size:var(--fs-xs);font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">${escapeHtml(m.label)}</div>
            <div style="font-size:var(--fs-sm);color:var(--ink);word-break:break-all;font-family:monospace;margin-bottom:8px;">${converted}</div>
            <button class="kw-copy-btn" onclick="ccQuickCopy(this,'${m.id}')">
                <i data-lucide="copy" style="width:11px;height:11px;"></i> Copy
            </button>
        </div>`;
    }).join('');
    refreshIcons();
}

function ccQuickCopy(btn, mode) {
    const raw = document.getElementById('ccInput').value;
    copyText(ccConvert(raw, mode), btn);
}

async function ccPaste() {
    // Try modern clipboard API first (requires HTTPS / secure context)
    if (navigator.clipboard && window.isSecureContext) {
        try {
            const text = await navigator.clipboard.readText();
            document.getElementById('ccInput').value = text;
            ccApply();
            return;
        } catch(e) { /* permission denied or unavailable — fall through */ }
    }
    // Fallback: focus the textarea so the user can Ctrl+V / long-press paste natively
    const inp = document.getElementById('ccInput');
    inp.focus();
    inp.select();
}

function ccClear() {
    document.getElementById('ccInput').value = '';
    document.getElementById('ccOutput').value = '';
    document.getElementById('ccQuickCards').style.display='none';
    ccUpdateStats('');
}

function ccCopy() {
    const text = document.getElementById('ccOutput').value;
    if (!text) return;
    const btn = document.getElementById('ccCopyBtn');
    copyText(text, btn);
}

function ccSwap() {
    const out = document.getElementById('ccOutput').value;
    if (!out) return;
    document.getElementById('ccInput').value = out;
    ccSetMode('none');
}

