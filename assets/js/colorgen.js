// ═══════════════════════════════════════
//  COLOUR CODE GENERATOR
// ═══════════════════════════════════════

// Internal state: always work in [r, g, b, a] (0-255, 0-255, 0-255, 0-100)
let _cg = { r: 59, g: 130, b: 246, a: 100 };
let _cgHue = 213;     // current hue for the picker canvas
let _cgHistory = [];
let _cgDragging = false;

// ── Init ────────────────────────────────────────────────────────
function cgInit() {
    cgDrawCanvas();
    cgSyncAll();
}

// ── Canvas gradient picker ───────────────────────────────────────
function cgDrawCanvas() {
    const canvas = document.getElementById('cgCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;

    // Base: white → pure hue
    const gradH = ctx.createLinearGradient(0, 0, W, 0);
    gradH.addColorStop(0, '#ffffff');
    gradH.addColorStop(1, `hsl(${_cgHue}, 100%, 50%)`);
    ctx.fillStyle = gradH;
    ctx.fillRect(0, 0, W, H);

    // Overlay: transparent → black
    const gradV = ctx.createLinearGradient(0, 0, 0, H);
    gradV.addColorStop(0, 'rgba(0,0,0,0)');
    gradV.addColorStop(1, 'rgba(0,0,0,1)');
    ctx.fillStyle = gradV;
    ctx.fillRect(0, 0, W, H);
}

function cgCanvasDown(e) {
    _cgDragging = true;
    cgCanvasPick(e);

    const move = ev => { if (_cgDragging) cgCanvasPick(ev); };
    const up = () => { _cgDragging = false; document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); };
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup', up);
}

function cgCanvasTouchStart(e) {
    e.preventDefault();
    cgCanvasPickTouch(e.touches[0]);

    const move = ev => { ev.preventDefault(); cgCanvasPickTouch(ev.touches[0]); };
    const up = () => { document.removeEventListener('touchmove', move); document.removeEventListener('touchend', up); };
    document.addEventListener('touchmove', move, { passive: false });
    document.addEventListener('touchend', up);
}

function cgCanvasPickTouch(touch) {
    const canvas = document.getElementById('cgCanvas');
    const rect = canvas.getBoundingClientRect();
    const x = Math.max(0, Math.min(touch.clientX - rect.left, rect.width));
    const y = Math.max(0, Math.min(touch.clientY - rect.top, rect.height));
    cgPickFromCanvas(x, y, rect.width, rect.height);
}

function cgCanvasPick(e) {
    const canvas = document.getElementById('cgCanvas');
    const rect = canvas.getBoundingClientRect();
    const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
    const y = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
    cgPickFromCanvas(x, y, rect.width, rect.height);
}

function cgPickFromCanvas(x, y, W, H) {
    // S from x, B from y (inverted)
    const s = x / W;          // saturation 0-1
    const b = 1 - (y / H);   // brightness 0-1

    const [r, g, b_] = cgHsvToRgb(_cgHue, s, b);
    _cg.r = r; _cg.g = g; _cg.b = b_;

    // Move cursor
    const cursor = document.getElementById('cgCursor');
    const canvas = document.getElementById('cgCanvas');
    const rect = canvas.getBoundingClientRect();
    cursor.style.left = ((x / rect.width) * 100) + '%';
    cursor.style.top  = ((y / rect.height) * 100) + '%';

    cgSyncAll();
}

function cgMoveCursorToColor() {
    // Convert current RGB to HSV, then position cursor
    const [h, s, v] = cgRgbToHsv(_cg.r, _cg.g, _cg.b);
    const cursor = document.getElementById('cgCursor');
    if (!cursor) return;
    cursor.style.left = (s * 100) + '%';
    cursor.style.top  = ((1 - v) * 100) + '%';
}

// ── Slider handlers ─────────────────────────────────────────────
function cgHueChanged() {
    _cgHue = parseInt(document.getElementById('cgHueSlider').value);
    cgDrawCanvas();
    // Recompute RGB for new hue, keeping same S and V
    const [, s, v] = cgRgbToHsv(_cg.r, _cg.g, _cg.b);
    const [r, g, b] = cgHsvToRgb(_cgHue, s, v);
    _cg.r = r; _cg.g = g; _cg.b = b;
    cgSyncAll(false);
}

function cgAlphaChanged() {
    _cg.a = parseInt(document.getElementById('cgAlphaSlider').value);
    cgSyncAll(false);
}

// ── Input handlers (each syncs everything else) ──────────────────
function cgNativePickerChanged() {
    const val = document.getElementById('cgNativePicker').value;
    const r = parseInt(val.slice(1, 3), 16);
    const g = parseInt(val.slice(3, 5), 16);
    const b = parseInt(val.slice(5, 7), 16);
    _cg.r = r; _cg.g = g; _cg.b = b;
    _cgHue = cgRgbToHsv(r, g, b)[0];
    cgDrawCanvas();
    cgSyncAll(true, 'native');
}

function cgHexInputChanged() {
    let val = document.getElementById('cgHexInput').value.trim();
    if (!val.startsWith('#')) val = '#' + val;
    // Support 3, 6, 8 char hex
    const hex3 = val.match(/^#([0-9a-f]{3})$/i);
    const hex6 = val.match(/^#([0-9a-f]{6})$/i);
    const hex8 = val.match(/^#([0-9a-f]{8})$/i);

    let r, g, b, a;
    if (hex3) {
        const [, h] = hex3;
        r = parseInt(h[0]+h[0], 16); g = parseInt(h[1]+h[1], 16); b = parseInt(h[2]+h[2], 16); a = 100;
    } else if (hex6) {
        const [, h] = hex6;
        r = parseInt(h.slice(0,2), 16); g = parseInt(h.slice(2,4), 16); b = parseInt(h.slice(4,6), 16); a = 100;
    } else if (hex8) {
        const [, h] = hex8;
        r = parseInt(h.slice(0,2), 16); g = parseInt(h.slice(2,4), 16); b = parseInt(h.slice(4,6), 16);
        a = Math.round(parseInt(h.slice(6,8), 16) / 255 * 100);
    } else {
        document.getElementById('cgHexInput').style.borderColor = '#fca5a5';
        return;
    }
    document.getElementById('cgHexInput').style.borderColor = 'var(--border)';
    _cg = { r, g, b, a };
    _cgHue = cgRgbToHsv(r, g, b)[0];
    cgDrawCanvas();
    cgSyncAll(true, 'hex');
}

function cgRgbChanged() {
    const r = Math.max(0, Math.min(255, parseInt(document.getElementById('cgR').value) || 0));
    const g = Math.max(0, Math.min(255, parseInt(document.getElementById('cgG').value) || 0));
    const b = Math.max(0, Math.min(255, parseInt(document.getElementById('cgB').value) || 0));
    const a = Math.max(0, Math.min(100, parseInt(document.getElementById('cgA').value) ?? 100));
    _cg = { r, g, b, a };
    _cgHue = cgRgbToHsv(r, g, b)[0];
    cgDrawCanvas();
    cgSyncAll(true, 'rgb');
}

function cgHslChanged() {
    const h = Math.max(0, Math.min(360, parseInt(document.getElementById('cgH').value) || 0));
    const s = Math.max(0, Math.min(100, parseInt(document.getElementById('cgS').value) || 0));
    const l = Math.max(0, Math.min(100, parseInt(document.getElementById('cgL').value) || 0));
    const [r, g, b] = cgHslToRgb(h, s / 100, l / 100);
    _cg.r = r; _cg.g = g; _cg.b = b;
    _cgHue = h;
    cgDrawCanvas();
    cgSyncAll(true, 'hsl');
}

function cgHsbChanged() {
    const val = document.getElementById('cgHsbDisplay').value;
    const m = val.match(/(\d+)[°,\s]+(\d+)[%,\s]+(\d+)/);
    if (!m) return;
    const h = Math.min(360, parseInt(m[1]));
    const s = Math.min(100, parseInt(m[2])) / 100;
    const v = Math.min(100, parseInt(m[3])) / 100;
    const [r, g, b] = cgHsvToRgb(h, s, v);
    _cg.r = r; _cg.g = g; _cg.b = b;
    _cgHue = h;
    cgDrawCanvas();
    cgSyncAll(true, 'hsb');
}

function cgCmykChanged() {
    const val = document.getElementById('cgCmykDisplay').value;
    const m = val.match(/([\d.]+)[%,\s]+([\d.]+)[%,\s]+([\d.]+)[%,\s]+([\d.]+)/);
    if (!m) return;
    const c = parseFloat(m[1]) / 100, my = parseFloat(m[2]) / 100,
          y  = parseFloat(m[3]) / 100, k  = parseFloat(m[4]) / 100;
    const r = Math.round(255 * (1 - c) * (1 - k));
    const g = Math.round(255 * (1 - my) * (1 - k));
    const b = Math.round(255 * (1 - y) * (1 - k));
    _cg.r = r; _cg.g = g; _cg.b = b;
    _cgHue = cgRgbToHsv(r, g, b)[0];
    cgDrawCanvas();
    cgSyncAll(true, 'cmyk');
}

// ── Master sync: update all fields from _cg ──────────────────────
function cgSyncAll(moveCursor = true, skip = null) {
    const { r, g, b, a } = _cg;

    // HEX
    const hexStr = '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
    const hexFull = a < 100
        ? hexStr + Math.round(a / 100 * 255).toString(16).padStart(2, '0').toUpperCase()
        : hexStr;

    if (skip !== 'hex') document.getElementById('cgHexInput').value = hexFull;

    // Native picker (no alpha)
    if (skip !== 'native') {
        document.getElementById('cgNativePicker').value = hexStr.toLowerCase();
        document.getElementById('cgHexSwatch').style.background = hexStr;
    }

    // RGB
    if (skip !== 'rgb') {
        document.getElementById('cgR').value = r;
        document.getElementById('cgG').value = g;
        document.getElementById('cgB').value = b;
        document.getElementById('cgA').value = a;
    }

    // HSL
    const [h, s, l] = cgRgbToHsl(r, g, b);
    if (skip !== 'hsl') {
        document.getElementById('cgH').value = Math.round(h);
        document.getElementById('cgS').value = Math.round(s * 100);
        document.getElementById('cgL').value = Math.round(l * 100);
    }

    // HSB
    const [hh, sv, bv] = cgRgbToHsv(r, g, b);
    if (skip !== 'hsb') {
        document.getElementById('cgHsbDisplay').value =
            `${Math.round(hh)}°, ${Math.round(sv * 100)}%, ${Math.round(bv * 100)}%`;
    }

    // CMYK
    const [c, m, yy, k] = cgRgbToCmyk(r, g, b);
    if (skip !== 'cmyk') {
        document.getElementById('cgCmykDisplay').value =
            `${Math.round(c * 100)}%, ${Math.round(m * 100)}%, ${Math.round(yy * 100)}%, ${Math.round(k * 100)}%`;
    }

    // CSS var
    document.getElementById('cgCssVar').value =
        `--color: ${hexStr.toLowerCase()};`;

    // Alpha + hue sliders
    document.getElementById('cgHueSlider').value = Math.round(hh);
    document.getElementById('cgAlphaSlider').value = a;

    // Alpha slider background
    const alphaSlider = document.getElementById('cgAlphaSlider');
    alphaSlider.style.background = `linear-gradient(to right, transparent, ${hexStr})`;

    // Move canvas cursor
    if (moveCursor) cgMoveCursorToColor();

    // Preview
    cgUpdatePreview(hexStr, hexFull, r, g, b, a);
    cgUpdatePalettes(hh, s, l);
    cgUpdateCssExport(hexStr, hexFull, r, g, b, a, h, s, l, c, m, yy, k);
}

// ── Preview ──────────────────────────────────────────────────────
function cgUpdatePreview(hex, hexFull, r, g, b, a) {
    const alpha = a / 100;
    const rgba = `rgba(${r},${g},${b},${alpha})`;

    document.getElementById('cgPreviewBlock').style.background = rgba;
    document.getElementById('cgPreviewHex').textContent = hexFull;

    // Text colour — light or dark?
    const lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    document.getElementById('cgPreviewHex').style.color = lum > 0.55 ? '#111' : '#fff';
    document.getElementById('cgPreviewHex').style.textShadow = lum > 0.55
        ? '0 1px 2px rgba(255,255,255,.4)' : '0 1px 3px rgba(0,0,0,.4)';

    document.getElementById('cgPreviewDark').style.background = rgba;
    document.getElementById('cgPreviewLight').style.background = rgba;

    // Contrast ratio vs white
    const lumNorm = lum;
    const lumWhite = 1;
    const ratio = (lumWhite + 0.05) / (lumNorm + 0.05);
    const ratioStr = ratio.toFixed(2) + ':1';
    document.getElementById('cgContrastRatio').textContent = ratioStr;

    const pct = Math.min(100, (ratio / 21) * 100);
    document.getElementById('cgContrastFill').style.width = pct + '%';
    document.getElementById('cgContrastFill').style.background =
        ratio >= 7 ? 'var(--success)' : ratio >= 4.5 ? 'var(--accent)' : 'var(--warning)';

    let label = '';
    if (ratio >= 7)   label = '<span style="color:var(--success);">AAA ✓</span>';
    else if (ratio >= 4.5) label = '<span style="color:var(--accent);">AA ✓</span>';
    else if (ratio >= 3)   label = '<span style="color:var(--warning);">AA Large</span>';
    else                   label = '<span style="color:var(--danger);">Fail ✗</span>';
    document.getElementById('cgContrastLabel').innerHTML = label;
}

// ── Palette generators ───────────────────────────────────────────
function cgUpdatePalettes(h, s, l) {
    const steps = 8;

    // Tints: increase lightness
    const tints = Array.from({ length: steps }, (_, i) => {
        const tl = Math.min(1, l + (i + 1) * (1 - l) / (steps + 1));
        return cgHslToHex(h, s, tl);
    });
    cgRenderSwatches('cgTints', tints);

    // Shades: decrease lightness
    const shades = Array.from({ length: steps }, (_, i) => {
        const sl = Math.max(0, l - (i + 1) * l / (steps + 1));
        return cgHslToHex(h, s, sl);
    });
    cgRenderSwatches('cgShades', shades);

    // Complementary
    const comp = [
        cgHslToHex(h, s, l),
        cgHslToHex((h + 180) % 360, s, l),
        cgHslToHex((h + 30) % 360, s * 0.8, l * 1.1 > 1 ? 1 : l * 1.1),
        cgHslToHex((h + 210) % 360, s * 0.8, l * 1.1 > 1 ? 1 : l * 1.1),
    ];
    cgRenderSwatches('cgComplementary', comp);

    // Analogous
    const analog = [-40, -20, 0, 20, 40].map(offset => cgHslToHex((h + offset + 360) % 360, s, l));
    cgRenderSwatches('cgAnalogous', analog);

    // Triadic
    const triad = [0, 120, 240].map(offset => cgHslToHex((h + offset) % 360, s, l));
    cgRenderSwatches('cgTriadic', triad);
}

function cgRenderSwatches(containerId, colors) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = colors.map(hex => `
        <div title="${hex}" onclick="cgLoadHex('${hex}')"
            style="flex:1;height:36px;background:${hex};border-radius:6px;cursor:pointer;
                   border:2px solid rgba(0,0,0,.08);transition:transform .15s;position:relative;"
            onmouseover="this.style.transform='scaleY(1.15)';this.style.zIndex=2"
            onmouseout="this.style.transform='';this.style.zIndex=''">
        </div>`).join('');
}

function cgLoadHex(hex) {
    document.getElementById('cgHexInput').value = hex;
    cgHexInputChanged();
}

// ── CSS export ───────────────────────────────────────────────────
function cgUpdateCssExport(hex, hexFull, r, g, b, a, h, s, l, c, m, y, k) {
    const alpha = (a / 100).toFixed(2);
    const hue = Math.round(h), sat = Math.round(s * 100), lig = Math.round(l * 100);
    const el = document.getElementById('cgCssExport');
    if (!el) return;
    el.textContent = [
        `/* Colour: ${hexFull} */`,
        `:root {`,
        `  --color-hex:    ${hex.toLowerCase()};`,
        `  --color-rgb:    rgb(${r}, ${g}, ${b});`,
        `  --color-rgba:   rgba(${r}, ${g}, ${b}, ${alpha});`,
        `  --color-hsl:    hsl(${hue}, ${sat}%, ${lig}%);`,
        `  --color-hsla:   hsla(${hue}, ${sat}%, ${lig}%, ${alpha});`,
        `}`,
    ].join('\n');
}

// ── Saved history ────────────────────────────────────────────────
function cgAddToHistory() {
    const { r, g, b, a } = _cg;
    const hex = '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
    if (_cgHistory.includes(hex)) return;
    _cgHistory.unshift(hex);
    if (_cgHistory.length > 24) _cgHistory.pop();
    cgRenderHistory();
}

function cgRenderHistory() {
    const el = document.getElementById('cgHistory');
    if (!el) return;
    if (!_cgHistory.length) {
        el.innerHTML = '<span class="text-xs" style="color:var(--muted);">No saved colours yet. Click "Save" to bookmark a colour.</span>';
        return;
    }
    el.innerHTML = _cgHistory.map(hex => `
        <div title="${hex}" onclick="cgLoadHex('${hex}')"
            style="width:32px;height:32px;border-radius:8px;background:${hex};cursor:pointer;
                   border:2px solid rgba(0,0,0,.1);transition:transform .15s;flex-shrink:0;"
            onmouseover="this.style.transform='scale(1.15)'"
            onmouseout="this.style.transform=''"></div>
    `).join('');
}

function cgClearHistory() {
    _cgHistory = [];
    cgRenderHistory();
}

// ── Copy helpers ─────────────────────────────────────────────────
function cgRandom() {
    _cg.r = Math.floor(Math.random() * 256);
    _cg.g = Math.floor(Math.random() * 256);
    _cg.b = Math.floor(Math.random() * 256);
    _cg.a = 100;
    _cgHue = cgRgbToHsv(_cg.r, _cg.g, _cg.b)[0];
    cgDrawCanvas();
    cgSyncAll();
}

function cgCopyAll() {
    const { r, g, b, a } = _cg;
    const hex = '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
    const [h, s, l] = cgRgbToHsl(r, g, b);
    const text = [
        `HEX: ${hex}`,
        `RGB: rgb(${r}, ${g}, ${b})`,
        `HSL: hsl(${Math.round(h)}, ${Math.round(s * 100)}%, ${Math.round(l * 100)}%)`,
        `Alpha: ${a}%`,
    ].join('\n');
    const btn = document.querySelector('[onclick="cgCopyAll()"]');
    copyText(text, btn);
}

function cgCopyCss() {
    const text = document.getElementById('cgCssExport').textContent;
    const btn = document.querySelector('[onclick="cgCopyCss()"]');
    copyText(text, btn);
}

// ── Colour conversion utilities ──────────────────────────────────
function cgHsvToRgb(h, s, v) {
    const c = v * s, x = c * (1 - Math.abs((h / 60) % 2 - 1)), m = v - c;
    let r, g, b;
    if (h < 60)       { r=c; g=x; b=0; }
    else if (h < 120) { r=x; g=c; b=0; }
    else if (h < 180) { r=0; g=c; b=x; }
    else if (h < 240) { r=0; g=x; b=c; }
    else if (h < 300) { r=x; g=0; b=c; }
    else              { r=c; g=0; b=x; }
    return [Math.round((r+m)*255), Math.round((g+m)*255), Math.round((b+m)*255)];
}

function cgRgbToHsv(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    let h = 0;
    if (d !== 0) {
        switch (max) {
            case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
            case g: h = ((b - r) / d + 2) / 6; break;
            case b: h = ((r - g) / d + 4) / 6; break;
        }
    }
    return [Math.round(h * 360), max === 0 ? 0 : d / max, max];
}

function cgRgbToHsl(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    const l = (max + min) / 2;
    if (max === min) return [0, 0, l];
    const d = max - min;
    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    let h;
    switch (max) {
        case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
        case g: h = ((b - r) / d + 2) / 6; break;
        case b: h = ((r - g) / d + 4) / 6; break;
    }
    return [h * 360, s, l];
}

function cgHslToRgb(h, s, l) {
    if (s === 0) { const v = Math.round(l * 255); return [v, v, v]; }
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    const hk = h / 360;
    const hue2rgb = (t) => {
        if (t < 0) t += 1; if (t > 1) t -= 1;
        if (t < 1/6) return p + (q - p) * 6 * t;
        if (t < 1/2) return q;
        if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
        return p;
    };
    return [Math.round(hue2rgb(hk + 1/3) * 255), Math.round(hue2rgb(hk) * 255), Math.round(hue2rgb(hk - 1/3) * 255)];
}

function cgRgbToCmyk(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    const k = 1 - Math.max(r, g, b);
    if (k === 1) return [0, 0, 0, 1];
    return [(1 - r - k) / (1 - k), (1 - g - k) / (1 - k), (1 - b - k) / (1 - k), k];
}

function cgHslToHex(h, s, l) {
    const [r, g, b] = cgHslToRgb(h, s, l);
    return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('');
}

// ── Responsive grid ──────────────────────────────────────────────
(function cgResponsive() {
    const check = () => {
        const grid = document.getElementById('cgGrid');
        if (grid) grid.style.gridTemplateColumns = window.innerWidth < 640 ? '1fr' : '1fr 1fr';
    };
    window.addEventListener('resize', check);
    document.addEventListener('DOMContentLoaded', () => {
        check();
        if (document.getElementById('cgCanvas')) cgInit();
    });
})();
