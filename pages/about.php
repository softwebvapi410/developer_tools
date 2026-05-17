<?php /* Page: about — About page */ ?>

<div id="page-about" class="page-section">
  <div class="legal-page-wrap">

    <div class="legal-header">
      <div class="flex items-center gap-3 mb-4">
        <div style="width:44px;height:44px;border-radius:13px;background:linear-gradient(135deg,#3b5bdb,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i data-lucide="info" style="width:22px;height:22px;color:#fff;"></i>
        </div>
        <div>
          <h1 style="font-size:var(--fs-xl);font-weight:800;color:var(--ink);">About DEV Toolz Pro</h1>
          <p style="font-size:var(--fs-sm);color:var(--muted);">A free suite of tools for web professionals</p>
        </div>
      </div>
      <p class="legal-intro">
        DEV Toolz Pro is a free, browser-based collection of tools designed for web developers, digital marketers, SEO specialists, and site owners. Our mission is to make professional-grade web diagnostics and utilities accessible to everyone — no account, no subscription, no complexity.
      </p>
    </div>

    <div class="about-grid">

      <div class="about-card">
        <div class="about-icon" style="background:linear-gradient(135deg,#3b5bdb,#4f46e5);">
          <i data-lucide="target" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <h2>Our Mission</h2>
        <p>Technical SEO and web diagnostics shouldn't require expensive subscriptions or complex software. We built DEV Toolz Pro to give developers and site owners immediate, accurate insights about their websites — for free, from any browser, on any device.</p>
      </div>

      <div class="about-card">
        <div class="about-icon" style="background:linear-gradient(135deg,#059669,#10b981);">
          <i data-lucide="shield-check" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <h2>Privacy First</h2>
        <p>Several of our tools — including the text case converter, code formatter, colour picker, number generator, and QR code generator — run entirely in your browser. Nothing leaves your device. For server-side tools, inputs are used only to fulfill your request and are not logged or stored.</p>
      </div>

      <div class="about-card">
        <div class="about-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
          <i data-lucide="layers" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <h2>What We Offer</h2>
        <p>DEV Toolz Pro includes 12 free tools covering technical SEO auditing, DNS and WHOIS lookups, email deliverability checks, IP geolocation, QR code generation, text transformation, code formatting, colour conversion, and website asset downloading.</p>
      </div>

      <div class="about-card">
        <div class="about-icon" style="background:linear-gradient(135deg,#ec4899,#8b5cf6);">
          <i data-lucide="code-2" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <h2>How It's Built</h2>
        <p>The platform is built with PHP (server-side API actions), vanilla JavaScript (client-side tools and routing), and plain CSS. DNS lookups use Google DNS-over-HTTPS. WHOIS data is fetched from RDAP registries. No external JavaScript frameworks are required for core functionality.</p>
      </div>

      <div class="about-card">
        <div class="about-icon" style="background:linear-gradient(135deg,#0ea5e9,#3b5bdb);">
          <i data-lucide="trending-up" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <h2>Continuously Improved</h2>
        <p>We regularly add new tools and improve existing ones based on user feedback. Recent additions include the Colour Code Generator, Code Formatter, Number &amp; UUID Generator, and Website Asset Downloader. If you have a suggestion, we'd love to hear it.</p>
      </div>

      <div class="about-card">
        <div class="about-icon" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
          <i data-lucide="heart" style="width:20px;height:20px;color:#fff;"></i>
        </div>
        <h2>Supporting the Site</h2>
        <p>DEV Toolz Pro is free to use and supported by non-intrusive advertising via Google AdSense. Displaying ads allows us to cover server costs and continue developing new tools. We never put tools behind a paywall or require sign-up to access any feature.</p>
      </div>

    </div><!-- /about-grid -->

    <!-- Tool overview table -->
    <div class="about-table-card">
      <h2 style="font-size:16px;font-weight:800;color:var(--ink);margin-bottom:16px;">All Tools at a Glance</h2>
      <div style="overflow-x:auto;">
        <table class="about-table">
          <thead>
            <tr>
              <th>Tool</th>
              <th>Category</th>
              <th>Runs In</th>
              <th>Data Stored?</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>Technical SEO Analyzer</td><td>SEO</td><td>Server</td><td>No</td></tr>
            <tr><td>DNS Record Lookup</td><td>Network</td><td>Server (via Google DoH)</td><td>No</td></tr>
            <tr><td>MX &amp; Email Check</td><td>Email</td><td>Server (via Google DoH)</td><td>No</td></tr>
            <tr><td>WHOIS &amp; Domain Lookup</td><td>Domain</td><td>Server (via RDAP)</td><td>No</td></tr>
            <tr><td>IP Address Info</td><td>Network</td><td>Browser + ipapi.co</td><td>No</td></tr>
            <tr><td>Browser &amp; UA Analyzer</td><td>Developer</td><td>Browser only</td><td>No</td></tr>
            <tr><td>QR Code Generator</td><td>Utility</td><td>Browser only</td><td>No</td></tr>
            <tr><td>Text Case Converter</td><td>Text</td><td>Browser only</td><td>No</td></tr>
            <tr><td>Code Formatter</td><td>Developer</td><td>Browser only</td><td>No</td></tr>
            <tr><td>Number &amp; UUID Generator</td><td>Developer</td><td>Browser only</td><td>No</td></tr>
            <tr><td>Colour Code Generator</td><td>Design</td><td>Browser only</td><td>No</td></tr>
            <tr><td>Website Asset Downloader</td><td>Developer</td><td>Server</td><td>Temp (5 min)</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /legal-page-wrap -->
</div>

<style>
.about-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:16px;margin-bottom:16px;}
.about-card{background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:clamp(16px,3vw,24px);}
.about-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.about-card h2{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:8px;}
.about-card p{font-size:13px;color:var(--muted);line-height:1.75;}
.about-table-card{background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:clamp(16px,3vw,24px);}
.about-table{width:100%;border-collapse:collapse;font-size:13px;}
.about-table th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);padding:8px 12px;border-bottom:1.5px solid var(--border);background:var(--surface);}
.about-table td{padding:9px 12px;border-bottom:1px solid var(--border);color:var(--ink-3);}
.about-table tr:last-child td{border-bottom:none;}
.about-table tr:hover td{background:var(--surface);}
</style>