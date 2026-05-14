<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>SEO Auditor Pro</title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%233b5bdb'/><circle cx='14' cy='14' r='7' fill='none' stroke='%23fff' stroke-width='2.5'/><line x1='19.5' y1='19.5' x2='26' y2='26' stroke='%23fff' stroke-width='2.5' stroke-linecap='round'/></svg>">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- ── Side Panel Toggle Button ── -->
<button id="spToggleBtn" onclick="toggleSidePanel()" title="Open menu" aria-label="Open navigation">
    <i data-lucide="menu" style="width:18px;height:18px;"></i>
</button>

<!-- ── Side Panel Overlay ── -->
<div id="sidePanelOverlay" onclick="closeSidePanel()"></div>

<!-- ── Side Panel ── -->
<nav id="sidePanel" aria-label="Navigation">
    <div class="sp-header">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <div style="width:30px;height:30px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="zap" style="width:16px;height:16px;color:#fff;"></i>
                </div>
                <span style="color:#fff;font-weight:800;font-size:15px;letter-spacing:-.01em;">SEO Auditor Pro</span>
            </div>
            <button onclick="closeSidePanel()" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="x" style="width:14px;height:14px;color:#fff;"></i>
            </button>
        </div>
        <p style="color:rgba(255,255,255,.7);font-size:11px;margin-top:4px;">Technical SEO Analyzer</p>
    </div>
    <div class="sp-nav">
        <div class="sp-label">Audit</div>
        <a class="sp-item" href="?page=audit" data-page="audit" onclick="return navigateTo('audit')">
            <i data-lucide="search"></i> Analyze Site
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">DNS &amp; Network</div>
        <a class="sp-item" href="?page=dns" data-page="dns" onclick="return navigateTo('dns')">
            <i data-lucide="terminal"></i> DNS Lookup
        </a>
        <a class="sp-item" href="?page=mx" data-page="mx" onclick="return navigateTo('mx')">
            <i data-lucide="mail-check"></i> Check MX / Email
        </a>
        <a class="sp-item" href="?page=whois" data-page="whois" onclick="return navigateTo('whois')">
            <i data-lucide="id-card"></i> WHOIS Lookup
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Browser</div>
        <a class="sp-item" href="?page=browser" data-page="browser" onclick="return navigateTo('browser')">
            <i data-lucide="monitor"></i> Browser &amp; UA
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Network</div>
        <a class="sp-item" href="?page=ip" data-page="ip" onclick="return navigateTo('ip')">
            <i data-lucide="wifi"></i> IP Address Info
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Tools</div>
        <a class="sp-item" href="?page=downloader" data-page="downloader" onclick="return navigateTo('downloader')">
            <i data-lucide="download"></i> Website Downloader
        </a>
        <a class="sp-item" href="?page=caseconvert" data-page="caseconvert" onclick="return navigateTo('caseconvert')">
            <i data-lucide="case-sensitive"></i> Text Case Converter
        </a>
        <a class="sp-item" href="?page=qr" data-page="qr" onclick="return navigateTo('qr')">
            <i data-lucide="qr-code"></i> QR Code Generator
        </a>
        <a class="sp-item" href="?page=codeformat" data-page="codeformat" onclick="return navigateTo('codeformat')">
            <i data-lucide="code-2"></i> Code Formatter
        </a>
        <a class="sp-item" href="?page=numgen" data-page="numgen" onclick="return navigateTo('numgen')">
            <i data-lucide="hash"></i> Number Generator
        </a>
        <a class="sp-item" href="?page=colorgen" data-page="colorgen" onclick="return navigateTo('colorgen')">
            <i data-lucide="palette"></i> Colour Generator
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Reference</div>
        <a class="sp-item" href="https://toolbox.googleapps.com/apps/main/" target="_blank" rel="noopener">
            <i data-lucide="external-link"></i> Google Toolbox
        </a>
    </div>
</nav>

<div class="page-wrap top max-w-6xl mx-auto" style="padding:var(--sp-4) clamp(14px,4vw,24px) var(--sp-5)">

    <!-- BREADCRUMB BAR -->
    <div id="pageBreadcrumb">
        <i data-lucide="home" style="width:13px;height:13px;color:var(--muted);"></i>
        <span class="bc-sep">SEO Auditor Pro</span>
        <span class="bc-sep">›</span>
        <span id="bcPageName" class="bc-page">Analyze Site</span>
    </div>

<div class="page-wrap max-w-6xl mx-auto">

