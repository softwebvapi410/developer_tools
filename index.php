<?php
// ============================================================
//  SEO Auditor Pro — Main Entry Point
//  All API actions are routed through api/router.php.
//  HTML pages are composed from components/ and pages/.
// ============================================================

// --- Route API requests ---
if (isset($_GET['action'])) {
    require __DIR__ . '/api/router.php';
    exit;
}

// --- Determine which page to show ---
$page = $_GET['page'] ?? 'audit';
$validPages = ['audit', 'dns', 'mx', 'whois', 'browser', 'ip', 'downloader', 'caseconvert', 'qr', 'codeformat', 'numgen', 'colorgen'];
if (!in_array($page, $validPages)) {
    $page = 'audit';
}
?>
<?php require __DIR__ . '/components/layout.php'; ?>

    <!-- ═══════════════════════════════════════════════════════════════════
         PAGE SECTIONS  (all rendered; JS shows/hides them)
         ═══════════════════════════════════════════════════════════════════ -->

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

    <?php require __DIR__ . '/components/google-toolbox.php'; ?>

</div><!-- /page-wrap -->

<?php require __DIR__ . '/components/modal.php'; ?>

<!-- ════════════════════════════════════════════════════
     JAVASCRIPT  (modular — each file handles one concern)
     ════════════════════════════════════════════════════ -->
<script src="assets/js/router.js"></script>
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
