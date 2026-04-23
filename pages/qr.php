<?php /* Page: qr — included by index.php */ ?>

    <div id="page-qr" class="page-section">
        <div class="flex items-center gap-3 mb-6">
            <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#0ea5e9,#3b5bdb);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(14,165,233,.3);">
                <i data-lucide="qr-code" style="width:22px;height:22px;color:#fff;"></i>
            </div>
            <div>
                <h2 class="heading font-bold" style="font-size:var(--fs-xl);color:var(--ink);">QR Code Generator</h2>
                <p style="font-size:var(--fs-sm);color:var(--muted);">Encode URLs, text, contacts &amp; more — download as SVG, PNG, or JPG</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- LHS: Controls -->
            <div class="glass-card" style="padding:var(--sp-4);">
                <!-- Content input -->
                <div style="margin-bottom:var(--sp-3);">
                    <label class="font-bold" style="font-size:var(--fs-sm);color:var(--ink);display:block;margin-bottom:8px;">Content to Encode</label>
                    <div style="position:relative;">
                        <i data-lucide="pencil-line" style="position:absolute;left:13px;top:13px;width:15px;height:15px;color:var(--muted);pointer-events:none;z-index:1;"></i>
                        <textarea id="qrInput" class="case-textarea" rows="3" style="padding-left:36px;" placeholder="https://yourwebsite.com — URL, text, phone, email…" oninput="qrGenerate()"></textarea>
                    </div>
                </div>
                <!-- Type tabs -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php foreach([['url','link','URL'],['text','type','Text'],['email','mail','Email'],['phone','phone','Phone'],['wifi','wifi','Wi-Fi'],['vcard','user','vCard']] as [$t,$ic,$lab]): ?>
                    <button class="case-btn" id="qrType_<?=$t?>" onclick="qrSetType('<?=$t?>')" title="<?=$lab?>">
                        <i data-lucide="<?=$ic?>" class="icon-sm"></i> <?=$lab?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <!-- Wi-Fi fields (hidden by default) -->
                <div id="qrWifiFields" style="display:none;margin-bottom:var(--sp-3);">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:5px;">SSID (Network Name)</label>
                            <div style="position:relative;">
                                <i data-lucide="wifi" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted);pointer-events:none;"></i>
                                <input id="qrWifiSSID" class="search-input" style="padding-left:36px;" placeholder="MyNetwork" oninput="qrGenerate()">
                            </div>
                        </div>
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:5px;">Password</label>
                            <div style="position:relative;">
                                <i data-lucide="lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted);pointer-events:none;"></i>
                                <input id="qrWifiPass" class="search-input" style="padding-left:36px;" type="password" placeholder="Password" oninput="qrGenerate()">
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <?php foreach(['WPA','WEP','nopass'] as $sec): ?>
                        <button class="case-btn" id="qrSec_<?=$sec?>" onclick="qrSetSecurity('<?=$sec?>')"><?=$sec?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- vCard fields -->
                <div id="qrVcardFields" style="display:none;margin-bottom:var(--sp-3);">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:5px;">Full Name</label>
                            <div style="position:relative;">
                                <i data-lucide="user" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted);pointer-events:none;"></i>
                                <input id="qrVcName" class="search-input" style="padding-left:36px;" placeholder="Jane Doe" oninput="qrGenerate()">
                            </div>
                        </div>
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:5px;">Phone</label>
                            <div style="position:relative;">
                                <i data-lucide="phone" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted);pointer-events:none;"></i>
                                <input id="qrVcPhone" class="search-input" style="padding-left:36px;" placeholder="+1 555 000 0000" oninput="qrGenerate()">
                            </div>
                        </div>
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:5px;">Email</label>
                            <div style="position:relative;">
                                <i data-lucide="mail" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted);pointer-events:none;"></i>
                                <input id="qrVcEmail" class="search-input" style="padding-left:36px;" placeholder="jane@example.com" oninput="qrGenerate()">
                            </div>
                        </div>
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:5px;">Organization</label>
                            <div style="position:relative;">
                                <i data-lucide="building-2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--muted);pointer-events:none;"></i>
                                <input id="qrVcOrg" class="search-input" style="padding-left:36px;" placeholder="Acme Inc." oninput="qrGenerate()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Design options -->
                <div style="border-top:1.5px solid var(--border);padding-top:var(--sp-3);margin-top:var(--sp-3);">
                    <div class="font-bold mb-3" style="font-size:var(--fs-sm);color:var(--ink);">Design Options</div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <!-- Foreground color -->
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:6px;">Foreground</label>
                            <div style="display:flex;align-items:center;gap:8px;border:2px solid var(--border);border-radius:10px;padding:6px 10px;background:#fff;transition:border-color .2s;" id="qrFgWrap">
                                <div class="color-swatch" id="qrFgSwatch" style="background:#000000;" onclick="document.getElementById('qrFgPicker').click()"></div>
                                <input type="text" id="qrFgHex" value="#000000" maxlength="7" class="search-input" style="border:none!important;box-shadow:none!important;padding:0!important;font-size:13px!important;font-weight:700;text-transform:uppercase;" oninput="qrSyncColor('fg')">
                                <input type="color" id="qrFgPicker" value="#000000" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;" oninput="qrPickerSync('fg')">
                            </div>
                        </div>
                        <!-- Background color -->
                        <div>
                            <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:6px;">Background</label>
                            <div style="display:flex;align-items:center;gap:8px;border:2px solid var(--border);border-radius:10px;padding:6px 10px;background:#fff;transition:border-color .2s;" id="qrBgWrap">
                                <div class="color-swatch" id="qrBgSwatch" style="background:#ffffff;border:1.5px solid #e5e7eb;" onclick="document.getElementById('qrBgPicker').click()"></div>
                                <input type="text" id="qrBgHex" value="#FFFFFF" maxlength="7" class="search-input" style="border:none!important;box-shadow:none!important;padding:0!important;font-size:13px!important;font-weight:700;text-transform:uppercase;" oninput="qrSyncColor('bg')">
                                <input type="color" id="qrBgPicker" value="#ffffff" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;" oninput="qrPickerSync('bg')">
                            </div>
                        </div>
                    </div>
                    <!-- Size -->
                    <div>
                        <label class="font-bold" style="font-size:var(--fs-xs);color:var(--muted);display:block;margin-bottom:6px;">Size: <span id="qrSizeLabel">300</span>px</label>
                        <input type="range" id="qrSizeRange" min="100" max="1200" value="300" step="50" style="width:100%;accent-color:var(--accent);" oninput="document.getElementById('qrSizeLabel').textContent=this.value;qrGenerate()">
                    </div>
                </div>
            </div>

            <!-- RHS: Preview + Download -->
            <div class="glass-card" style="padding:var(--sp-4);display:flex;flex-direction:column;align-items:center;text-align:center;">
                <div class="font-bold mb-3" style="font-size:var(--fs-sm);color:var(--ink);width:100%;text-align:left;">Preview</div>
                <div class="qr-preview-box w-full mb-4" id="qrPreviewBox">
                    <div style="color:var(--muted);text-align:center;">
                        <i data-lucide="qr-code" style="width:48px;height:48px;opacity:.2;margin:0 auto 8px;display:block;"></i>
                        <p style="font-size:var(--fs-sm);">Enter content to generate QR code</p>
                    </div>
                </div>
                <!-- Download buttons -->
                <div id="qrDownloadBtns" style="display:none;width:100%;">
                    <div class="flex gap-2 justify-center flex-wrap">
                        <button onclick="qrDownload('svg')" class="btn-primary btn-success" style="flex:1;min-width:80px;max-width:120px;">
                            <i data-lucide="download" class="icon-sm"></i> SVG
                        </button>
                        <button onclick="qrDownload('png')" class="btn-primary" style="flex:1;min-width:80px;max-width:120px;">
                            <i data-lucide="image" class="icon-sm"></i> PNG
                        </button>
                        <button onclick="qrDownload('jpg')" class="btn-secondary" style="flex:1;min-width:80px;max-width:120px;">
                            <i data-lucide="image" class="icon-sm"></i> JPG
                        </button>
                    </div>
                    <p class="text-center mt-2" style="font-size:var(--fs-xs);color:var(--muted);">Downloads at <span id="qrDlSizeLabel">300</span>px resolution</p>
                </div>
                <canvas id="qrCanvas" style="display:none;position:absolute;left:-9999px;"></canvas>
            </div>
        </div>
    </div><!-- /page-qr -->

</div><!-- /page-wrap -->
