// DNS Lookup page

// ═══════════════════════════════════════════════════
//  DNS TOOLS — powered by Google DNS-over-HTTPS proxy
// ═══════════════════════════════════════════════════

const DNS_META = {
    A:     {color:'#2563eb',bg:'#dbeafe',border:'#93c5fd',desc:'IPv4 Address'},
    AAAA:  {color:'#4f46e5',bg:'#ede9fe',border:'#a5b4fc',desc:'IPv6 Address'},
    MX:    {color:'#0284c7',bg:'#e0f2fe',border:'#7dd3fc',desc:'Mail Exchange'},
    NS:    {color:'#059669',bg:'#d1fae5',border:'#6ee7b7',desc:'Name Server'},
    TXT:   {color:'#b45309',bg:'#fef3c7',border:'#fcd34d',desc:'Text Record'},
    CNAME: {color:'#7c3aed',bg:'#f5f3ff',border:'#c4b5fd',desc:'Alias (CNAME)'},
    SOA:   {color:'#be123c',bg:'#ffe4e6',border:'#fda4af',desc:'Start of Authority'},
    CAA:   {color:'#0e7490',bg:'#cffafe',border:'#67e8f9',desc:'CA Authorization'},
    SRV:   {color:'#15803d',bg:'#dcfce7',border:'#86efac',desc:'Service Record'},
    PTR:   {color:'#6b7280',bg:'#f3f4f6',border:'#d1d5db',desc:'Pointer Record'},
};

function quickDns(type) {
    document.getElementById('dnsType').value = type;
    // Highlight active pill
    document.querySelectorAll('[id^="dnsQ_"]').forEach(b => b.classList.remove('active'));
    const pill = document.getElementById('dnsQ_' + type);
    if (pill) pill.classList.add('active');
    runDnsLookup();
}

async function runDnsLookup() {
    const raw  = document.getElementById('dnsInput').value.trim();
    const type = document.getElementById('dnsType').value;
    const res  = document.getElementById('dnsResult');
    const btn  = document.getElementById('dnsBtn');
    if (!raw) { document.getElementById('dnsInput').focus(); return; }

    // Highlight pill
    document.querySelectorAll('[id^="dnsQ_"]').forEach(b => b.classList.remove('active'));
    const pill = document.getElementById('dnsQ_' + type);
    if (pill) pill.classList.add('active');

    res.style.display = 'block';
    res.innerHTML = `<div class="flex items-center gap-2 py-4 text-sm" style="color:var(--muted);">
        <i data-lucide="loader" class="icon spin"></i>
        Querying <strong>${escapeHtml(type)}</strong> records for <strong>${escapeHtml(raw)}</strong>…
    </div>`;
    refreshIcons();
    btn.disabled = true;

    try {
        const resp = await fetch('?action=dns_lookup&domain=' + encodeURIComponent(raw) + '&type=' + encodeURIComponent(type));
        const data = await resp.json();

        if (data.error) {
            res.innerHTML = `<div class="flex items-center gap-2 p-4 rounded-xl" style="background:var(--danger-bg);border:1.5px solid #fecaca;color:var(--danger);">
                <i data-lucide="alert-circle" class="icon-sm flex-shrink-0"></i>
                <span class="text-sm font-semibold">${escapeHtml(data.error)}</span>
            </div>`;
            refreshIcons(); btn.disabled = false; return;
        }

        // ── Header bar ──
        let html = `<div class="flex flex-wrap items-center gap-2 mb-4 p-3 rounded-xl" style="background:var(--surface-2);border:1.5px solid var(--border);">
            <i data-lucide="database" class="icon-sm" style="color:var(--muted);flex-shrink:0;"></i>
            <span class="font-bold text-sm">${escapeHtml(data.domain)}</span>
            <span class="tag tag-neutral">${data.count} record${data.count!==1?'s':''}</span>
            <span class="tag" style="background:#ede9fe;color:#6d28d9;font-size:10px;">${escapeHtml(data.type)}</span>
            <span class="text-xs ml-auto" style="color:var(--muted);">${escapeHtml(data.source||'')} · ${escapeHtml(data.queried_at||'')}</span>
        </div>`;

        if (!data.records || data.records.length === 0) {
            html += `<div class="text-center py-8" style="color:var(--muted);">
                <i data-lucide="inbox" class="icon-xl mx-auto mb-3 opacity-30"></i>
                <p class="text-sm font-semibold">No ${type==='ALL'?'':type+' '}records found</p>
                <p class="text-xs mt-1">Domain may not exist or record type not set</p>
            </div>`;
        } else {
            // Group records by type
            const grouped = {};
            data.records.forEach(r => {
                const t = (r.record_type||'?').toUpperCase();
                (grouped[t] = grouped[t]||[]).push(r);
            });
            const typeOrder = ['A','AAAA','MX','NS','TXT','CNAME','SOA','CAA','SRV','PTR'];
            const sorted = [...typeOrder.filter(t=>grouped[t]), ...Object.keys(grouped).filter(t=>!typeOrder.includes(t))];

            for (const t of sorted) {
                const m = DNS_META[t] || {color:'#6b7280',bg:'#f3f4f6',border:'#e5e7eb',desc:t};
                html += `<div class="mb-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span style="display:inline-block;padding:2px 10px;background:${m.bg};color:${m.color};font-size:10px;font-weight:800;border-radius:6px;letter-spacing:.06em;border:1.5px solid ${m.border};">${t}</span>
                        <span class="text-xs" style="color:var(--muted);">${m.desc}</span>
                        <span class="text-xs ml-auto" style="color:var(--muted);">${grouped[t].length} record${grouped[t].length!==1?'s':''}</span>
                    </div>
                    <div style="background:#fff;border:1.5px solid var(--border);border-radius:12px;overflow:hidden;">`;

                for (const r of grouped[t]) {
                    const ttlBadge = r.ttl!=null ? `<span class="text-xs" style="color:var(--muted);flex-shrink:0;">TTL ${r.ttl}s</span>` : '';
                    const hostLabel = r.host && r.host !== data.domain ? `<span class="text-xs mr-2" style="color:var(--muted);">${escapeHtml(r.host)}</span>` : '';
                    let val = '';

                    if (t==='A')    val = `<span class="font-mono font-bold" style="color:var(--ink);font-size:14px;">${escapeHtml(r.ip||r.raw||'')}</span>`;
                    else if (t==='AAAA') val = `<span class="font-mono font-bold" style="color:var(--ink);font-size:13px;">${escapeHtml(r.ipv6||r.raw||'')}</span>`;
                    else if (t==='NS' || t==='CNAME' || t==='PTR')
                        val = `<span class="font-mono font-bold" style="color:var(--ink);font-size:13px;">${escapeHtml(r.target||r.raw||'')}</span>`;
                    else if (t==='MX') {
                        const prov = detectMailProvider(r.target||'');
                        val = `<span style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="font-mono font-bold" style="color:var(--ink);font-size:13px;">${escapeHtml(r.target||r.raw||'')}</span>
                            <span class="tag tag-neutral" style="font-size:10px;">Priority ${r.pri??'?'}</span>
                            ${prov?`<span class="tag" style="background:${prov.bg};color:${prov.color};font-size:10px;">${prov.name}</span>`:''}
                        </span>`;
                    } else if (t==='TXT') {
                        const txt = r.txt||r.raw||'';
                        let badge = '';
                        if (/^v=spf1/i.test(txt))   badge = `<span class="tag" style="background:#d1fae5;color:#065f46;font-size:9px;margin-left:6px;">SPF</span>`;
                        else if (/v=DMARC1/i.test(txt)) badge = `<span class="tag" style="background:#dbeafe;color:#1e40af;font-size:9px;margin-left:6px;">DMARC</span>`;
                        else if (/v=DKIM1/i.test(txt))  badge = `<span class="tag" style="background:#fef9c3;color:#92400e;font-size:9px;margin-left:6px;">DKIM</span>`;
                        val = `<span class="font-mono" style="font-size:11px;color:var(--ink-3);word-break:break-all;line-height:1.6;">${escapeHtml(txt)}</span>${badge}`;
                    } else if (t==='SOA') {
                        val = `<div class="text-xs font-mono" style="color:var(--ink-3);line-height:1.8;">
                            <div><strong style="color:var(--muted);">Primary NS:</strong> ${escapeHtml(r.mname||'')}</div>
                            <div><strong style="color:var(--muted);">Responsible:</strong> ${escapeHtml(r.rname||'')}</div>
                            <div class="flex flex-wrap gap-x-4 mt-1">
                                <span><strong style="color:var(--muted);">Serial:</strong> ${r.serial||'?'}</span>
                                <span><strong style="color:var(--muted);">Refresh:</strong> ${r.refresh||'?'}s</span>
                                <span><strong style="color:var(--muted);">Retry:</strong> ${r.retry||'?'}s</span>
                                <span><strong style="color:var(--muted);">Expire:</strong> ${r.expire||'?'}s</span>
                                <span><strong style="color:var(--muted);">Min TTL:</strong> ${r['minimum-ttl']||r.ttl||'?'}s</span>
                            </div>
                        </div>`;
                    } else if (t==='CAA') {
                        val = `<span class="font-mono text-sm" style="color:var(--ink);">${r.flags||0} ${escapeHtml(r.tag||'')} &quot;${escapeHtml(r.value||r.raw||'')}&quot;</span>`;
                    } else if (t==='SRV') {
                        val = `<span class="font-mono text-sm" style="color:var(--ink);">Pri:${r.priority||'?'} W:${r.weight||'?'} Port:${r.port||'?'} → ${escapeHtml(r.target||r.raw||'')}</span>`;
                    } else {
                        val = `<span class="font-mono text-sm" style="color:var(--ink-3);word-break:break-all;">${escapeHtml(r.raw||JSON.stringify(r))}</span>`;
                    }

                    html += `<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                        <div style="flex:1;min-width:0;">${hostLabel}${val}</div>${ttlBadge}
                    </div>`;
                }
                html += `</div></div>`;
            }
        }

        // Email health panel
        if (data.email_health) {
            const eh = data.email_health;
            html += `<div class="mt-3 p-4 rounded-xl" style="background:#f8fafc;border:1.5px solid var(--border);">
                <div class="flex items-center gap-2 mb-3"><i data-lucide="shield" class="icon-sm" style="color:var(--accent);"></i><strong class="text-sm">Email Security</strong></div>
                <div class="flex flex-wrap gap-2">
                    ${emailSecBadge('SPF',  eh.spf,   'Sender Policy Framework')}
                    ${emailSecBadge('DMARC',eh.dmarc, 'Domain Message Auth')}
                    ${emailSecBadge('DKIM', eh.dkim,  'DomainKeys Identified Mail', true)}
                </div>
                ${eh.mx_issues&&eh.mx_issues.length?`<div class="mt-3">${eh.mx_issues.map(i=>`<p class="text-xs mt-1" style="color:var(--danger);"><i data-lucide="alert-circle" class="icon-sm"></i> ${escapeHtml(i)}</p>`).join('')}</div>`:''}
            </div>`;
        }

        if (data.errors&&data.errors.length) {
            html += `<div class="mt-2">${data.errors.map(e=>`<p class="text-xs mt-1" style="color:var(--warning);"><i data-lucide="alert-triangle" class="icon-sm"></i> ${escapeHtml(e)}</p>`).join('')}</div>`;
        }

        res.innerHTML = html;
    } catch(e) {
        res.innerHTML = `<div class="flex items-center gap-2 p-4 rounded-xl" style="background:var(--danger-bg);border:1.5px solid #fecaca;color:var(--danger);">
            <i data-lucide="wifi-off" class="icon-sm"></i>
            <span class="text-sm">DNS lookup failed — network error. Please try again.</span>
        </div>`;
    }
    refreshIcons();
    btn.disabled = false;
}

function emailSecBadge(label, ok, tip, warn=false) {
    const st = ok ? 'ok' : (warn ? 'warn' : 'bad');
    const cfg = {ok:{bg:'#d1fae5',color:'#065f46',border:'#6ee7b7',icon:'shield-check'},
                 warn:{bg:'#fef3c7',color:'#92400e',border:'#fcd34d',icon:'alert-triangle'},
                 bad:{bg:'#fee2e2',color:'#991b1b',border:'#fca5a5',icon:'shield-x'}};
    const c = cfg[st];
    return `<div title="${tip}" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:9px;background:${c.bg};border:1.5px solid ${c.border};">
        <i data-lucide="${c.icon}" class="icon-sm" style="color:${c.color};"></i>
        <span class="text-xs font-bold" style="color:${c.color};">${label}: ${ok?'Pass':(warn?'Unknown':'Fail')}</span>
    </div>`;
}

