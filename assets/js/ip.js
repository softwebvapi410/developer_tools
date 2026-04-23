// IP Address Info page

// ═══════════════════════════════════════
//  IP ADDRESS INFO
// ═══════════════════════════════════════

function ipRow(label, value, badge) {
    const color = badge === 'good' ? 'var(--success)' : badge === 'warn' ? 'var(--warning)' : badge === 'bad' ? 'var(--danger)' : 'var(--ink)';
    return `<div class="meta-row"><span class="meta-key">${escapeHtml(label)}</span><span class="meta-val" style="color:${color};">${escapeHtml(String(value ?? '—'))}</span></div>`;
}

async function loadIpInfo() {
    document.getElementById('ipLoading').style.display = '';
    document.getElementById('ipResults').style.display = 'none';

    // 1. Get local LAN IPs via WebRTC (only true RFC-1918 addresses)
    let localIPs = [];
    try { localIPs = await getLocalIPs(); } catch(e) {}

    // 2. Get public IP + geo from ipapi.co
    let geo = {};
    try {
        const r = await fetch('https://ipapi.co/json/', { cache: 'no-store' });
        geo = await r.json();
    } catch(e) {}

    // 3. Cloudflare trace for DNS resolver detection
    let dnsInfo = { resolver: 'Unknown', provider: 'Unknown', colo: '' };
    try {
        const cf = await fetch('https://one.one.one.one/cdn-cgi/trace', { cache: 'no-store' });
        const txt = await cf.text();
        const lines = Object.fromEntries(txt.trim().split('\n').map(l => l.split('=')));
        if (lines.warp === 'on') dnsInfo.resolver = 'Cloudflare WARP';
        else if (lines.warp === 'off') dnsInfo.resolver = 'Cloudflare (1.1.1.1)';
        dnsInfo.provider = 'Cloudflare';
        dnsInfo.colo = lines.colo || '';
    } catch(e) {}

    let usingGoogle = false;
    try {
        const g = await fetch('https://dns.google/resolve?name=whoami.akamai.net&type=TXT', { cache: 'no-store' });
        const gj = await g.json();
        if (gj && gj.Answer) usingGoogle = true;
    } catch(e) {}

    // Override DNS resolver by known IP prefixes
    const ip = geo.ip || '';
    if (ip) {
        if (ip.startsWith('94.140.14.') || ip.startsWith('94.140.15.'))
            { dnsInfo.resolver = 'AdGuard DNS'; dnsInfo.provider = 'AdGuard'; }
        else if (ip.startsWith('8.8.') || ip.startsWith('8.34.'))
            { dnsInfo.resolver = 'Google Public DNS'; dnsInfo.provider = 'Google'; }
        else if (ip.startsWith('1.1.1.') || ip.startsWith('1.0.0.'))
            { dnsInfo.resolver = 'Cloudflare DNS (1.1.1.1)'; dnsInfo.provider = 'Cloudflare'; }
        else if (ip.startsWith('208.67.222.') || ip.startsWith('208.67.220.'))
            { dnsInfo.resolver = 'OpenDNS'; dnsInfo.provider = 'Cisco OpenDNS'; }
        else if (ip.startsWith('9.9.9.') || ip.startsWith('149.112.'))
            { dnsInfo.resolver = 'Quad9'; dnsInfo.provider = 'Quad9'; }
        else if (ip.startsWith('45.90.28.') || ip.startsWith('45.90.30.'))
            { dnsInfo.resolver = 'NextDNS'; dnsInfo.provider = 'NextDNS'; }
    }

    // 4. VPN heuristics
    const isVpn = geo.org && /vpn|proxy|tor|hosting|datacenter|data center|cloud|digitalocean|linode|vultr|ovh|hetzner|amazon|google|microsoft|azure/i.test(geo.org);
    const isTor = geo.org && /tor/i.test(geo.org);

    // Store geo coords globally for device-location upgrade
    window._ipGeoLat = geo.latitude || null;
    window._ipGeoLon = geo.longitude || null;
    window._ipGeoData = geo;
    window._ipDnsInfo = dnsInfo;
    window._ipLocalIPs = localIPs;
    window._ipIsVpn = isVpn;
    window._ipIsTor = isTor;
    window._ipUsingGoogle = usingGoogle;

    // Render everything
    renderIpPage(geo, localIPs, isVpn, isTor, dnsInfo, usingGoogle, null);

    document.getElementById('ipLoading').style.display = 'none';
    document.getElementById('ipResults').style.display = '';
    refreshIcons();
}

function renderIpPage(geo, localIPs, isVpn, isTor, dnsInfo, usingGoogle, deviceGeo) {
    const ip = geo.ip || '';
    // Use device geo if available, else fall back to IP geo
    const lat    = deviceGeo ? deviceGeo.latitude  : (geo.latitude  || null);
    const lon    = deviceGeo ? deviceGeo.longitude : (geo.longitude || null);
    const acc    = deviceGeo ? deviceGeo.accuracy  : null;
    const isPrecise = !!deviceGeo;

    // ── Public IP block ──
    document.getElementById('ipPublicBlock').innerHTML = `
        <div style="font-size: clamp(12px, 2vw, 24px); line-height: 1.6;font-weight:800;font-family:'DM Sans',sans-serif;color:var(--accent);letter-spacing:-.02em;margin-bottom:10px;">${escapeHtml(ip || 'Unavailable')}</div>
        ${ipRow('IPv4 Address', geo.ip || '—')}
        ${ipRow('Country', (geo.country_name || '—') + (geo.country_code ? ' ('+geo.country_code+')' : ''))}
        ${ipRow('Region / State', geo.region || '—')}
        ${ipRow('City', geo.city || '—')}
        ${ipRow('Postal Code', geo.postal || '—')}
        ${ipRow('Timezone', geo.timezone || '—')}
        ${ipRow('Coordinates (IP)', lat && lon ? lat.toFixed(5)+', '+lon.toFixed(5) : (geo.latitude && geo.longitude ? geo.latitude+', '+geo.longitude : '—'))}
        ${isPrecise ? ipRow('Coordinates (Device)', deviceGeo.latitude.toFixed(6)+', '+deviceGeo.longitude.toFixed(6)+'  ±'+Math.round(acc)+'m', 'good') : ''}
        ${ipRow('Currency', geo.currency_name ? geo.currency_name + ' (' + (geo.currency||'') + ')' : '—')}
        ${ipRow('Languages', geo.languages || '—')}
        ${ipRow('Calling Code', geo.country_calling_code || '—')}
    `;

    // ── Local IP block ──
    const filteredLocal = localIPs.filter(i => i !== ip);
    if (filteredLocal.length) {
        document.getElementById('ipLocalBlock').innerHTML = `
            <div style="margin-bottom:10px;">
                ${filteredLocal.map(lip => `
                    <div style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#e0f2fe,#dbeafe);border:1.5px solid #bae6fd;border-radius:12px;padding:8px 16px;margin:4px 0;width:100%;">
                        <i data-lucide="network" style="width:16px;height:16px;color:#0ea5e9;flex-shrink:0;"></i>
                        <span style="font-size:20px;font-weight:800;font-family:monospace;color:#0369a1;">${escapeHtml(lip)}</span>
                        <span style="font-size:10px;font-weight:700;background:#0ea5e9;color:#fff;padding:2px 7px;border-radius:100px;margin-left:auto;">${classifyLocalIP(lip)}</span>
                    </div>`).join('')}
            </div>
            ${ipRow('Source', 'WebRTC ICE Candidates')}
            ${ipRow('Network Type', filteredLocal.some(i => /^(192\.168\.|10\.|172\.(1[6-9]|2\d|3[01])\.)/.test(i)) ? 'Private LAN (RFC 1918)' : 'Link-local', 'good')}
            ${ipRow('Router Range', filteredLocal.find(i => /^192\.168\./.test(i)) ? '192.168.x.x — Home/Office Router' : filteredLocal.find(i => /^10\./.test(i)) ? '10.x.x.x — Corporate/VPN LAN' : 'Other private range')}
            <p style="font-size:11px;color:var(--muted);margin-top:8px;line-height:1.5;">Your device's local IP on your router/LAN. This address is only visible within your local network — websites cannot see this.</p>`;
    } else {
        document.getElementById('ipLocalBlock').innerHTML = `
            <div style="text-align:center;padding:20px 0;">
                <i data-lucide="wifi-off" style="width:32px;height:32px;color:var(--muted);margin:0 auto 8px;display:block;opacity:.4;"></i>
                <p style="font-size:13px;font-weight:600;color:var(--muted);">Local IP not detected</p>
                <p style="font-size:11px;color:var(--muted);margin-top:4px;">WebRTC may be blocked or disabled in this browser.</p>
            </div>`;
    }
    refreshIcons();

    // ── VPN/Proxy block ──
    const vpnColor = isTor ? 'var(--danger)' : isVpn ? 'var(--warning)' : 'var(--success)';
    const vpnLabel = isTor ? '⚠ Tor Exit Node Detected' : isVpn ? '⚠ Likely VPN / Hosting / Proxy' : '✓ No VPN / Proxy Detected';
    document.getElementById('ipVpnBlock').innerHTML = `
        <div style="font-size:15px;font-weight:800;font-family:'DM Sans',sans-serif;color:${vpnColor};margin-bottom:10px;">${vpnLabel}</div>
        ${ipRow('ISP / Org', geo.org || '—', isVpn ? 'warn' : '')}
        ${ipRow('ASN', geo.asn || '—')}
        ${ipRow('Connection Type', isVpn ? 'Datacenter / Hosting' : 'Residential / ISP', isVpn ? 'warn' : 'good')}
        ${ipRow('Tor Exit Node', isTor ? 'Yes — Tor detected' : 'No', isTor ? 'bad' : 'good')}
        <p style="font-size:11px;color:var(--muted);margin-top:8px;">Heuristic detection via ISP name. Not 100% accurate.</p>`;

    // ── DNS block ──
    document.getElementById('ipDnsBlock').innerHTML = `
        <div style="font-size:17px;font-weight:800;font-family:'DM Sans',sans-serif;color:#10b981;margin-bottom:10px;">${escapeHtml(dnsInfo.resolver)}</div>
        ${ipRow('Provider', dnsInfo.provider)}
        ${ipRow('Cloudflare PoP', dnsInfo.colo || '—')}
        ${ipRow('Google DNS', usingGoogle ? '✓ Reachable' : '— Not detected', usingGoogle ? 'good' : '')}
        ${ipRow('Privacy DNS', /cloudflare|adguard|nextdns|quad9/i.test(dnsInfo.provider) ? '✓ Privacy DNS in use' : '✗ Standard ISP resolver', /cloudflare|adguard|nextdns|quad9/i.test(dnsInfo.provider) ? 'good' : 'warn')}
        <p style="font-size:11px;color:var(--muted);margin-top:8px;">Detected via Cloudflare WARP trace &amp; known IP ranges.</p>`;

    // ── Map: Google Maps iframe ──
    updateIpMap(lat, lon, acc, isPrecise);

    // ── All details table ──
    const browserIP = window.location.hostname !== 'localhost' ? window.location.hostname : '(localhost)';
    const displayLat = isPrecise ? deviceGeo.latitude.toFixed(6) : (geo.latitude || '—');
    const displayLon = isPrecise ? deviceGeo.longitude.toFixed(6) : (geo.longitude || '—');
    document.getElementById('ipAllDetails').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border);border-radius:12px;overflow:hidden;">
            ${allDetailRow('Public IP', ip || '—')}
            ${allDetailRow('Local IP(s)', filteredLocal.join(', ') || '—')}
            ${allDetailRow('Browser Hostname', browserIP)}
            ${allDetailRow('Country', (geo.country_name||'—') + (geo.country_code?' ('+geo.country_code+')':''))}
            ${allDetailRow('Region / State', geo.region||'—')}
            ${allDetailRow('City', geo.city||'—')}
            ${allDetailRow('Postal Code', geo.postal||'—')}
            ${allDetailRow('Latitude', String(displayLat))}
            ${allDetailRow('Longitude', String(displayLon))}
            ${isPrecise ? allDetailRow('Location Accuracy', '±'+Math.round(acc)+'m (Device GPS)', true) : allDetailRow('Location Source', 'IP Geolocation (approximate)')}
            ${allDetailRow('Coordinates (combined)', lat && lon ? lat+(isPrecise?'':'')+', '+lon : '—')}
            ${allDetailRow('Timezone', geo.timezone||'—')}
            ${allDetailRow('ISP / Org', geo.org||'—')}
            ${allDetailRow('ASN', geo.asn||'—')}
            ${allDetailRow('VPN / Proxy', isVpn ? 'Likely YES' : 'No')}
            ${allDetailRow('Tor Exit Node', isTor ? 'YES' : 'No')}
            ${allDetailRow('DNS Resolver', dnsInfo.resolver)}
            ${allDetailRow('DNS Provider', dnsInfo.provider)}
            ${allDetailRow('Cloudflare PoP', dnsInfo.colo||'—')}
            ${allDetailRow('Currency', geo.currency_name ? geo.currency_name+' ('+(geo.currency||'')+')' : '—')}
            ${allDetailRow('Languages', geo.languages||'—')}
            ${allDetailRow('Calling Code', geo.country_calling_code||'—')}
            ${allDetailRow('User-Agent', navigator.userAgent)}
            ${allDetailRow('Browser Language', navigator.language||'—')}
            ${allDetailRow('Platform', navigator.platform||'—')}
            ${allDetailRow('Cookies', navigator.cookieEnabled ? 'Enabled' : 'Disabled')}
        </div>`;
    refreshIcons();
}

function updateIpMap(lat, lon, acc, isPrecise) {
    const mapTitle = document.getElementById('ipMapTitle');
    const mapTag   = document.getElementById('ipMapTag');
    const coordsBar = document.getElementById('ipCoordsBar');
    const coordsLat = document.getElementById('ipCoordsLat');
    const coordsLon = document.getElementById('ipCoordsLon');
    const coordsAcc = document.getElementById('ipCoordsAcc');
    const gmapLink  = document.getElementById('ipCoordsGmapLink');

    if (!lat || !lon) {
        document.getElementById('ipMapWrap').innerHTML = '<div class="p-8 text-center" style="color:var(--muted);">Location unavailable</div>';
        return;
    }

    // Update title & tag
    if (mapTitle) mapTitle.textContent = isPrecise ? 'Precise Device Location' : 'Approximate IP Location';
    if (mapTag) {
        mapTag.textContent = isPrecise ? 'GPS / DEVICE LOCATION' : 'GEO-IP — NOT EXACT';
        mapTag.style.background = isPrecise ? '#d1fae5' : '';
        mapTag.style.color = isPrecise ? '#065f46' : '';
    }

    // Show coordinates bar
    if (coordsBar) {
        coordsBar.style.display = 'flex';
        coordsLat.textContent = 'Lat: ' + Number(lat).toFixed(6);
        coordsLon.textContent = 'Lon: ' + Number(lon).toFixed(6);
        coordsAcc.textContent = acc ? '±'+Math.round(acc)+'m accuracy' : (isPrecise ? '' : '(IP-based, ~city level)');
        if (gmapLink) {
            gmapLink.href = `https://www.google.com/maps?q=${lat},${lon}`;
        }
    }

    // Google Maps embed iframe
    const zoom = isPrecise ? 15 : 10;
    const gmapSrc = `https://maps.google.com/maps?q=${lat},${lon}&z=${zoom}&output=embed`;
    const mapFrame = document.getElementById('ipMapFrame');
    if (mapFrame) mapFrame.src = gmapSrc;
}

function requestDeviceLocation() {
    const btn = document.getElementById('ipUseLocationBtn');
    if (!navigator.geolocation) {
        showNotifToast('warning', 'Geolocation Not Supported', 'Your browser does not support geolocation.');
        return;
    }
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader" style="width:13px;height:13px;" class="spin"></i> Requesting…';
        refreshIcons();
    }
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const deviceGeo = {
                latitude:  pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy:  pos.coords.accuracy,
            };
            // Ensure results panel is visible
            const loading = document.getElementById('ipLoading');
            const results = document.getElementById('ipResults');
            if (loading) loading.style.display = 'none';
            if (results) results.style.display = '';

            // Reverse geocode the precise GPS coordinates via OpenStreetMap Nominatim
            // and merge the real city / postal / region / country into geo data
            try {
                const rgUrl = `https://nominatim.openstreetmap.org/reverse?lat=${deviceGeo.latitude}&lon=${deviceGeo.longitude}&format=json&addressdetails=1`;
                const rgRes = await fetch(rgUrl, {
                    headers: { 'Accept-Language': 'en', 'User-Agent': 'SEOAuditorPro/2.0' },
                    cache: 'no-store'
                });
                const rg = await rgRes.json();
                if (rg && rg.address) {
                    const addr = rg.address;
                    // Update the stored geo object so all detail rows reflect real location
                    const updatedGeo = Object.assign({}, window._ipGeoData || {});
                    // City: try city → town → village → county
                    updatedGeo.city    = addr.city || addr.town || addr.village || addr.county || updatedGeo.city || '—';
                    // Postal code
                    updatedGeo.postal  = addr.postcode || updatedGeo.postal || '—';
                    // Region / State
                    updatedGeo.region  = addr.state || addr.province || addr.county || updatedGeo.region || '—';
                    // Country
                    updatedGeo.country_name = addr.country || updatedGeo.country_name || '—';
                    updatedGeo.country_code = (addr.country_code || '').toUpperCase() || updatedGeo.country_code || '';
                    // Keep precise lat/lon in geo too
                    updatedGeo.latitude  = deviceGeo.latitude;
                    updatedGeo.longitude = deviceGeo.longitude;
                    window._ipGeoData = updatedGeo;
                }
            } catch(rgErr) {
                // Reverse geocode failed — geo data stays as-is (IP-based fallback)
            }

            // Re-render entire page with precise coords + updated geo
            renderIpPage(
                window._ipGeoData || {},
                window._ipLocalIPs || [],
                window._ipIsVpn || false,
                window._ipIsTor || false,
                window._ipDnsInfo || {},
                window._ipUsingGoogle || false,
                deviceGeo
            );
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check-circle" style="width:13px;height:13px;"></i> Device Location Active';
                btn.style.background = 'var(--success)';
                btn.style.color = '#fff';
                btn.style.borderColor = 'var(--success)';
                refreshIcons();
            }
            showNotifToast('success', 'Precise Location Loaded', 'Map & address updated with GPS coordinates. Accuracy: ±'+Math.round(pos.coords.accuracy)+'m');
        },
        (err) => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="crosshair" style="width:13px;height:13px;"></i> Use My Precise Location';
                refreshIcons();
            }
            const msgs = { 1: 'Permission denied — please allow location access in your browser.', 2: 'Position unavailable — GPS or network error.', 3: 'Request timed out.' };
            showNotifToast('warning', 'Location Access Failed', msgs[err.code] || 'Could not get location.');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function classifyLocalIP(ip) {
    if (/^192\.168\./.test(ip)) return 'Home/Office Router';
    if (/^10\./.test(ip))       return 'Corporate / VPN LAN';
    if (/^172\.(1[6-9]|2\d|3[01])\./.test(ip)) return 'Private Range B';
    if (/^169\.254\./.test(ip)) return 'Link-Local (no DHCP)';
    if (/^fd/.test(ip) || /^fc/.test(ip)) return 'IPv6 ULA Private';
    return 'Local Network';
}

function allDetailRow(label, value, highlight) {
    return `
        <div style="background:${highlight?'#f0fdf4':'#fff'};padding:10px 14px;">
            <div style="font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:2px;">${escapeHtml(label)}</div>
            <div style="font-size:12px;font-weight:600;color:${highlight?'var(--success)':'var(--ink)'};word-break:break-all;">${escapeHtml(String(value||'—'))}</div>
        </div>`;
}

// Get ONLY true private LAN IPs via WebRTC (RFC 1918 + link-local)
function getLocalIPs() {
    return new Promise((resolve) => {
        const ips = new Set();
        const pc = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        });
        pc.createDataChannel('');
        pc.createOffer().then(o => pc.setLocalDescription(o)).catch(() => {});

        const timer = setTimeout(() => { try { pc.close(); } catch{} resolve([...ips]); }, 2500);

        pc.onicecandidate = (e) => {
            if (!e || !e.candidate) {
                clearTimeout(timer);
                try { pc.close(); } catch{}
                resolve([...ips]);
                return;
            }
            const cand = e.candidate.candidate;
            // Extract IPv4 addresses only from ICE candidates
            const ipv4Match = cand.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/g);
            if (ipv4Match) {
                ipv4Match.forEach(ip => {
                    // Only keep RFC 1918 private + link-local addresses
                    if (
                        /^192\.168\./.test(ip) ||          // 192.168.0.0/16 — home/office
                        /^10\./.test(ip) ||                // 10.0.0.0/8 — corporate
                        /^172\.(1[6-9]|2\d|3[01])\./.test(ip) || // 172.16-31.x — private B
                        /^169\.254\./.test(ip)             // 169.254.x.x — link-local (APIPA)
                    ) {
                        ips.add(ip);
                    }
                    // Explicitly skip: 0.x, 127.x, public IPs
                });
            }
        };
    });
}

