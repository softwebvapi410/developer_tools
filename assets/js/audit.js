// Audit page — crawl queue, result cards, SEO report modal

let queue = [], indexedUrls = new Set(), indexedData = [], activeRequests = 0;
const CONCURRENCY = 4;

lucide.createIcons();
function refreshIcons() { lucide.createIcons(); }

function startAudit() {
    let input = document.getElementById('targetUrl').value.trim();
    if (!input) return;
    // Lowercase scheme+host only, preserve path case
    if (!/^https?:\/\//i.test(input)) input = 'https://' + input;
    const urlObj = (() => { try { return new URL(input); } catch(e) { return null; } })();
    let url = urlObj ? (urlObj.protocol.toLowerCase() + '//' + urlObj.host.toLowerCase() + urlObj.pathname + urlObj.search + urlObj.hash) : input.toLowerCase();

    // Sync domain to ALL other inputs (always, unconditionally)
    const domainOnly = urlObj ? urlObj.hostname.replace(/^www\./i,'') : stripToDomain(input);
    const ALL_DOMAIN_INPUTS = ['targetUrl', 'dnsInput', 'mxInput', 'whoisInput'];
    ALL_DOMAIN_INPUTS.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = id === 'targetUrl' ? el.value : domainOnly;
    });

    queue = [url];
    indexedUrls.clear();
    indexedData = [];

    // Show initial skeletons immediately — one per concurrent slot
    const list = document.getElementById('resultList');
    list.innerHTML = '';
    for (let i = 0; i < CONCURRENCY; i++) addSkeletonCard();

    document.getElementById('btnAction').disabled = true;
    document.getElementById('finishArea').classList.add('hidden');
    document.getElementById('progressWrap').classList.remove('hidden');
    document.getElementById('statStatus').textContent = 'Crawling…';
    document.getElementById('statStatus').classList.add('pulse-anim');
    refreshIcons();
    processQueue();
}

async function processQueue() {
    if (queue.length === 0 && activeRequests === 0) { finish(); return; }
    while (activeRequests < CONCURRENCY && queue.length > 0) {
        const url = queue.shift();
        if (indexedUrls.has(normUrl(url))) { processQueue(); continue; }
        activeRequests++;
        updateStats();
        // Add a skeleton only if we need more than what's already showing
        const list = document.getElementById('resultList');
        const skelCount = list.querySelectorAll('.skeleton-card').length;
        if (skelCount < activeRequests) addSkeletonCard();
        runCrawl(url);
    }
}

async function runCrawl(url) {
    try {
        const resp = await fetch(`?action=crawl&url=${encodeURIComponent(url)}`);
        if (!resp.ok) return;
        const data = await resp.json();
        if (!data || !data.url) return;
        const norm = normUrl(data.url);
        if (!indexedUrls.has(norm)) {
            indexedUrls.add(norm);
            indexedData.push(data);
            // Replace the FIRST skeleton with the real card in-place
            replaceSkeletonWithCard(data);
            if (Array.isArray(data.links)) {
                data.links.forEach(l => {
                    const ln = normUrl(l);
                    if (!indexedUrls.has(ln) && !queue.some(q => normUrl(q) === ln)) queue.push(l);
                });
            }
        } else {
            // Duplicate — still remove one skeleton placeholder
            const list = document.getElementById('resultList');
            const skel = list.querySelector('.skeleton-card');
            if (skel) skel.remove();
        }
    } catch(e) {
        console.error('Crawl error:', url, e);
        // Remove skeleton on error too
        const list = document.getElementById('resultList');
        const skel = list.querySelector('.skeleton-card');
        if (skel) skel.remove();
    }
    finally { activeRequests--; updateStats(); processQueue(); }
}

// Normalize URL for deduplication: lowercase scheme+host, preserve path
function normUrl(u) {
    try {
        const o = new URL(u);
        return o.protocol.toLowerCase() + '//' + o.host.toLowerCase() + o.pathname + o.search;
    } catch(e) { return u.toLowerCase(); }
}

function getPriorityDot(p) {
    if (p >= 0.8) return 'dot-high';
    if (p >= 0.5) return 'dot-med';
    return 'dot-low';
}

// Replace the first skeleton placeholder with the real card, preserving its list position
function replaceSkeletonWithCard(data) {
    const list = document.getElementById('resultList');
    const skel = list.querySelector('.skeleton-card');
    const cardHtml = buildResultCard(data);
    if (skel) {
        // Create a wrapper, extract the real node, swap it in-place
        const tmp = document.createElement('div');
        tmp.innerHTML = cardHtml;
        const newCard = tmp.firstElementChild;
        list.insertBefore(newCard, skel);
        skel.remove();
    } else {
        // Fallback: no skeleton found, just append
        list.insertAdjacentHTML('beforeend', cardHtml);
    }
    refreshIcons();
}

function buildResultCard(data) {
    const id = 'r' + Math.random().toString(36).substr(2,9);
    const sc = data.seo.score;
    const scColor = sc>=80?'var(--success)':(sc>=50?'var(--warning)':'var(--danger)');
    const dotClass = getPriorityDot(data.priority);
    const sslTag = data.seo.ssl_valid
        ? `<span class="tag tag-good" style="font-size:10px;">Secure</span>`
        : `<span class="tag tag-issue" style="font-size:10px;">Insecure</span>`;
    return `
    <div class="result-card" style="animation:fadeSlide .3s ease;">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full inline-block flex-shrink-0 ${dotClass}"></span>
                    <h4 class="heading font-bold text-sm truncate" style="color:var(--ink);">${escapeHtml(data.seo.title)}</h4>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs truncate" style="color:var(--accent);" data-url="${escapeHtml(data.url)}">${escapeHtml(data.url)}</p>
                    ${sslTag}
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="heading font-extrabold text-lg" style="color:${scColor};">${sc}%</span>
                <button onclick="toggleDetails('${id}')" class="btn-secondary" style="padding:8px 12px;font-size:12px!important;">
                    <i data-lucide="chevron-down" class="icon-sm"></i> Details
                </button>
                <button onclick="showFullReport('${id}')" class="btn-primary" style="padding:8px 14px;font-size:12px!important;box-shadow:none;">
                    <i data-lucide="file-search" class="icon-sm"></i> Full Report
                </button>
            </div>
        </div>
        <div id="${id}" class="details-content mt-4 pt-4" style="border-top:1px solid var(--border);">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--muted);">Technical Summary</p>
                    <div>
                        <div class="meta-row"><span class="meta-key">H1 / H2 / H3</span><span class="meta-val">${data.seo.h1_count} / ${data.seo.h2_count} / ${data.seo.h3_count}</span></div>
                        <div class="meta-row"><span class="meta-key">Images (missing alt)</span><span class="meta-val">${data.seo.total_images} (${data.seo.img_no_alt})</span></div>
                        <div class="meta-row"><span class="meta-key">Load Time</span><span class="meta-val">${data.seo.load_time}s</span></div>
                        <div class="meta-row"><span class="meta-key">Page Size</span><span class="meta-val">${data.seo.page_size_kb} KB</span></div>
                        <div class="meta-row"><span class="meta-key">SSL / HTTPS</span><span class="meta-val" style="color:${data.seo.ssl_valid?'var(--success)':'var(--danger)'};">${data.seo.ssl_valid?'Secure':'Not Secure'}</span></div>
                        <div class="meta-row"><span class="meta-key">Priority</span><span class="meta-val">${data.priority.toFixed(2)}</span></div>
                    </div>
                </div>
                <div style="background:var(--danger-bg);border:1px solid #fecaca;border-radius:14px;padding:16px;">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="alert-circle" class="icon-sm" style="color:var(--danger);flex-shrink:0;"></i>
                        <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--danger);">Critical Issues</p>
                    </div>
                    ${data.seo.issues.length>0
                        ? `<ul style="list-style:disc inside;">${data.seo.issues.slice(0,3).map(i=>`<li class="text-xs" style="color:#991b1b;margin-bottom:4px;">${escapeHtml(i)}</li>`).join('')}</ul>${data.seo.issues.length>3?`<p class="text-xs mt-2" style="color:var(--danger);">+${data.seo.issues.length-3} more</p>`:''}`
                        : `<p class="text-xs font-bold text-center py-3 flex items-center justify-center gap-2" style="color:var(--success);"><i data-lucide="check-circle" class="icon-sm"></i> No critical issues!</p>`
                    }
                </div>
            </div>
        </div>
    </div>`;
}

function addSkeletonCard() {
    const list = document.getElementById('resultList');
    list.insertAdjacentHTML('beforeend', `
    <div class="skeleton-card result-card">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <div class="skel" style="width:8px;height:8px;border-radius:50%;flex-shrink:0;"></div>
                    <div class="skel" style="width:55%;height:14px;border-radius:6px;"></div>
                </div>
                <div class="flex gap-2">
                    <div class="skel" style="width:40%;height:11px;border-radius:5px;"></div>
                    <div class="skel" style="width:48px;height:11px;border-radius:5px;"></div>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="skel" style="width:38px;height:22px;border-radius:6px;"></div>
                <div class="skel" style="width:80px;height:34px;border-radius:10px;"></div>
                <div class="skel" style="width:100px;height:34px;border-radius:10px;"></div>
            </div>
        </div>
    </div>`);
}

function showFullReport(cardId) {
    const card = document.getElementById(cardId);
    if (!card) return;
    const urlEl = card.previousElementSibling?.querySelector('[data-url]');
    const pageUrl = urlEl ? urlEl.getAttribute('data-url') : '';
    const actualData = indexedData.find(d => d.url === pageUrl) || indexedData[indexedData.length-1];
    if (!actualData) return;

    document.getElementById('modalUrl').textContent = actualData.url;
    const modalLink = document.getElementById('modalOpenLink');
    if (modalLink) modalLink.href = actualData.url;
    const seo = actualData.seo;
    const scoreClass = seo.score>=80?'good':(seo.score>=50?'warning':'issue');
    const host = (() => { try { return new URL(actualData.url).hostname; } catch(e) { return actualData.url; } })();

    // ---- Social image helpers ----
    function socialImgHtml(imgUrl, faviconUrl, h, label) {
        const wrapStyle = 'width:100%;aspect-ratio:1200/630;background:#f1f5f9;overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center;';
        if (!imgUrl) return `<div style="${wrapStyle}color:#9ca3af;flex-direction:column;gap:8px;">
            <i data-lucide="image-off" class="icon-lg"></i><span style="font-size:12px;">No ${label} set</span></div>`;
        const fallback = faviconUrl
            ? `<div style="${wrapStyle}color:#9ca3af;flex-direction:column;gap:8px;">
                <img src="${escapeHtml(faviconUrl)}" width="40" height="40" style="border-radius:8px;" onerror="this.style.display='none'"></div>`
            : `<div style="${wrapStyle}color:#9ca3af;flex-direction:column;gap:6px;">
                <i data-lucide="image-off" class="icon-lg"></i><span style="font-size:12px;">Image failed to load</span></div>`;
        return `<div style="${wrapStyle}"><img src="${escapeHtml(imgUrl)}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;position:absolute;inset:0;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;flex-direction:column;gap:8px;">${fallback}</div></div>`;
    }

    // ---- KEYWORD SECTION ----
    function kwSection(seo) {
        if (!seo.keyword_density?.length) return '<p style="color:var(--muted);">No keyword data available.</p>';
        const maxPct = Math.max(...seo.keyword_density.map(k=>k.percentage), 0.01);
        const srcBadge = {
            title:       `<span class="tag" style="background:#dbeafe;color:#1d4ed8;font-size:10px;">TITLE</span>`,
            description: `<span class="tag" style="background:#ede9fe;color:#6d28d9;font-size:10px;">DESC</span>`,
            meta:        `<span class="tag tag-meta" style="font-size:10px;">META</span>`,
            body:        `<span class="tag tag-neutral" style="font-size:10px;">BODY</span>`,
        };
        return seo.keyword_density.map(kw => `
        <div style="margin-bottom:14px;">
            <div class="flex justify-between text-sm mb-1 flex-wrap gap-1">
                <span class="font-semibold flex items-center gap-1">
                    ${escapeHtml(kw.keyword)}
                    ${srcBadge[kw.source]||srcBadge.body}
                    ${kw.in_title&&kw.source!=='title'?srcBadge.title:''}
                    ${kw.in_desc&&kw.source!=='description'?srcBadge.description:''}
                    ${kw.in_meta&&kw.source!=='meta'?srcBadge.meta:''}
                </span>
                <span style="color:var(--muted);">${kw.count} × (${kw.percentage}%)</span>
            </div>
            <div class="kw-bar-track"><div class="${kw.source==='title'?'kw-bar-title':kw.source==='description'?'kw-bar-desc':kw.in_meta?'kw-bar-meta':'kw-bar-body'} kw-bar-fill" style="width:${Math.min((kw.percentage/maxPct)*100,100)}%;"></div></div>
        </div>`).join('');
    }

    document.getElementById('modalContent').innerHTML = `
    <div class="space-y-5">
        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn active" id="tabOverviewBtn" onclick="showTab('overview')"><i data-lucide="layout-dashboard" class="icon-sm"></i> Overview</button>
            <button class="tab-btn" id="tabSocialBtn" onclick="showTab('social')"><i data-lucide="share-2" class="icon-sm"></i> Social</button>
            <button class="tab-btn" id="tabSecurityBtn" onclick="showTab('security')"><i data-lucide="shield-check" class="icon-sm"></i> Security</button>
            <button class="tab-btn" id="tabKeywordsBtn" onclick="showTab('keywords')"><i data-lucide="bar-chart-2" class="icon-sm"></i> Keywords</button>
            <button class="tab-btn" id="tabSerpBtn" onclick="showTab('serp')"><i data-lucide="search" class="icon-sm"></i> SERP</button>
            <button class="tab-btn" id="tabTrackingBtn" onclick="showTab('tracking')"><i data-lucide="radio" class="icon-sm"></i> Tracking</button>
        </div>

        <!-- ===== OVERVIEW ===== -->
        <div id="tabOverview">
            <div class="flex flex-col sm:flex-row items-center justify-between p-5 rounded-2xl gap-4 mb-4" style="background:linear-gradient(135deg,#eff6ff,#eef2ff);">
                <div class="flex items-center gap-3">
                    <i data-lucide="activity" class="icon-xl" style="color:var(--accent);"></i>
                    <div>
                        <div class="heading font-bold text-base" style="color:var(--ink);">SEO Health Score</div>
                        <p class="text-sm" style="color:var(--muted);">Overall optimization rating</p>
                    </div>
                </div>
                <div class="score-ring ${scoreClass}">${seo.score}%</div>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;">
                <div class="section-head"><i data-lucide="tag" class="icon-sm"></i> Meta Tags</div>
                <div class="p-4">
                    <div class="meta-row"><span class="meta-key">Title</span><span class="meta-val">${escapeHtml(seo.title.substring(0,41))}... <span class="tag ${seo.title_length>=30&&seo.title_length<=60?'tag-good':'tag-warn'}">${seo.title_length} chars</span></span></div>
                    <div class="meta-row"><span class="meta-key">Description</span><span class="meta-val">${escapeHtml(seo.description.substring(0,51))}...<span class="tag ${seo.description_length>=120&&seo.description_length<=160?'tag-good':'tag-warn'}">${seo.description_length} chars</span></span></div>
                    ${seo.meta_keywords ? `<div class="meta-row"><span class="meta-key">Keywords</span><span class="meta-val" style="font-size:12px;">${escapeHtml(seo.meta_keywords.substring(0,69))}...</span></div>` : ''}
                    <div class="meta-row"><span class="meta-key">Canonical</span><span class="meta-val">${seo.canonical?`<span style="display:inline-flex;align-items:center;gap:5px;">${escapeHtml(seo.canonical.substring(0,45))}${seo.canonical_valid?' <span class="tag tag-good">Valid</span>':' <span class="tag tag-warn">Mismatch</span>'}<a href="${escapeHtml(seo.canonical)}" target="_blank" rel="noopener" title="Open canonical URL" style="display:inline-flex;align-items:center;color:var(--accent);text-decoration:none;"><i data-lucide="external-link" class="icon-sm"></i></a></span>`:'<span class="tag tag-warn">Missing</span>'}</span></div>
                    <div class="meta-row"><span class="meta-key">Robots</span><span class="meta-val">${escapeHtml(seo.robots)}</span></div>
                    <div class="meta-row"><span class="meta-key">Viewport</span><span class="meta-val">${seo.viewport?'<span class="tag tag-good">Present</span>':'<span class="tag tag-issue">Missing</span>'}</span></div>
                    <div class="meta-row"><span class="meta-key">HTTP Status</span><span class="meta-val"><span class="tag ${seo.status<400?'tag-good':'tag-issue'}">${seo.status}</span></span></div>
                    <div class="meta-row"><span class="meta-key">Load Time</span><span class="meta-val">${seo.load_time}s</span></div>
                    <div class="meta-row"><span class="meta-key">Page Size</span><span class="meta-val">${seo.page_size_kb} KB</span></div>
                </div>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;">
                <div class="section-head"><i data-lucide="heading" class="icon-sm"></i> Heading Structure</div>
                <div class="p-4">
                    <div class="grid grid-cols-4 gap-3 text-center">
                        ${['h1','h2','h3','h4'].map(h=>`<div style="background:var(--surface);border-radius:12px;padding:12px 6px;"><div class="heading font-bold text-xl" style="color:${seo[h+'_count']>0?'var(--accent)':'var(--danger)'};">${seo[h+'_count']}</div><div class="text-xs font-bold uppercase" style="color:var(--muted);">${h.toUpperCase()}</div></div>`).join('')}
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;">
                    <div class="section-head"><i data-lucide="link-2" class="icon-sm"></i> Links</div>
                    <div class="p-4">
                        <div class="meta-row"><span class="meta-key">Internal</span><span class="meta-val" style="color:var(--success);">${seo.internal_links}</span></div>
                        <div class="meta-row"><span class="meta-key">External</span><span class="meta-val">${seo.external_links}</span></div>
                        <div class="meta-row"><span class="meta-key">Broken</span><span class="meta-val" style="color:${seo.broken_links?.length>0?'var(--danger)':'var(--success)'};">${seo.broken_links?.length||0}</span></div>
                    </div>
                </div>
                <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;">
                    <div class="section-head"><i data-lucide="image" class="icon-sm"></i> Images</div>
                    <div class="p-4">
                        <div class="meta-row"><span class="meta-key">Total</span><span class="meta-val">${seo.total_images}</span></div>
                        <div class="meta-row"><span class="meta-key">With Alt</span><span class="meta-val" style="color:var(--success);">${seo.img_with_alt}</span></div>
                        <div class="meta-row"><span class="meta-key">Missing Alt</span><span class="meta-val" style="color:${seo.img_no_alt>0?'var(--danger)':'var(--success)'};">${seo.img_no_alt}</span></div>
                        <div class="meta-row"><span class="meta-key">Alt Coverage</span><span class="meta-val">${seo.total_images>0?Math.round(seo.img_with_alt/seo.total_images*100):100}%</span></div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${seo.issues.length>0?`<div style="border:1.5px solid #fecaca;border-radius:16px;overflow:hidden;"><div class="section-head" style="background:var(--danger-bg);color:var(--danger);"><i data-lucide="alert-circle" class="icon-sm"></i> Critical Issues (${seo.issues.length})</div><div class="p-4 max-h-56 overflow-y-auto"><ul style="list-style:disc inside;">${seo.issues.map(i=>`<li class="text-xs" style="color:#991b1b;margin-bottom:5px;">${escapeHtml(i)}</li>`).join('')}</ul></div></div>`:''}
                ${seo.warnings.length>0?`<div style="border:1.5px solid #fde68a;border-radius:16px;overflow:hidden;"><div class="section-head" style="background:var(--warning-bg);color:var(--warning);"><i data-lucide="alert-triangle" class="icon-sm"></i> Warnings (${seo.warnings.length})</div><div class="p-4 max-h-56 overflow-y-auto"><ul style="list-style:disc inside;">${seo.warnings.map(w=>`<li class="text-xs" style="color:#92400e;margin-bottom:5px;">${escapeHtml(w)}</li>`).join('')}</ul></div></div>`:''}
                ${seo.suggestions.length>0?`<div style="border:1.5px solid #bfdbfe;border-radius:16px;overflow:hidden;"><div class="section-head" style="background:#eff6ff;color:var(--accent);"><i data-lucide="lightbulb" class="icon-sm"></i> Suggestions (${seo.suggestions.length})</div><div class="p-4 max-h-40 overflow-y-auto"><ul style="list-style:disc inside;">${seo.suggestions.map(s=>`<li class="text-xs" style="color:#1d4ed8;margin-bottom:5px;">${escapeHtml(s)}</li>`).join('')}</ul></div></div>`:''}
            </div>
            <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-top:16px;">
                <div class="section-head"><i data-lucide="smartphone" class="icon-sm"></i> Mobile Friendliness</div>
                <div class="p-4 flex items-center gap-4">
                    <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.viewport?'var(--success-bg)':'var(--danger-bg)'};">
                        <i data-lucide="smartphone" class="icon" style="color:${seo.viewport?'var(--success)':'var(--danger)'};"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-sm">Viewport Meta Tag</div>
                        <p class="text-xs mt-0.5" style="color:var(--muted);">${seo.viewport?'Viewport configured — page is mobile responsive.':'Missing viewport meta tag — page not mobile optimized.'}</p>
                    </div>
                </div>
            </div>

            <!-- ===== KEYWORD COPY CARDS ===== -->
            <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-top:16px;">
                <div class="section-head"><i data-lucide="copy" class="icon-sm"></i> Keyword Strategy — Copy Cards</div>
                <div class="p-4 space-y-3" id="kwCopyCards"></div>
            </div>

            <!-- ===== SITEMAP CHECKER ===== -->
            <div id="sitemapSection_${host.replace(/\./g,'_')}" style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-top:16px;">
                <div class="section-head"><i data-lucide="map" class="icon-sm"></i> Sitemap Checker
                    <button onclick="checkSitemap('${escapeHtml(actualData.url)}', '${host.replace(/\./g,'_')}')" class="ml-auto btn-secondary" style="padding:5px 12px;font-size:11px!important;min-height:28px;">
                        <i data-lucide="search" class="icon-sm"></i> Check Sitemap
                    </button>
                </div>
                <div id="sitemapResult_${host.replace(/\./g,'_')}" class="p-4">
                    <p class="text-sm" style="color:var(--muted);">Click "Check Sitemap" to detect sitemap.xml on this domain.</p>
                </div>
            </div>

            <!-- ===== GOOGLE SEARCH CONSOLE LINK ===== -->
            ${(seo.issues.length>0||seo.warnings.length>0)?`
            <div class="mt-4 p-4 rounded-xl flex flex-col sm:flex-row items-start sm:items-center gap-3" style="background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1.5px solid #a7f3d0;">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i data-lucide="trending-up" class="icon" style="color:var(--success);flex-shrink:0;"></i>
                        <strong class="text-sm" style="color:var(--success);">Improve Your Google Rankings</strong>
                    </div>
                    <p class="text-xs" style="color:#065f46;">This page has ${seo.issues.length} issue(s) and ${seo.warnings.length} warning(s). Use Google's official resources to fix them and boost your ranking.</p>
                </div>
                <div class="flex flex-wrap gap-2 flex-shrink-0">
                    <a href="${(()=>{try{const o=new URL(actualData.url);return 'https://search.google.com/search-console?resource_id=sc-domain:'+encodeURIComponent(o.hostname);}catch(e){return 'https://search.google.com/search-console/welcome';}})()}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1.5px solid #34d399;color:#065f46;font-size:12px;font-weight:700;padding:7px 14px;border-radius:10px;text-decoration:none;transition:all .15s;"
                       onmouseover="this.style.background='#34d399';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#065f46'">
                        <i data-lucide="external-link" class="icon-sm"></i> Search Console
                    </a>
                    <a href="https://developers.google.com/search" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1.5px solid #60a5fa;color:#1d4ed8;font-size:12px;font-weight:700;padding:7px 14px;border-radius:10px;text-decoration:none;transition:all .15s;"
                       onmouseover="this.style.background='#60a5fa';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#1d4ed8'">
                        <i data-lucide="book-open" class="icon-sm"></i> SEO Docs
                    </a>
                </div>
            </div>`:''}
        </div>

        <!-- ===== SOCIAL ===== -->
        <div id="tabSocial" style="display:none;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="facebook" class="icon" style="color:#1877f2;"></i> Facebook / Open Graph</h3>
                    <div class="social-preview mb-4">
                        <div class="fb-card">
                            <div class="fb-card-img" style="overflow:hidden;">${socialImgHtml(seo.og_image, seo.favicon_url, 180, 'og:image')}</div>
                            <div class="fb-card-body">
                                <div class="fb-card-domain">${escapeHtml(host)}</div>
                                <div class="fb-card-title">${escapeHtml(seo.og_title||seo.title||'Missing Title')}</div>
                                <div class="fb-card-desc">${escapeHtml((seo.og_description||seo.description||'No description').substring(0,120))}</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="meta-row"><span class="meta-key">og:title</span><span class="meta-val">${seo.og_title?escapeHtml(seo.og_title.substring(0,50)):'<span class="tag tag-issue">Missing</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">og:description</span><span class="meta-val">${seo.og_description?'<span class="tag tag-good">Present</span>':'<span class="tag tag-issue">Missing</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">og:image</span><span class="meta-val">${seo.og_image?'<span class="tag tag-good">Present</span>':'<span class="tag tag-warn">Missing — using favicon</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">og:type</span><span class="meta-val">${seo.og_type?escapeHtml(seo.og_type):'<span class="tag tag-neutral">Not set</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">og:url</span><span class="meta-val">${seo.og_url?'<span class="tag tag-good">Present</span>':'<span class="tag tag-neutral">Not set</span>'}</span></div>
                    </div>
                </div>
                <div>
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="twitter" class="icon" style="color:#000;"></i> Twitter / X Card</h3>
                    <div style="background:#f7f9f9;border-radius:12px;padding:12px;margin-bottom:16px;">
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                            ${socialImgHtml(seo.twitter_image, seo.favicon_url, 160, 'twitter:image')}
                            <div style="padding:12px;">
                                <div style="font-weight:700;font-size:14px;">${escapeHtml(seo.twitter_title||seo.title||'No Title')}</div>
                                <div style="font-size:12px;color:#64748b;margin-top:2px;">${escapeHtml((seo.twitter_description||seo.description||'No description').substring(0,100))}</div>
                                <div style="font-size:11px;color:#94a3b8;margin-top:4px;display:flex;align-items:center;gap:4px;">
                                    <i data-lucide="link" class="icon-sm"></i> ${escapeHtml(host)}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="meta-row"><span class="meta-key">twitter:card</span><span class="meta-val">${seo.twitter_card?escapeHtml(seo.twitter_card):'<span class="tag tag-neutral">Not set</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">twitter:title</span><span class="meta-val">${seo.twitter_title?escapeHtml(seo.twitter_title.substring(0,50)):'<span class="tag tag-neutral">Optional</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">twitter:description</span><span class="meta-val">${seo.twitter_description?'<span class="tag tag-good">Present</span>':'<span class="tag tag-neutral">Optional</span>'}</span></div>
                        <div class="meta-row"><span class="meta-key">twitter:image</span><span class="meta-val">${seo.twitter_image?'<span class="tag tag-good">Present</span>':'<span class="tag tag-neutral">Optional — using favicon</span>'}</span></div>
                    </div>
                </div>
            </div>
            <div class="mt-5 p-4 rounded-xl text-sm flex items-start gap-2" style="background:#eff6ff;color:#1e40af;">
                <i data-lucide="info" class="icon-sm" style="flex-shrink:0;margin-top:1px;"></i>
                <span><strong>Tip:</strong> When og:image is missing, the website favicon is used as a fallback placeholder in previews. Use Facebook's Sharing Debugger and Twitter Card Validator to verify your tags.</span>
            </div>
        </div>

        <!-- ===== SECURITY ===== -->
        <div id="tabSecurity" style="display:none;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="lock" class="icon" style="color:var(--accent);"></i> SSL / HTTPS</h3>
                    <div class="flex items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.ssl_valid?'var(--success-bg)':'var(--danger-bg)'};">
                            <i data-lucide="${seo.ssl_valid?'shield-check':'shield-off'}" class="icon" style="color:${seo.ssl_valid?'var(--success)':'var(--danger)'};"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm">${seo.ssl_valid?'SSL Certificate Valid':'SSL Certificate Missing'}</div>
                            <p class="text-xs mt-0.5" style="color:var(--muted);">${seo.ssl_valid?'Site served over HTTPS':'HTTPS required for SEO & trust signals'}</p>
                        </div>
                    </div>
                </div>
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="alert-triangle" class="icon" style="color:var(--warning);"></i> Mixed Content</h3>
                    <div class="flex items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.mixed_content.length===0?'var(--success-bg)':'var(--warning-bg)'};">
                            <i data-lucide="${seo.mixed_content.length===0?'check-circle':'alert-triangle'}" class="icon" style="color:${seo.mixed_content.length===0?'var(--success)':'var(--warning)'};"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm">${seo.mixed_content.length===0?'No Mixed Content':seo.mixed_content.length+' Mixed Content Items'}</div>
                            <p class="text-xs mt-0.5" style="color:var(--muted);">${seo.mixed_content.length===0?'All resources use HTTPS':'HTTP resources on an HTTPS page'}</p>
                        </div>
                    </div>
                    ${seo.mixed_content.length>0?`<div class="mt-3"><ul class="text-xs" style="color:var(--danger);">${seo.mixed_content.slice(0,3).map(m=>`<li class="truncate mb-1" style="display:flex;align-items:center;gap:4px;"><i data-lucide="link-2-off" class="icon-sm" style="flex-shrink:0;"></i>${escapeHtml(m)}</li>`).join('')}</ul></div>`:''}
                </div>
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="bot" class="icon" style="color:var(--accent);"></i> robots.txt</h3>
                    <div class="flex items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.robots_txt_valid?'var(--success-bg)':'var(--warning-bg)'};">
                            <i data-lucide="${seo.robots_txt_valid?'file-check':'file-x'}" class="icon" style="color:${seo.robots_txt_valid?'var(--success)':'var(--warning)'};"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">${seo.robots_txt_valid?'Found at /robots.txt':'Not Found'}</div>
                            <p class="text-xs mt-0.5" style="color:var(--muted);">${seo.robots_txt_valid?'File exists and accessible':'Create a robots.txt for crawler control'}</p>
                        </div>
                        ${seo.robots_txt_valid?`<a href="${escapeHtml((()=>{try{return new URL(actualData.url).origin}catch(e){return ''}})()+'/robots.txt')}" target="_blank" rel="noopener" title="Open robots.txt" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--surface-2);border:1.5px solid var(--border);color:var(--muted);text-decoration:none;flex-shrink:0;"><i data-lucide="external-link" class="icon-sm"></i></a>`:''}
                    </div>
                    ${seo.robots_txt_content?`<pre class="text-xs mt-3 p-3 rounded-lg overflow-x-auto" style="background:var(--surface-2);color:var(--ink-3);max-height:120px;white-space:pre-wrap;word-break:break-all;">${escapeHtml(seo.robots_txt_content)}</pre>`:''}
                </div>
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="map-pin" class="icon" style="color:#ef4444;"></i> Local Business</h3>
                    <div class="flex items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.local_business_schema?'var(--success-bg)':'var(--surface-2)'};">
                            <i data-lucide="building-2" class="icon" style="color:${seo.local_business_schema?'var(--success)':'var(--muted)'};"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm">${seo.local_business_schema?'LocalBusiness Schema Found':'No LocalBusiness Schema'}</div>
                            <p class="text-xs mt-0.5" style="color:var(--muted);">${seo.local_business_schema?'Good for local SEO rankings':'Add LocalBusiness schema for local visibility'}</p>
                        </div>
                    </div>
                    ${seo.maps_presence?`<div class="mt-2 flex items-center gap-1 text-xs" style="color:var(--success);"><i data-lucide="map" class="icon-sm"></i> Google Maps embedded on page</div>`:''}
                </div>
            </div>

            <!-- ===== CRAWLABILITY SECTION ===== -->
            <div class="mt-5 mb-2">
                <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--muted);letter-spacing:.1em;">Crawlability</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- SITEMAP CARD -->
                <div style="border:1.5px solid ${seo.sitemap_valid?'#a7f3d0':'#fde68a'};border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="map" class="icon" style="color:${seo.sitemap_valid?'var(--success)':'var(--warning)'};"></i> sitemap.xml
                        ${seo.sitemap_valid&&seo.sitemap_url_count>0?`<span class="tag tag-good ml-auto" style="font-size:10px;">${seo.sitemap_url_count} URLs</span>`:''}
                    </h3>
                    <div class="flex items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.sitemap_valid?'var(--success-bg)':'var(--warning-bg)'};">
                            <i data-lucide="${seo.sitemap_valid?'file-check':'file-x'}" class="icon" style="color:${seo.sitemap_valid?'var(--success)':'var(--warning)'};"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">${seo.sitemap_valid?'Sitemap Found':'No Sitemap Detected'}</div>
                            <p class="text-xs mt-0.5 truncate" style="color:var(--muted);">${seo.sitemap_valid?escapeHtml(seo.sitemap_url):'Add sitemap.xml to improve crawlability'}</p>
                        </div>
                        ${seo.sitemap_valid?`<a href="${escapeHtml(seo.sitemap_url)}" target="_blank" rel="noopener" title="Open sitemap.xml" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--surface-2);border:1.5px solid var(--border);color:var(--muted);text-decoration:none;flex-shrink:0;"><i data-lucide="external-link" class="icon-sm"></i></a>`:''}
                    </div>
                    ${seo.robots_sitemap_ref?`
                    <div class="mt-3 flex items-center gap-2 text-xs p-2 rounded-lg" style="background:${seo.robots_sitemap_match?'var(--success-bg)':'var(--warning-bg)'};color:${seo.robots_sitemap_match?'var(--success)':'#92400e'};">
                        <i data-lucide="${seo.robots_sitemap_match?'check-circle':'alert-triangle'}" class="icon-sm" style="flex-shrink:0;"></i>
                        <span><strong>robots.txt ref:</strong> ${escapeHtml(seo.robots_sitemap_ref.substring(0,50))} — ${seo.robots_sitemap_match?'✓ matches found sitemap':'⚠ mismatch with found sitemap'}</span>
                    </div>`:''}
                    ${seo.sitemap_content?`<pre class="text-xs mt-3 p-3 rounded-lg overflow-x-auto" style="background:var(--surface-2);color:var(--ink-3);max-height:140px;white-space:pre-wrap;word-break:break-all;">${escapeHtml(seo.sitemap_content)}</pre>`:''}
                    ${!seo.sitemap_valid?`<div class="mt-3"><a href="https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:5px;background:var(--warning-bg);border:1.5px solid #fde68a;color:#92400e;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;text-decoration:none;"><i data-lucide="book-open" class="icon-sm"></i> Learn about sitemaps</a></div>`:''}
                </div>
                <!-- LLMS.TXT CARD -->
                <div style="border:1.5px solid ${seo.llms_txt_valid?'#c4b5fd':'var(--border)'};border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="cpu" class="icon" style="color:${seo.llms_txt_valid?'#7c3aed':'var(--muted)'};"></i> llms.txt <span class="tag" style="background:#ede9fe;color:#5b21b6;font-size:9px;margin-left:2px;">AI</span></h3>
                    <div class="flex items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:${seo.llms_txt_valid?'#ede9fe':'var(--surface-2)'};">
                            <i data-lucide="${seo.llms_txt_valid?'check-circle':'circle-dashed'}" class="icon" style="color:${seo.llms_txt_valid?'#7c3aed':'var(--muted)'};"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">${seo.llms_txt_valid?'llms.txt Present':'No llms.txt Found'}</div>
                            <p class="text-xs mt-0.5" style="color:var(--muted);">${seo.llms_txt_valid?'AI crawlers can read site context':'Optional: helps AI bots understand your content'}</p>
                        </div>
                        ${seo.llms_txt_valid?`<a href="${escapeHtml(seo.llms_txt_url)}" target="_blank" rel="noopener" title="Open llms.txt" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:var(--surface-2);border:1.5px solid var(--border);color:var(--muted);text-decoration:none;flex-shrink:0;"><i data-lucide="external-link" class="icon-sm"></i></a>`:''}
                    </div>
                    ${seo.llms_txt_valid&&seo.llms_txt_sections?.length>0?`
                    <div class="mt-3 flex flex-wrap gap-1">
                        ${['Title','Docs','Examples','API','Optional'].map(s=>`<span class="tag" style="font-size:10px;${seo.llms_txt_sections.includes(s)?'background:#ede9fe;color:#5b21b6;':'background:var(--surface-2);color:var(--muted);opacity:.6;'}">${seo.llms_txt_sections.includes(s)?'✓ ':''}${s}</span>`).join('')}
                    </div>`:''}
                    ${seo.llms_txt_content?`<pre class="text-xs mt-3 p-3 rounded-lg overflow-x-auto" style="background:#faf5ff;color:#4c1d95;border:1px solid #ddd6fe;max-height:140px;white-space:pre-wrap;word-break:break-all;">${escapeHtml(seo.llms_txt_content)}</pre>`:''}
                    ${!seo.llms_txt_valid?`<div class="mt-3 flex flex-wrap gap-2">
                        <a href="https://llmstxt.org" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:5px;background:#ede9fe;border:1.5px solid #c4b5fd;color:#5b21b6;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;text-decoration:none;"><i data-lucide="external-link" class="icon-sm"></i> llmstxt.org</a>
                        <a href="https://llmstxt.org/#how-to" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:5px;background:var(--surface-2);border:1.5px solid var(--border);color:var(--muted);font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;text-decoration:none;"><i data-lucide="book-open" class="icon-sm"></i> How to create</a>
                    </div>`:''}
                </div>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;margin-top:16px;">
                <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="phone" class="icon" style="color:var(--accent);"></i> NAP Consistency</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color:var(--muted);">Email Addresses</p>
                        ${seo.nap_consistency?.emails?.length>0?seo.nap_consistency.emails.map(e=>`<p class="text-sm font-medium break-all">${escapeHtml(e)}</p>`).join(''):'<p class="text-sm" style="color:var(--muted);">None detected</p>'}
                    </div>
                </div>
            </div>
            ${seo.broken_links?.length>0?`
            <div style="border:1.5px solid #fecaca;border-radius:16px;overflow:hidden;margin-top:16px;">
                <div class="section-head" style="background:var(--danger-bg);color:var(--danger);"><i data-lucide="link-2-off" class="icon-sm"></i> Broken Links (${seo.broken_links.length})</div>
                <div class="p-4 max-h-48 overflow-y-auto">
                    <ul class="text-xs space-y-1" style="color:#991b1b;">${seo.broken_links.map(l=>`<li class="flex items-center gap-2"><i data-lucide="x-circle" class="icon-sm" style="flex-shrink:0;color:var(--danger);"></i><span class="break-all flex-1">${escapeHtml(l)}</span><a href="${escapeHtml(l)}" target="_blank" rel="noopener" title="Open link" style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:6px;background:var(--danger-bg);border:1px solid #fecaca;color:var(--danger);text-decoration:none;flex-shrink:0;"><i data-lucide="external-link" class="icon-sm"></i></a></li>`).join('')}</ul>
                </div>
            </div>`:''}
        </div>

        <!-- ===== KEYWORDS ===== -->
        <div id="tabKeywords" style="display:none;">
            <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;">
                <div class="section-head"><i data-lucide="bar-chart-2" class="icon-sm"></i> Top Keywords by Density
                    <span class="ml-auto text-xs font-normal flex flex-wrap gap-1" style="color:var(--muted);">
                        <span class="tag" style="background:#dbeafe;color:#1d4ed8;font-size:10px;">TITLE</span>
                        <span class="tag" style="background:#ede9fe;color:#6d28d9;font-size:10px;">DESC</span>
                        <span class="tag tag-meta">META</span>
                        <span class="tag tag-neutral">BODY</span>
                    </span>
                </div>
                <div class="p-4">
                    <p class="text-sm mb-4" style="color:var(--muted);">Total words: <strong style="color:var(--ink);">${seo.word_count.toLocaleString()}</strong>
                    ${seo.meta_keywords?` &nbsp;|&nbsp; Meta keywords: <strong style="color:var(--ink);">${escapeHtml(seo.meta_keywords.substring(0,60))}</strong>`:''}
                    </p>
                    <div>${kwSection(seo)}</div>
                </div>
            </div>
            <div class="p-4 rounded-xl text-sm flex items-start gap-2" style="background:var(--warning-bg);color:#92400e;">
                <i data-lucide="lightbulb" class="icon-sm" style="flex-shrink:0;margin-top:1px;"></i>
                <span><strong>Keyword Strategy:</strong> Keywords are ranked by source priority — <span style="color:#1d4ed8;font-weight:700;">Title</span> → <span style="color:#6d28d9;font-weight:700;">Description</span> → <span style="color:#5b21b6;font-weight:700;">Meta</span> → Body. Ideal density: 1–3%. Keywords in Title &amp; Description are your primary targets.</span>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;margin-top:16px;">
                <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="file-bar-chart" class="icon" style="color:var(--accent);"></i> Content Stats</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--muted);">Word Count</p><p class="heading font-extrabold text-2xl" style="color:var(--ink);">${seo.word_count.toLocaleString()}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--muted);">Reading Time</p><p class="heading font-extrabold text-2xl" style="color:var(--ink);">${Math.ceil(seo.word_count/200)} min</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--muted);">Total Images</p><p class="heading font-extrabold text-2xl" style="color:var(--ink);">${seo.total_images}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:var(--muted);">Alt Coverage</p><p class="heading font-extrabold text-2xl" style="color:var(--ink);">${seo.total_images>0?Math.round(seo.img_with_alt/seo.total_images*100):100}%</p></div>
                </div>
            </div>
        </div>

        <!-- ===== SERP ===== -->
        <div id="tabSerp" style="display:none;">
            <div style="border:1.5px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;">
                <div class="section-head"><i data-lucide="search" class="icon-sm"></i> Google SERP Preview</div>
                <div class="p-4">${seo.serp_preview}</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="star" class="icon" style="color:#f59e0b;"></i> Schema / Rich Snippets</h3>
                    <div class="meta-row"><span class="meta-key">Schema Count</span><span class="meta-val">${seo.schema_count}</span></div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        ${seo.schema_types?.length>0?seo.schema_types.map(t=>`<span class="tag tag-good">${escapeHtml(t)}</span>`).join(''):'<span class="tag tag-issue">None detected</span>'}
                    </div>
                    ${seo.featured_snippet_potential?`<div class="mt-3 p-3 rounded-lg text-sm flex items-center gap-2" style="background:#ecfdf5;color:var(--success);"><i data-lucide="sparkles" class="icon-sm"></i> Featured Snippet Potential detected!</div>`:''}
                    <div class="mt-3 pt-3" style="border-top:1px solid var(--border);">
                        <a href="https://search.google.com/test/rich-results?url=${encodeURIComponent(actualData.url)}" target="_blank" rel="noopener"
                           style="display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#4285f4,#1a73e8);color:#fff;font-size:12px;font-weight:700;padding:9px 14px;border-radius:10px;text-decoration:none;transition:opacity .15s;"
                           onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                            Test Rich Results on Google
                            <i data-lucide="external-link" class="icon-sm" style="margin-left:auto;opacity:.8;"></i>
                        </a>
                    </div>
                </div>
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                    <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="help-circle" class="icon" style="color:var(--accent);"></i> People Also Ask</h3>
                    <ul class="space-y-2">
                        ${seo.people_also_ask?.map(q=>`<li class="text-sm flex items-start gap-2"><i data-lucide="message-circle" class="icon-sm" style="color:var(--accent);flex-shrink:0;margin-top:2px;"></i>${escapeHtml(q)}</li>`).join('')||'<li class="text-sm" style="color:var(--muted);">Not available</li>'}
                    </ul>
                </div>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:16px;padding:16px;">
                <h3 class="heading font-bold mb-3 flex items-center gap-2"><i data-lucide="trending-up" class="icon" style="color:var(--muted);"></i> Related Searches</h3>
                <div class="flex flex-wrap gap-2">
                    ${seo.related_searches?.map(r=>`<span class="tag tag-neutral" style="font-size:12px;">${escapeHtml(r)}</span>`).join('')||'<span class="text-sm" style="color:var(--muted);">Not available</span>'}
                </div>
                <div class="mt-4 p-3 rounded-lg text-sm flex items-start gap-2" style="background:#eff6ff;color:#1d4ed8;">
                    <i data-lucide="trending-up" class="icon-sm" style="flex-shrink:0;margin-top:1px;"></i>
                    <span><strong>Tip:</strong> Use clear headings, numbered lists, tables, and concise direct answers to optimize for featured snippets.</span>
                </div>
            </div>
        </div>

        <!-- ===== TRACKING ===== -->
        <div id="tabTracking" style="display:none;">
            ${(()=>{
                const tools = seo.tracking_tools || [];
                const detected = tools.filter(t=>t.detected);
                const missing  = tools.filter(t=>!t.detected);
                const total    = tools.length;
                const pct      = total>0 ? Math.round(detected.length/total*100) : 0;

                // Group detected by category
                const catOrder = ['Analytics','Tag Manager','Advertising','Heatmap / UX','Chat / Support','CRM / Marketing'];
                const byCat = {};
                detected.forEach(t => { if(!byCat[t.category]) byCat[t.category]=[]; byCat[t.category].push(t); });

                const catIcon = {
                    'Analytics':      'bar-chart-2',
                    'Tag Manager':    'tag',
                    'Advertising':    'megaphone',
                    'Heatmap / UX':   'mouse-pointer-click',
                    'Chat / Support': 'message-circle',
                    'CRM / Marketing':'users',
                };

                // Summary bar
                let html = `
                <div style="border:1.5px solid var(--border);border-radius:16px;padding:20px;margin-bottom:16px;background:linear-gradient(135deg,#f8faff,#f0f4ff);">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                        <div>
                            <div class="heading font-bold text-base" style="color:var(--ink);">Tracking & Analytics Stack</div>
                            <p class="text-xs mt-0.5" style="color:var(--muted);">Tools detected via HTML source scanning</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-center">
                                <div class="heading font-extrabold text-2xl" style="color:var(--success);">${detected.length}</div>
                                <div class="text-xs font-bold uppercase" style="color:var(--muted);">Found</div>
                            </div>
                            <div style="width:1px;height:32px;background:var(--border);"></div>
                            <div class="text-center">
                                <div class="heading font-extrabold text-2xl" style="color:var(--muted);">${missing.length}</div>
                                <div class="text-xs font-bold uppercase" style="color:var(--muted);">Not Found</div>
                            </div>
                            <div style="width:1px;height:32px;background:var(--border);"></div>
                            <div class="text-center">
                                <div class="heading font-extrabold text-2xl" style="color:var(--accent);">${total}</div>
                                <div class="text-xs font-bold uppercase" style="color:var(--muted);">Checked</div>
                            </div>
                        </div>
                    </div>
                    <div style="background:var(--border);border-radius:100px;height:8px;overflow:hidden;">
                        <div style="height:8px;border-radius:100px;width:${pct}%;background:linear-gradient(90deg,var(--success),#34d399);transition:width .6s ease;"></div>
                    </div>
                    <p class="text-xs mt-2" style="color:var(--muted);">${detected.length} of ${total} tools detected (${pct}% coverage)</p>
                </div>`;

                // Detected tools grouped by category
                if (detected.length > 0) {
                    html += `<div class="mb-4"><p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--muted);letter-spacing:.1em;">Detected Tools</p>`;
                    catOrder.forEach(cat => {
                        if (!byCat[cat]) return;
                        html += `<div class="mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <i data-lucide="${catIcon[cat]||'circle'}" class="icon-sm" style="color:var(--accent);"></i>
                                <span class="text-xs font-bold uppercase" style="color:var(--ink-3);letter-spacing:.05em;">${cat}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">`;
                        byCat[cat].forEach(t => {
                            html += `<div style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;border:1.5px solid ${t.color}22;background:${t.color}08;">
                                <div style="width:36px;height:36px;border-radius:9px;background:${t.color};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="check" class="icon-sm" style="color:#fff;"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm" style="color:var(--ink);">${escapeHtml(t.name)}</div>
                                    <div class="text-xs mt-0.5" style="color:var(--success);font-weight:600;">✓ Active</div>
                                </div>
                                <span class="tag" style="background:${t.color}18;color:${t.color};font-size:9px;white-space:nowrap;border:1px solid ${t.color}33;">${escapeHtml(t.category)}</span>
                            </div>`;
                        });
                        html += `</div></div>`;
                    });
                    html += `</div>`;
                } else {
                    html += `<div class="mb-4 p-4 rounded-xl text-sm flex items-center gap-3" style="background:var(--warning-bg);color:#92400e;border:1.5px solid #fde68a;">
                        <i data-lucide="alert-triangle" class="icon" style="flex-shrink:0;"></i>
                        <span><strong>No tracking tools detected.</strong> This page may use tag managers that load scripts dynamically — check with browser DevTools for full visibility.</span>
                    </div>`;
                }

                // Not detected dimmed grid
                html += `<div>
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--muted);letter-spacing:.1em;">Not Detected (${missing.length})</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">`;
                missing.forEach(t => {
                    html += `<div style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;border:1.5px solid var(--border);background:var(--surface);opacity:.55;">
                        <div style="width:28px;height:28px;border-radius:7px;background:var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="minus" class="icon-sm" style="color:var(--muted);"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-semibold truncate" style="color:var(--muted);">${escapeHtml(t.name)}</div>
                            <div class="text-xs" style="color:var(--muted);opacity:.7;">${escapeHtml(t.category)}</div>
                        </div>
                    </div>`;
                });
                html += `</div></div>`;

                // Accuracy note
                html += `<div class="mt-4 p-3 rounded-xl text-xs flex items-start gap-2" style="background:var(--surface-2);color:var(--muted);">
                    <i data-lucide="info" class="icon-sm" style="flex-shrink:0;margin-top:1px;"></i>
                    <span><strong style="color:var(--ink-3);">Detection note:</strong> Results are based on HTML source pattern matching. Tools loaded via Google Tag Manager or server-side may not appear. Use browser DevTools → Network tab or extensions like Wappalyzer for full verification.</span>
                </div>`;

                return html;
            })()}
        </div>

    </div>`;

    refreshIcons();
    renderCopyCards(seo);
    document.getElementById('seoModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function showTab(tabName) {
    ['overview','social','security','keywords','serp','tracking'].forEach(t => {
        const id = 'tab' + t.charAt(0).toUpperCase() + t.slice(1);
        const el = document.getElementById(id);
        const btn = document.getElementById(id + 'Btn');
        if (el) el.style.display = t === tabName ? 'block' : 'none';
        if (btn) btn.classList.toggle('active', t === tabName);
    });
}

function closeModal() {
    document.getElementById('seoModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleDetails(id) {
    document.getElementById(id)?.classList.toggle('active');
    refreshIcons();
}

function updateStats() {
    document.getElementById('statCount').textContent = indexedData.length;
    document.getElementById('statQueue').textContent = queue.length;
    const avg = indexedData.length > 0
        ? Math.round(indexedData.reduce((a,b) => a + b.seo.score, 0) / indexedData.length)
        : 0;
    document.getElementById('statScore').textContent = avg + '%';
    document.getElementById('statScore').style.color = avg>=80?'var(--success)':(avg>=50?'var(--warning)':'var(--danger)');
    const statusText = activeRequests > 0 ? 'Crawling…' : (indexedData.length > 0 ? 'Complete' : 'Ready');
    document.getElementById('statStatus').textContent = statusText;
}

function finish() {
    // Remove any leftover skeleton cards from failed/skipped requests
    document.querySelectorAll('.skeleton-card').forEach(el => el.remove());
    document.getElementById('btnAction').disabled = false;
    document.getElementById('statStatus').textContent = 'Complete';
    document.getElementById('statStatus').classList.remove('pulse-anim');
    document.getElementById('progressWrap').classList.add('hidden');
    refreshIcons();

    // Validate domain via WHOIS before showing sitemap download
    validateDomainBeforeSitemap();
}

async function validateDomainBeforeSitemap() {
    const finishArea = document.getElementById('finishArea');
    const sitemapBtn = document.getElementById('sitemapDownloadBtn');
    const domainStatus = document.getElementById('domainValidStatus');

    // Get domain from indexed data
    let domain = '';
    if (indexedData.length > 0) {
        try { domain = new URL(indexedData[0].url).hostname.replace(/^www\./i,''); } catch {}
    }
    if (!domain) {
        // No data at all — hide finish area
        if (finishArea) finishArea.classList.add('hidden');
        return;
    }

    // Show finish area with validating state
    if (finishArea) finishArea.classList.remove('hidden');
    if (domainStatus) {
        domainStatus.innerHTML = `<span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:rgba(255,255,255,.7);"><i data-lucide="loader" style="width:13px;height:13px;" class="spin"></i> Verifying domain…</span>`;
        refreshIcons();
    }
    if (sitemapBtn) sitemapBtn.style.display = 'none';

    try {
        const resp = await fetch('?action=whois&domain=' + encodeURIComponent(domain));
        const d = await resp.json();

        // Domain is valid if RDAP returned at least a registrar or created date or status
        const isValid = d && !d.error && (d.registrar || d.created || (d.status && d.status.length > 0) || d.nameservers?.length > 0);

        if (isValid) {
            if (sitemapBtn) sitemapBtn.style.display = '';
            if (domainStatus) {
                const exp = d.days_until_expiry;
                const expStr = exp != null ? (exp < 0 ? ` · ⚠ EXPIRED` : ` · Expires in ${exp}d`) : '';
                domainStatus.innerHTML = `<span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#6ee7b7;"><i data-lucide="check-circle" style="width:13px;height:13px;"></i> Domain verified: <strong style="color:#fff;">${escapeHtml(domain)}</strong>${escapeHtml(expStr)}</span>`;
                refreshIcons();
            }
        } else {
            // Domain doesn't exist or RDAP failed
            if (sitemapBtn) sitemapBtn.style.display = 'none';
            if (domainStatus) {
                domainStatus.innerHTML = `<span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:#fca5a5;"><i data-lucide="alert-circle" style="width:13px;height:13px;"></i> Domain not found in registry — sitemap not available for <strong style="color:#fff;">${escapeHtml(domain)}</strong></span>`;
                refreshIcons();
            }
        }
    } catch(e) {
        // Network error — show sitemap button anyway (don't block on network fail)
        if (sitemapBtn) sitemapBtn.style.display = '';
        if (domainStatus) {
            domainStatus.innerHTML = `<span style="font-size:12px;color:rgba(255,255,255,.5);">Could not verify domain (network error)</span>`;
        }
    }
}

function downloadSitemapPHP() {
    if (indexedData.length === 0) { alert('No data to generate sitemap.'); return; }
    const form = document.createElement('form');
    form.method = 'POST'; form.action = '?action=download_sitemap';
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'sitemap_data'; inp.value = JSON.stringify(indexedData);
    form.appendChild(inp); document.body.appendChild(form); form.submit(); document.body.removeChild(form);
}


function escapeHtml(t) {
    if (!t) return '';
    const d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
}

// ── Toast notification ──
function showNotifToast(type, title, message) {
    const existing = document.getElementById('_notifToast');
    if (existing) existing.remove();

    const colors = {
        success: { bg: 'var(--success-bg)', border: '#a7f3d0', icon: '#059669', lucide: 'check-circle' },
        warning: { bg: '#fffbeb',            border: '#fde68a', icon: '#d97706', lucide: 'alert-triangle' },
        danger:  { bg: 'var(--danger-bg)',   border: '#fecaca', icon: '#dc2626', lucide: 'alert-circle' },
        info:    { bg: '#eff6ff',            border: '#bfdbfe', icon: '#3b82f6', lucide: 'info' },
    };
    const c = colors[type] || colors.info;

    const el = document.createElement('div');
    el.id = '_notifToast';
    el.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:9999;background:${c.bg};border:1.5px solid ${c.border};border-radius:14px;padding:12px 16px;max-width:320px;box-shadow:0 8px 24px rgba(0,0,0,.12);display:flex;align-items:flex-start;gap:10px;animation:slideUp .3s cubic-bezier(.34,1.56,.64,1);`;
    el.innerHTML = `
        <i data-lucide="${c.lucide}" style="width:18px;height:18px;color:${c.icon};flex-shrink:0;margin-top:1px;"></i>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:var(--ink);">${escapeHtml(title)}</div>
            ${message ? `<div style="font-size:12px;color:var(--muted);margin-top:2px;">${escapeHtml(message)}</div>` : ''}
        </div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:var(--muted);padding:0;width:20px;height:20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i data-lucide="x" style="width:14px;height:14px;"></i>
        </button>`;
    document.body.appendChild(el);
    lucide.createIcons({ el });
    setTimeout(() => { if (el.parentElement) el.remove(); }, 4000);
}

async function checkSitemap(pageUrl, hostKey) {
    const resultEl = document.getElementById('sitemapResult_' + hostKey);
    if (!resultEl) return;
    resultEl.innerHTML = `<div class="flex items-center gap-2 text-sm" style="color:var(--muted);"><i data-lucide="loader" class="icon-sm spin"></i> Checking for sitemaps…</div>`;
    refreshIcons();
    try {
        const res = await fetch(`?action=check_sitemap&url=${encodeURIComponent(pageUrl)}`);
        const data = await res.json();
        // GSC requires the root property URL (origin + trailing slash)
        let gscHostname = '';
        try { gscHostname = new URL(data.base_url).hostname; } catch(e){}
        const gscBase = 'https://search.google.com/search-console/sitemaps?resource_id=sc-domain:' + encodeURIComponent(gscHostname);
        if (data.sitemaps && data.sitemaps.length > 0) {
            resultEl.innerHTML = `
            <div class="flex items-center gap-2 mb-3" style="color:var(--success);">
                <i data-lucide="check-circle" class="icon-sm"></i>
                <strong class="text-sm">${data.sitemaps.length} sitemap(s) found!</strong>
            </div>
            ${data.sitemaps.map(sm => `
            <div class="sitemap-row">
                <i data-lucide="file-check" class="icon-sm" style="color:var(--success);flex-shrink:0;"></i>
                <a href="${escapeHtml(sm)}" target="_blank" rel="noopener" class="text-sm font-medium break-all flex-1" style="color:var(--accent);">${escapeHtml(sm)}</a>
                <a href="${gscBase}" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;border:1.5px solid #34d399;color:#065f46;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                    <i data-lucide="external-link" class="icon-sm"></i> Search Console
                </a>
            </div>`).join('')}
            ${data.from_robots?.length > 0 ? `<p class="text-xs mt-2" style="color:var(--muted);"><i data-lucide="bot" class="icon-sm"></i> ${data.from_robots.length} sitemap(s) also declared in robots.txt</p>` : ''}`;
        } else {
            resultEl.innerHTML = `
            <div class="flex items-center gap-2 mb-3" style="color:var(--warning);">
                <i data-lucide="alert-triangle" class="icon-sm"></i>
                <strong class="text-sm">No sitemap found</strong>
            </div>
            <p class="text-sm mb-3" style="color:var(--muted);">Checked ${data.checked?.length||0} common paths — no sitemap detected on <strong>${escapeHtml(data.base_url)}</strong>.</p>
            <div class="flex flex-wrap gap-2">
                <a href="https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1.5px solid #60a5fa;color:#1d4ed8;font-size:12px;font-weight:700;padding:6px 12px;border-radius:9px;text-decoration:none;">
                    <i data-lucide="book-open" class="icon-sm"></i> How to Create a Sitemap
                </a>
                <a href="${gscBase}" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;border:1.5px solid #34d399;color:#065f46;font-size:12px;font-weight:700;padding:6px 12px;border-radius:9px;text-decoration:none;">
                    <i data-lucide="external-link" class="icon-sm"></i> Open Search Console
                </a>
            </div>`;
        }
    } catch(e) {
        resultEl.innerHTML = `<p class="text-sm" style="color:var(--danger);">Error checking sitemap. Please try again.</p>`;
    }
    refreshIcons();
}

// _copyStore: maps button id -> raw text to copy (avoids HTML-entity issues with data attributes)
const _copyStore = {};

function copyFromData(btn) {
    const id = btn.dataset.cid;
    const text = id ? (_copyStore[id] || '') : '';
    copyText(text, btn);
}

function copyText(text, btn) {
    const doUI = () => {
        const origHTML = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = '<i data-lucide="check" class="icon-sm"></i> Copied!';
        refreshIcons();
        setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = origHTML; refreshIcons(); }, 2000);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(doUI).catch(() => {
            const ta = document.createElement('textarea');
            ta.value = text; ta.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta);
            doUI();
        });
    } else {
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        doUI();
    }
}

// Build copy button HTML — text stored in _copyStore by id, not in attribute
function makeCopyBtn(text, label) {
    const id = 'cb_' + Math.random().toString(36).substr(2,9);
    _copyStore[id] = text;
    return `<button class="kw-copy-btn" data-cid="${id}" onclick="copyFromData(this)"><i data-lucide="copy" class="icon-sm"></i> ${label}</button>`;
}

// Render copy cards into #kwCopyCards after innerHTML is set
function renderCopyCards(seo) {
    const container = document.getElementById('kwCopyCards');
    if (!container) return;
    let html = '';

    // Title card
    const titleKeywords = [...new Set((seo.title.toLowerCase().match(/\b[a-z]{4,}\b/g)||[]))].slice(0,8).join(', ') || '—';
    const titleBtnId = 'cb_' + Math.random().toString(36).substr(2,9);
    _copyStore[titleBtnId] = seo.title;
    html += `<div class="kw-copy-card">
        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
            <div class="flex items-center gap-2"><span class="tag" style="background:#dbeafe;color:#1d4ed8;font-size:10px;">TITLE</span><span class="font-semibold text-sm">Page Title</span></div>
            <button class="kw-copy-btn" data-cid="${titleBtnId}" onclick="copyFromData(this)"><i data-lucide="copy" class="icon-sm"></i> Copy Title</button>
        </div>
        <p class="text-sm" style="color:var(--ink-3);word-break:break-word;">${escapeHtml(seo.title)}</p>
        <p class="text-xs mt-1" style="color:var(--muted);">Keywords found: <strong>${titleKeywords}</strong></p>
    </div>`;

    // Description card
    const descBtnId = 'cb_' + Math.random().toString(36).substr(2,9);
    _copyStore[descBtnId] = seo.description;
    html += `<div class="kw-copy-card">
        <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
            <div class="flex items-center gap-2"><span class="tag" style="background:#ede9fe;color:#6d28d9;font-size:10px;">DESC</span><span class="font-semibold text-sm">Meta Description</span></div>
            <button class="kw-copy-btn" data-cid="${descBtnId}" onclick="copyFromData(this)"><i data-lucide="copy" class="icon-sm"></i> Copy Description</button>
        </div>
        <p class="text-sm" style="color:var(--ink-3);word-break:break-word;">${escapeHtml(seo.description.substring(0,200))}</p>
    </div>`;

    // Meta keywords card
    if (seo.meta_keywords) {
        const metaBtnId = 'cb_' + Math.random().toString(36).substr(2,9);
        _copyStore[metaBtnId] = seo.meta_keywords;
        html += `<div class="kw-copy-card">
            <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                <div class="flex items-center gap-2"><span class="tag tag-meta" style="font-size:10px;">META</span><span class="font-semibold text-sm">Meta Keywords</span></div>
                <button class="kw-copy-btn" data-cid="${metaBtnId}" onclick="copyFromData(this)"><i data-lucide="copy" class="icon-sm"></i> Copy Keywords</button>
            </div>
            <p class="text-sm" style="color:var(--ink-3);word-break:break-word;">${escapeHtml(seo.meta_keywords)}</p>
        </div>`;
    }

    // Top 5 keywords card
    if (seo.keyword_density && seo.keyword_density.length) {
        const top5 = seo.keyword_density.slice(0,5);
        const allBtnId = 'cb_' + Math.random().toString(36).substr(2,9);
        _copyStore[allBtnId] = top5.map(k=>k.keyword).join(', ');
        const kwChips = top5.map(kw => {
            const chipId = 'cb_' + Math.random().toString(36).substr(2,9);
            _copyStore[chipId] = kw.keyword;
            return `<div style="display:inline-flex;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--border);border-radius:8px;padding:4px 10px;">
                <span class="text-sm font-semibold">${escapeHtml(kw.keyword)}</span>
                <span style="font-size:10px;color:var(--muted);">${kw.percentage}%</span>
                <button class="kw-copy-btn" style="padding:2px 7px;font-size:10px;" data-cid="${chipId}" onclick="copyFromData(this)"><i data-lucide="copy" class="icon-sm"></i></button>
            </div>`;
        }).join('');
        html += `<div class="kw-copy-card">
            <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                <div class="flex items-center gap-2"><span class="tag tag-good" style="font-size:10px;">TOP 5</span><span class="font-semibold text-sm">Top Ranking Keywords</span></div>
                <button class="kw-copy-btn" data-cid="${allBtnId}" onclick="copyFromData(this)"><i data-lucide="copy" class="icon-sm"></i> Copy All</button>
            </div>
            <div class="flex flex-wrap gap-2 mt-1">${kwChips}</div>
        </div>`;
    }

    container.innerHTML = html;
    refreshIcons();
}



