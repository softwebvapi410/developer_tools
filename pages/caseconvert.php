<?php /* Page: caseconvert — included by index.php */ ?>

    <div id="page-caseconvert" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(245,158,11,.3);">
                <i data-lucide="case-sensitive" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold" style="font-size:var(--fs-xl);color:var(--ink);">Text Case Converter</h2>
                <p style="font-size:var(--fs-sm);color:var(--muted);">Instantly transform text — uppercase, lowercase, title case, camelCase &amp; more</p>
            </div>
        </div>

        <div class="glass-card" style="padding:var(--sp-4);margin-bottom:var(--sp-4);">
            <!-- Input -->
            <div style="margin-bottom:var(--sp-3);">
                <div class="flex items-center justify-between mb-2">
                    <label style="font-size:var(--fs-sm);font-weight:700;color:var(--ink);">Input Text</label>
                    <div class="flex gap-2">
                        <button onclick="ccPaste()" class="btn-secondary" style="font-size:11px!important;padding:5px 11px;min-height:28px;">
                            <i data-lucide="clipboard" class="icon-sm"></i> Paste
                        </button>
                        <button onclick="ccClear()" class="btn-secondary" style="font-size:11px!important;padding:5px 11px;min-height:28px;">
                            <i data-lucide="x" class="icon-sm"></i> Clear
                        </button>
                    </div>
                </div>
                <textarea id="ccInput" class="case-textarea" rows="5" placeholder="Type or paste your text here…" oninput="ccApply()"></textarea>
            </div>
            <!-- Stats row -->
            <div class="flex flex-wrap gap-2 mb-4" id="ccStats">
                <span class="case-stat">0 chars</span>
                <span class="case-stat">0 words</span>
                <span class="case-stat">0 sentences</span>
                <span class="case-stat">0 lines</span>
            </div>
            <!-- Conversion buttons -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button class="case-btn active" id="ccBtn_none" onclick="ccSetMode('none')" title="No transformation">Original</button>
                <button class="case-btn" id="ccBtn_upper" onclick="ccSetMode('upper')" title="HELLO WORLD">UPPER CASE</button>
                <button class="case-btn" id="ccBtn_lower" onclick="ccSetMode('lower')" title="hello world">lower case</button>
                <button class="case-btn" id="ccBtn_title" onclick="ccSetMode('title')" title="Hello World">Title Case</button>
                <button class="case-btn" id="ccBtn_sentence" onclick="ccSetMode('sentence')" title="Hello world. This is text.">Sentence case</button>
                <button class="case-btn" id="ccBtn_camel" onclick="ccSetMode('camel')" title="helloWorld">camelCase</button>
                <button class="case-btn" id="ccBtn_pascal" onclick="ccSetMode('pascal')" title="HelloWorld">PascalCase</button>
                <button class="case-btn" id="ccBtn_snake" onclick="ccSetMode('snake')" title="hello_world">snake_case</button>
                <button class="case-btn" id="ccBtn_kebab" onclick="ccSetMode('kebab')" title="hello-world">kebab-case</button>
                <button class="case-btn" id="ccBtn_const" onclick="ccSetMode('const')" title="HELLO_WORLD">CONSTANT_CASE</button>
                <button class="case-btn" id="ccBtn_dot" onclick="ccSetMode('dot')" title="hello.world">dot.case</button>
                <button class="case-btn" id="ccBtn_alt" onclick="ccSetMode('alt')" title="hElLo WoRlD">aLtErNaTiNg</button>
                <button class="case-btn" id="ccBtn_inv" onclick="ccSetMode('inv')" title="HeLLo WoRLD">iNVERSE</button>
            </div>
            <!-- Output -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label style="font-size:var(--fs-sm);font-weight:700;color:var(--ink);">Output</label>
                    <div class="flex gap-2">
                        <button onclick="ccCopy()" id="ccCopyBtn" class="btn-secondary" style="font-size:11px!important;padding:5px 11px;min-height:28px;">
                            <i data-lucide="copy" class="icon-sm"></i> Copy
                        </button>
                        <button onclick="ccSwap()" class="btn-secondary" style="font-size:11px!important;padding:5px 11px;min-height:28px;">
                            <i data-lucide="arrow-up-down" class="icon-sm"></i> Use as Input
                        </button>
                    </div>
                </div>
                <textarea id="ccOutput" class="case-textarea" rows="5" placeholder="Converted text will appear here…" readonly style="background:var(--surface);cursor:default;"></textarea>
            </div>
        </div>

        <!-- Quick-copy cards for common cases -->
        <div id="ccQuickCards" style="display:none;" class="mb-6">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="layers" class="icon" style="color:var(--muted);"></i>
                <span class="font-bold" style="font-size:var(--fs-sm);color:var(--ink);">All Conversions at a Glance</span>
            </div>
            <div id="ccAllCards" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
        </div>
    </div><!-- /page-caseconvert -->
