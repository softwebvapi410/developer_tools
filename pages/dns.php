<?php /* Page: dns — included by index.php */ ?>

    <div id="page-dns" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(99,102,241,.3);">
                <i data-lucide="terminal" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold text-xl" style="color:var(--ink);">DNS Record Lookup</h2>
                <p class="text-sm" style="color:var(--muted);">Query A, AAAA, MX, NS, TXT, CNAME, SOA, CAA — via Google DNS-over-HTTPS</p>
            </div>
        </div>

        <!-- Shared domain input -->
        <div class="glass-card p-4 sm:p-5 mb-5">
            <div class="flex flex-col sm:flex-row gap-3 mb-3">
                <div class="relative flex-1">
                    <i data-lucide="globe" class="icon absolute left-3.5 top-1/2 -translate-y-1/2" style="color:var(--muted);pointer-events:none;"></i>
                    <input type="text" id="dnsInput" placeholder="e.g. example.com or https://example.com" class="search-input"
                        oninput="syncDomainInput('dnsInput')"
                        onkeydown="if(event.key==='Enter')runDnsLookup()">
                </div>
                <select id="dnsType" style="display:none;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;color:var(--ink);background:#fff;border:2px solid var(--border);border-radius:14px;padding:0 16px;height:50px;outline:none;cursor:pointer;min-width:140px;transition:border-color .2s;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
                    <option value="ALL">All Records</option>
                    <option value="A">A — IPv4 Address</option>
                    <option value="AAAA">AAAA — IPv6 Address</option>
                    <option value="MX">MX — Mail Servers</option>
                    <option value="NS">NS — Nameservers</option>
                    <option value="TXT">TXT — Text Records</option>
                    <option value="CNAME">CNAME — Alias</option>
                    <option value="SOA">SOA — Start of Authority</option>
                    <option value="CAA">CAA — SSL Authorization</option>
                    <option value="SRV">SRV — Service</option>
                </select>
                <button onclick="runDnsLookup()" id="dnsBtn" class="btn-primary">
                    <i data-lucide="search" class="icon"></i> Lookup
                </button>
            </div>
            <!-- Quick-select record type pills -->
            <div class="flex flex-wrap gap-2 mb-1" id="dnsQuickBtns">
                <?php foreach(['ALL','A','AAAA','MX','NS','TXT','CNAME','SOA','CAA'] as $qt): ?>
                <button onclick="quickDns('<?=$qt?>')" id="dnsQ_<?=$qt?>"
                    class="tab-btn" style="font-size:11px;padding:4px 12px;min-height:28px;"><?=$qt?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="dnsResult" style="display:none;"></div>
    </div><!-- /page-dns -->
