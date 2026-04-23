<?php /* Page: audit — included by index.php */ ?>

    <div id="page-audit" class="page-section">

        <!-- HEADER -->
        <header id="topSection" class="text-center mb-10">
            <div class="badge-pill mb-4">
                <i data-lucide="zap" class="icon-sm"></i>
                SEO Auditor Pro
            </div>
            <h1 class="font-extrabold mb-3" style="font-size:var(--fs-4xl);color:var(--ink);letter-spacing:-.02em;line-height:1.1;">
                Technical SEO Analyzer
            </h1>
            <p class="text-base sm:text-lg" style="color:var(--muted);max-width:560px;margin:0 auto;line-height:1.65;">
                Deep-crawl any site for meta tags, social previews, security, keyword density, broken links &amp; XML sitemap generation.
            </p>
        </header>

        <!-- INPUT -->
        <div class="glass-card p-4 sm:p-5 mb-7">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="globe" class="icon absolute left-3.5 top-1/2 -translate-y-1/2" style="color:var(--muted);pointer-events:none"></i>
                    <input type="text" id="targetUrl" placeholder="Enter domain — e.g., example.com"
                        class="search-input" oninput="syncDomainInput('targetUrl')" onkeydown="if(event.key==='Enter') startAudit()">
                </div>
                <button onclick="startAudit()" id="btnAction" class="btn-primary">
                    <i data-lucide="search" class="icon"></i>
                    <span>Analyze Site</span>
                </button>
            </div>
            <div id="progressWrap" class="hidden mt-3">
                <div class="progress-bar"></div>
            </div>
        </div>

        <!-- FINISH AREA -->
        <div id="finishArea" class="hidden mb-7">
            <div class="finish-banner flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-white">
                    <i data-lucide="check-circle-2" class="icon-xl" style="color:#34d399;flex-shrink:0;"></i>
                    <div>
                        <div class="heading text-lg sm:text-xl font-bold">Audit Complete!</div>
                        <div id="domainValidStatus" class="mt-1">
                            <span style="font-size:12px;color:rgba(255,255,255,.6);">Priority-ranked sitemap ready for download</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <button id="sitemapDownloadBtn" onclick="downloadSitemapPHP()" class="btn-primary btn-success" style="display:none;">
                        <i data-lucide="download" class="icon"></i>
                        <span>Download Sitemap.xml</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-7">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div><div class="stat-label">Pages Crawled</div><div id="statCount" class="stat-value" style="color:var(--accent);">0</div></div>
                    <i data-lucide="file-text" class="icon-lg opacity-30" style="color:var(--muted);flex-shrink:0;"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div><div class="stat-label">Avg. SEO Score</div><div id="statScore" class="stat-value" style="color:var(--success);">0%</div></div>
                    <i data-lucide="activity" class="icon-lg opacity-30" style="color:var(--muted);flex-shrink:0;"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div><div class="stat-label">Queue</div><div id="statQueue" class="stat-value" style="color:var(--ink-3);">0</div></div>
                    <i data-lucide="list" class="icon-lg opacity-30" style="color:var(--muted);flex-shrink:0;"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div><div class="stat-label">Status</div><div id="statStatus" class="stat-value text-base pulse-anim" style="color:var(--muted);font-size:16px!important;">Ready</div></div>
                    <i data-lucide="radio" class="icon-lg opacity-30" style="color:var(--muted);flex-shrink:0;"></i>
                </div>
            </div>
        </div>

        <!-- RESULTS LIST -->
        <div id="resultSection" class="result-list-wrap">
            <div style="background:linear-gradient(to right,var(--surface),#fff);border-bottom:1.5px solid var(--border);padding:16px 24px;" class="flex items-center gap-2">
                <i data-lucide="layout-list" class="icon" style="color:var(--muted);"></i>
                <span class="heading font-bold" style="font-size:15px;">Crawled Pages</span>
                <span class="text-xs ml-2" style="color:var(--muted);">Click "Full Report" for detailed analysis</span>
            </div>
            <div id="resultList">
                <div class="p-10 sm:p-14 text-center" style="color:var(--muted);">
                    <i data-lucide="scan-search" class="icon-xl mx-auto mb-3 opacity-25"></i>
                    <p class="text-sm">No data yet. Enter a URL above and click Analyze Site.</p>
                </div>
            </div>
        </div>

    </div><!-- /page-audit -->
