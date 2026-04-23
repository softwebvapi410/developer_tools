// QR Code Generator page

// ══════════════════════════════════════════════════════════════
//  QR CODE GENERATOR (client-side using qrcodejs)
// ══════════════════════════════════════════════════════════════
let _qrType = 'url', _qrSecurity = 'WPA', _qrInstance = null, _qrGenerated = false;

// Lazy-load qrcode-generator (exposes module matrix — needed for clean SVG output)
// Also lazy-load qrcodejs for canvas preview
function qrLoadLib() {
    return new Promise((resolve) => {
        if (window.qrcode && window.QRCode) { resolve(); return; }
        var pending = 2;
        var done = function() { if (--pending === 0) resolve(); };
        if (!window.qrcode) {
            var s1 = document.createElement('script');
            s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js';
            s1.onload = done;
            document.head.appendChild(s1);
        } else { done(); }
        if (!window.QRCode) {
            var s2 = document.createElement('script');
            s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
            s2.onload = done;
            document.head.appendChild(s2);
        } else { done(); }
    });
}

function qrSetType(t) {
    _qrType = t;
    document.querySelectorAll('[id^="qrType_"]').forEach(b => b.classList.remove('active'));
    const el = document.getElementById('qrType_' + t);
    if (el) el.classList.add('active');
    // Toggle special fields
    document.getElementById('qrWifiFields').style.display = t === 'wifi' ? '' : 'none';
    document.getElementById('qrVcardFields').style.display = t === 'vcard' ? '' : 'none';
    const inp = document.getElementById('qrInput');
    const placeholders = {
        url: 'https://yourwebsite.com',
        text: 'Type any text here…',
        email: 'mailto:example@email.com',
        phone: 'tel:+1555000000',
        wifi: 'Fill the Wi-Fi fields below',
        vcard: 'Fill the vCard fields below',
    };
    inp.placeholder = placeholders[t] || 'Enter content…';
    qrGenerate();
}

function qrSetSecurity(s) {
    _qrSecurity = s;
    document.querySelectorAll('[id^="qrSec_"]').forEach(b => b.classList.remove('active'));
    const el = document.getElementById('qrSec_' + s);
    if (el) el.classList.add('active');
    qrGenerate();
}

function qrBuildData() {
    switch (_qrType) {
        case 'wifi': {
            const ssid = document.getElementById('qrWifiSSID').value;
            const pass = document.getElementById('qrWifiPass').value;
            if (!ssid) return '';
            return `WIFI:T:${_qrSecurity};S:${ssid};P:${pass};;`;
        }
        case 'vcard': {
            const name  = document.getElementById('qrVcName').value;
            const phone = document.getElementById('qrVcPhone').value;
            const email = document.getElementById('qrVcEmail').value;
            const org   = document.getElementById('qrVcOrg').value;
            if (!name && !phone && !email) return '';
            return `BEGIN:VCARD\nVERSION:3.0\nFN:${name}\nTEL:${phone}\nEMAIL:${email}\nORG:${org}\nEND:VCARD`;
        }
        default: return document.getElementById('qrInput').value.trim();
    }
}

async function qrGenerate() {
    const data = qrBuildData();
    const previewBox = document.getElementById('qrPreviewBox');
    if (!data) {
        previewBox.innerHTML = `<div style="color:var(--muted);text-align:center;"><i data-lucide="qr-code" style="width:48px;height:48px;opacity:.2;margin:0 auto 8px;display:block;"></i><p style="font-size:var(--fs-sm);">Enter content to generate QR code</p></div>`;
        document.getElementById('qrDownloadBtns').style.display = 'none';
        _qrGenerated = false;
        refreshIcons();
        return;
    }
    await qrLoadLib();

    const fg = document.getElementById('qrFgHex').value || '#000000';
    const bg = document.getElementById('qrBgHex').value || '#ffffff';
    const size = parseInt(document.getElementById('qrSizeRange').value) || 300;
    const displaySize = Math.min(size, 280);

    previewBox.innerHTML = '<div id="qrRender" style="background:' + escapeHtml(bg) + ';display:inline-block;padding:10px;border-radius:8px;"></div>';

    try {
        new QRCode(document.getElementById('qrRender'), {
            text: data,
            width: displaySize,
            height: displaySize,
            colorDark: fg,
            colorLight: bg,
            correctLevel: QRCode.CorrectLevel.H,
        });
        _qrGenerated = true;
        document.getElementById('qrDownloadBtns').style.display = '';
        document.getElementById('qrDlSizeLabel').textContent = size;
    } catch(e) {
        previewBox.innerHTML = `<p style="color:var(--danger);font-size:var(--fs-sm);">Error generating QR code. Try shorter content.</p>`;
        _qrGenerated = false;
        document.getElementById('qrDownloadBtns').style.display = 'none';
    }
    refreshIcons();
}

function qrSyncColor(which) {
    const hexEl = document.getElementById(which === 'fg' ? 'qrFgHex' : 'qrBgHex');
    const swatchEl = document.getElementById(which === 'fg' ? 'qrFgSwatch' : 'qrBgSwatch');
    const pickerEl = document.getElementById(which === 'fg' ? 'qrFgPicker' : 'qrBgPicker');
    let val = hexEl.value;
    if (/^#?[0-9a-fA-F]{6}$/.test(val)) {
        if (!val.startsWith('#')) val = '#' + val;
        hexEl.value = val.toUpperCase();
        swatchEl.style.background = val;
        pickerEl.value = val;
        qrGenerate();
    }
}

function qrPickerSync(which) {
    const pickerEl = document.getElementById(which === 'fg' ? 'qrFgPicker' : 'qrBgPicker');
    const hexEl = document.getElementById(which === 'fg' ? 'qrFgHex' : 'qrBgHex');
    const swatchEl = document.getElementById(which === 'fg' ? 'qrFgSwatch' : 'qrBgSwatch');
    hexEl.value = pickerEl.value.toUpperCase();
    swatchEl.style.background = pickerEl.value;
    qrGenerate();
}

async function qrDownload(format) {
    if (!_qrGenerated) return;
    const size = parseInt(document.getElementById('qrSizeRange').value) || 300;
    const bg   = document.getElementById('qrBgHex').value || '#ffffff';
    const fg   = document.getElementById('qrFgHex').value || '#000000';
    const data = qrBuildData();
    if (!data) return;

    await qrLoadLib();

    // Render off-screen at full resolution
    const offDiv = document.createElement('div');
    offDiv.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
    document.body.appendChild(offDiv);

    new QRCode(offDiv, {
        text: data, width: size, height: size,
        colorDark: fg, colorLight: bg,
        correctLevel: QRCode.CorrectLevel.H,
    });

    await new Promise(r => setTimeout(r, 120));

    const canvas = offDiv.querySelector('canvas');


    if (format === 'svg') {
        // Use qrcode-generator matrix directly — produces a single <path>, tiny file (~2-5 KB)
        var typeNumber = 0; // auto-detect
        var qr = qrcode(typeNumber, 'H');
        qr.addData(data);
        qr.make();

        var count = qr.getModuleCount();
        var pathD = '';
        for (var row = 0; row < count; row++) {
            for (var col = 0; col < count; col++) {
                if (qr.isDark(row, col)) {
                    pathD += 'M' + col + ',' + row + 'h1v1h-1z';
                }
            }
        }

        var svgStr = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + count + ' ' + count + '" width="300" height="300" shape-rendering="crispEdges">'
                   + '<rect width="' + count + '" height="' + count + '" fill="' + bg + '"/>'
                   + '<path fill="' + fg + '" d="' + pathD + '"/>'
                   + '</svg>';

        var blob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
        var url  = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.download = 'qrcode.svg';
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        document.body.removeChild(offDiv);
        return;
    }

    // PNG / JPG
    if (canvas) {
        const link = document.createElement('a');
        link.download = format === 'jpg' ? 'qrcode.jpg' : 'qrcode.png';
        const mime   = format === 'jpg' ? 'image/jpeg' : 'image/png';
        link.href    = canvas.toDataURL(mime, 0.92);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    document.body.removeChild(offDiv);
}

// Init QR type on page load — merged into main DOMContentLoaded above
