<?php /* SEO Report Modal */ ?>

<!-- MODAL -->
<div id="seoModal" class="modal" onclick="if(event.target===this)closeModal()">
    <div class="modal-inner">
        <div class="modal-header flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="file-search" class="icon" style="color:var(--accent);flex-shrink:0;"></i>
                    <span class="heading font-bold text-lg" style="color:var(--ink);">Detailed SEO Report</span>
                </div>
                <p id="modalUrl" class="text-xs truncate" style="color:var(--muted);"></p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a id="modalOpenLink" href="#" target="_blank" rel="noopener" title="Open page in new tab" class="close-btn" style="text-decoration:none;">
                    <i data-lucide="external-link" class="icon-sm"></i>
                </a>
                <button class="close-btn" onclick="closeModal()">
                    <i data-lucide="x" class="icon-sm"></i>
                </button>
            </div>
        </div>
        <div id="modalContent" class="p-5 sm:p-6"></div>
    </div>
</div>

