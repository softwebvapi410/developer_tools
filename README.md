# SEO Auditor Pro — Refactored Structure

## Overview

This project was refactored from a single monolithic `index.php` (~4,671 lines) into a clean, modular file structure. Every concern now lives in its own file.

---

## File Structure

```
seo-auditor-pro/
├── index.php                        ← Entry point: routes API + composes HTML pages
│
├── api/
│   ├── router.php                   ← Dispatches ?action=X to the right action file
│   ├── helpers.php                  ← Shared PHP helpers (determineChangeFrequency, doh_query, etc.)
│   └── actions/
│       ├── crawl.php                ← Full SEO crawl: meta, headings, images, links, schema, tracking
│       ├── dns_lookup.php           ← DNS-over-HTTPS lookup (A, AAAA, MX, TXT, NS, SOA, CAA...)
│       ├── whois.php                ← RDAP/WHOIS domain lookup
│       ├── check_sitemap.php        ← Sitemap.xml detection
│       └── download_sitemap.php     ← Generates and streams sitemap.xml download
│
├── assets/
│   ├── css/
│   │   └── app.css                  ← All styles (variables, components, animations, responsive)
│   └── js/
│       ├── router.js                ← Page router, side panel, breadcrumb, domain sync
│       ├── audit.js                 ← Crawl queue, result cards, SEO modal, sitemap, keyword cards
│       ├── dns.js                   ← DNS lookup UI and record rendering
│       ├── mx.js                    ← MX/email check, mail provider detection, SPF/DMARC badges
│       ├── whois.js                 ← WHOIS results rendering, registrar detection
│       ├── browser.js               ← Browser info detection (engine, OS, WebGL, screen)
│       ├── ua.js                    ← User Agent string parser and analyzer
│       ├── ip.js                    ← IP geolocation, local IP detection, VPN detection, map
│       ├── caseconvert.js           ← Text case converter (14+ modes)
│       └── qr.js                   ← QR code generator (URL, WiFi, vCard, etc.)
│
├── components/
│   ├── layout.php                   ← DOCTYPE, <head>, CSS/JS links, side nav, breadcrumb, page-wrap open
│   ├── modal.php                    ← SEO report modal overlay HTML
│   └── google-toolbox.php           ← Google Admin Toolbox banner with quick links
│
└── pages/
    ├── audit.php                    ← Analyze Site page HTML
    ├── dns.php                      ← DNS Lookup page HTML
    ├── mx.php                       ← MX / Email Check page HTML
    ├── whois.php                    ← WHOIS Lookup page HTML
    ├── browser.php                  ← Browser & UA page HTML
    ├── ip.php                       ← IP Address Info page HTML
    ├── caseconvert.php              ← Text Case Converter page HTML
    └── qr.php                       ← QR Code Generator page HTML
```

---

## How It Works

### Request Flow

```
Browser → index.php
           │
           ├─ ?action=X  →  api/router.php  →  api/actions/X.php  →  JSON response
           │
           └─ ?page=X    →  components/layout.php   (head + nav)
                             pages/X.php             (page HTML)
                             components/modal.php    (SEO modal)
                             assets/js/*.js          (page logic)
```

### API Actions

| Action             | File                           | Description                         |
|--------------------|--------------------------------|-------------------------------------|
| `crawl`            | `api/actions/crawl.php`        | Full SEO audit of a URL             |
| `dns_lookup`       | `api/actions/dns_lookup.php`   | DNS records via Google DoH          |
| `whois`            | `api/actions/whois.php`        | RDAP domain registration info       |
| `check_sitemap`    | `api/actions/check_sitemap.php`| Detect sitemap.xml on a domain      |
| `download_sitemap` | `api/actions/download_sitemap.php` | Stream sitemap.xml to browser   |

### Pages

| Page         | URL                  | JS File          |
|--------------|----------------------|------------------|
| Audit        | `?page=audit`        | `audit.js`       |
| DNS Lookup   | `?page=dns`          | `dns.js`         |
| MX Check     | `?page=mx`           | `mx.js`          |
| WHOIS        | `?page=whois`        | `whois.js`       |
| Browser Info | `?page=browser`      | `browser.js`     |
| IP Info      | `?page=ip`           | `ip.js`          |
| Case Convert | `?page=caseconvert`  | `caseconvert.js` |
| QR Generator | `?page=qr`           | `qr.js`          |

---

## Deployment

Drop the `seo-auditor-pro/` folder into your web root. Requires PHP 7.4+ with `curl` and `dom` extensions enabled.

```
/var/www/html/seo-auditor-pro/
```

Then visit: `https://yourdomain.com/seo-auditor-pro/`

---

## Adding a New Page

1. Create `pages/mypage.php` with `<div id="page-mypage" class="page-section">...</div>`
2. Create `assets/js/mypage.js` with your page logic
3. Add `<?php require __DIR__ . '/pages/mypage.php'; ?>` in `index.php`
4. Add `<script src="assets/js/mypage.js"></script>` in `index.php`
5. Add a nav link in `components/layout.php` inside the `<nav>` block

## Adding a New API Action

1. Create `api/actions/myaction.php` — set headers and `echo json_encode(...); exit;`
2. Add a `case 'myaction':` entry in `api/router.php`
