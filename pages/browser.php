<?php /* Page: browser — included by index.php */ ?>

    <div id="page-browser" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#10b981,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(16,185,129,.3);">
                <i data-lucide="monitor" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold text-xl" style="color:var(--ink);">Browser &amp; User Agent</h2>
                <p class="text-sm" style="color:var(--muted);">Your current browser environment and UA string parser</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div id="browserCard" class="glass-card p-5">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="monitor" class="icon" style="color:#10b981;"></i>
                    <span class="font-bold text-base" style="color:var(--ink);">Browser Info</span>
                    <span class="tag" style="background:#ecfdf5;color:#065f46;font-size:10px;">DEBUG</span>
                </div>
                <p class="text-sm mb-3" style="color:var(--muted);">Your current browser environment — useful for debugging user experience issues.</p>
                <div id="browserInfoResult">
                    <div class="flex items-center gap-2 py-4" style="color:var(--muted);">
                        <i data-lucide="loader" class="icon spin"></i><span class="text-sm">Collecting browser data…</span>
                    </div>
                </div>
            </div>

            <div id="uaCard" class="glass-card p-5">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="cpu" class="icon" style="color:#f59e0b;"></i>
                    <span class="font-bold text-base" style="color:var(--ink);">User Agent Analyzer</span>
                    <span class="tag" style="background:#fffbeb;color:#b45309;font-size:10px;">UA PARSER</span>
                </div>
                <p class="text-sm mb-3" style="color:var(--muted);">Paste any UA string to detect browser, OS, engine, and device type.</p>
                <textarea id="uaInput" rows="3"
                    style="font-family:'DM Sans',sans-serif;font-size:12px;width:100%;border:2px solid var(--border);border-radius:12px;padding:10px 12px;outline:none;resize:none;color:var(--ink);background:#fff;transition:border-color .2s;margin-bottom:8px;"
                    placeholder="Paste any user agent string…"
                    onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
                    oninput="analyzeUA()"></textarea>
                <div class="flex gap-2 mb-3">
                    <button onclick="loadMyUA()" class="btn-secondary" style="font-size:12px;padding:6px 12px;min-height:32px;">
                        <i data-lucide="user" class="icon-sm"></i> Use My UA
                    </button>
                    <button onclick="document.getElementById('uaInput').value='';document.getElementById('uaResult').innerHTML=''" class="btn-secondary" style="font-size:12px;padding:6px 12px;min-height:32px;">
                        <i data-lucide="x" class="icon-sm"></i> Clear
                    </button>
                </div>
                <div id="uaResult"></div>
            </div>
        </div>
    </div><!-- /page-browser -->
