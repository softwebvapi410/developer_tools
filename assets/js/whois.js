// WHOIS + Nameserver Lookup page

// ═══════════════════════════════════════
//  WHOIS + NAMESERVER LOOKUP
// ═══════════════════════════════════════

async function runWhois() {
    const raw = document.getElementById('whoisInput').value.trim();
    const res = document.getElementById('whoisResult');
    const btn = document.getElementById('whoisBtn');
    if (!raw) { document.getElementById('whoisInput').focus(); return; }

    res.style.display = 'block';
    res.innerHTML = `<div class="flex items-center gap-2 py-3 text-sm" style="color:var(--muted);">
        <i data-lucide="loader" class="icon spin"></i> Looking up WHOIS + Nameservers for <strong>${escapeHtml(raw)}</strong>…
    </div>`;
    refreshIcons();
    btn.disabled = true;

    try {
        const resp = await fetch('?action=whois&domain=' + encodeURIComponent(raw));
        const d    = await resp.json();

        if (d.error && !d.registrar) {
            res.innerHTML = `<div class="p-4 rounded-xl" style="background:var(--danger-bg);border:1.5px solid #fecaca;color:var(--danger);">
                <strong>WHOIS Lookup Failed</strong><br>
                <span class="text-sm">${escapeHtml(d.error)}</span>
                <p class="text-xs mt-2" style="color:var(--muted);">This may occur for new TLDs, premium domains, or if the RDAP registry is unreachable.</p>
            </div>`;
            refreshIcons(); btn.disabled = false; return;
        }

        // Expiry color
        const daysExp = d.days_until_expiry;
        let expiryColor = 'var(--success)';
        let expiryBg    = '#d1fae5';
        let expiryNote  = '';
        if (daysExp != null) {
            if (daysExp < 0)   { expiryColor='var(--danger)';  expiryBg='#fee2e2'; expiryNote='(EXPIRED)'; }
            else if (daysExp < 30) { expiryColor='var(--danger)'; expiryBg='#fee2e2'; expiryNote=`(${daysExp}d — RENEW NOW)`; }
            else if (daysExp < 90) { expiryColor='var(--warning)'; expiryBg='#fef3c7'; expiryNote=`(${daysExp} days left)`; }
            else { expiryNote = `(${daysExp} days left)`; }
        }

        // Status badge builder
        const statusBadge = (s) => {
            const good = ['active','ok','registered'];
            const bad  = ['clienthold','serverhold','pendingdelete','redemptionperiod'];
            s = s.toLowerCase();
            const col = bad.some(b=>s.includes(b)) ? {c:'#991b1b',bg:'#fee2e2'} :
                        good.some(g=>s.includes(g)) ? {c:'#065f46',bg:'#d1fae5'} : {c:'#92400e',bg:'#fef3c7'};
            return `<span style="display:inline-block;padding:2px 9px;border-radius:6px;font-size:10px;font-weight:700;background:${col.bg};color:${col.c};margin:2px;">${escapeHtml(s)}</span>`;
        };

        // DNSSEC badge
        const dnssecBadge = d.dnssec === 'Signed'
            ? `<span class="tag" style="background:#d1fae5;color:#065f46;">DNSSEC ✓ Signed</span>`
            : `<span class="tag" style="background:#f3f4f6;color:#6b7280;">DNSSEC Unsigned</span>`;

        let html = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="sm:grid-cols-2">`;

        // ── Left: Registration Info ──
        html += `<div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;grid-column:1/-1;">
            <div class="section-head"><i data-lucide="id-card" class="icon-sm"></i> Domain Registration — ${escapeHtml(d.domain)}</div>
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-0">`;

        const regRows = [
            ['Domain',     d.domain || null],
            ['TLD',        d.tld ? '.'+d.tld : null],
            ['Registrar',  (d.registrar && d.registrar !== 'Not disclosed') ? d.registrar : null],
            ['Registrant', (d.registrant && !d.registrant.includes('Not disclosed')) ? d.registrant : null],
            ['Org',        d.registrant_org || null],
            ['Email',      (d.registrant_email && d.registrant_email !== 'Redacted for privacy') ? d.registrant_email : null],
            ['Created',    d.created || null],
            ['Updated',    d.updated || null],
            ['Expires',    d.expires ? d.expires + ' ' + expiryNote : null],
            ['Handle/ID',  d.handle || null],
        ].filter(([k,v]) => v != null && v !== '');
        regRows.forEach(([k,v], i) => {
            const isExp = k === 'Expires' && d.expires;
            html += `<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:8px;border-bottom:1px solid var(--border);gap:8px;">
                <span class="text-xs font-semibold" style="color:var(--muted);flex-shrink:0;min-width:90px;">${k}</span>
                <span class="text-xs font-bold text-right" style="color:${isExp?expiryColor:'var(--ink)'};word-break:break-all;">${escapeHtml(String(v))}</span>
            </div>`;
        });

        html += `</div>
                <!-- Status badges -->
                <div class="mt-3 flex flex-wrap items-center gap-1">
                    <span class="text-xs font-semibold mr-1" style="color:var(--muted);">Status:</span>
                    ${d.status&&d.status.length ? d.status.map(statusBadge).join('') : statusBadge('unknown')}
                </div>
                <div class="mt-2">${dnssecBadge}</div>
            </div>
        </div>`;

        // ── Nameservers (RDAP + Live DNS) ──
        const rdapNs = d.nameservers || [];
        const liveNs = d.nameservers_live || [];
        const allNs  = [...new Set([...rdapNs, ...liveNs])];

        html += `<div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;grid-column:1/-1;">
            <div class="section-head"><i data-lucide="server" class="icon-sm" style="color:#059669;"></i> Nameservers
                <span class="tag tag-good ml-2" style="font-size:10px;">${allNs.length} server${allNs.length!==1?'s':''}</span>
            </div>`;

        if (!allNs.length) {
            html += `<p class="p-4 text-sm" style="color:var(--muted);">No nameservers found.</p>`;
        } else {
            html += `<div>`;
            allNs.forEach((ns, i) => {
                const fromRdap = rdapNs.includes(ns);
                const fromLive = liveNs.includes(ns);
                const reg = detectRegistrar(ns);
                html += `<div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:${i<allNs.length-1?'1px solid var(--border)':'none'};">
                    <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="server" class="icon-sm" style="color:#059669;"></i>
                    </div>
                    <span class="font-mono font-semibold text-sm flex-1" style="color:var(--ink);">${escapeHtml(ns)}</span>
                    ${reg?`<span class="tag" style="background:${reg.bg};color:${reg.color};font-size:9px;">${reg.name}</span>`:''}
                    <div class="flex gap-1">
                        ${fromRdap?`<span class="tag tag-neutral" style="font-size:9px;">RDAP</span>`:''}
                        ${fromLive?`<span class="tag tag-good" style="font-size:9px;">Live DNS</span>`:''}
                    </div>
                </div>`;
            });
            html += `</div>`;
        }
        html += `</div>`;

        // ── Expiry warning banner ──
        if (daysExp != null && daysExp < 60) {
            const urgent = daysExp < 0 || daysExp < 7;
            html += `<div style="padding:14px 16px;border-radius:12px;background:${urgent?'#fee2e2':'#fef3c7'};border:1.5px solid ${urgent?'#fca5a5':'#fcd34d'};display:flex;align-items:center;gap:10px;grid-column:1/-1;">
                <i data-lucide="${urgent?'alert-octagon':'alert-triangle'}" class="icon" style="color:${urgent?'var(--danger)':'var(--warning)'};flex-shrink:0;"></i>
                <div>
                    <p class="font-bold text-sm" style="color:${urgent?'var(--danger)':'#92400e'};">${daysExp<0?'Domain has EXPIRED!':daysExp<7?'Domain expires in '+daysExp+' day(s) — URGENT':'Domain expiring in '+daysExp+' days'}</p>
                    <p class="text-xs mt-0.5" style="color:${urgent?'#991b1b':'#92400e'};">Log in to your registrar dashboard immediately to renew this domain.</p>
                </div>
            </div>`;
        }

        html += `</div>`; // close grid

        res.innerHTML = html;
    } catch(e) {
        res.innerHTML = `<div class="p-4 rounded-xl text-sm" style="background:var(--danger-bg);color:var(--danger);">WHOIS lookup failed — network error. Please try again. (${escapeHtml(e.message||'')})</div>`;
    }
    refreshIcons();
    btn.disabled = false;
}

function detectRegistrar(ns) {
    ns = (ns||'').toLowerCase();
    if (/cloudflare/.test(ns))   return {name:'Cloudflare',color:'#c2410c',bg:'#fff7ed'};
    if (/awsdns/.test(ns))       return {name:'AWS Route 53',color:'#ea580c',bg:'#fff7ed'};
    if (/google/.test(ns))       return {name:'Google DNS',color:'#2563eb',bg:'#dbeafe'};
    if (/azure-dns/.test(ns))    return {name:'Azure DNS',color:'#0284c7',bg:'#e0f2fe'};
    if (/domaincontrol/.test(ns)) return {name:'GoDaddy',color:'#16a34a',bg:'#dcfce7'};
    if (/registrar-servers|namecheap/.test(ns)) return {name:'Namecheap',color:'#d97706',bg:'#fffbeb'};
    if (/bluehost|hostgator|a2hosting/.test(ns)) return {name:'Shared Host',color:'#6b7280',bg:'#f3f4f6'};
    if (/vercel/.test(ns))       return {name:'Vercel',color:'#000',bg:'#f3f4f6'};
    if (/netlify/.test(ns))      return {name:'Netlify',color:'#0ea5e9',bg:'#e0f2fe'};
    if (/squarespace/.test(ns))  return {name:'Squarespace',color:'#111827',bg:'#f3f4f6'};
    if (/wixdns/.test(ns))       return {name:'Wix',color:'#2563eb',bg:'#dbeafe'};
    if (/shopdns/.test(ns))      return {name:'Shopify',color:'#059669',bg:'#d1fae5'};
    return null;
}

