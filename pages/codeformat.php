<?php /* Page: codeformat */ ?>

<div id="page-codeformat" class="page-section">
    <div class="flex items-center gap-3 mb-6">
        <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(99,102,241,.3);">
            <i data-lucide="code-2" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div>
            <h2 class="heading font-bold text-xl" style="color:var(--ink);">Code Formatter</h2>
            <p class="text-sm" style="color:var(--muted);">Beautify &amp; format HTML, CSS, JavaScript, JSON, SQL and more</p>
        </div>
    </div>

    <!-- Language + Options bar -->
    <div class="glass-card p-4 mb-4">
        <div class="flex flex-wrap items-center gap-3">
            <!-- Language selector -->
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold" style="color:var(--muted);white-space:nowrap;">Language</label>
                <select id="cfLang" onchange="cfOnLangChange()" style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;padding:7px 12px;border:1.5px solid var(--border);border-radius:10px;background:#fff;color:var(--ink);outline:none;cursor:pointer;">
                    <option value="javascript">JavaScript</option>
                    <option value="json">JSON</option>
                    <option value="html">HTML</option>
                    <option value="css">CSS</option>
                    <option value="sql">SQL</option>
                    <option value="xml">XML</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                    <option value="markdown">Markdown</option>
                </select>
            </div>

            <!-- Indent size -->
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold" style="color:var(--muted);">Indent</label>
                <select id="cfIndent" style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;padding:7px 10px;border:1.5px solid var(--border);border-radius:10px;background:#fff;color:var(--ink);outline:none;cursor:pointer;">
                    <option value="2">2 spaces</option>
                    <option value="4" selected>4 spaces</option>
                    <option value="tab">Tab</option>
                </select>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-2 ml-auto flex-wrap">
                <button onclick="cfFormat()" class="btn-primary" id="cfFormatBtn" style="padding:8px 18px;font-size:13px;">
                    <i data-lucide="wand-2" class="icon-sm"></i> Format
                </button>
                <button onclick="cfMinify()" class="btn-secondary" style="padding:8px 14px;font-size:13px;">
                    <i data-lucide="minimize-2" class="icon-sm"></i> Minify
                </button>
                <button onclick="cfCopyOutput()" class="btn-secondary" id="cfCopyBtn" style="padding:8px 14px;font-size:13px;">
                    <i data-lucide="copy" class="icon-sm"></i> Copy
                </button>
                <button onclick="cfClear()" class="btn-secondary" style="padding:8px 14px;font-size:13px;">
                    <i data-lucide="trash-2" class="icon-sm"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Editor pane: input | output -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" id="cfGrid">

        <!-- Input -->
        <div style="display:flex;flex-direction:column;">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Input</span>
                <div class="flex gap-2">
                    <button onclick="cfPaste()" class="btn-secondary" style="font-size:11px;padding:4px 10px;min-height:26px;">
                        <i data-lucide="clipboard" class="icon-sm"></i> Paste
                    </button>
                    <button onclick="cfUpload()" class="btn-secondary" style="font-size:11px;padding:4px 10px;min-height:26px;">
                        <i data-lucide="upload" class="icon-sm"></i> Upload
                    </button>
                    <input type="file" id="cfFileInput" accept=".js,.json,.html,.css,.sql,.xml,.php,.py,.md,.txt" style="display:none;" onchange="cfLoadFile(event)">
                </div>
            </div>
            <div style="position:relative;flex:1;">
                <textarea id="cfInput" class="case-textarea" rows="22"
                    placeholder="Paste your code here…"
                    oninput="cfOnInput()"
                    style="font-family:'DM Mono',monospace,sans-serif;font-size:13px;width:100%;height:100%;min-height:420px;resize:vertical;tab-size:4;line-height:1.65;"></textarea>
                <div id="cfInputStats" style="position:absolute;bottom:8px;right:10px;font-size:10px;color:var(--muted);pointer-events:none;font-family:'DM Sans',sans-serif;">0 lines · 0 chars</div>
            </div>
        </div>

        <!-- Output -->
        <div style="display:flex;flex-direction:column;">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Output</span>
                <div class="flex gap-2 items-center">
                    <span id="cfStatusBadge" class="tag tag-neutral" style="font-size:10px;display:none;"></span>
                    <button onclick="cfDownload()" class="btn-secondary" style="font-size:11px;padding:4px 10px;min-height:26px;">
                        <i data-lucide="download" class="icon-sm"></i> Download
                    </button>
                </div>
            </div>
            <div style="position:relative;flex:1;">
                <textarea id="cfOutput" class="case-textarea" rows="22" readonly
                    placeholder="Formatted output will appear here…"
                    style="font-family:'DM Mono',monospace,sans-serif;font-size:13px;width:100%;height:100%;min-height:420px;resize:vertical;line-height:1.65;background:var(--surface);cursor:default;"></textarea>
                <div id="cfOutputStats" style="position:absolute;bottom:8px;right:10px;font-size:10px;color:var(--muted);pointer-events:none;font-family:'DM Sans',sans-serif;"></div>
            </div>
        </div>
    </div>

    <!-- Error / info bar -->
    <div id="cfError" style="display:none;margin-top:12px;" class="p-3 rounded-xl flex items-center gap-2" style="background:var(--danger-bg);border:1.5px solid #fecaca;">
        <i data-lucide="alert-circle" class="icon-sm" style="color:var(--danger);flex-shrink:0;"></i>
        <span id="cfErrorMsg" class="text-sm" style="color:var(--danger);"></span>
    </div>

    <!-- Quick examples -->
    <div class="mt-5 glass-card p-4">
        <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Quick Examples</div>
        <div class="flex flex-wrap gap-2">
            <button onclick="cfLoadExample('json')" class="btn-secondary" style="font-size:12px;padding:5px 12px;">JSON</button>
            <button onclick="cfLoadExample('html')" class="btn-secondary" style="font-size:12px;padding:5px 12px;">HTML</button>
            <button onclick="cfLoadExample('css')" class="btn-secondary" style="font-size:12px;padding:5px 12px;">CSS</button>
            <button onclick="cfLoadExample('javascript')" class="btn-secondary" style="font-size:12px;padding:5px 12px;">JavaScript</button>
            <button onclick="cfLoadExample('sql')" class="btn-secondary" style="font-size:12px;padding:5px 12px;">SQL</button>
            <button onclick="cfLoadExample('xml')" class="btn-secondary" style="font-size:12px;padding:5px 12px;">XML</button>
        </div>
    </div>

</div><!-- /page-codeformat -->
