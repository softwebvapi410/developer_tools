<?php /* Page: ip — included by index.php */ ?>

    <div id="page-ip" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#7c3aed,#a855f7);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(124,58,237,.3);">
                <i data-lucide="wifi" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold text-xl" style="color:var(--ink);">IP Address Info</h2>
                <p class="text-sm" style="color:var(--muted);">Your public IP, location, ISP, VPN/proxy detection, DNS resolver &amp; browser fingerprint</p>
            </div>
        </div>

        <!-- Loading state -->
        <div id="ipLoading" class="glass-card p-8 text-center mb-5">
            <i data-lucide="loader" class="icon-xl mx-auto mb-3 spin" style="color:var(--accent);"></i>
            <p class="text-sm font-semibold" style="color:var(--muted);">Gathering your IP &amp; network details…</p>
        </div>

        <!-- Results grid (hidden until loaded) -->
        <div id="ipResults" style="display:none;">

            <!-- Row 1: Public IP + Location -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

                <!-- Public IP card -->
                <div class="glass-card p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="globe-2" class="icon" style="color:#7c3aed;"></i>
                        <span class="font-bold text-base" style="color:var(--ink);">Public / Internet IP</span>
                        <span class="tag" style="background:#ede9fe;color:#5b21b6;font-size:10px;">EXTERNAL</span>
                    </div>
                    <div id="ipPublicBlock"></div>
                </div>

                <!-- Local IP card -->
                <div class="glass-card p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="network" class="icon" style="color:#0ea5e9;"></i>
                        <span class="font-bold text-base" style="color:var(--ink);">Local / Device IP</span>
                        <span class="tag" style="background:#e0f2fe;color:#0369a1;font-size:10px;">LAN</span>
                    </div>
                    <div id="ipLocalBlock"></div>
                </div>
            </div>

            <!-- Row 2: VPN/Proxy + DNS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

                <!-- VPN / Proxy detection -->
                <div class="glass-card p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="shield" class="icon" style="color:#ef4444;"></i>
                        <span class="font-bold text-base" style="color:var(--ink);">VPN / Proxy Detection</span>
                    </div>
                    <div id="ipVpnBlock"></div>
                </div>

                <!-- DNS Resolver -->
                <div class="glass-card p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="server" class="icon" style="color:#10b981;"></i>
                        <span class="font-bold text-base" style="color:var(--ink);">DNS Resolver Detected</span>
                        <span class="tag" style="background:#ecfdf5;color:#065f46;font-size:10px;">DoH/DoT</span>
                    </div>
                    <div id="ipDnsBlock"></div>
                </div>
            </div>

            <!-- Row 3: Map iframe -->
            <div class="glass-card mb-5" style="overflow:hidden;">
                <div class="flex items-center gap-2 p-4" style="border-bottom:1.5px solid var(--border);">
                    <i data-lucide="map-pin" class="icon" style="color:#ef4444;"></i>
                    <span class="font-bold text-base" style="color:var(--ink);" id="ipMapTitle">Approximate Location</span>
                    <span class="tag tag-neutral" style="font-size:10px;" id="ipMapTag">GEO-IP — NOT EXACT</span>
                    <button id="ipUseLocationBtn" onclick="requestDeviceLocation()" class="btn-secondary ml-auto" style="font-size:11px;padding:5px 12px;min-height:30px;gap:5px;">
                        <i data-lucide="crosshair" style="width:13px;height:13px;"></i> Use My Precise Location
                    </button>
                </div>
                <!-- Coordinates bar -->
                <div id="ipCoordsBar" style="display:none;background:linear-gradient(90deg,#1e1b4b,#312e81);padding:8px 16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span style="color:rgba(255,255,255,.6);font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">Coordinates</span>
                    <span id="ipCoordsLat" style="color:#a5b4fc;font-family:monospace;font-size:13px;font-weight:700;"></span>
                    <span style="color:rgba(255,255,255,.3);">•</span>
                    <span id="ipCoordsLon" style="color:#a5b4fc;font-family:monospace;font-size:13px;font-weight:700;"></span>
                    <span id="ipCoordsAcc" style="color:rgba(255,255,255,.5);font-size:11px;"></span>
                    <a id="ipCoordsGmapLink" href="#" target="_blank" rel="noopener" style="margin-left:auto;display:inline-flex;align-items:center;gap:5px;color:#818cf8;font-size:11px;font-weight:700;text-decoration:none;">
                        <i data-lucide="external-link" style="width:12px;height:12px;"></i> Open in Google Maps
                    </a>
                </div>
                <div id="ipMapWrap" style="height:340px;background:var(--surface);">
                    <iframe id="ipMapFrame" src="" frameborder="0" style="width:100%;height:100%;border:none;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            <!-- Row 4: Full detail table -->
            <div class="glass-card mb-5">
                <div class="flex items-center gap-2 p-4" style="border-bottom:1.5px solid var(--border);">
                    <i data-lucide="list" class="icon" style="color:var(--muted);"></i>
                    <span class="font-bold text-base" style="color:var(--ink);">All Details</span>
                </div>
                <div id="ipAllDetails" class="p-4"></div>
            </div>

        </div><!-- /ipResults -->

    </div><!-- /page-ip -->
