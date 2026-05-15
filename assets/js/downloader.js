// ═══════════════════════════════════════
//  WEBSITE ASSET DOWNLOADER
// ═══════════════════════════════════════

let _dlStream = null;
let _dlTimerInterval = null;
let _dlTimerSecs = 3600;
let _dlSessionId = null;

// ── Toggle helper (checkbox-as-track) ──────────────────────────
function dlToggle(checkId, trackId) {
    const cb = document.getElementById(checkId);
    if (!cb) return;
    cb.checked = !cb.checked;
    dlSyncToggle(checkId, trackId);
}

function dlSyncToggle(checkId, trackId) {
    const cb    = document.getElementById(checkId);
    const track = document.getElementById(trackId);
    if (!cb || !track) return;
    const thumb = track.querySelector('div');
    if (cb.checked) {
        track.style.background = 'var(--accent)';
        if (thumb) thumb.style.left = '18px';
    } else {
        track.style.background = '#e2e8f0';
        if (thumb) thumb.style.left = '2.5px';
    }
}

// ── Format helpers ──────────────────────────────────────────────
function dlFmtSize(b) {
    if (b >= 1073741824) return (b / 1073741824).toFixed(2) + ' GB';
    if (b >= 1048576)    return (b / 1048576).toFixed(2) + ' MB';
    if (b >= 1024)       return (b / 1024).toFixed(1) + ' KB';
    return b + ' B';
}

// ── Log line renderer ───────────────────────────────────────────
function dlLog(level, msg) {
    const container = document.getElementById('dlLog');
    if (!container) return;

    const icons = {
        ok: '✓', err: '✗', warn: '⚠', head: '◆', info: '→',
        dim: '·', retry: '↺', cdn: '⇢', scan: '⌕', debug: '🔍',
    };
    const colors = {
        ok:    'var(--success)',
        err:   'var(--danger)',
        warn:  'var(--warning)',
        head:  'var(--ink)',
        info:  'var(--ink-3)',
        dim:   '#94a3b8',
        retry: '#d97706',
        cdn:   '#7c3aed',
        scan:  '#0284c7',
        debug: '#6b7280',
    };

    const row = document.createElement('div');
    row.style.cssText = `
        display:flex;gap:10px;padding:5px 20px;
        border-bottom:1px solid var(--border);
        animation:fadeSlide .15s ease;
        color:${colors[level] || 'var(--ink-3)'};
        font-size:12px;line-height:1.6;
    `;
    row.innerHTML = `
        <span style="flex-shrink:0;width:14px;text-align:center;opacity:.7;">${icons[level] || '·'}</span>
        <span style="word-break:break-all;">${escapeHtml(String(msg))}</span>
    `;
    container.appendChild(row);

    // Auto-scroll to bottom
    container.scrollTop = container.scrollHeight;
}

function dlClearLog() {
    const el = document.getElementById('dlLog');
    if (el) el.innerHTML = '';
}

// ── Live indicator ──────────────────────────────────────────────
function dlSetLive(state) {
    const dot   = document.getElementById('dlLiveDot');
    const label = document.getElementById('dlLiveLabel');
    if (!dot || !label) return;
    const states = {
        connecting: { bg: 'var(--accent)',  label: 'CONNECTING…', anim: 'pulse 1.5s ease infinite' },
        live:       { bg: 'var(--success)', label: 'LIVE',         anim: 'pulse 1.5s ease infinite' },
        done:       { bg: 'var(--success)', label: 'DONE',         anim: 'none' },
        error:      { bg: 'var(--danger)',  label: 'ERROR',        anim: 'none' },
    };
    const s = states[state] || states.connecting;
    dot.style.background = s.bg;
    dot.style.animation  = s.anim;
    label.textContent    = s.label;
    label.style.color    = s.bg;
}

// ── Timer ───────────────────────────────────────────────────────
function dlStartTimer() {
    clearInterval(_dlTimerInterval);
    _dlTimerSecs = 3600;
    _dlTimerInterval = setInterval(() => {
        if (--_dlTimerSecs <= 0) {
            clearInterval(_dlTimerInterval);
            _dlTimerSecs = 0;
        }
        const m = String(Math.floor(_dlTimerSecs / 60)).padStart(2, '0');
        const s = String(_dlTimerSecs % 60).padStart(2, '0');
        const bar  = document.getElementById('dlTimerBar');
        const exp  = document.getElementById('dlZipExpiry');
        if (bar) bar.style.width = ((_dlTimerSecs / 3600) * 100) + '%';
        if (exp) exp.textContent = `ZIP auto-deletes in ${m}:${s}`;
    }, 1000);
}

// ── Reset button state ──────────────────────────────────────────
function dlResetBtn() {
    const btn  = document.getElementById('dlStartBtn');
    if (!btn) return;
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="download" class="icon"></i> Download';
    refreshIcons();
}

// ── Main: start download ────────────────────────────────────────
function dlStart() {
    const url  = (document.getElementById('dlUrl')?.value  || '').trim();
    const out  = (document.getElementById('dlOut')?.value  || 'downloaded').trim();
    const conc = document.getElementById('dlConc')?.value  || '12';
    const deep = document.getElementById('dlDeep')?.checked ? '1' : '';
    const zip  = document.getElementById('dlZip')?.checked  ? '1' : '';

    if (!url) {
        document.getElementById('dlUrl')?.focus();
        return;
    }

    // Show UI panels
    document.getElementById('dlLogWrap').style.display   = '';
    document.getElementById('dlStatsBar').style.display  = '';
    document.getElementById('dlZipArea').style.display   = 'none';
    dlClearLog();

    // Reset stats
    ['dlStatOk','dlStatRetry','dlStatFail','dlStatBytes'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = id === 'dlStatBytes' ? '0 B' : '0';
    });

    // Lock button
    const btn = document.getElementById('dlStartBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader" class="icon spin"></i> Downloading…';
        refreshIcons();
    }

    dlSetLive('connecting');
    dlEnableUnloadWarning();

    // Close any existing stream
    if (_dlStream) { _dlStream.abort(); _dlStream = null; }

    // Build FormData
    const fd = new FormData();
    fd.append('url',         url);
    fd.append('outdir',      out);
    fd.append('concurrency', conc);
    if (deep) fd.append('deepscan',   '1');
    if (zip)  fd.append('create_zip', '1');

    const controller = new AbortController();
    _dlStream = controller;

    fetch('?action=download_website', { method: 'POST', body: fd, signal: controller.signal })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            dlSetLive('live');

            const reader = res.body.getReader();
            const dec    = new TextDecoder();
            let buf = '';

            // Running stat totals
            let totalOk = 0, totalRetry = 0, totalFail = 0, totalBytes = 0;

            function pump() {
                reader.read().then(({ done, value }) => {
                    if (done) { dlSetLive('done'); dlResetBtn(); return; }

                    buf += dec.decode(value, { stream: true });
                    const parts = buf.split('\n\n');
                    buf = parts.pop();

                    for (const part of parts) {
                        if (!part.trim()) continue;
                        const lines = part.split('\n');
                        let event = 'message', data = '';
                        for (const l of lines) {
                            if (l.startsWith('event:')) event = l.slice(6).trim();
                            if (l.startsWith('data:'))  data  = l.slice(5).trim();
                        }
                        if (!data) continue;

                        let parsed;
                        try { parsed = JSON.parse(data); } catch(e) { continue; }

                        if (event === 'log') {
                            const lvl = parsed.level || 'info';
                            dlLog(lvl, parsed.msg || '');

                            // Parse live counters from log messages
                            if (lvl === 'ok')    { totalOk++;    dlUpdStat('dlStatOk',    totalOk); }
                            if (lvl === 'retry')  { totalRetry++; dlUpdStat('dlStatRetry', totalRetry); }
                            if (lvl === 'err')    { totalFail++;  dlUpdStat('dlStatFail',  totalFail); }

                            // Try to parse file size from log line like "(12.3 KB)"
                            const sizeMatch = (parsed.msg || '').match(/\(([0-9.]+ (?:B|KB|MB|GB))\)/);
                            if (sizeMatch && lvl === 'ok') {
                                totalBytes += dlParseSize(sizeMatch[1]);
                                dlUpdStat('dlStatBytes', dlFmtSize(totalBytes));
                            }

                        } else if (event === 'done') {
                            dlHandleDone(parsed, !!zip);
                        } else if (event === 'error') {
                            dlDisableUnloadWarning();
                            dlLog('err', parsed.msg || 'Unknown error');
                            dlSetLive('error');
                            dlResetBtn();
                        }
                    }
                    pump();
                }).catch(e => {
                    dlDisableUnloadWarning();
                    if (e.name !== 'AbortError') {
                        dlLog('err', 'Stream error: ' + e.message);
                        dlSetLive('error');
                    }
                    dlResetBtn();
                });
            }
            pump();
        })
        .catch(e => {
            dlDisableUnloadWarning();
            if (e.name !== 'AbortError') {
                dlLog('err', 'Request failed: ' + e.message);
                dlSetLive('error');
            }
            dlResetBtn();
        });
}

function dlHandleBeforeUnload(event) {
    const msg = 'Download is in progress. Do not switch tabs or close this page until the download completes.';
    event.preventDefault();
    event.returnValue = msg;
    return msg;
}

function dlEnableUnloadWarning() {
    window.addEventListener('beforeunload', dlHandleBeforeUnload);
}

function dlDisableUnloadWarning() {
    window.removeEventListener('beforeunload', dlHandleBeforeUnload);
}

function dlUpdStat(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function dlParseSize(str) {
    const [num, unit] = str.split(' ');
    const n = parseFloat(num);
    if (unit === 'GB') return n * 1073741824;
    if (unit === 'MB') return n * 1048576;
    if (unit === 'KB') return n * 1024;
    return n;
}

function dlHandleDone(data, wantZip) {
    dlDisableUnloadWarning();
    dlSetLive('done');
    dlResetBtn();

    _dlSessionId = data.sessionId || null;

    // Final stats from server
    if (data.ok    !== undefined) dlUpdStat('dlStatOk',    data.ok);
    if (data.retry !== undefined) dlUpdStat('dlStatRetry', data.retry);
    if (data.fail  !== undefined) dlUpdStat('dlStatFail',  data.fail);
    if (data.bytes !== undefined) dlUpdStat('dlStatBytes', dlFmtSize(data.bytes));

    if (data.zip && wantZip) {
        const zipArea = document.getElementById('dlZipArea');
        const zipBtn  = document.getElementById('dlZipBtn');
        const zipLbl  = document.getElementById('dlZipLabel');
        const zipUrl  = '?action=dl_zip&file=' + encodeURIComponent(data.zip);
        if (zipArea) zipArea.style.display = '';
        if (zipBtn) {
            zipBtn.href = zipUrl;
            zipBtn.onclick = dlInterceptZipDownload;
        }
        if (zipLbl)  zipLbl.textContent = 'Download ZIP — ' + dlFmtSize(data.zipSize || 0);
        dlStartTimer();
    }

    refreshIcons();
}

function dlShowPopup(message) {
    const modal = document.getElementById('seoModal');
    const heading = modal?.querySelector('.modal-header .heading');
    const urlEl = document.getElementById('modalUrl');
    const content = document.getElementById('modalContent');

    if (!modal || !content) {
        dlLog('warn', message);
        return;
    }

    if (heading) heading.textContent = 'Download Warning';
    if (urlEl) urlEl.textContent = 'Please keep this tab active until the download completes.';
    content.innerHTML = `
        <div style="display:flex;flex-direction:column;align-items:center;gap:20px;padding:28px 24px;">
            <div style="width:84px;height:84px;border-radius:50%;background:rgba(245, 158, 11, 0.14);display:flex;align-items:center;justify-content:center;">
                <i data-lucide="alert-triangle" style="width:36px;height:36px;color:#f59e0b;"></i>
            </div>
            <div style="text-align:center;max-width:520px;">
                <div style="font-size:1.1rem;font-weight:700;color:var(--ink);margin-bottom:10px;">Do Not Leave This Page</div>
                <div style="color:var(--muted);line-height:1.75;">${message}</div>
            </div>
            <div style="width:100%;max-width:520px;border-radius:20px;background:rgba(15, 23, 42, 0.05);padding:18px 20px;text-align:left;color:var(--ink);line-height:1.7;">
                <strong style="display:block;margin-bottom:8px;">Warning:</strong>
                Keep this tab open until the download completes. Switching away may interrupt the session or cause the ZIP to expire before it is ready.</div>
        </div>`;
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.padding = '20px';
    document.body.style.overflow = 'hidden';
    if (typeof refreshIcons === 'function') refreshIcons();
}

function dlInterceptZipDownload(event) {
    event.preventDefault();
    const url = event.currentTarget.href;
    if (!url) return;

    fetch(url, { method: 'HEAD' })
        .then(res => {
            if (res.ok) {
                window.location.href = url;
            } else if (res.status === 404) {
                dlShowPopup('ZIP package has been deleted or expired. Please rerun the download.');
            } else {
                dlShowPopup('ZIP download failed: HTTP ' + res.status);
            }
        })
        .catch(err => {
            dlShowPopup('ZIP download failed: ' + err.message);
        });
}

// ── Session beacon cleanup on page close ─────────────────────────
function dlSendCleanup() {
    if (!_dlSessionId) return;
    const fd = new FormData();
    fd.append('session', _dlSessionId);
    navigator.sendBeacon('?action=dl_cleanup', fd);
    _dlSessionId = null;
}

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') dlSendCleanup();
});
window.addEventListener('pagehide', dlSendCleanup);
