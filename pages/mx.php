<?php /* Page: mx — included by index.php */ ?>

    <div id="page-mx" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#0ea5e9,#38bdf8);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(14,165,233,.3);">
                <i data-lucide="mail-check" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold text-xl" style="color:var(--ink);">Check MX — Email Deliverability</h2>
                <p class="text-sm" style="color:var(--muted);">Validates MX records, detects mail providers, checks SPF / DMARC / DKIM security</p>
            </div>
        </div>

        <div class="glass-card p-4 sm:p-5 mb-5">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="at-sign" class="icon absolute left-3.5 top-1/2 -translate-y-1/2" style="color:var(--muted);pointer-events:none;"></i>
                    <input type="text" id="mxInput" placeholder="e.g. example.com or https://example.com" class="search-input"
                        oninput="syncDomainInput('mxInput')"
                        onkeydown="if(event.key==='Enter')runMxCheck()">
                </div>
                <button onclick="runMxCheck()" id="mxBtn" class="btn-primary">
                    <i data-lucide="mail" class="icon"></i> Check MX
                </button>
            </div>
        </div>
        <div id="mxResult" style="display:none;"></div>
    </div><!-- /page-mx -->
