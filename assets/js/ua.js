// User Agent Analyzer page

// ═══════════════════════════════════════
//  USER AGENT ANALYZER
// ═══════════════════════════════════════

function loadMyUA() {
    document.getElementById('uaInput').value = navigator.userAgent;
    analyzeUA();
}

function analyzeUA() {
    const ua  = document.getElementById('uaInput').value.trim();
    const out = document.getElementById('uaResult');
    if (!ua) { out.innerHTML = ''; return; }

    const p = parseUA(ua);
    const rows = [
        ['Browser',   p.browser],
        ['Version',   p.browserVersion],
        ['Engine',    p.engine],
        ['OS',        p.os],
        ['OS Ver.',   p.osVersion],
        ['Device',    p.device],
        ['Mobile',    p.mobile?'✓ Yes':'✗ No'],
        ['Bot',       p.bot?'⚠ Bot/Crawler':'✓ Human'],
    ];
    let html = `<div style="background:#fff;border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:10px;">`;
    rows.forEach(([k,v], i) => {
        const ok = String(v).startsWith('✓');
        const bad= String(v).startsWith('✗');
        const warn=String(v).startsWith('⚠');
        html += `<div style="display:flex;justify-content:space-between;padding:7px 12px;border-bottom:${i<rows.length-1?'1px solid var(--border)':'none'};">
            <span style="font-size:11px;font-weight:600;color:var(--muted);min-width:80px;">${k}</span>
            <span style="font-size:11px;font-weight:700;color:${ok?'var(--success)':bad?'var(--danger)':warn?'var(--warning)':'var(--ink)'};text-align:right;">${escapeHtml(String(v))}</span>
        </div>`;
    });
    html += `</div>
    <div style="background:var(--surface-2);border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;">
        <p style="font-size:10px;font-weight:700;color:var(--muted);margin-bottom:4px;letter-spacing:.06em;">RAW UA STRING</p>
        <p style="font-family:monospace;font-size:11px;color:var(--ink-3);word-break:break-all;line-height:1.6;">${escapeHtml(ua)}</p>
    </div>`;
    out.innerHTML = html;
}

function parseUA(ua) {
    const r = {browser:'Unknown',browserVersion:'?',engine:'Unknown',os:'Unknown',osVersion:'?',device:'Desktop',mobile:false,bot:false};
    if (/bot|crawl|spider|slurp|facebookexternalhit|linkedinbot|twitterbot|googlebot|bingbot|yandex|baidu/i.test(ua)) {
        r.bot = true; r.device = 'Bot/Crawler';
    }
    if (/Edg\/([\d.]+)/.test(ua))           { r.browser='Edge';       r.browserVersion=RegExp.$1; }
    else if (/OPR\/([\d.]+)/.test(ua))      { r.browser='Opera';      r.browserVersion=RegExp.$1; }
    else if (/SamsungBrowser\/([\d.]+)/.test(ua)) { r.browser='Samsung Browser'; r.browserVersion=RegExp.$1; }
    else if (/Chrome\/([\d.]+)/.test(ua)&&!ua.includes('Chromium')) { r.browser='Chrome'; r.browserVersion=RegExp.$1; }
    else if (/Firefox\/([\d.]+)/.test(ua))  { r.browser='Firefox';    r.browserVersion=RegExp.$1; }
    else if (/Safari\//.test(ua)&&!/Chrome/.test(ua)&&/Version\/([\d.]+)/.test(ua)) { r.browser='Safari'; r.browserVersion=RegExp.$1; }
    else if (/Chromium\/([\d.]+)/.test(ua)) { r.browser='Chromium';   r.browserVersion=RegExp.$1; }
    else if (/rv:([\d.]+).*Gecko/.test(ua)) { r.browser='Firefox';    r.browserVersion=RegExp.$1; }

    if (ua.includes('Gecko/')&&!ua.includes('WebKit')) r.engine='Gecko';
    else if (ua.includes('AppleWebKit/')) r.engine=ua.includes('Chrome')?'Blink (WebKit fork)':'WebKit';
    else if (ua.includes('Trident/'))    r.engine='Trident';

    if (/Windows NT 10/.test(ua))      { r.os='Windows';  r.osVersion='10/11'; }
    else if (/Windows NT 6\.3/.test(ua)) { r.os='Windows'; r.osVersion='8.1'; }
    else if (/Windows NT 6\.1/.test(ua)) { r.os='Windows'; r.osVersion='7'; }
    else if (/Android ([\d.]+)/.test(ua)) { r.os='Android'; r.osVersion=RegExp.$1; r.mobile=true; r.device='Mobile'; }
    else if (/iPhone/.test(ua)&&/OS ([\d_]+)/.test(ua)) { r.os='iOS'; r.osVersion=RegExp.$1.replace(/_/g,'.'); r.mobile=true; r.device='iPhone'; }
    else if (/iPad/.test(ua)&&/OS ([\d_]+)/.test(ua))   { r.os='iPadOS'; r.osVersion=RegExp.$1.replace(/_/g,'.'); r.device='iPad'; }
    else if (/Mac OS X ([\d_]+)/.test(ua)) { r.os='macOS'; r.osVersion=RegExp.$1.replace(/_/g,'.'); }
    else if (/CrOS/.test(ua))  { r.os='Chrome OS'; }
    else if (/Linux/.test(ua)) { r.os='Linux'; }
    if (/Mobi|Android|iPhone/.test(ua)&&!r.bot) { r.mobile=true; if(r.device==='Desktop') r.device='Mobile'; }
    return r;
}

