// Browser Info page

// ═══════════════════════════════════════
//  BROWSER INFO
// ═══════════════════════════════════════

function loadBrowserInfo() {
    const el = document.getElementById('browserInfoResult');
    if (!el) return;
    const rows = [
        ['Browser',       detectBrowser()],
        ['Engine',        detectEngine()],
        ['OS',            detectOS()],
        ['Platform',      navigator.platform||'—'],
        ['Language',      navigator.language||'—'],
        ['Screen',        screen.width+'×'+screen.height+' ('+screen.colorDepth+'bit)'],
        ['Viewport',      window.innerWidth+'×'+window.innerHeight+' px'],
        ['DPR',           (window.devicePixelRatio||1).toFixed(2)+'×'],
        ['Touch',         'ontouchstart' in window ? '✓ Touch device' : '✗ Non-touch'],
        ['Connection',    (navigator.connection?.effectiveType||'—')+(navigator.connection?.downlink?' / '+navigator.connection.downlink+' Mbps':'')],
        ['Cookies',       navigator.cookieEnabled ? '✓ Enabled' : '✗ Disabled'],
        ['Online',        navigator.onLine ? '✓ Online' : '✗ Offline'],
        ['Timezone',      Intl.DateTimeFormat().resolvedOptions().timeZone||'—'],
        ['DNT',           navigator.doNotTrack==='1'?'✓ On':navigator.doNotTrack==='0'?'✗ Off':'—'],
        ['WebGL',         detectWebGL()],
    ];

    let html = `<div style="background:#fff;border:1.5px solid var(--border);border-radius:12px;overflow:hidden;">`;
    rows.forEach(([k,v], i) => {
        const ok = String(v).startsWith('✓');
        const bad= String(v).startsWith('✗');
        html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:7px 12px;border-bottom:${i<rows.length-1?'1px solid var(--border)':'none'};">
            <span style="font-size:11px;font-weight:600;color:var(--muted);flex-shrink:0;min-width:90px;">${k}</span>
            <span style="font-size:11px;font-weight:700;color:${ok?'var(--success)':bad?'var(--danger)':'var(--ink)'};text-align:right;word-break:break-all;">${escapeHtml(String(v))}</span>
        </div>`;
    });
    html += `</div>`;
    el.innerHTML = html;
}

function detectBrowser() {
    const ua = navigator.userAgent;
    if (/Edg\/([\d.]+)/.test(ua))          return 'Microsoft Edge '+RegExp.$1;
    if (/OPR\/([\d.]+)/.test(ua))          return 'Opera '+RegExp.$1;
    if (/Chrome\/([\d.]+)/.test(ua)&&!ua.includes('Chromium')) return 'Chrome '+RegExp.$1;
    if (/Firefox\/([\d.]+)/.test(ua))      return 'Firefox '+RegExp.$1;
    if (/Safari\//.test(ua)&&!/Chrome/.test(ua)&&/Version\/([\d.]+)/.test(ua)) return 'Safari '+RegExp.$1;
    if (/Chromium\/([\d.]+)/.test(ua))     return 'Chromium '+RegExp.$1;
    return 'Unknown';
}
function detectEngine() {
    const ua = navigator.userAgent;
    if (ua.includes('Gecko/')&&!ua.includes('WebKit')) return 'Gecko';
    if (ua.includes('AppleWebKit/')) return ua.includes('Chrome')?'Blink':'WebKit';
    if (ua.includes('Trident/')) return 'Trident (IE)';
    return 'Unknown';
}
function detectOS() {
    const ua = navigator.userAgent;
    if (/Windows NT 10/.test(ua))       return 'Windows 10/11';
    if (/Windows NT 6\.3/.test(ua))     return 'Windows 8.1';
    if (/Windows NT 6\.1/.test(ua))     return 'Windows 7';
    if (/Android ([\d.]+)/.test(ua))    return 'Android '+RegExp.$1;
    if (/iPhone/.test(ua)&&/OS ([\d_]+)/.test(ua)) return 'iOS '+RegExp.$1.replace(/_/g,'.');
    if (/iPad/.test(ua)&&/OS ([\d_]+)/.test(ua))   return 'iPadOS '+RegExp.$1.replace(/_/g,'.');
    if (/Mac OS X ([\d_]+)/.test(ua))   return 'macOS '+RegExp.$1.replace(/_/g,'.');
    if (/CrOS/.test(ua))                return 'Chrome OS';
    if (/Linux/.test(ua))               return 'Linux';
    return 'Unknown';
}
function detectWebGL() {
    try {
        const c = document.createElement('canvas');
        const gl = c.getContext('webgl')||c.getContext('experimental-webgl');
        if (!gl) return '✗ Not supported';
        const d = gl.getExtension('WEBGL_debug_renderer_info');
        return '✓ '+(d ? gl.getParameter(d.UNMASKED_RENDERER_WEBGL) : 'Supported');
    } catch(e) { return '✗ Error'; }
}

