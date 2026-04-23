<?php /* Page: whois — included by index.php */ ?>

    <div id="page-whois" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#059669,#10b981);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(5,150,105,.3);">
                <i data-lucide="id-card" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold text-xl" style="color:var(--ink);">WHOIS &amp; Nameserver Lookup</h2>
                <p class="text-sm" style="color:var(--muted);">Registrar, dates, status, DNSSEC, live nameservers — via RDAP</p>
            </div>
        </div>

        <div class="glass-card p-4 sm:p-5 mb-5">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="icon absolute left-3.5 top-1/2 -translate-y-1/2" style="color:var(--muted);pointer-events:none;"></i>
                    <input type="text" id="whoisInput" placeholder="e.g. example.com or https://example.com" class="search-input"
                        oninput="syncDomainInput('whoisInput')"
                        onkeydown="if(event.key==='Enter')runWhois()">
                </div>
                <button onclick="runWhois()" id="whoisBtn" class="btn-primary" style="background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 4px 14px rgba(5,150,105,.35);">
                    <i data-lucide="search" class="icon"></i> WHOIS Lookup
                </button>
            </div>
        </div>
        <div id="whoisResult" style="display:none;"></div>
    </div><!-- /page-whois -->
