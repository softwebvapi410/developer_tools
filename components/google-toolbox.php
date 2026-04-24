<?php /* Google Toolbox Banner */ ?>

    <div class="mt-8 mb-2 p-5 glass-card" style="border:1.5px solid #dbeafe;">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div style="width:36px;height:36px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1.5px solid #dbeafe;flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-sm" style="color:var(--ink);">Google Admin Toolbox</p>
                    <p class="text-xs mt-0.5" style="color:var(--muted);">Official Google tools for DNS, email, browser debugging &amp; more.</p>
                </div>
            </div>
            <a href="https://toolbox.googleapps.com/apps/main/" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1.5px solid #dbeafe;color:#1d4ed8;font-size:13px;font-weight:700;padding:9px 16px;border-radius:12px;text-decoration:none;white-space:nowrap;flex-shrink:0;transition:all .2s;"
               onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='#fff'">
                <i data-lucide="external-link" class="icon-sm"></i>
                Open Toolbox
            </a>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <?php
            $gTools = [
                ['Check MX','https://toolbox.googleapps.com/apps/checkmx/','mail'],
                ['Dig','https://toolbox.googleapps.com/apps/dig/','terminal'],
                ['Browser Info','https://toolbox.googleapps.com/apps/browserinfo/','monitor'],
                ['Useragent','https://toolbox.googleapps.com/apps/useragent/','cpu'],
                ['Log Analyzer','https://toolbox.googleapps.com/apps/loganalyzer/','file-text'],
                ['Encode/Decode','https://toolbox.googleapps.com/apps/encode_decode/','code'],
            ];
            foreach ($gTools as [$label, $href, $icon]):
            ?>
            <a href="<?= $href ?>" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:5px;background:var(--surface);border:1.5px solid var(--border);color:var(--ink-3);font-size:11px;font-weight:600;padding:5px 11px;border-radius:8px;text-decoration:none;transition:all .15s;"
               onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--ink-3)'">
                <i data-lucide="<?= $icon ?>" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

