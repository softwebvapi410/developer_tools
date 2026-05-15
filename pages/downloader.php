<?php /* Page: downloader — included by index.php */ ?>

<div id="page-downloader" class="page-section">
    <div class="flex items-center gap-3 mb-6">
        <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#3b5bdb,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(59,91,219,.3);">
            <i data-lucide="download" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div>
            <h2 class="heading font-bold text-xl" style="color:var(--ink);">Website Asset Downloader</h2>
            <p class="text-sm" style="color:var(--muted);">Deep-crawl any page — CSS, JS, images, fonts, documents — with proxy rotation, CDN fallback &amp; ZIP export</p>
        </div>
    </div>

    <!-- Input card -->
    <div class="glass-card p-4 sm:p-5 mb-5">
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <i data-lucide="globe" class="icon absolute left-3.5 top-1/2 -translate-y-1/2" style="color:var(--muted);pointer-events:none;"></i>
                <input type="url" id="dlUrl" placeholder="https://example.com/page" class="search-input"
                    autocomplete="off" spellcheck="false"
                    onkeydown="if(event.key==='Enter')dlStart()">
            </div>
            <button onclick="dlStart()" id="dlStartBtn" class="btn-primary">
                <i data-lucide="download" class="icon"></i> Download
            </button>
        </div>

        <!-- Options row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Concurrency -->
            <div>
                <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Threads</label>
                <input type="number" id="dlConc" min="1" max="32" value="12"
                    class="search-input" style="padding:8px 12px;font-size:13px;text-align:center;">
            </div>
            <!-- Deep scan -->
            <div class="flex flex-col justify-between">
                <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Deep Scan (CSS/JS)</label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;height:40px;">
                    <div class="relative" style="flex-shrink:0;">
                        <input type="checkbox" id="dlDeep" checked class="sr-only">
                        <div id="dlDeepTrack" onclick="dlToggle('dlDeep','dlDeepTrack')"
                            style="width:38px;height:21px;border-radius:100px;background:var(--accent);cursor:pointer;position:relative;transition:background .2s;">
                            <div id="dlDeepThumb" style="position:absolute;top:2.5px;left:18px;width:16px;height:16px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></div>
                        </div>
                    </div>
                    <span class="text-sm" style="color:var(--ink);">Enabled</span>
                </label>
            </div>
            <!-- Create ZIP -->
            <div class="flex flex-col justify-between">
                <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Package as ZIP</label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;height:40px;">
                    <div class="relative" style="flex-shrink:0;">
                        <input type="checkbox" id="dlZip" checked class="sr-only">
                        <div id="dlZipTrack" onclick="dlToggle('dlZip','dlZipTrack')"
                            style="width:38px;height:21px;border-radius:100px;background:var(--accent);cursor:pointer;position:relative;transition:background .2s;">
                            <div id="dlZipThumb" style="position:absolute;top:2.5px;left:18px;width:16px;height:16px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s;"></div>
                        </div>
                    </div>
                    <span class="text-sm" style="color:var(--ink);">Enabled</span>
                </label>
            </div>
            <!-- Output folder -->
            <div>
                <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Output Folder</label>
                <input type="text" id="dlOut" value="downloaded"
                    class="search-input" style="padding:8px 12px;font-size:13px;">
            </div>
        </div>
    </div>

    <!-- Stats bar (hidden until download starts) -->
    <div id="dlStatsBar" style="display:none;" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="stat-card">
            <div class="stat-label">Downloaded</div>
            <div id="dlStatOk" class="stat-value" style="color:var(--success);">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Retried</div>
            <div id="dlStatRetry" class="stat-value" style="color:var(--warning);">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Failed</div>
            <div id="dlStatFail" class="stat-value" style="color:var(--danger);">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Size</div>
            <div id="dlStatBytes" class="stat-value" style="color:var(--accent);">0 B</div>
        </div>
    </div>

    <!-- Download ZIP button (hidden until done) -->
    <div id="dlZipArea" style="display:none;" class="mb-5">
        <div class="finish-banner flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="icon-xl" style="color:#34d399;flex-shrink:0;"></i>
                <div>
                    <div class="heading text-lg font-bold text-white">Download Complete!</div>
                    <div id="dlZipExpiry" class="text-sm" style="color:rgba(255,255,255,.6);">ZIP auto-deletes in 5 minutes</div>
                </div>
            </div>
            <a id="dlZipBtn" href="#" class="btn-primary" style="background:linear-gradient(135deg,#059669,#0e9f6e);box-shadow:0 4px 14px rgba(14,159,110,.35);text-decoration:none;white-space:nowrap;">
                <i data-lucide="archive" class="icon"></i>
                <span id="dlZipLabel">Download ZIP</span>
            </a>
        </div>
        <!-- Timer bar -->
        <div style="margin-top:10px;height:3px;background:rgba(255,255,255,.1);border-radius:100px;overflow:hidden;">
            <div id="dlTimerBar" style="height:100%;background:#34d399;border-radius:100px;width:100%;transform-origin:left;transition:width 1s linear;"></div>
        </div>
    </div>

    <!-- Log output -->
    <div id="dlLogWrap" style="display:none;" class="result-list-wrap">
        <div style="background:linear-gradient(to right,var(--surface),#fff);border-bottom:1.5px solid var(--border);padding:14px 20px;" class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="activity" class="icon" style="color:var(--muted);"></i>
                <span class="heading font-bold" style="font-size:15px;">Download Log</span>
                <span id="dlLiveDot" class="w-2 h-2 rounded-full inline-block" style="background:var(--accent);animation:pulse 1.5s ease infinite;"></span>
                <span id="dlLiveLabel" class="text-xs font-bold" style="color:var(--muted);">CONNECTING…</span>
            </div>
            <button onclick="dlClearLog()" class="btn-secondary" style="font-size:11px;padding:4px 10px;min-height:26px;">
                <i data-lucide="trash-2" class="icon-sm"></i> Clear
            </button>
        </div>
        <div id="dlLog" style="max-height:480px;overflow-y:auto;padding:12px 0;font-family:'DM Mono',monospace;font-size:12px;line-height:1.7;"></div>
    </div>

    <!-- Feature chips -->
    <div class="mt-5 glass-card p-4">
        <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Features</div>
        <div class="flex flex-wrap gap-2">
            <?php $chips=[
                ['zap','12× parallel threads'],['refresh-cw','3× auto-retry'],
                ['shield','Proxy rotation on 429'],['server','CDN fallback'],
                ['scan-line','CSS url() / @import'],['braces','JS import/require'],
                ['layers','Webpack chunks'],['cpu','Service workers'],
                ['archive','ZIP export'],['clock','1h auto-delete'],
                ['file-check','200MB cap per file'],['list','200+ file types'],
            ]; foreach($chips as [$ic,$lb]): ?>
            <span class="tag tag-neutral" style="font-size:11px;display:inline-flex;align-items:center;gap:4px;">
                <i data-lucide="<?=$ic?>" style="width:11px;height:11px;"></i> <?=$lb?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /page-downloader -->
