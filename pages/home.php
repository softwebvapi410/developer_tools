<?php /* Page: home — SEO landing page with tool descriptions */ ?>

<div id="page-home" class="page-section">

  <!-- ══ HERO ══════════════════════════════════════════════════════════ -->
  <section class="hero-section" itemscope itemtype="https://schema.org/WebApplication">
    <meta itemprop="name" content="DEV Toolz Pro">
    <meta itemprop="description" content="Free online SEO tools: technical auditor, DNS lookup, WHOIS, QR code generator, text case converter, code formatter, IP info and more.">
    <meta itemprop="applicationCategory" content="DeveloperApplication">
    <meta itemprop="operatingSystem" content="Web">

    <div class="hero-badge">
      <i data-lucide="zap" style="width:13px;height:13px;"></i>
      Free Developer &amp; SEO Tools — No Login Required
    </div>

    <h1 class="hero-title">
      All-in-One<br>
      <span class="hero-accent">SEO &amp; Developer</span><br>
      Toolbox
    </h1>

    <p class="hero-desc">
      A free, fast, privacy-friendly suite of tools for web developers, marketers, and site owners.
      Audit your SEO, check DNS records, look up WHOIS data, generate QR codes, format code, convert text — all in one place, no sign-up needed.
    </p>

    <div class="hero-actions">
      <button onclick="navigateTo('audit')" class="btn-primary hero-btn">
        <i data-lucide="search" class="icon"></i> Start Free SEO Audit
      </button>
      <button onclick="document.getElementById('tools-section').scrollIntoView({behavior:'smooth'})" class="btn-secondary hero-btn-sec">
        <i data-lucide="layers" class="icon"></i> Explore All Tools
      </button>
    </div>

    <!-- Trust signals -->
    <div class="trust-bar">
      <span class="trust-item"><i data-lucide="shield-check" class="icon-sm"></i> No login required</span>
      <span class="trust-item"><i data-lucide="lock" class="icon-sm"></i> Privacy-first</span>
      <span class="trust-item"><i data-lucide="zap" class="icon-sm"></i> Instant results</span>
      <span class="trust-item"><i data-lucide="globe" class="icon-sm"></i> Works on any site</span>
    </div>
  </section>

  <!-- ══ TOOLS GRID ═════════════════════════════════════════════════════ -->
  <section id="tools-section" class="tools-section" aria-label="Available tools">
    <div class="section-label">All Tools</div>
    <h2 class="section-title">Everything You Need to Optimize &amp; Build</h2>
    <p class="section-desc">Each tool is free, browser-based, and designed to save you time. No accounts, no rate limits on basic use, no hidden paywalls.</p>

    <div class="tools-grid">

      <!-- SEO Audit -->
      <article class="tool-card featured" onclick="navigateTo('audit')" tabindex="0" role="button" aria-label="Open SEO Auditor"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="SEO Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#3b5bdb,#4f46e5);">
          <i data-lucide="search" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <div class="tool-badge">Most Popular</div>
          <h3 class="tool-name" itemprop="name">Technical SEO Analyzer</h3>
          <p class="tool-desc" itemprop="description">
            Deep-crawl any website to analyze meta tags, Open Graph tags, heading structure, broken links, image alt text, page speed signals, schema markup, robots.txt, sitemap.xml, and keyword density. Get a scored SEO report with actionable recommendations to improve your search rankings.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> Meta title &amp; description analysis</li>
            <li><i data-lucide="check" class="icon-sm"></i> Social media preview cards (OG + Twitter)</li>
            <li><i data-lucide="check" class="icon-sm"></i> Schema.org structured data detection</li>
            <li><i data-lucide="check" class="icon-sm"></i> Broken link checker</li>
            <li><i data-lucide="check" class="icon-sm"></i> Keyword density &amp; content stats</li>
            <li><i data-lucide="check" class="icon-sm"></i> Sitemap.xml generator &amp; download</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Audit My Site <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- DNS Lookup -->
      <article class="tool-card" onclick="navigateTo('dns')" tabindex="0" role="button" aria-label="Open DNS Lookup"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Network Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
          <i data-lucide="terminal" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">DNS Record Lookup</h3>
          <p class="tool-desc" itemprop="description">
            Query all DNS record types — A, AAAA, MX, NS, TXT, CNAME, SOA, CAA — in real time via Google DNS-over-HTTPS. Check SPF, DMARC, and DKIM email security configurations. Diagnose propagation issues and verify nameserver changes instantly.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> All record types (A, MX, TXT, NS, SOA…)</li>
            <li><i data-lucide="check" class="icon-sm"></i> SPF / DMARC / DKIM detection</li>
            <li><i data-lucide="check" class="icon-sm"></i> Real-time via Google DoH</li>
            <li><i data-lucide="check" class="icon-sm"></i> MX host A-record validation</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Look Up DNS <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- MX Check -->
      <article class="tool-card" onclick="navigateTo('mx')" tabindex="0" role="button" aria-label="Open MX Check"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Email Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#0ea5e9,#38bdf8);">
          <i data-lucide="mail-check" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">MX &amp; Email Deliverability Check</h3>
          <p class="tool-desc" itemprop="description">
            Verify that your domain is correctly configured to send and receive email. Detects MX records, identifies your mail provider (Google Workspace, Microsoft 365, Zoho, etc.), and checks for SPF, DMARC, and DKIM records. Includes recommended fixes for missing configurations.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> MX record priority &amp; host validation</li>
            <li><i data-lucide="check" class="icon-sm"></i> Mail provider auto-detection</li>
            <li><i data-lucide="check" class="icon-sm"></i> SPF / DMARC recommendation engine</li>
            <li><i data-lucide="check" class="icon-sm"></i> Email deliverability score</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Check Email Health <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- WHOIS -->
      <article class="tool-card" onclick="navigateTo('whois')" tabindex="0" role="button" aria-label="Open WHOIS Lookup"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Domain Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#059669,#10b981);">
          <i data-lucide="id-card" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">WHOIS &amp; Domain Lookup</h3>
          <p class="tool-desc" itemprop="description">
            Look up domain registration details using the modern RDAP protocol. See registrar, registration and expiry dates, nameservers, DNSSEC status, and domain statuses. Get expiry warnings so you never accidentally lose a domain.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> Registrar &amp; registration dates</li>
            <li><i data-lucide="check" class="icon-sm"></i> Expiry countdown &amp; renewal alerts</li>
            <li><i data-lucide="check" class="icon-sm"></i> Live nameserver comparison (RDAP vs DNS)</li>
            <li><i data-lucide="check" class="icon-sm"></i> DNSSEC verification</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Look Up Domain <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- IP Info -->
      <article class="tool-card" onclick="navigateTo('ip')" tabindex="0" role="button" aria-label="Open IP Info"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Network Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
          <i data-lucide="wifi" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">IP Address &amp; Network Info</h3>
          <p class="tool-desc" itemprop="description">
            Instantly see your public IP address, geolocation, ISP, ASN, and timezone. Also detects your local LAN IP via WebRTC, identifies your DNS resolver, checks for VPN/proxy usage, and displays an interactive map of your approximate location.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> Public &amp; local IP detection</li>
            <li><i data-lucide="check" class="icon-sm"></i> VPN / proxy / Tor detection</li>
            <li><i data-lucide="check" class="icon-sm"></i> DNS resolver identification</li>
            <li><i data-lucide="check" class="icon-sm"></i> Interactive geolocation map</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Check My IP <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- QR Generator -->
      <article class="tool-card" onclick="navigateTo('qr')" tabindex="0" role="button" aria-label="Open QR Generator"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Utility">
        <div class="tool-icon" style="background:linear-gradient(135deg,#0ea5e9,#3b5bdb);">
          <i data-lucide="qr-code" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">QR Code Generator</h3>
          <p class="tool-desc" itemprop="description">
            Generate high-quality QR codes for URLs, plain text, email addresses, phone numbers, Wi-Fi network credentials, and vCard contacts. Customize foreground/background colors, set the output size up to 1200px, and download as SVG, PNG, or JPG.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> URL, text, email, phone, Wi-Fi, vCard</li>
            <li><i data-lucide="check" class="icon-sm"></i> Custom colors &amp; sizes up to 1200px</li>
            <li><i data-lucide="check" class="icon-sm"></i> Download as SVG, PNG, or JPG</li>
            <li><i data-lucide="check" class="icon-sm"></i> Error-correction level H (30%)</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Create QR Code <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- Case Converter -->
      <article class="tool-card" onclick="navigateTo('caseconvert')" tabindex="0" role="button" aria-label="Open Case Converter"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Text Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
          <i data-lucide="case-sensitive" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">Text Case Converter</h3>
          <p class="tool-desc" itemprop="description">
            Transform any text into 13 different cases instantly. Convert between UPPERCASE, lowercase, Title Case, Sentence case, camelCase, PascalCase, snake_case, kebab-case, CONSTANT_CASE, dot.case, aLtErNaTiNg, and iNVERSE case. Includes word, character, sentence, and line counters.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> 13 conversion modes</li>
            <li><i data-lucide="check" class="icon-sm"></i> camelCase, snake_case, kebab-case for devs</li>
            <li><i data-lucide="check" class="icon-sm"></i> Real-time stats (words, chars, lines)</li>
            <li><i data-lucide="check" class="icon-sm"></i> One-click copy for every variant</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Convert Text <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- Code Formatter -->
      <article class="tool-card" onclick="navigateTo('codeformat')" tabindex="0" role="button" aria-label="Open Code Formatter"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Developer Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
          <i data-lucide="code-2" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">Code Formatter &amp; Beautifier</h3>
          <p class="tool-desc" itemprop="description">
            Instantly beautify or minify code in multiple languages. Supports JSON (with syntax validation), HTML, CSS, JavaScript, PHP, Python, SQL, XML, and Markdown. Choose your indentation (2 spaces, 4 spaces, or tabs), upload files, and download the formatted output.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> JSON, HTML, CSS, JS, SQL, XML, PHP, Python</li>
            <li><i data-lucide="check" class="icon-sm"></i> Format and Minify modes</li>
            <li><i data-lucide="check" class="icon-sm"></i> File upload &amp; download support</li>
            <li><i data-lucide="check" class="icon-sm"></i> Configurable indentation</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Format Code <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- Number Generator -->
      <article class="tool-card" onclick="navigateTo('numgen')" tabindex="0" role="button" aria-label="Open Number Generator"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Developer Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
          <i data-lucide="hash" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">Number &amp; UUID Generator</h3>
          <p class="tool-desc" itemprop="description">
            Generate random data for testing, development, and data science. Supports random integers, floats, arithmetic sequences, UUID v4/v7, Nano IDs, CUIDs, secure passwords, Unix timestamps, hex/binary/base64 strings, and Gaussian-distributed numbers. Export as lines, CSV, JSON, or PHP arrays.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> UUID v4, v7, Nano ID, CUID</li>
            <li><i data-lucide="check" class="icon-sm"></i> Secure password generator</li>
            <li><i data-lucide="check" class="icon-sm"></i> Gaussian / normal distribution</li>
            <li><i data-lucide="check" class="icon-sm"></i> Export as CSV, JSON, PHP array</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Generate Numbers <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- Colour Generator -->
      <article class="tool-card" onclick="navigateTo('colorgen')" tabindex="0" role="button" aria-label="Open Colour Generator"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Design Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#ec4899,#8b5cf6);">
          <i data-lucide="palette" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">Colour Code Generator &amp; Converter</h3>
          <p class="tool-desc" itemprop="description">
            Pick any color with a visual canvas picker and instantly convert between HEX, RGB, HSL, HSB/HSV, and CMYK. Auto-generates tints, shades, complementary, analogous, and triadic palettes. Checks WCAG contrast ratios and exports ready-to-use CSS custom properties.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> HEX ↔ RGB ↔ HSL ↔ HSB ↔ CMYK</li>
            <li><i data-lucide="check" class="icon-sm"></i> Auto tints, shades &amp; harmonies</li>
            <li><i data-lucide="check" class="icon-sm"></i> WCAG contrast ratio checker</li>
            <li><i data-lucide="check" class="icon-sm"></i> CSS variable export</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Pick a Color <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- Browser Info -->
      <article class="tool-card" onclick="navigateTo('browser')" tabindex="0" role="button" aria-label="Open Browser Info"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Developer Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#10b981,#06b6d4);">
          <i data-lucide="monitor" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">Browser &amp; User Agent Analyzer</h3>
          <p class="tool-desc" itemprop="description">
            Instantly detect your current browser name, version, rendering engine, operating system, screen resolution, device pixel ratio, connection type, and WebGL renderer. Also includes a user agent string parser — paste any UA string to identify the browser, OS, and device type.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> Browser, engine &amp; OS detection</li>
            <li><i data-lucide="check" class="icon-sm"></i> Screen, viewport &amp; DPR info</li>
            <li><i data-lucide="check" class="icon-sm"></i> WebGL renderer fingerprint</li>
            <li><i data-lucide="check" class="icon-sm"></i> UA string parser for any browser</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Check Browser <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

      <!-- Website Downloader -->
      <article class="tool-card" onclick="navigateTo('downloader')" tabindex="0" role="button" aria-label="Open Website Downloader"
               itemscope itemtype="https://schema.org/SoftwareApplication">
        <meta itemprop="applicationCategory" content="Developer Tool">
        <div class="tool-icon" style="background:linear-gradient(135deg,#3b5bdb,#4f46e5);">
          <i data-lucide="download" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div class="tool-body">
          <h3 class="tool-name" itemprop="name">Website Asset Downloader</h3>
          <p class="tool-desc" itemprop="description">
            Download all assets from any web page — images, CSS, JavaScript, fonts, PDFs, and documents — in a single click. Uses deep scanning to find resources referenced inside CSS and JS files, with CDN fallback, proxy rotation for rate-limited servers, and ZIP export with auto-cleanup.
          </p>
          <ul class="tool-features">
            <li><i data-lucide="check" class="icon-sm"></i> CSS url() and JS import deep scan</li>
            <li><i data-lucide="check" class="icon-sm"></i> 12× parallel download threads</li>
            <li><i data-lucide="check" class="icon-sm"></i> CDN fallback + proxy rotation</li>
            <li><i data-lucide="check" class="icon-sm"></i> ZIP export, 200+ file types</li>
          </ul>
          <div class="tool-cta">
            <span class="tool-cta-btn">Download Assets <i data-lucide="arrow-right" class="icon-sm"></i></span>
          </div>
        </div>
      </article>

    </div><!-- /tools-grid -->
  </section>

  <!-- ══ HOW IT WORKS ══════════════════════════════════════════════════ -->
  <section class="how-section" aria-labelledby="how-title">
    <div class="section-label">How It Works</div>
    <h2 class="section-title" id="how-title">Simple, Fast, Free</h2>
    <div class="how-grid">
      <div class="how-step">
        <div class="how-num">1</div>
        <h3 class="how-title">Enter Your URL</h3>
        <p class="how-desc">Type or paste any domain or page URL into the tool of your choice. The domain is automatically shared across all network tools so you don't have to retype it.</p>
      </div>
      <div class="how-step">
        <div class="how-num">2</div>
        <h3 class="how-title">Get Instant Results</h3>
        <p class="how-desc">Results appear in seconds — no server queues, no waiting. Our tools query real-time data sources like Google DNS-over-HTTPS and live RDAP registries.</p>
      </div>
      <div class="how-step">
        <div class="how-num">3</div>
        <h3 class="how-title">Act on the Insights</h3>
        <p class="how-desc">Follow the prioritized recommendations, download sitemaps, copy keyword strategies, or export generated assets. Every tool is designed to produce actionable output.</p>
      </div>
    </div>
  </section>

  <!-- ══ FAQ ════════════════════════════════════════════════════════════ -->
  <section class="faq-section" aria-labelledby="faq-title"
           itemscope itemtype="https://schema.org/FAQPage">
    <div class="section-label">FAQ</div>
    <h2 class="section-title" id="faq-title">Frequently Asked Questions</h2>

    <div class="faq-list">

      <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="faq-q" itemprop="name">Are these tools completely free?</summary>
        <div class="faq-a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <div itemprop="text">Yes. All tools on DEV Toolz Pro are completely free to use with no account, no subscription, and no hidden rate limits on basic use. The site is supported by non-intrusive advertising.</div>
        </div>
      </details>

      <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="faq-q" itemprop="name">Do you store the URLs or data I enter?</summary>
        <div class="faq-a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <div itemprop="text">No personal data is stored. URLs are used only to perform the requested lookup or crawl and are not logged or retained. The text case converter, code formatter, colour picker, number generator, and QR code tools all run entirely in your browser — nothing leaves your device.</div>
        </div>
      </details>

      <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="faq-q" itemprop="name">How does the SEO auditor score pages?</summary>
        <div class="faq-a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <div itemprop="text">The SEO score starts at 100 and deductions are applied for critical issues: missing title tag (−20), missing meta description (−20), noindex directive (−30), missing H1 (−15), multiple H1 tags (−10), images missing alt text (−3 each, up to −15), mixed content (−15), and various warnings. Bonus points are added for schema markup. The score gives you a quick health snapshot — not a guarantee of search ranking.</div>
        </div>
      </details>

      <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="faq-q" itemprop="name">What DNS record types does the lookup tool support?</summary>
        <div class="faq-a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <div itemprop="text">The DNS lookup tool supports A (IPv4), AAAA (IPv6), MX (mail exchange), NS (nameservers), TXT (text records, SPF, DMARC, DKIM), CNAME (aliases), SOA (start of authority), CAA (certificate authority authorization), and SRV (service records). All queries are made via Google DNS-over-HTTPS for accuracy and privacy.</div>
        </div>
      </details>

      <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="faq-q" itemprop="name">Can I use the QR code generator for commercial purposes?</summary>
        <div class="faq-a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <div itemprop="text">Yes. QR codes generated by this tool are yours to use for any purpose, including commercial use. The SVG and PNG outputs are clean, unbranded files at your chosen resolution.</div>
        </div>
      </details>

      <details class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <summary class="faq-q" itemprop="name">How is the sitemap.xml generated?</summary>
        <div class="faq-a" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
          <div itemprop="text">After the SEO crawl completes, a sitemap is generated from all discovered pages, priority-ranked by URL depth and content type. You can download it as a standards-compliant sitemap.xml and submit it directly to Google Search Console to improve your site's indexing.</div>
        </div>
      </details>

    </div>
  </section>

</div><!-- /page-home -->

<style>
/* ── Hero ─────────────────────────────────────────────────────── */
.hero-section{text-align:center;padding:clamp(40px,8vw,80px) 0 clamp(32px,6vw,64px);max-width:760px;margin:0 auto;}
.hero-badge{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:6px 16px;border-radius:100px;margin-bottom:clamp(16px,3vw,28px);}
.hero-title{font-family:'DM Sans',sans-serif;font-size:clamp(2.2rem,7vw,4rem);font-weight:900;letter-spacing:-.04em;line-height:1.05;color:var(--ink);margin-bottom:clamp(12px,2.5vw,20px);}
.hero-accent{background:linear-gradient(135deg,var(--accent),#818cf8,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero-desc{font-size:clamp(14px,2.5vw,17px);color:var(--muted);line-height:1.75;max-width:620px;margin:0 auto clamp(24px,4vw,40px);}
.hero-actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-bottom:clamp(24px,4vw,40px);}
.hero-btn{min-width:180px;}
.hero-btn-sec{background:#fff;color:var(--ink);border:1.5px solid var(--border);}
.hero-btn-sec:hover{background:var(--surface-2);}
.trust-bar{display:flex;flex-wrap:wrap;gap:20px;justify-content:center;}
.trust-item{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--muted);}
.trust-item i{color:var(--accent);}

/* ── Section headers ──────────────────────────────────────────── */
.section-label{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);margin-bottom:8px;}
.section-title{font-family:'DM Sans',sans-serif;font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;letter-spacing:-.03em;color:var(--ink);margin-bottom:12px;line-height:1.15;}
.section-desc{color:var(--muted);font-size:15px;line-height:1.7;max-width:620px;margin-bottom:clamp(24px,4vw,40px);}

/* ── Tools Section ────────────────────────────────────────────── */
.tools-section{padding:clamp(32px,6vw,64px) 0;}
.tools-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,340px),1fr));gap:20px;}

/* ── Tool Card ────────────────────────────────────────────────── */
.tool-card{background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:24px;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;gap:16px;position:relative;overflow:hidden;}
.tool-card::before{content:'';position:absolute;inset:0;opacity:0;background:linear-gradient(135deg,rgba(59,91,219,.04),rgba(79,70,229,.04));transition:opacity .2s;}
.tool-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(13,17,23,.1);border-color:rgba(59,91,219,.3);}
.tool-card:hover::before{opacity:1;}
.tool-card:focus{outline:2px solid var(--accent);outline-offset:2px;}
.tool-card.featured{border-color:rgba(59,91,219,.3);background:linear-gradient(145deg,#fff,#f8f9ff);}
.tool-card.featured::after{content:'';position:absolute;top:-1px;left:-1px;right:-1px;height:3px;background:linear-gradient(90deg,var(--accent),var(--accent-2));border-radius:20px 20px 0 0;}
.tool-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 14px rgba(0,0,0,.15);}
.tool-body{display:flex;flex-direction:column;gap:10px;}
.tool-badge{display:inline-block;background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;font-size:10px;font-weight:700;letter-spacing:.06em;padding:3px 10px;border-radius:100px;text-transform:uppercase;width:fit-content;}
.tool-name{font-family:'DM Sans',sans-serif;font-size:17px;font-weight:800;color:var(--ink);letter-spacing:-.02em;line-height:1.2;}
.tool-desc{font-size:13px;color:var(--muted);line-height:1.7;}
.tool-features{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:5px;}
.tool-features li{display:flex;align-items:flex-start;gap:7px;font-size:12px;color:var(--ink-3);font-weight:500;}
.tool-features i{color:var(--success);flex-shrink:0;margin-top:2px;}
.tool-cta{margin-top:4px;}
.tool-cta-btn{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--accent);}
.tool-cta-btn i{transition:transform .15s;}
.tool-card:hover .tool-cta-btn i{transform:translateX(4px);}

/* ── How it works ─────────────────────────────────────────────── */
.how-section{padding:clamp(32px,6vw,64px) 0;}
.how-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;margin-top:clamp(20px,3vw,36px);}
.how-step{background:#fff;border:1.5px solid var(--border);border-radius:18px;padding:28px 24px;position:relative;}
.how-num{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;font-size:18px;font-weight:900;display:flex;align-items:center;justify-content:center;margin-bottom:16px;}
.how-title{font-size:16px;font-weight:700;color:var(--ink);margin-bottom:8px;}
.how-desc{font-size:13px;color:var(--muted);line-height:1.7;}

/* ── FAQ ──────────────────────────────────────────────────────── */
.faq-section{padding:clamp(32px,6vw,64px) 0;}
.faq-list{display:flex;flex-direction:column;gap:10px;}
.faq-item{background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;transition:border-color .2s;}
.faq-item[open]{border-color:rgba(59,91,219,.3);}
.faq-q{padding:18px 20px;font-size:15px;font-weight:700;color:var(--ink);cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;gap:12px;}
.faq-q::after{content:'＋';font-size:20px;color:var(--accent);flex-shrink:0;transition:transform .2s;}
.faq-item[open] .faq-q::after{transform:rotate(45deg);}
.faq-a{padding:0 20px 18px;font-size:13px;color:var(--muted);line-height:1.75;}
</style>