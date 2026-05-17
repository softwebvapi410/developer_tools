<?php
// ============================================================
//  DEV Toolz Pro — Main Entry Point
//  All API actions are routed through api/router.php.
//  HTML pages are composed from components/ and pages/.
// ============================================================

// --- Route API requests ---
if (isset($_GET['action'])) {
    require __DIR__ . '/api/router.php';
    exit;
}

// --- Determine which page to show ---
$page = $_GET['page'] ?? 'home';
$validPages = ['home', 'audit', 'dns', 'mx', 'whois', 'browser', 'ip', 'downloader',
               'caseconvert', 'qr', 'codeformat', 'numgen', 'colorgen', 'about', 'privacy'];
if (!in_array($page, $validPages)) {
    $page = 'home';
}
?>
<?php require __DIR__ . '/components/layout.php'; ?>

    <!-- ═══════════════════════════════════════════════════════════════════
         PAGE SECTIONS  (all rendered; JS shows/hides them)
         ═══════════════════════════════════════════════════════════════════ -->

    <?php require __DIR__ . '/pages/home.php'; ?>
    <?php require __DIR__ . '/pages/audit.php'; ?>
    <?php require __DIR__ . '/pages/dns.php'; ?>
    <?php require __DIR__ . '/pages/mx.php'; ?>
    <?php require __DIR__ . '/pages/whois.php'; ?>
    <?php require __DIR__ . '/pages/browser.php'; ?>
    <?php require __DIR__ . '/pages/ip.php'; ?>
    <?php require __DIR__ . '/pages/downloader.php'; ?>
    <?php require __DIR__ . '/pages/caseconvert.php'; ?>
    <?php require __DIR__ . '/pages/qr.php'; ?>
    <?php require __DIR__ . '/pages/codeformat.php'; ?>
    <?php require __DIR__ . '/pages/numgen.php'; ?>
    <?php require __DIR__ . '/pages/colorgen.php'; ?>
    <?php require __DIR__ . '/pages/about.php'; ?>
    <?php require __DIR__ . '/pages/privacy.php'; ?>

    <?php require __DIR__ . '/components/google-toolbox.php'; ?>

</div><!-- /page-wrap -->

<!-- ── AdSense: Bottom / In-content slot ── -->
<div class="adsense-slot" style="max-width:1200px;margin:0 auto 20px;padding:0 clamp(14px,4vw,24px);">
  <ins class="adsbygoogle"
       style="display:block"
       data-ad-client="ca-pub-5885950581818992"
       data-ad-slot="auto"
       data-ad-format="auto"
       data-full-width-responsive="true"></ins>
  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</div>

<!-- ── Footer ── -->
<footer class="site-footer" itemscope itemtype="https://schema.org/WPFooter">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">
        <i data-lucide="zap" style="width:16px;height:16px;color:var(--accent);"></i>
        DEV Toolz Pro
      </div>
      <p class="footer-tagline">Free, instant SEO &amp; developer tools — no login required.</p>
    </div>
    <nav class="footer-nav" aria-label="Footer navigation">
      <div class="footer-col">
        <div class="footer-col-title">SEO Tools</div>
        <a href="?page=audit" onclick="return navigateTo('audit')">SEO Analyzer</a>
        <a href="?page=dns"   onclick="return navigateTo('dns')">DNS Lookup</a>
        <a href="?page=mx"    onclick="return navigateTo('mx')">MX Check</a>
        <a href="?page=whois" onclick="return navigateTo('whois')">WHOIS Lookup</a>
        <a href="?page=ip"    onclick="return navigateTo('ip')">IP Info</a>
      </div>
      <div class="footer-col">
        <div class="footer-col-title">Developer Tools</div>
        <a href="?page=codeformat"  onclick="return navigateTo('codeformat')">Code Formatter</a>
        <a href="?page=numgen"      onclick="return navigateTo('numgen')">Number Generator</a>
        <a href="?page=colorgen"    onclick="return navigateTo('colorgen')">Colour Picker</a>
        <a href="?page=downloader"  onclick="return navigateTo('downloader')">Asset Downloader</a>
        <a href="?page=browser"     onclick="return navigateTo('browser')">Browser Info</a>
      </div>
      <div class="footer-col">
        <div class="footer-col-title">Utilities</div>
        <a href="?page=caseconvert" onclick="return navigateTo('caseconvert')">Case Converter</a>
        <a href="?page=qr"          onclick="return navigateTo('qr')">QR Generator</a>
        <a href="?page=about"       onclick="return navigateTo('about')">About</a>
        <a href="?page=privacy"     onclick="return navigateTo('privacy')">Privacy Policy</a>
      </div>
    </nav>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> DEV Toolz Pro. All tools are free to use. Supported by Google AdSense advertising.</p>
    <p style="margin-top:4px;font-size:11px;color:#6b7280;">
      DNS data via <a href="https://developers.google.com/speed/public-dns" target="_blank" rel="noopener">Google Public DNS</a> ·
      WHOIS via <a href="https://www.iana.org/rdap" target="_blank" rel="noopener">RDAP</a> ·
      IP data via <a href="https://ipapi.co" target="_blank" rel="noopener">ipapi.co</a>
    </p>
  </div>
</footer>

<style>
/* ── Footer ── */
.site-footer{background:#fff;border-top:1.5px solid var(--border);margin-top:clamp(32px,6vw,64px);}
.footer-inner{max-width:1200px;margin:0 auto;padding:clamp(32px,5vw,48px) clamp(14px,4vw,24px) clamp(24px,4vw,32px);display:flex;flex-wrap:wrap;gap:40px;justify-content:space-between;}
.footer-brand{max-width:280px;}
.footer-logo{display:flex;align-items:center;gap:8px;font-size:16px;font-weight:800;color:var(--ink);margin-bottom:10px;}
.footer-tagline{font-size:13px;color:var(--muted);line-height:1.6;}
.footer-nav{display:flex;flex-wrap:wrap;gap:40px;}
.footer-col{display:flex;flex-direction:column;gap:7px;}
.footer-col-title{font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;}
.footer-col a{font-size:13px;color:var(--ink-3);text-decoration:none;transition:color .15s;}
.footer-col a:hover{color:var(--accent);}
.footer-bottom{border-top:1px solid var(--border);padding:clamp(12px,2vw,16px) clamp(14px,4vw,24px);text-align:center;font-size:12px;color:var(--muted);}
.footer-bottom a{color:var(--muted);text-decoration:underline;}
/* ── AdSense wrapper ── */
.adsense-slot{background:transparent;}
</style>

<?php require __DIR__ . '/components/modal.php'; ?>

<!-- ════════════════════════════════════════════════════
     JAVASCRIPT  (modular — each file handles one concern)
     ════════════════════════════════════════════════════ -->
<script src="assets/js/router.js"></script>
<script src="assets/js/downloader.js"></script>
<script src="assets/js/audit.js"></script>
<script src="assets/js/dns.js"></script>
<script src="assets/js/mx.js"></script>
<script src="assets/js/whois.js"></script>
<script src="assets/js/browser.js"></script>
<script src="assets/js/ua.js"></script>
<script src="assets/js/ip.js"></script>
<script src="assets/js/caseconvert.js"></script>
<script src="assets/js/qr.js"></script>
<script src="assets/js/codeformat.js"></script>
<script src="assets/js/numgen.js"></script>
<script src="assets/js/colorgen.js"></script>

</body>
</html>