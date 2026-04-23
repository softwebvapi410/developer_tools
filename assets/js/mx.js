// MX / Email Check page

// ═══════════════════════════════════════
//  CHECK MX
// ═══════════════════════════════════════

async function runMxCheck() {
    const raw = document.getElementById('mxInput').value.trim();
    const res = document.getElementById('mxResult');
    const btn = document.getElementById('mxBtn');
    if (!raw) { document.getElementById('mxInput').focus(); return; }

    res.style.display = 'block';
    res.innerHTML = `<div class="flex items-center gap-2 py-3 text-sm" style="color:var(--muted);">
        <i data-lucide="loader" class="icon spin"></i> Checking MX records for <strong>${escapeHtml(raw)}</strong>…
    </div>`;
    refreshIcons();
    btn.disabled = true;

    try {
        const resp = await fetch('?action=dns_lookup&domain=' + encodeURIComponent(raw) + '&type=MX');
        const data = await resp.json();

        if (data.error) {
            res.innerHTML = `<div class="p-4 rounded-xl" style="background:var(--danger-bg);border:1.5px solid #fecaca;color:var(--danger);font-size:14px;">${escapeHtml(data.error)}</div>`;
            refreshIcons(); btn.disabled = false; return;
        }

        const mxRecs = (data.records||[]).filter(r=>(r.record_type||'').toUpperCase()==='MX').sort((a,b)=>(a.pri||0)-(b.pri||0));
        data.mx_records = mxRecs; // for provider-aware SPF recommendation
        const eh = data.email_health || {};
        let html = `<div class="p-4 rounded-xl" style="background:#f8fafc;border:1.5px solid var(--border);">`;

        if (!mxRecs.length) {
            html += `<div class="flex items-center gap-2 mb-2" style="color:var(--danger);">
                <i data-lucide="x-circle" class="icon"></i>
                <strong>No MX records found for ${escapeHtml(data.domain)}</strong>
            </div>
            <p class="text-sm" style="color:var(--muted);">This domain cannot receive email. Configure MX records at your DNS provider.</p>`;
        } else {
            html += `<div class="flex items-center gap-2 mb-3" style="color:var(--success);">
                <i data-lucide="check-circle" class="icon"></i>
                <strong class="text-sm">${mxRecs.length} MX record${mxRecs.length!==1?'s':''} found — ${escapeHtml(data.domain)}</strong>
            </div>
            <div style="background:#fff;border:1.5px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:14px;">
                <div style="display:grid;grid-template-columns:70px 1fr auto;gap:8px;background:var(--surface-2);border-bottom:1.5px solid var(--border);padding:8px 14px;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);">
                    <span>Priority</span><span>Mail Server (MX Host)</span><span>TTL</span>
                </div>`;
            for (const mx of mxRecs) {
                const prov = detectMailProvider(mx.target||'');
                html += `<div style="display:grid;grid-template-columns:70px 1fr auto;gap:8px;padding:11px 14px;border-bottom:1px solid var(--border);align-items:center;">
                    <span class="font-bold text-sm" style="color:var(--accent);">${mx.pri??'?'}</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono font-semibold text-sm" style="color:var(--ink);">${escapeHtml(mx.target||mx.raw||'')}</span>
                        ${prov?`<span class="tag" style="background:${prov.bg};color:${prov.color};font-size:9px;">${prov.name}</span>`:''}
                    </div>
                    <span class="text-xs" style="color:var(--muted);">${mx.ttl??'?'}s</span>
                </div>`;
            }
            html += `</div>`;
        }

        // Email security section
        html += `<div>
            <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color:var(--muted);">Email Security</p>
            <div class="flex flex-wrap gap-2">
                ${emailSecBadge('SPF',  eh.spf,   'v=spf1 — Prevents spoofing')}
                ${emailSecBadge('DMARC',eh.dmarc, 'v=DMARC1 — Anti-phishing policy')}
                ${emailSecBadge('DKIM', eh.dkim,  'v=DKIM1 — Email signing', true)}
            </div>
        </div>`;

        if (!eh.spf || !eh.dmarc) {
            const spfRec = detectSpfForProvider(data.mx_records || []);
            html += `<div class="mt-3 p-3 rounded-lg text-xs" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
                <strong>⚠ Recommendations:</strong><br>
                ${!eh.spf   ? '• Add a TXT record on <strong>' + escapeHtml(data.domain) + '</strong>: <code style="background:#fef3c7;padding:1px 4px;border-radius:3px;">' + spfRec + '</code><br>' : ''}
                ${!eh.dmarc ? '• Add a TXT record on <strong>_dmarc.' + escapeHtml(data.domain) + '</strong>: <code style="background:#fef3c7;padding:1px 4px;border-radius:3px;">v=DMARC1; p=none; rua=mailto:dmarc@' + escapeHtml(data.domain) + '</code>' : ''}
            </div>`;
        }

        if (eh.mx_issues && eh.mx_issues.length) {
            html += `<div class="mt-2">${eh.mx_issues.map(i=>`<p class="text-xs mt-1" style="color:var(--danger);"><i data-lucide="alert-circle" class="icon-sm"></i> ${escapeHtml(i)}</p>`).join('')}</div>`;
        }
        html += `</div>`;
        res.innerHTML = html;
    } catch(e) {
        res.innerHTML = `<div class="p-4 rounded-xl text-sm" style="background:var(--danger-bg);color:var(--danger);">MX check failed. Please try again.</div>`;
    }
    refreshIcons();
    btn.disabled = false;
}

function detectMailProvider(mx) {
    mx = (mx||'').toLowerCase();
    if (/google|googlemail|aspmx|smtp\.gmail/.test(mx))      return {name:'Google Workspace',color:'#1d4ed8',bg:'#dbeafe'};
    if (/outlook|office365|microsoft|protection\.outlook|hotmail/.test(mx)) return {name:'Microsoft 365',color:'#0284c7',bg:'#e0f2fe'};
    if (/mimecast/.test(mx))    return {name:'Mimecast',color:'#7c3aed',bg:'#f5f3ff'};
    if (/proofpoint/.test(mx))  return {name:'Proofpoint',color:'#dc2626',bg:'#fef2f2'};
    if (/mailgun/.test(mx))     return {name:'Mailgun',color:'#d97706',bg:'#fffbeb'};
    if (/sendgrid/.test(mx))    return {name:'SendGrid',color:'#0891b2',bg:'#ecfeff'};
    if (/zoho/.test(mx))        return {name:'Zoho Mail',color:'#059669',bg:'#ecfdf5'};
    if (/fastmail/.test(mx))    return {name:'Fastmail',color:'#6366f1',bg:'#ede9fe'};
    if (/amazon|aws|ses/.test(mx)) return {name:'Amazon SES',color:'#ea580c',bg:'#fff7ed'};
    if (/icloud|apple/.test(mx))   return {name:'Apple iCloud',color:'#0284c7',bg:'#e0f2fe'};
    return null;
}

function detectSpfForProvider(mxRecords) {
    const targets = (mxRecords||[]).map(r=>(r.target||r||'').toLowerCase()).join(' ');
    if (/zoho/.test(targets))                                         return 'v=spf1 include:zoho.com ~all';
    if (/rediffmail|reddif/.test(targets))                            return 'v=spf1 include:spf.rediffmail.com ~all';
    if (/outlook|office365|microsoft|protection\.outlook/.test(targets)) return 'v=spf1 include:spf.protection.outlook.com ~all';
    if (/sendgrid/.test(targets))                                     return 'v=spf1 include:sendgrid.net ~all';
    if (/mailgun/.test(targets))                                      return 'v=spf1 include:mailgun.org ~all';
    if (/amazon|aws|ses/.test(targets))                               return 'v=spf1 include:amazonses.com ~all';
    if (/fastmail/.test(targets))                                     return 'v=spf1 include:spf.messagingengine.com ~all';
    if (/mimecast/.test(targets))                                     return 'v=spf1 include:spf.mimecast.com ~all';
    return 'v=spf1 include:_spf.google.com ~all'; // default Google Workspace
}

