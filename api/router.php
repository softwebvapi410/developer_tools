<?php
// ==================== API ROUTER ====================
// Dispatches ?action=X requests to the appropriate handler file.

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
    exit;
}

$action = $_GET['action'];

require_once __DIR__ . '/helpers.php';

// Run cleanup on every API request
dl_cleanup_expired(__DIR__ . '/../downloads');

switch ($action) {
    case 'crawl':
        require __DIR__ . '/actions/crawl.php';
        break;
    case 'dns_lookup':
        require __DIR__ . '/actions/dns_lookup.php';
        break;
    case 'whois':
        require __DIR__ . '/actions/whois.php';
        break;
    case 'check_sitemap':
        require __DIR__ . '/actions/check_sitemap.php';
        break;
    case 'download_sitemap':
        require __DIR__ . '/actions/download_sitemap.php';
        break;

    // ── Website Asset Downloader ──────────────────────────────────
    case 'download_website':
        require __DIR__ . '/actions/download_website.php';
        break;
    case 'dl_zip':
        require __DIR__ . '/actions/dl_zip.php';
        break;
    case 'dl_cleanup':
        require __DIR__ . '/actions/dl_cleanup.php';
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
        exit;
}
