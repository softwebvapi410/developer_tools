<?php /* Page: colorgen */ ?>

<div id="page-colorgen" class="page-section">
    <div class="flex items-center gap-3 mb-6">
        <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#ec4899,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(236,72,153,.3);">
            <i data-lucide="palette" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div>
            <h2 class="heading font-bold text-xl" style="color:var(--ink);">Colour Code Generator</h2>
            <p class="text-sm" style="color:var(--muted);">Enter any colour code → auto-converts HEX · RGB · HSL · HSB · CMYK · CSS vars. Edit the visual picker or any field.</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" id="cgGrid">

        <!-- LEFT: Picker + Inputs -->
        <div style="display:flex;flex-direction:column;gap:14px;">

            <!-- Visual colour picker -->
            <div class="glass-card p-4">
                <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Colour Picker</div>

                <!-- Gradient canvas picker -->
                <div style="position:relative;border-radius:12px;overflow:hidden;margin-bottom:12px;">
                    <canvas id="cgCanvas" width="400" height="220"
                        style="width:100%;height:220px;display:block;cursor:crosshair;border-radius:12px;touch-action:none;"
                        onmousedown="cgCanvasDown(event)"
                        ontouchstart="cgCanvasTouchStart(event)"></canvas>
                    <!-- Crosshair cursor -->
                    <div id="cgCursor" style="position:absolute;width:16px;height:16px;border-radius:50%;border:2.5px solid #fff;box-shadow:0 0 0 1.5px rgba(0,0,0,.4),0 2px 6px rgba(0,0,0,.3);pointer-events:none;transform:translate(-50%,-50%);top:50%;left:50%;transition:none;"></div>
                </div>

                <!-- Hue slider -->
                <div style="margin-bottom:10px;">
                    <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Hue</label>
                    <input type="range" id="cgHueSlider" min="0" max="360" value="200"
                        style="width:100%;height:12px;border-radius:6px;outline:none;cursor:pointer;
                               background:linear-gradient(to right,#f00,#ff0,#0f0,#0ff,#00f,#f0f,#f00);"
                        oninput="cgHueChanged()">
                </div>

                <!-- Alpha slider -->
                <div style="margin-bottom:4px;">
                    <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">Opacity / Alpha</label>
                    <div style="position:relative;">
                        <div id="cgAlphaBg" style="position:absolute;inset:0;border-radius:6px;background:repeating-conic-gradient(#ccc 0% 25%,#fff 0% 50%) 0 0/12px 12px;"></div>
                        <input type="range" id="cgAlphaSlider" min="0" max="100" value="100"
                            style="position:relative;width:100%;height:12px;border-radius:6px;outline:none;cursor:pointer;background:transparent;"
                            oninput="cgAlphaChanged()">
                    </div>
                </div>
            </div>

            <!-- Colour inputs — all editable, all auto-sync -->
            <div class="glass-card p-4">
                <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Colour Codes — Edit Any Field</div>

                <!-- HEX -->
                <div class="flex items-center gap-2 mb-3">
                    <div id="cgHexSwatch" style="width:32px;height:32px;border-radius:8px;border:2px solid var(--border);flex-shrink:0;cursor:pointer;position:relative;overflow:hidden;"
                        onclick="document.getElementById('cgNativePicker').click()">
                        <input type="color" id="cgNativePicker" style="opacity:0;position:absolute;inset:0;width:100%;height:100%;cursor:pointer;border:none;padding:0;" oninput="cgNativePickerChanged()">
                    </div>
                    <div style="flex:1;">
                        <label class="text-xs font-bold" style="color:var(--muted);">HEX</label>
                        <input type="text" id="cgHexInput" value="#3B82F6" maxlength="9"
                            style="font-family:'DM Mono',monospace;font-size:14px;font-weight:700;width:100%;padding:6px 10px;border:1.5px solid var(--border);border-radius:9px;outline:none;color:var(--ink);background:#fff;transition:border-color .2s;"
                            oninput="cgHexInputChanged()" onfocus="this.select()"
                            placeholder="#RRGGBB or #RRGGBBAA">
                    </div>
                </div>

                <!-- RGB -->
                <div class="mb-3">
                    <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">RGB</label>
                    <div class="flex gap-2">
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgR" min="0" max="255" value="59"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid #fca5a5;border-radius:9px;outline:none;text-align:center;background:#fff5f5;"
                                oninput="cgRgbChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:#ef4444;font-weight:700;">R</div>
                        </div>
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgG" min="0" max="255" value="130"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid #86efac;border-radius:9px;outline:none;text-align:center;background:#f0fdf4;"
                                oninput="cgRgbChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:#16a34a;font-weight:700;">G</div>
                        </div>
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgB" min="0" max="255" value="246"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid #93c5fd;border-radius:9px;outline:none;text-align:center;background:#eff6ff;"
                                oninput="cgRgbChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:#2563eb;font-weight:700;">B</div>
                        </div>
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgA" min="0" max="100" value="100"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:9px;outline:none;text-align:center;background:#fff;"
                                oninput="cgRgbChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:var(--muted);font-weight:700;">A%</div>
                        </div>
                    </div>
                </div>

                <!-- HSL -->
                <div class="mb-3">
                    <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">HSL</label>
                    <div class="flex gap-2">
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgH" min="0" max="360" value="213"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:9px;outline:none;text-align:center;background:#fff;"
                                oninput="cgHslChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:var(--muted);font-weight:700;">H°</div>
                        </div>
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgS" min="0" max="100" value="89"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:9px;outline:none;text-align:center;background:#fff;"
                                oninput="cgHslChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:var(--muted);font-weight:700;">S%</div>
                        </div>
                        <div style="flex:1;text-align:center;">
                            <input type="number" id="cgL" min="0" max="100" value="60"
                                style="font-family:'DM Mono',monospace;font-size:13px;font-weight:700;width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:9px;outline:none;text-align:center;background:#fff;"
                                oninput="cgHslChanged()" onfocus="this.select()">
                            <div class="text-xs mt-1" style="color:var(--muted);font-weight:700;">L%</div>
                        </div>
                    </div>
                </div>

                <!-- HSB / CMYK row -->
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div>
                        <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">HSB / HSV</label>
                        <input type="text" id="cgHsbDisplay"
                            style="font-family:'DM Mono',monospace;font-size:12px;width:100%;padding:6px 10px;border:1.5px solid var(--border);border-radius:9px;outline:none;color:var(--ink);background:var(--surface);"
                            oninput="cgHsbChanged()" onfocus="this.select()" placeholder="213°, 76%, 96%">
                    </div>
                    <div>
                        <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">CMYK</label>
                        <input type="text" id="cgCmykDisplay"
                            style="font-family:'DM Mono',monospace;font-size:12px;width:100%;padding:6px 10px;border:1.5px solid var(--border);border-radius:9px;outline:none;color:var(--ink);background:var(--surface);"
                            oninput="cgCmykChanged()" onfocus="this.select()" placeholder="76%, 47%, 0%, 4%">
                    </div>
                </div>

                <!-- CSS var name -->
                <div>
                    <label class="text-xs font-bold mb-1 block" style="color:var(--muted);">CSS Variable</label>
                    <input type="text" id="cgCssVar"
                        style="font-family:'DM Mono',monospace;font-size:12px;width:100%;padding:6px 10px;border:1.5px solid var(--border);border-radius:9px;outline:none;color:var(--ink);background:var(--surface);"
                        readonly>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="flex gap-2 flex-wrap">
                <button onclick="cgRandom()" class="btn-primary flex-1" style="min-height:40px;">
                    <i data-lucide="shuffle" class="icon-sm"></i> Random Colour
                </button>
                <button onclick="cgCopyAll()" class="btn-secondary" style="min-height:40px;padding:0 14px;">
                    <i data-lucide="copy" class="icon-sm"></i> Copy All
                </button>
                <button onclick="cgAddToHistory()" class="btn-secondary" style="min-height:40px;padding:0 14px;">
                    <i data-lucide="bookmark" class="icon-sm"></i> Save
                </button>
            </div>

        </div>

        <!-- RIGHT: Preview + Palettes + History -->
        <div style="display:flex;flex-direction:column;gap:14px;">

            <!-- Live Preview -->
            <div class="glass-card p-4">
                <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Live Preview</div>

                <!-- Large colour block -->
                <div id="cgPreviewBlock" style="height:120px;border-radius:12px;border:1.5px solid var(--border);margin-bottom:12px;transition:background .15s;display:flex;align-items:center;justify-content:center;">
                    <span id="cgPreviewHex" style="font-family:'DM Mono',monospace;font-size:18px;font-weight:800;text-shadow:0 1px 3px rgba(0,0,0,.3);color:#fff;letter-spacing:.04em;">#3B82F6</span>
                </div>

                <!-- Text on colour previews -->
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div id="cgPreviewDark" style="padding:14px;border-radius:10px;text-align:center;transition:background .15s;">
                        <div style="font-size:11px;font-weight:700;margin-bottom:2px;opacity:.7;">Dark text</div>
                        <div style="font-size:15px;font-weight:800;color:#1a1a1a;">Aa Bb Cc</div>
                    </div>
                    <div id="cgPreviewLight" style="padding:14px;border-radius:10px;text-align:center;transition:background .15s;">
                        <div style="font-size:11px;font-weight:700;margin-bottom:2px;opacity:.7;color:#fff;">Light text</div>
                        <div style="font-size:15px;font-weight:800;color:#fff;">Aa Bb Cc</div>
                    </div>
                </div>

                <!-- Contrast ratio -->
                <div id="cgContrastBar" class="p-3 rounded-xl" style="background:var(--surface);border:1.5px solid var(--border);">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold" style="color:var(--muted);">Contrast Ratio (vs White)</span>
                        <span id="cgContrastRatio" class="font-bold text-sm" style="color:var(--ink);">—</span>
                    </div>
                    <div style="height:6px;background:var(--border);border-radius:100px;overflow:hidden;">
                        <div id="cgContrastFill" style="height:100%;border-radius:100px;transition:width .3s;background:var(--accent);width:0%;"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-xs" style="color:var(--muted);">AA Normal: 4.5:1</span>
                        <span id="cgContrastLabel" class="text-xs font-bold"></span>
                    </div>
                </div>
            </div>

            <!-- Auto-generated palettes -->
            <div class="glass-card p-4">
                <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Auto Palettes</div>

                <div id="cgPalettes" style="display:flex;flex-direction:column;gap:10px;">
                    <!-- Tints -->
                    <div>
                        <div class="text-xs font-semibold mb-1" style="color:var(--muted);">Tints (lighter)</div>
                        <div id="cgTints" class="flex gap-1" style="height:36px;"></div>
                    </div>
                    <!-- Shades -->
                    <div>
                        <div class="text-xs font-semibold mb-1" style="color:var(--muted);">Shades (darker)</div>
                        <div id="cgShades" class="flex gap-1" style="height:36px;"></div>
                    </div>
                    <!-- Complementary -->
                    <div>
                        <div class="text-xs font-semibold mb-1" style="color:var(--muted);">Complementary</div>
                        <div id="cgComplementary" class="flex gap-1" style="height:36px;"></div>
                    </div>
                    <!-- Analogous -->
                    <div>
                        <div class="text-xs font-semibold mb-1" style="color:var(--muted);">Analogous</div>
                        <div id="cgAnalogous" class="flex gap-1" style="height:36px;"></div>
                    </div>
                    <!-- Triadic -->
                    <div>
                        <div class="text-xs font-semibold mb-1" style="color:var(--muted);">Triadic</div>
                        <div id="cgTriadic" class="flex gap-1" style="height:36px;"></div>
                    </div>
                </div>
            </div>

            <!-- Saved colours history -->
            <div class="glass-card p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-xs font-bold" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">Saved Colours</div>
                    <button onclick="cgClearHistory()" class="btn-secondary" style="font-size:11px;padding:3px 9px;min-height:24px;">Clear</button>
                </div>
                <div id="cgHistory" class="flex flex-wrap gap-2">
                    <span class="text-xs" style="color:var(--muted);">No saved colours yet. Click "Save" to bookmark a colour.</span>
                </div>
            </div>

            <!-- CSS export snippets -->
            <div class="glass-card p-4">
                <div class="text-xs font-bold mb-3" style="color:var(--muted);letter-spacing:.06em;text-transform:uppercase;">CSS Export</div>
                <div id="cgCssExport" style="font-family:'DM Mono',monospace;font-size:12px;background:var(--surface);border:1.5px solid var(--border);border-radius:10px;padding:12px;line-height:1.8;white-space:pre-wrap;word-break:break-all;"></div>
                <button onclick="cgCopyCss()" class="btn-secondary mt-2 w-full" style="font-size:12px;">
                    <i data-lucide="copy" class="icon-sm"></i> Copy CSS
                </button>
            </div>

        </div>
    </div><!-- /cgGrid -->
</div><!-- /page-colorgen -->
