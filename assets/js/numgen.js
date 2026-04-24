// ═══════════════════════════════════════
//  NUMBER GENERATOR
// ═══════════════════════════════════════

let _ngMode = 'integer';
let _ngUuidVer = 4;
let _ngTsFmt = 'unix';
let _ngHexFmt = 'hex';

// ── Mode switcher ───────────────────────────────────────────────
function ngSetMode(mode) {
    _ngMode = mode;
    document.querySelectorAll('#ngModeBtns .tab-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('ngMode_' + mode);
    if (btn) btn.classList.add('active');

    document.querySelectorAll('[id^="ngOpt_"]').forEach(el => el.style.display = 'none');
    const opt = document.getElementById('ngOpt_' + mode);
    if (opt) opt.style.display = '';
}

function ngSetUuidVer(ver) {
    _ngUuidVer = ver;
    document.querySelectorAll('#ngUuidVerBtns .tab-btn').forEach(b => b.classList.remove('active'));
    const id = 'ngUuidVer' + ver;
    const btn = document.getElementById(id);
    if (btn) btn.classList.add('active');
}

function ngSetTsFmt(fmt) {
    _ngTsFmt = fmt;
    document.querySelectorAll('[id^="ngTsFmt_"]').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('ngTsFmt_' + fmt);
    if (btn) btn.classList.add('active');
}

function ngSetHexFmt(fmt) {
    _ngHexFmt = fmt;
    document.querySelectorAll('[id^="ngHexFmt_"]').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('ngHexFmt_' + fmt);
    if (btn) btn.classList.add('active');
}

// ── Generate dispatcher ─────────────────────────────────────────
function ngGenerate() {
    let values = [];

    switch (_ngMode) {
        case 'integer':   values = ngGenIntegers();   break;
        case 'float':     values = ngGenFloats();      break;
        case 'sequence':  values = ngGenSequence();    break;
        case 'uuid':      values = ngGenUuids();       break;
        case 'password':  values = ngGenPasswords();   break;
        case 'timestamp': values = ngGenTimestamps();  break;
        case 'hex':       values = ngGenHex();         break;
        case 'gaussian':  values = ngGenGaussian();    break;
    }

    const fmt = document.getElementById('ngOutFmt').value;
    const output = ngApplyFormat(values, fmt);
    document.getElementById('ngOutput').value = output;

    // Badge
    const badge = document.getElementById('ngCountBadge');
    badge.textContent = values.length.toLocaleString() + ' items';
    badge.style.display = 'inline-block';

    // Stats (numeric modes only)
    const numeric = ['integer','float','sequence','gaussian'];
    if (numeric.includes(_ngMode)) {
        ngShowStats(values.map(Number).filter(n => !isNaN(n)));
    } else {
        document.getElementById('ngStats').style.display = 'none !important';
    }
}

// ── Integer generator ───────────────────────────────────────────
function ngGenIntegers() {
    const min = parseInt(document.getElementById('ngIntMin').value);
    const max = parseInt(document.getElementById('ngIntMax').value);
    const count = Math.min(parseInt(document.getElementById('ngIntCount').value) || 10, 100000);
    const unique = document.getElementById('ngIntUnique').checked;
    const sorted = document.getElementById('ngIntSorted').checked;

    if (isNaN(min) || isNaN(max) || min > max) return ['Invalid range'];

    const range = max - min + 1;
    const actualCount = unique ? Math.min(count, range) : count;

    let result = [];

    if (unique && range <= 1000000) {
        // Fisher-Yates on limited range
        const pool = Array.from({ length: range }, (_, i) => min + i);
        for (let i = pool.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [pool[i], pool[j]] = [pool[j], pool[i]];
        }
        result = pool.slice(0, actualCount).map(String);
    } else {
        const seen = unique ? new Set() : null;
        while (result.length < actualCount) {
            const n = Math.floor(Math.random() * range) + min;
            if (!unique || !seen.has(n)) {
                if (seen) seen.add(n);
                result.push(String(n));
            }
        }
    }

    if (sorted) result.sort((a, b) => Number(a) - Number(b));
    return result;
}

// ── Float generator ─────────────────────────────────────────────
function ngGenFloats() {
    const min = parseFloat(document.getElementById('ngFloatMin').value);
    const max = parseFloat(document.getElementById('ngFloatMax').value);
    const dec = parseInt(document.getElementById('ngFloatDecimals').value);
    const count = Math.min(parseInt(document.getElementById('ngFloatCount').value) || 10, 100000);

    if (isNaN(min) || isNaN(max) || min > max) return ['Invalid range'];

    return Array.from({ length: count }, () =>
        (Math.random() * (max - min) + min).toFixed(dec)
    );
}

// ── Sequence generator ──────────────────────────────────────────
function ngGenSequence() {
    const start = parseFloat(document.getElementById('ngSeqStart').value);
    const end = parseFloat(document.getElementById('ngSeqEnd').value);
    const step = parseFloat(document.getElementById('ngSeqStep').value) || 1;
    const shuffle = document.getElementById('ngSeqShuffle').checked;

    if (isNaN(start) || isNaN(end)) return ['Invalid values'];

    const result = [];
    const dir = start <= end ? 1 : -1;
    const absStep = Math.abs(step);

    for (let v = start; dir > 0 ? v <= end : v >= end; v = parseFloat((v + dir * absStep).toFixed(10))) {
        result.push(String(v));
        if (result.length > 100000) break;
    }

    if (shuffle) {
        for (let i = result.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [result[i], result[j]] = [result[j], result[i]];
        }
    }

    return result;
}

// ── UUID / ID generators ────────────────────────────────────────
function ngGenUuids() {
    const count = Math.min(parseInt(document.getElementById('ngUuidCount').value) || 5, 10000);
    const upper = document.getElementById('ngUuidUpper').checked;

    return Array.from({ length: count }, () => {
        let id;
        switch (_ngUuidVer) {
            case 4:     id = ngUuidV4();      break;
            case 7:     id = ngUuidV7();      break;
            case 'nano': id = ngNanoId();     break;
            case 'cuid': id = ngCuidLike();   break;
            default:    id = ngUuidV4();
        }
        return upper ? id.toUpperCase() : id;
    });
}

function ngUuidV4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
        const r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}

function ngUuidV7() {
    // Timestamp-based UUID v7 (simplified)
    const now = BigInt(Date.now());
    const ms = now.toString(16).padStart(12, '0');
    const rand = Array.from({ length: 16 }, () => Math.floor(Math.random() * 16).toString(16)).join('');
    return `${ms.slice(0,8)}-${ms.slice(8,12)}-7${rand.slice(0,3)}-${((Math.random() * 4 | 0) + 8).toString(16)}${rand.slice(4,7)}-${rand.slice(7,19)}`;
}

function ngNanoId(size = 21) {
    const alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_-';
    return Array.from({ length: size }, () => alphabet[Math.floor(Math.random() * alphabet.length)]).join('');
}

function ngCuidLike() {
    const ts = Date.now().toString(36);
    const rand = () => Math.random().toString(36).slice(2);
    return 'c' + ts + rand() + rand();
}

// ── Password generator ──────────────────────────────────────────
function ngGenPasswords() {
    const len = Math.min(Math.max(parseInt(document.getElementById('ngPassLen').value) || 16, 4), 128);
    const count = Math.min(parseInt(document.getElementById('ngPassCount').value) || 5, 1000);
    const useUpper = document.getElementById('ngPassUpper').checked;
    const useLower = document.getElementById('ngPassLower').checked;
    const useNums = document.getElementById('ngPassNums').checked;
    const useSyms = document.getElementById('ngPassSyms').checked;

    let charset = '';
    if (useUpper)  charset += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if (useLower)  charset += 'abcdefghijklmnopqrstuvwxyz';
    if (useNums)   charset += '0123456789';
    if (useSyms)   charset += '!@#$%^&*()-_=+[]{}|;:,.<>?';
    if (!charset)  charset = 'abcdefghijklmnopqrstuvwxyz';

    return Array.from({ length: count }, () =>
        Array.from({ length: len }, () => charset[Math.floor(Math.random() * charset.length)]).join('')
    );
}

// ── Timestamp generator ─────────────────────────────────────────
function ngGenTimestamps() {
    const count = Math.min(parseInt(document.getElementById('ngTsCount').value) || 5, 1000);

    if (_ngTsFmt === 'now') {
        const now = Date.now();
        const offsets = [0, 60000, 3600000, 86400000, 604800000, 2592000000, 31536000000];
        const labels = ['Now', '+1 min', '+1 hr', '+1 day', '+1 week', '+1 month', '+1 year'];
        return offsets.slice(0, count).map((off, i) => {
            const d = new Date(now + off);
            return `${labels[i].padEnd(10)} → Unix: ${Math.floor(d/1000)}  |  ISO: ${d.toISOString()}`;
        });
    }

    return Array.from({ length: count }, () => {
        // Random timestamp within last 10 years
        const ms = Date.now() - Math.floor(Math.random() * 315360000000);
        const d = new Date(ms);
        switch (_ngTsFmt) {
            case 'unix':   return String(Math.floor(ms / 1000));
            case 'ms':     return String(ms);
            case 'iso':    return d.toISOString();
            default:       return String(Math.floor(ms / 1000));
        }
    });
}

// ── Hex / Binary generator ──────────────────────────────────────
function ngGenHex() {
    const byteLen = Math.min(Math.max(parseInt(document.getElementById('ngHexLen').value) || 16, 1), 64);
    const count = Math.min(parseInt(document.getElementById('ngHexCount').value) || 5, 10000);
    const prefix = document.getElementById('ngHexPrefix').checked;

    return Array.from({ length: count }, () => {
        const bytes = Array.from({ length: byteLen }, () => Math.floor(Math.random() * 256));

        switch (_ngHexFmt) {
            case 'hex': {
                const h = bytes.map(b => b.toString(16).padStart(2, '0')).join('');
                return prefix ? '0x' + h : h;
            }
            case 'binary': {
                const b = bytes.map(b => b.toString(2).padStart(8, '0')).join('');
                return prefix ? '0b' + b : b;
            }
            case 'octal': {
                const o = bytes.map(b => b.toString(8).padStart(3, '0')).join('');
                return prefix ? '0o' + o : o;
            }
            case 'base64': {
                const binary = bytes.map(b => String.fromCharCode(b)).join('');
                return btoa(binary);
            }
        }
        return '';
    });
}

// ── Gaussian / Normal distribution ──────────────────────────────
function ngGenGaussian() {
    const mean = parseFloat(document.getElementById('ngGausMean').value) || 0;
    const std = Math.max(parseFloat(document.getElementById('ngGausStd').value) || 1, 0.001);
    const dec = parseInt(document.getElementById('ngGausDec').value);
    const count = Math.min(parseInt(document.getElementById('ngGausCount').value) || 10, 100000);

    // Box-Muller transform
    function boxMuller() {
        let u = 0, v = 0;
        while (u === 0) u = Math.random();
        while (v === 0) v = Math.random();
        return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v);
    }

    return Array.from({ length: count }, () =>
        (mean + std * boxMuller()).toFixed(dec)
    );
}

// ── Output formatter ────────────────────────────────────────────
function ngApplyFormat(values, fmt) {
    switch (fmt) {
        case 'lines':      return values.join('\n');
        case 'csv':        return values.join(',');
        case 'ssv':        return values.join(' ');
        case 'array_js':   return '[' + values.map(v => isNaN(v) ? JSON.stringify(v) : v).join(', ') + ']';
        case 'array_json': return JSON.stringify(values.map(v => isNaN(v) ? v : Number(v) || v), null, 2);
        case 'array_php':  return '<?php\n$values = [\n' + values.map(v => `    ${isNaN(v) ? "'" + v.replace(/'/g, "\\'") + "'" : v},`).join('\n') + '\n];';
        default:           return values.join('\n');
    }
}

// ── Stats (numeric only) ─────────────────────────────────────────
function ngShowStats(nums) {
    if (!nums.length) return;
    const statsEl = document.getElementById('ngStats');
    statsEl.style.cssText = 'display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;';
    document.getElementById('ngStatCount').textContent = `Count: ${nums.length.toLocaleString()}`;
    document.getElementById('ngStatMin').textContent = `Min: ${Math.min(...nums).toLocaleString()}`;
    document.getElementById('ngStatMax').textContent = `Max: ${Math.max(...nums).toLocaleString()}`;
    const sum = nums.reduce((a, b) => a + b, 0);
    document.getElementById('ngStatSum').textContent = `Sum: ${sum.toLocaleString()}`;
    document.getElementById('ngStatAvg').textContent = `Avg: ${(sum / nums.length).toFixed(4)}`;
}

// ── Copy + Download ─────────────────────────────────────────────
function ngCopyOutput() {
    const text = document.getElementById('ngOutput').value;
    if (!text) return;
    const btn = document.getElementById('ngCopyBtn');
    copyText(text, btn);
}

function ngDownload() {
    const text = document.getElementById('ngOutput').value;
    if (!text) return;
    const blob = new Blob([text], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'generated-numbers.txt';
    a.click();
    URL.revokeObjectURL(a.href);
}

// ── Responsive grid ─────────────────────────────────────────────
(function ngResponsive() {
    const check = () => {
        const grid = document.getElementById('ngGrid');
        if (grid) grid.style.gridTemplateColumns = window.innerWidth < 640 ? '1fr' : '1fr 1fr';
    };
    window.addEventListener('resize', check);
    document.addEventListener('DOMContentLoaded', check);
})();
