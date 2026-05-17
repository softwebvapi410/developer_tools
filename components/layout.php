<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">

<!-- ── SEO Meta Tags ── -->
<title>DEV Toolz Pro — Free SEO Tools, DNS Lookup, WHOIS, QR Code Generator &amp; More</title>
<meta name="description" content="Free suite of professional web tools: technical SEO auditor, DNS record lookup, WHOIS domain search, MX email check, IP geolocation, QR code generator, text case converter, code formatter, colour picker, and more. No login required.">
<meta name="keywords" content="SEO auditor, DNS lookup, WHOIS lookup, QR code generator, text case converter, code formatter, colour picker, IP address lookup, MX record check, website tools, free SEO tools">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="author" content="DEV Toolz Pro">
<link rel="canonical" href="https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/">

<!-- ── Open Graph ── -->
<meta property="og:type" content="website">
<meta property="og:title" content="DEV Toolz Pro — Free SEO &amp; Developer Tools">
<meta property="og:description" content="Free, instant web tools: SEO audit, DNS lookup, WHOIS, QR codes, text conversion, code formatting and more. No login needed.">
<meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/">
<meta property="og:site_name" content="DEV Toolz Pro">
<meta property="og:locale" content="en_US">

<!-- ── Twitter Card ── -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="DEV Toolz Pro — Free SEO &amp; Developer Tools">
<meta name="twitter:description" content="Free, instant web tools: SEO audit, DNS lookup, WHOIS, QR codes, text conversion, code formatting and more.">

<!-- ── Schema.org WebSite ── -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "DEV Toolz Pro",
  "description": "Free suite of professional SEO and developer tools including technical SEO auditor, DNS lookup, WHOIS, MX check, IP info, QR code generator, text case converter, code formatter, and colour picker.",
  "url": "https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=audit&url={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  },
  "publisher": {
    "@type": "Organization",
    "name": "DEV Toolz Pro",
    "url": "https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/"
  }
}
</script>

<!-- ── Schema.org BreadcrumbList ── -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "DEV Toolz Pro Tools",
  "description": "Complete list of free SEO and developer tools",
  "itemListElement": [
    {"@type":"ListItem","position":1,"name":"Technical SEO Analyzer","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=audit"},
    {"@type":"ListItem","position":2,"name":"DNS Record Lookup","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=dns"},
    {"@type":"ListItem","position":3,"name":"MX Email Check","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=mx"},
    {"@type":"ListItem","position":4,"name":"WHOIS Domain Lookup","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=whois"},
    {"@type":"ListItem","position":5,"name":"IP Address Info","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=ip"},
    {"@type":"ListItem","position":6,"name":"QR Code Generator","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=qr"},
    {"@type":"ListItem","position":7,"name":"Text Case Converter","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=caseconvert"},
    {"@type":"ListItem","position":8,"name":"Code Formatter","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=codeformat"},
    {"@type":"ListItem","position":9,"name":"Number Generator","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=numgen"},
    {"@type":"ListItem","position":10,"name":"Colour Code Generator","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=colorgen"},
    {"@type":"ListItem","position":11,"name":"Browser Info","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=browser"},
    {"@type":"ListItem","position":12,"name":"Website Downloader","url":"https://<?= $_SERVER['HTTP_HOST'] ?? 'seoauditorpro.com' ?>/?page=downloader"}
  ]
}
</script>

<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%233b5bdb'/><circle cx='14' cy='14' r='7' fill='none' stroke='%23fff' stroke-width='2.5'/><line x1='19.5' y1='19.5' x2='26' y2='26' stroke='%23fff' stroke-width='2.5' stroke-linecap='round'/></svg>">

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/css/app.css">

<!-- Google AdSense -->
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5885950581818992" crossorigin="anonymous"></script>
<meta name="google-adsense-account" content="ca-pub-5885950581818992">
</head>
<body>

<!-- ── Side Panel Toggle Button ── -->
<button id="spToggleBtn" onclick="toggleSidePanel()" title="Open menu" aria-label="Open navigation">
    <i data-lucide="menu" style="width:18px;height:18px;"></i>
</button>

<!-- ── Side Panel Overlay ── -->
<div id="sidePanelOverlay" onclick="closeSidePanel()"></div>

<!-- ── Side Panel ── -->
<nav id="sidePanel" aria-label="Main navigation">
    <div class="sp-header">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <div style="width:30px;height:30px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="zap" style="width:16px;height:16px;color:#fff;"></i>
                </div>
                <span style="color:#fff;font-weight:800;font-size:15px;letter-spacing:-.01em;">DEV Toolz Pro</span>
            </div>
            <button onclick="closeSidePanel()" aria-label="Close menu" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="x" style="width:14px;height:14px;color:#fff;"></i>
            </button>
        </div>
        <p style="color:rgba(255,255,255,.7);font-size:11px;margin-top:4px;">Free SEO &amp; Developer Tools</p>
    </div>
    <div class="sp-nav">
        <div class="sp-label">Home</div>
        <a class="sp-item" href="/" data-page="home" onclick="return navigateTo('home')">
            <i data-lucide="home"></i> Home &amp; All Tools
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">SEO &amp; Audit</div>
        <a class="sp-item" href="?page=audit" data-page="audit" onclick="return navigateTo('audit')">
            <i data-lucide="search"></i> Technical SEO Analyzer
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
        <a class="sp-item" href="?page=ip" data-page="ip" onclick="return navigateTo('ip')">
            <i data-lucide="wifi"></i> IP Address Info
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Browser &amp; Device</div>
        <a class="sp-item" href="?page=browser" data-page="browser" onclick="return navigateTo('browser')">
            <i data-lucide="monitor"></i> Browser &amp; UA
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Developer Tools</div>
        <a class="sp-item" href="?page=codeformat" data-page="codeformat" onclick="return navigateTo('codeformat')">
            <i data-lucide="code-2"></i> Code Formatter
        </a>
        <a class="sp-item" href="?page=numgen" data-page="numgen" onclick="return navigateTo('numgen')">
            <i data-lucide="hash"></i> Number Generator
        </a>
        <a class="sp-item" href="?page=downloader" data-page="downloader" onclick="return navigateTo('downloader')">
            <i data-lucide="download"></i> Website Downloader
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Utilities</div>
        <a class="sp-item" href="?page=caseconvert" data-page="caseconvert" onclick="return navigateTo('caseconvert')">
            <i data-lucide="case-sensitive"></i> Text Case Converter
        </a>
        <a class="sp-item" href="?page=qr" data-page="qr" onclick="return navigateTo('qr')">
            <i data-lucide="qr-code"></i> QR Code Generator
        </a>
        <a class="sp-item" href="?page=colorgen" data-page="colorgen" onclick="return navigateTo('colorgen')">
            <i data-lucide="palette"></i> Colour Generator
        </a>
        <div class="sp-divider"></div>
        <div class="sp-label">Info</div>
        <a class="sp-item" href="?page=about" data-page="about" onclick="return navigateTo('about')">
            <i data-lucide="info"></i> About
        </a>
        <a class="sp-item" href="?page=privacy" data-page="privacy" onclick="return navigateTo('privacy')">
            <i data-lucide="shield-check"></i> Privacy Policy
        </a>
        <a class="sp-item" href="https://toolbox.googleapps.com/apps/main/" target="_blank" rel="noopener">
            <i data-lucide="external-link"></i> Google Toolbox
        </a>
    </div>
</nav>

<div class="page-wrap top max-w-6xl mx-auto" style="padding:var(--sp-4) clamp(14px,4vw,24px) var(--sp-5)">

    <!-- BREADCRUMB BAR -->
    <div id="pageBreadcrumb">
        <a href="/" onclick="return navigateTo('home')" style="display:flex;align-items:center;gap:4px;color:var(--muted);text-decoration:none;">
          <i data-lucide="home" style="width:13px;height:13px;color:var(--muted);"></i>
          <span>DEV Toolz Pro</span>
        </a>
        <span class="bc-sep">›</span>
        <span id="bcPageName" class="bc-page">Home</span>
    </div>

    <!-- AdSense Banner (top) — only show on non-home pages to not clutter hero -->
    <div id="adsense-top" class="adsense-slot" style="margin-bottom:20px;display:none;">
      <ins class="adsbygoogle"
           style="display:block"
           data-ad-client="ca-pub-5885950581818992"
           data-ad-slot="auto"
           data-ad-format="auto"
           data-full-width-responsive="true"></ins>
      <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>

<div class="page-wrap max-w-6xl mx-auto">