<?php /* Page: numgen */ ?>

<div id="page-numgen" class="page-section">
    <div class="flex items-center gap-3 mb-6">
        <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(245,158,11,.3);">
            <i data-lucide="hash" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div>
            <h2 class="heading font-bold text-xl" style="color:var(--ink);">Number Generator</h2>
            <p class="text-sm" style="color:var(--muted);">Random integers, floats, UUIDs, sequences, passwords &amp; more</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" id="ngGrid">

        <!-- Left: Controls -->
        <div style="display:flex;flex-direction:column;gap:14px;">

            <!-- Mode tabs -->
            <div class="glass-card p-4">
                <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Generator Type</div>
                <div class="flex flex-wrap gap-2" id="ngModeBtns">
                    <button class="tab-btn active" onclick="ngSetMode('integer')" id="ngMode_integer">
                        <i data-lucide="hash" style="width:13px;height:13px;"></i> Integer
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('float')" id="ngMode_float">
                        <i data-lucide="percent" style="width:13px;height:13px;"></i> Float
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('sequence')" id="ngMode_sequence">
                        <i data-lucide="list-ordered" style="width:13px;height:13px;"></i> Sequence
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('uuid')" id="ngMode_uuid">
                        <i data-lucide="fingerprint" style="width:13px;height:13px;"></i> UUID
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('password')" id="ngMode_password">
                        <i data-lucide="key-round" style="width:13px;height:13px;"></i> Password
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('timestamp')" id="ngMode_timestamp">
                        <i data-lucide="clock" style="width:13px;height:13px;"></i> Timestamp
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('hex')" id="ngMode_hex">
                        <i data-lucide="binary" style="width:13px;height:13px;"></i> Hex / Binary
                    </button>
                    <button class="tab-btn" onclick="ngSetMode('gaussian')" id="ngMode_gaussian">
                        <i data-lucide="activity" style="width:13px;height:13px;"></i> Gaussian
                    </button>
                </div>
            </div>

            <!-- Options panel (dynamic) -->
            <div class="glass-card p-4" id="ngOptions">

                <!-- INTEGER options -->
                <div id="ngOpt_integer">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Min Value</label>
                            <input type="number" id="ngIntMin" value="1" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Max Value</label>
                            <input type="number" id="ngIntMax" value="100" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                        <input type="number" id="ngIntCount" value="10" min="1" max="10000" class="search-input" style="padding:8px 12px;font-size:13px;">
                    </div>
                    <div class="flex items-center gap-2 mb-1">
                        <input type="checkbox" id="ngIntUnique" checked style="accent-color:var(--accent);">
                        <label for="ngIntUnique" class="text-sm font-medium" style="color:var(--ink);">Unique values only</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="ngIntSorted" style="accent-color:var(--accent);">
                        <label for="ngIntSorted" class="text-sm font-medium" style="color:var(--ink);">Sort ascending</label>
                    </div>
                </div>

                <!-- FLOAT options -->
                <div id="ngOpt_float" style="display:none;">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Min</label>
                            <input type="number" id="ngFloatMin" value="0" step="any" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Max</label>
                            <input type="number" id="ngFloatMax" value="1" step="any" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Decimal places</label>
                            <input type="number" id="ngFloatDecimals" value="4" min="0" max="15" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                            <input type="number" id="ngFloatCount" value="10" min="1" max="10000" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                </div>

                <!-- SEQUENCE options -->
                <div id="ngOpt_sequence" style="display:none;">
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Start</label>
                            <input type="number" id="ngSeqStart" value="1" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">End</label>
                            <input type="number" id="ngSeqEnd" value="50" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Step</label>
                            <input type="number" id="ngSeqStep" value="1" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="ngSeqShuffle" style="accent-color:var(--accent);">
                        <label for="ngSeqShuffle" class="text-sm font-medium" style="color:var(--ink);">Shuffle result</label>
                    </div>
                </div>

                <!-- UUID options -->
                <div id="ngOpt_uuid" style="display:none;">
                    <div class="mb-3">
                        <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                        <input type="number" id="ngUuidCount" value="5" min="1" max="1000" class="search-input" style="padding:8px 12px;font-size:13px;">
                    </div>
                    <div class="flex flex-wrap gap-2 mb-3" id="ngUuidVerBtns">
                        <button class="tab-btn active" onclick="ngSetUuidVer(4)" id="ngUuidVer4">v4 (random)</button>
                        <button class="tab-btn" onclick="ngSetUuidVer(7)" id="ngUuidVer7">v7 (timestamp)</button>
                        <button class="tab-btn" onclick="ngSetUuidVer('nano')" id="ngUuidVernano">Nano ID</button>
                        <button class="tab-btn" onclick="ngSetUuidVer('cuid')" id="ngUuidVercuid">CUID-like</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="ngUuidUpper" style="accent-color:var(--accent);">
                        <label for="ngUuidUpper" class="text-sm font-medium" style="color:var(--ink);">Uppercase</label>
                    </div>
                </div>

                <!-- PASSWORD options -->
                <div id="ngOpt_password" style="display:none;">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Length</label>
                            <input type="number" id="ngPassLen" value="16" min="4" max="128" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                            <input type="number" id="ngPassCount" value="5" min="1" max="100" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="ngPassUpper" checked style="accent-color:var(--accent);"> Uppercase (A-Z)</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="ngPassLower" checked style="accent-color:var(--accent);"> Lowercase (a-z)</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="ngPassNums" checked style="accent-color:var(--accent);"> Numbers (0-9)</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="ngPassSyms" style="accent-color:var(--accent);"> Symbols (!@#$...)</label>
                    </div>
                </div>

                <!-- TIMESTAMP options -->
                <div id="ngOpt_timestamp" style="display:none;">
                    <div class="mb-3">
                        <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                        <input type="number" id="ngTsCount" value="5" min="1" max="1000" class="search-input" style="padding:8px 12px;font-size:13px;">
                    </div>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <button class="tab-btn active" onclick="ngSetTsFmt('unix')" id="ngTsFmt_unix">Unix (s)</button>
                        <button class="tab-btn" onclick="ngSetTsFmt('ms')" id="ngTsFmt_ms">Unix (ms)</button>
                        <button class="tab-btn" onclick="ngSetTsFmt('iso')" id="ngTsFmt_iso">ISO 8601</button>
                        <button class="tab-btn" onclick="ngSetTsFmt('now')" id="ngTsFmt_now">Current + offsets</button>
                    </div>
                </div>

                <!-- HEX / BINARY options -->
                <div id="ngOpt_hex" style="display:none;">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Byte length</label>
                            <input type="number" id="ngHexLen" value="16" min="1" max="64" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                            <input type="number" id="ngHexCount" value="5" min="1" max="1000" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <button class="tab-btn active" onclick="ngSetHexFmt('hex')" id="ngHexFmt_hex">Hex</button>
                        <button class="tab-btn" onclick="ngSetHexFmt('binary')" id="ngHexFmt_binary">Binary</button>
                        <button class="tab-btn" onclick="ngSetHexFmt('octal')" id="ngHexFmt_octal">Octal</button>
                        <button class="tab-btn" onclick="ngSetHexFmt('base64')" id="ngHexFmt_base64">Base64</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="ngHexPrefix" checked style="accent-color:var(--accent);">
                        <label for="ngHexPrefix" class="text-sm">Prefix (0x / 0b)</label>
                    </div>
                </div>

                <!-- GAUSSIAN options -->
                <div id="ngOpt_gaussian" style="display:none;">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Mean (μ)</label>
                            <input type="number" id="ngGausMean" value="0" step="any" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Std Dev (σ)</label>
                            <input type="number" id="ngGausStd" value="1" step="any" min="0.001" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Decimal places</label>
                            <input type="number" id="ngGausDec" value="4" min="0" max="15" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                        <div>
                            <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Count</label>
                            <input type="number" id="ngGausCount" value="10" min="1" max="10000" class="search-input" style="padding:8px 12px;font-size:13px;">
                        </div>
                    </div>
                </div>

            </div><!-- /ngOptions -->

            <!-- Format + Generate button -->
            <div class="flex gap-2 flex-wrap">
                <button onclick="ngGenerate()" class="btn-primary flex-1" style="min-height:44px;">
                    <i data-lucide="shuffle" class="icon-sm"></i> Generate
                </button>
                <select id="ngOutFmt" style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;padding:0 12px;border:1.5px solid var(--border);border-radius:14px;background:#fff;color:var(--ink);outline:none;cursor:pointer;min-height:44px;">
                    <option value="lines">One per line</option>
                    <option value="csv">CSV</option>
                    <option value="array_js">JS Array</option>
                    <option value="array_json">JSON Array</option>
                    <option value="array_php">PHP Array</option>
                    <option value="ssv">Space separated</option>
                </select>
            </div>
        </div>

        <!-- Right: Output -->
        <div style="display:flex;flex-direction:column;">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Generated Output</span>
                <div class="flex gap-2">
                    <span id="ngCountBadge" class="tag tag-neutral" style="font-size:10px;"></span>
                    <button onclick="ngCopyOutput()" id="ngCopyBtn" class="btn-secondary" style="font-size:11px;padding:4px 10px;min-height:26px;">
                        <i data-lucide="copy" class="icon-sm"></i> Copy
                    </button>
                    <button onclick="ngDownload()" class="btn-secondary" style="font-size:11px;padding:4px 10px;min-height:26px;">
                        <i data-lucide="download" class="icon-sm"></i> Download
                    </button>
                </div>
            </div>
            <textarea id="ngOutput" class="case-textarea" rows="28" readonly
                placeholder="Generated numbers will appear here…"
                style="font-family:'DM Mono',monospace,sans-serif;font-size:13px;flex:1;min-height:500px;resize:vertical;line-height:1.7;background:var(--surface);cursor:default;"></textarea>

            <!-- Stats bar -->
            <div id="ngStats" class="flex flex-wrap gap-3 mt-3" style="display:none!important;">
                <span class="case-stat" id="ngStatCount">—</span>
                <span class="case-stat" id="ngStatMin">—</span>
                <span class="case-stat" id="ngStatMax">—</span>
                <span class="case-stat" id="ngStatSum">—</span>
                <span class="case-stat" id="ngStatAvg">—</span>
            </div>
        </div>

    </div><!-- /ngGrid -->
</div><!-- /page-numgen -->
