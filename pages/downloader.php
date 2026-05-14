<?php /* Page: downloader — included by index.php */ ?>

    <div id="page-downloader" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:16px;background:linear-gradient(135deg,var(--accent),var(--accent-2));display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 20px rgba(59,91,219,.18);">
                <i data-lucide="download" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold text-xl" style="color:var(--ink);">Website Downloader</h2>
                <p class="text-sm" style="color:var(--muted);">Open the full website downloader tool and download pages with assets, CSS, JS and images.</p>
            </div>
        </div>

        <div class="glass-card p-4 sm:p-5 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <p style="font-size:13px;font-weight:700;color:var(--ink-3);margin-bottom:4px;">Website Downloader</p>
                    <p style="font-size:13px;color:var(--muted);max-width:680px;">This page loads the standalone website downloader tool inside the same interface.</p>
                </div>
                <a href="website_downloader.php" class="btn-secondary" style="white-space:nowrap;">Open Standalone Downloader</a>
            </div>
        </div>

        <div class="glass-card p-3 sm:p-4" style="min-height:72vh;">
            <iframe src="website_downloader.php" class="h-full w-full rounded-3xl" style="min-height:72vh;width:100%;border:none;border-radius:24px;"></iframe>
        </div>
    </div><!-- /page-downloader -->
