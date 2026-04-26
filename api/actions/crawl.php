<?php
header('Content-Type: application/json');

$url = filter_var(trim($_GET['url'] ?? ''), FILTER_SANITIZE_URL);
if (!$url) { echo json_encode(['error' => 'No URL provided']); exit; }

// Lowercase scheme and host only — path is case-sensitive on many servers
if (preg_match('/^(https?:\/\/)([^\/]+)(.*)/i', $url, $m)) {
    $url = strtolower($m[1]) . strtolower($m[2]) . $m[3];
} else {
    $url = 'https://' . $url;
}

// ── Document / binary extension detection ──────────────────────
$docExtensions = [
    'pdf'  => ['label' => 'PDF',       'icon' => 'file', 'color' => '#dc2626', 'bg' => '#fee2e2'],
    'xls'  => ['label' => 'Excel',     'icon' => 'bar-chart-2', 'color' => '#16a34a', 'bg' => '#dcfce7'],
    'xlsx' => ['label' => 'Excel',     'icon' => 'bar-chart-2', 'color' => '#16a34a', 'bg' => '#dcfce7'],
    'csv'  => ['label' => 'CSV',       'icon' => 'clipboard-list', 'color' => '#059669', 'bg' => '#d1fae5'],
    'doc'  => ['label' => 'Word',      'icon' => 'file-text', 'color' => '#2563eb', 'bg' => '#dbeafe'],
    'docx' => ['label' => 'Word',      'icon' => 'file-text', 'color' => '#2563eb', 'bg' => '#dbeafe'],
    'ppt'  => ['label' => 'PowerPoint','icon' => 'bar-chart-2', 'color' => '#ea580c', 'bg' => '#ffedd5'],
    'pptx' => ['label' => 'PowerPoint','icon' => 'bar-chart-2', 'color' => '#ea580c', 'bg' => '#ffedd5'],
    'vcf'  => ['label' => 'vCard',     'icon' => 'user', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
    'ics'  => ['label' => 'Calendar',  'icon' => 'calendar', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'zip'  => ['label' => 'ZIP',       'icon' => 'archive', 'color' => '#92400e', 'bg' => '#fef3c7'],
    'rar'  => ['label' => 'RAR',       'icon' => 'archive', 'color' => '#92400e', 'bg' => '#fef3c7'],
    'tar'  => ['label' => 'TAR',       'icon' => 'archive', 'color' => '#92400e', 'bg' => '#fef3c7'],
    'gz'   => ['label' => 'GZ',        'icon' => 'archive', 'color' => '#92400e', 'bg' => '#fef3c7'],
    'mp4'  => ['label' => 'Video',     'icon' => 'film', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
    'mp3'  => ['label' => 'Audio',     'icon' => 'music', 'color' => '#6d28d9', 'bg' => '#f5f3ff'],
    'txt'  => ['label' => 'Text',      'icon' => 'file', 'color' => '#374151', 'bg' => '#f3f4f6'],
    'xml'  => ['label' => 'XML',       'icon' => 'file', 'color' => '#0369a1', 'bg' => '#e0f2fe'],
    'json' => ['label' => 'JSON',      'icon' => 'braces', 'color' => '#b45309', 'bg' => '#fef3c7'],
    'svg'  => ['label' => 'SVG',       'icon' => 'image', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
    'cdr'  => ['label' => 'CorelDRAW', 'icon' => 'image', 'color' => '#d946ef', 'bg' => '#f3e8ff'],
];

// Image extensions — skip entirely (don't crawl, don't list)
$imageExtensions = ['jpg','jpeg','png','gif','webp','ico','bmp','tiff','avif','heic'];

// Static asset extensions — skip entirely
$staticExtensions = ['css','js','woff','woff2','ttf','eot','map'];

function getUrlExtension(string $url): string {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    // Strip query-string fragments that may have leaked into ext
    return preg_replace('/[^a-z0-9].*$/', '', $ext);
}

$urlExt = getUrlExtension($url);

// Skip images and static assets entirely — return empty so queue ignores them
if (in_array($urlExt, array_merge($imageExtensions, $staticExtensions))) {
    echo json_encode(['error' => 'Skipped: static asset']); exit;
}

// ── If it's a known document, return a lightweight "document" result ──
global $docExtensions;
if (isset($docExtensions[$urlExt])) {
    $docInfo = $docExtensions[$urlExt];

    // Quick HEAD request to verify the file exists & get size
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_NOBODY         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SEOAuditorPro/2.0)',
    ]);
    curl_exec($ch);
    $info    = curl_getinfo($ch);
    $status  = $info['http_code'];
    $sizeMb  = isset($info['download_content_length']) && $info['download_content_length'] > 0
               ? round($info['download_content_length'] / 1024, 1) . ' KB'
               : '—';
    curl_close($ch);

    $parsedDoc = parse_url($url);
    $filename  = basename($parsedDoc['path'] ?? $url);
    $ssl       = ($parsedDoc['scheme'] ?? '') === 'https';

    $seo = [
        'title'           => $filename,
        'title_length'    => strlen($filename),
        'description'     => $docInfo['label'] . ' document — ' . $filename,
        'description_length' => 0,
        'meta_keywords'   => '',
        'h1_count' => 0, 'h2_count' => 0, 'h3_count' => 0, 'h4_count' => 0,
        'img_no_alt' => 0, 'img_with_alt' => 0, 'total_images' => 0,
        'canonical' => null, 'canonical_valid' => false,
        'robots' => 'index, follow',
        'viewport' => false,
        'word_count' => 0,
        'internal_links' => 0, 'external_links' => 0,
        'broken_links' => [],
        'schema_count' => 0, 'schema_types' => [],
        'page_size_kb'  => $sizeMb,
        'status'        => $status,
        'load_time'     => '—',
        'score'         => 100,   // Documents always 100% — N/A for SEO scoring
        'issues'        => [],
        'warnings'      => [],
        'suggestions'   => [],
        'og_title' => null, 'og_description' => null, 'og_image' => null,
        'og_url' => null, 'og_type' => null,
        'twitter_card' => null, 'twitter_title' => null,
        'twitter_description' => null, 'twitter_image' => null,
        'ssl_valid'     => $ssl,
        'mixed_content' => [],
        'keyword_density' => [],
        'robots_txt_valid' => null, 'robots_txt_content' => null,
        'serp_preview'  => '',
        'local_business_schema' => false,
        'nap_consistency' => [],
        'maps_presence' => false,
        'featured_snippet_potential' => false,
        'people_also_ask' => [], 'related_searches' => [],
        'favicon_url'   => ($parsedDoc['scheme'] ?? 'https') . '://' . ($parsedDoc['host'] ?? '') . '/favicon.ico',
        'sitemap_valid' => false, 'sitemap_url' => null,
        'sitemap_content' => null, 'sitemap_url_count' => 0,
        'llms_txt_valid' => false, 'llms_txt_url' => null,
        'llms_txt_content' => null, 'llms_txt_sections' => [],
        'robots_sitemap_ref' => null, 'robots_sitemap_match' => false,
        'tracking_tools' => [],
        // Document-specific extras
        'is_document'   => true,
        'doc_ext'       => strtoupper($urlExt),
        'doc_label'     => $docInfo['label'],
        'doc_icon'      => $docInfo['icon'],
        'doc_color'     => $docInfo['color'],
        'doc_bg'        => $docInfo['bg'],
        'doc_size'      => $sizeMb,
    ];

    $urlPath = parse_url($url, PHP_URL_PATH);
    $depth   = substr_count(trim($urlPath ?? '', '/'), '/');
    $priority = max(0.1, 1.0 - ($depth * 0.1));

    echo json_encode([
        'url'         => $url,
        'seo'         => $seo,
        'links'       => [],          // Documents contain no crawlable links
        'priority'    => $priority,
        'change_freq' => 'monthly',
        'depth'       => $depth,
    ]);
    exit;
}

// ── Standard HTML page crawl ───────────────────────────────────
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SEOAuditorPro/2.0; +https://seoauditor.pro)',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HEADER         => true,
    CURLOPT_ENCODING       => 'gzip, deflate',
]);
$response    = curl_exec($ch);
$info        = curl_getinfo($ch);
$curlerror   = curl_error($ch);
curl_close($ch);

if ($response === false || !empty($curlerror)) {
    echo json_encode(['error' => 'Failed to fetch URL: ' . ($curlerror ?: 'Unknown error')]); exit;
}

$header_size = $info['header_size'] ?? 0;
$html        = $header_size > 0 ? substr($response, $header_size) : $response;

// If the server returned a document content-type despite no extension, handle it
$contentType = $info['content_type'] ?? '';
$isDocument  = false;
$docExtFromCT = null;
if (strpos($contentType, 'application/pdf') !== false)  { $isDocument = true; $docExtFromCT = 'pdf'; }
elseif (strpos($contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml') !== false) { $isDocument = true; $docExtFromCT = 'xlsx'; }
elseif (strpos($contentType, 'application/vnd.ms-excel') !== false) { $isDocument = true; $docExtFromCT = 'xls'; }
elseif (strpos($contentType, 'application/vnd.openxmlformats-officedocument.wordprocessingml') !== false) { $isDocument = true; $docExtFromCT = 'docx'; }
elseif (strpos($contentType, 'application/msword') !== false) { $isDocument = true; $docExtFromCT = 'doc'; }
elseif (strpos($contentType, 'text/csv') !== false) { $isDocument = true; $docExtFromCT = 'csv'; }
elseif (preg_match('/^(image|audio|video)\//i', $contentType)) {
    // Binary media — skip
    echo json_encode(['error' => 'Skipped: binary media']); exit;
}

if ($isDocument && $docExtFromCT) {
    $docInfo  = $docExtensions[$docExtFromCT] ?? ['label'=>strtoupper($docExtFromCT),'icon'=>'file','color'=>'#374151','bg'=>'#f3f4f6'];
    $parsedDoc = parse_url($url);
    $filename  = basename($parsedDoc['path'] ?? $url) ?: $url;
    $ssl       = ($parsedDoc['scheme'] ?? '') === 'https';

    $seo = [
        'title'           => $filename,
        'title_length'    => strlen($filename),
        'description'     => $docInfo['label'] . ' document — ' . $filename,
        'description_length' => 0,
        'meta_keywords'   => '',
        'h1_count' => 0, 'h2_count' => 0, 'h3_count' => 0, 'h4_count' => 0,
        'img_no_alt' => 0, 'img_with_alt' => 0, 'total_images' => 0,
        'canonical' => null, 'canonical_valid' => false,
        'robots' => 'index, follow', 'viewport' => false,
        'word_count' => 0, 'internal_links' => 0, 'external_links' => 0,
        'broken_links' => [], 'schema_count' => 0, 'schema_types' => [],
        'page_size_kb'  => round(strlen($html) / 1024, 1),
        'status'        => $info['http_code'],
        'load_time'     => number_format($info['total_time'], 2),
        'score'         => 100,
        'issues' => [], 'warnings' => [], 'suggestions' => [],
        'og_title' => null, 'og_description' => null, 'og_image' => null,
        'og_url' => null, 'og_type' => null,
        'twitter_card' => null, 'twitter_title' => null,
        'twitter_description' => null, 'twitter_image' => null,
        'ssl_valid' => $ssl, 'mixed_content' => [],
        'keyword_density' => [], 'robots_txt_valid' => null, 'robots_txt_content' => null,
        'serp_preview' => '', 'local_business_schema' => false,
        'nap_consistency' => [], 'maps_presence' => false,
        'featured_snippet_potential' => false,
        'people_also_ask' => [], 'related_searches' => [],
        'favicon_url' => ($parsedDoc['scheme'] ?? 'https') . '://' . ($parsedDoc['host'] ?? '') . '/favicon.ico',
        'sitemap_valid' => false, 'sitemap_url' => null,
        'sitemap_content' => null, 'sitemap_url_count' => 0,
        'llms_txt_valid' => false, 'llms_txt_url' => null,
        'llms_txt_content' => null, 'llms_txt_sections' => [],
        'robots_sitemap_ref' => null, 'robots_sitemap_match' => false,
        'tracking_tools' => [],
        'is_document'   => true,
        'doc_ext'       => strtoupper($docExtFromCT),
        'doc_label'     => $docInfo['label'],
        'doc_icon'      => $docInfo['icon'],
        'doc_color'     => $docInfo['color'],
        'doc_bg'        => $docInfo['bg'],
        'doc_size'      => round(strlen($html) / 1024, 1) . ' KB',
    ];

    $urlPath = parse_url($url, PHP_URL_PATH);
    $depth   = substr_count(trim($urlPath ?? '', '/'), '/');

    echo json_encode([
        'url'         => $url,
        'seo'         => $seo,
        'links'       => [],
        'priority'    => max(0.1, 1.0 - ($depth * 0.1)),
        'change_freq' => 'monthly',
        'depth'       => $depth,
    ]);
    exit;
}

$parsedUrl = parse_url($url);
$baseUrl   = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
$baseDomain = $parsedUrl['host'] ?? '';

$seo = [
    'title'                   => 'Missing Title',
    'title_length'            => 0,
    'description'             => 'Missing Meta Description',
    'description_length'      => 0,
    'meta_keywords'           => '',
    'h1_count'                => 0,
    'h2_count'                => 0,
    'h3_count'                => 0,
    'h4_count'                => 0,
    'img_no_alt'              => 0,
    'img_with_alt'            => 0,
    'total_images'            => 0,
    'canonical'               => null,
    'canonical_valid'         => false,
    'robots'                  => 'index, follow',
    'viewport'                => false,
    'word_count'              => 0,
    'internal_links'          => 0,
    'external_links'          => 0,
    'broken_links'            => [],
    'schema_count'            => 0,
    'schema_types'            => [],
    'page_size_kb'            => round(strlen($html) / 1024, 1),
    'status'                  => $info['http_code'],
    'load_time'               => number_format($info['total_time'], 2),
    'score'                   => 100,
    'issues'                  => [],
    'warnings'                => [],
    'suggestions'             => [],
    'og_title'                => null,
    'og_description'          => null,
    'og_image'                => null,
    'og_url'                  => null,
    'og_type'                 => null,
    'twitter_card'            => null,
    'twitter_title'           => null,
    'twitter_description'     => null,
    'twitter_image'           => null,
    'ssl_valid'               => false,
    'mixed_content'           => [],
    'keyword_density'         => [],
    'robots_txt_valid'        => null,
    'robots_txt_content'      => null,
    'serp_preview'            => '',
    'local_business_schema'   => false,
    'nap_consistency'         => [],
    'maps_presence'           => false,
    'featured_snippet_potential' => false,
    'people_also_ask'         => [],
    'related_searches'        => [],
    'favicon_url'             => $baseUrl . '/favicon.ico',
    'sitemap_valid'           => false,
    'sitemap_url'             => null,
    'sitemap_content'         => null,
    'sitemap_url_count'       => 0,
    'llms_txt_valid'          => false,
    'llms_txt_url'            => null,
    'llms_txt_content'        => null,
    'llms_txt_sections'       => [],
    'robots_sitemap_ref'      => null,
    'robots_sitemap_match'    => false,
    'tracking_tools'          => [],
    'is_document'             => false,
];

$links = $internalLinks = $externalLinks = $brokenLinks = [];

if ($html && strpos($contentType, 'text/html') !== false) {
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
    $xpath = new DOMXPath($dom);

    // ===== FAVICON =====
    $iconNode = $xpath->query('//link[contains(@rel,"icon")]/@href');
    if ($iconNode->length > 0) {
        $iconHref = trim($iconNode->item(0)->nodeValue);
        $seo['favicon_url'] = strpos($iconHref, 'http') === 0
            ? $iconHref
            : rtrim($baseUrl, '/') . '/' . ltrim($iconHref, '/');
    }

    // ===== TEXT / WORD COUNT =====
    $textContent       = preg_replace('/\s+/', ' ', strip_tags($html));
    $seo['word_count'] = str_word_count($textContent);

    // ===== SSL =====
    if (isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'https') {
        $seo['ssl_valid'] = true;
    } else {
        $seo['ssl_valid'] = false;
        $seo['warnings'][] = "Site is using HTTP — consider enabling HTTPS for production sites.";
    }

    // ===== MIXED CONTENT =====
    if ($seo['ssl_valid']) {
        $mc = [];
        foreach (['script' => 'src', 'link' => 'href', 'img' => 'src'] as $tag => $attr) {
            foreach ($dom->getElementsByTagName($tag) as $el) {
                $v = $el->getAttribute($attr);
                if ($v && strpos($v, 'http://') === 0) $mc[] = $v;
            }
        }
        $seo['mixed_content'] = array_slice(array_unique($mc), 0, 10);
        if (count($seo['mixed_content']) > 0) {
            $seo['issues'][] = count($seo['mixed_content']) . " mixed content item(s) found (HTTP resources on HTTPS page).";
            $seo['score'] -= 15;
        }
    }

    // ===== OPEN GRAPH =====
    $ogMap = ['og_title'=>'og:title','og_description'=>'og:description','og_image'=>'og:image','og_url'=>'og:url','og_type'=>'og:type'];
    foreach ($ogMap as $key => $prop) {
        $n = $xpath->query('//meta[@property="'.$prop.'"]/@content');
        if ($n->length > 0) $seo[$key] = trim($n->item(0)->nodeValue);
    }
    if (!$seo['og_title'])       $seo['warnings'][] = "Missing og:title — affects Facebook & LinkedIn sharing preview.";
    if (!$seo['og_description']) $seo['warnings'][] = "Missing og:description — no description shown on social share.";
    if (!$seo['og_image'])       $seo['warnings'][] = "Missing og:image — social shares will show no image. Using favicon as fallback.";

    // ===== TWITTER / X CARD =====
    $twMap = ['twitter_card'=>'twitter:card','twitter_title'=>'twitter:title','twitter_description'=>'twitter:description','twitter_image'=>'twitter:image'];
    foreach ($twMap as $key => $name) {
        $n = $xpath->query('//meta[@name="'.$name.'"]/@content');
        if ($n->length > 0) $seo[$key] = trim($n->item(0)->nodeValue);
    }

    // ===== TITLE =====
    $titleNodes = $dom->getElementsByTagName('title');
    if ($titleNodes->length > 0) {
        $seo['title']        = trim($titleNodes->item(0)->nodeValue);
        $seo['title_length'] = mb_strlen($seo['title']);
        if ($seo['title_length'] < 30) {
            $seo['warnings'][] = "Title too short ({$seo['title_length']} chars). Aim for 50–60 characters.";
            $seo['score'] -= 5;
        } elseif ($seo['title_length'] > 60) {
            $seo['issues'][] = "Title too long ({$seo['title_length']} chars). Keep under 60 characters to avoid truncation.";
            $seo['score'] -= 10;
        }
    } else {
        $seo['score'] -= 20;
        $seo['issues'][] = "Missing <title> tag — critical for SEO.";
    }

    // ===== META DESCRIPTION =====
    $descNode = $xpath->query('//meta[@name="description"]/@content');
    if ($descNode->length > 0) {
        $seo['description']        = trim($descNode->item(0)->nodeValue);
        $seo['description_length'] = mb_strlen($seo['description']);
        if ($seo['description_length'] < 120) {
            $seo['warnings'][] = "Meta description too short ({$seo['description_length']} chars). Aim for 150–160.";
            $seo['score'] -= 5;
        } elseif ($seo['description_length'] > 160) {
            $seo['warnings'][] = "Meta description too long ({$seo['description_length']} chars). Keep under 160.";
            $seo['score'] -= 5;
        }
    } else {
        $seo['score'] -= 20;
        $seo['issues'][] = "Missing meta description — hurts click-through rates from search results.";
    }

    // ===== META KEYWORDS =====
    $kwNode = $xpath->query('//meta[@name="keywords"]/@content');
    if ($kwNode->length > 0) {
        $seo['meta_keywords'] = trim($kwNode->item(0)->nodeValue);
    }

    // ===== ROBOTS META =====
    $robotsNode = $xpath->query('//meta[@name="robots"]/@content');
    if ($robotsNode->length > 0) {
        $seo['robots'] = $robotsNode->item(0)->nodeValue;
        if (strpos($seo['robots'], 'noindex') !== false) {
            $seo['issues'][] = "Page has 'noindex' directive — it won't appear in search results.";
            $seo['score'] -= 30;
        }
        if (strpos($seo['robots'], 'nofollow') !== false) {
            $seo['warnings'][] = "Page has 'nofollow' directive — search engines won't follow links.";
            $seo['score'] -= 10;
        }
    }

    // ===== VIEWPORT =====
    if ($xpath->query('//meta[@name="viewport"]')->length > 0) {
        $seo['viewport'] = true;
    } else {
        $seo['warnings'][] = "Missing viewport meta tag — page is not mobile responsive.";
        $seo['score'] -= 5;
    }

    // ===== CANONICAL =====
    $canonicalNode = $xpath->query('//link[@rel="canonical"]/@href');
    if ($canonicalNode->length > 0) {
        $seo['canonical'] = $canonicalNode->item(0)->nodeValue;
        $seo['canonical_valid'] = rtrim(strtolower($seo['canonical']), '/') === rtrim($url, '/');
        if (!$seo['canonical_valid']) {
            $seo['warnings'][] = "Canonical URL doesn't match page URL — may confuse search engines.";
            $seo['score'] -= 5;
        }
    } else {
        $seo['warnings'][] = "No canonical tag — consider adding one to prevent duplicate content issues.";
        $seo['score'] -= 5;
    }

    // ===== HEADINGS =====
    foreach (['h1','h2','h3','h4'] as $h) {
        $seo[$h.'_count'] = $dom->getElementsByTagName($h)->length;
    }
    if ($seo['h1_count'] === 0) { $seo['issues'][] = "Missing H1 heading — required for page structure."; $seo['score'] -= 15; }
    elseif ($seo['h1_count'] > 1) { $seo['issues'][] = "Multiple H1 tags ({$seo['h1_count']}) detected — use exactly one per page."; $seo['score'] -= 10; }
    if ($seo['h2_count'] === 0 && $seo['word_count'] > 300) {
        $seo['warnings'][] = "No H2 headings found — use H2 tags to structure long content.";
        $seo['score'] -= 5;
    }

    // ===== IMAGES =====
    $images = $dom->getElementsByTagName('img');
    $seo['total_images'] = $images->length;
    foreach ($images as $img) {
        if (!$img->hasAttribute('alt') || empty(trim($img->getAttribute('alt')))) $seo['img_no_alt']++;
        else $seo['img_with_alt']++;
    }
    if ($seo['img_no_alt'] > 0) {
        $seo['score'] -= min(15, $seo['img_no_alt'] * 3);
        $seo['issues'][] = "{$seo['img_no_alt']} image(s) missing alt text — bad for accessibility and SEO.";
    }

    // ===== SCHEMA.ORG =====
    $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
    $seo['schema_count'] = $scriptNodes->length;
    $seenTypes = [];
    foreach ($scriptNodes as $script) {
        $data = json_decode($script->nodeValue, true);
        if ($data && isset($data['@type'])) {
            $type = is_array($data['@type']) ? implode(', ', $data['@type']) : $data['@type'];
            if (!in_array($type, $seenTypes)) { $seenTypes[] = $type; $seo['schema_types'][] = $type; }
        }
    }
    $extraTypes = ['Product','Article','LocalBusiness','Organization','Person','Event','Recipe','Review','FAQPage','BreadcrumbList'];
    foreach ($extraTypes as $t) {
        if ((strpos($html, '"@type":"'.$t.'"') !== false || strpos($html, '"@type": "'.$t.'"') !== false)
            && !in_array($t, $seo['schema_types'])) {
            $seo['schema_types'][] = $t;
        }
    }
    if ($seo['schema_count'] === 0) {
        $seo['suggestions'][] = "No Schema.org structured data found — add it for rich snippets in search.";
    } else {
        $seo['score'] += min(10, $seo['schema_count'] * 2);
    }
    if (in_array('LocalBusiness', $seo['schema_types']) || strpos($html, 'LocalBusiness') !== false) {
        $seo['local_business_schema'] = true;
    }

    // ===== KEYWORD DENSITY =====
    $metaKwList = [];
    if (!empty($seo['meta_keywords'])) {
        foreach (preg_split('/[\s,]+/', strtolower($seo['meta_keywords'])) as $mk) {
            $mk = trim($mk);
            if (strlen($mk) > 2) $metaKwList[$mk] = true;
        }
    }
    $stopWords = ['the','and','for','that','this','with','from','have','are','was','were','but','not','you','your',
                  'can','will','all','about','get','has','had','how','what','when','where','who','which','why',
                  'its','our','more','also','into','been','they','them','their','than','then','even','some',
                  'just','over','such','after','before','between','through','during','each','both'];
    $bodyWords = str_word_count(strtolower($textContent), 1);
    $freq = [];
    foreach ($bodyWords as $w) {
        if (strlen($w) > 3 && !in_array($w, $stopWords)) {
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
    }
    $titleWords = [];
    if (!empty($seo['title'])) {
        foreach (str_word_count(strtolower($seo['title']), 1) as $tw) {
            if (strlen($tw) > 3 && !in_array($tw, $stopWords)) $titleWords[$tw] = true;
        }
    }
    $descWords = [];
    if (!empty($seo['description']) && $seo['description'] !== 'Missing Meta Description') {
        foreach (str_word_count(strtolower($seo['description']), 1) as $dw) {
            if (strlen($dw) > 3 && !in_array($dw, $stopWords)) $descWords[$dw] = true;
        }
    }
    $ranked = $freq;
    foreach ($metaKwList as $mk => $_) { if (isset($ranked[$mk])) $ranked[$mk] *= 2; }
    foreach ($descWords as $dk => $_)  { if (isset($ranked[$dk])) $ranked[$dk] *= 3; }
    foreach ($titleWords as $tk => $_) { if (isset($ranked[$tk])) $ranked[$tk] *= 5; }
    arsort($ranked);
    $topKw = array_slice($ranked, 0, 20, true);
    $total = count($bodyWords);
    foreach ($topKw as $kw => $boostedCount) {
        $realCount = $freq[$kw] ?? 0;
        $pct = $total > 0 ? round(($realCount / $total) * 100, 2) : 0;
        $source = 'body';
        if (isset($titleWords[$kw])) $source = 'title';
        elseif (isset($descWords[$kw])) $source = 'description';
        elseif (isset($metaKwList[$kw])) $source = 'meta';
        $seo['keyword_density'][] = [
            'keyword'    => $kw,
            'count'      => $realCount,
            'percentage' => $pct,
            'in_meta'    => isset($metaKwList[$kw]),
            'in_title'   => isset($titleWords[$kw]),
            'in_desc'    => isset($descWords[$kw]),
            'source'     => $source,
        ];
    }

    // ===== ROBOTS.TXT =====
    $robotsUrl = $baseUrl . '/robots.txt';
    $chR = curl_init();
    curl_setopt_array($chR, [CURLOPT_URL=>$robotsUrl, CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0, CURLOPT_TIMEOUT=>5]);
    $rc = curl_exec($chR);
    $rCode = curl_getinfo($chR, CURLINFO_HTTP_CODE);
    curl_close($chR);
    if ($rCode === 200) {
        $seo['robots_txt_valid']   = true;
        $seo['robots_txt_content'] = substr($rc, 0, 600);
        if (strpos($rc, 'Disallow: /') !== false && strpos($rc, 'Disallow: /wp-admin') === false) {
            $seo['issues'][] = "robots.txt may be blocking all crawlers with 'Disallow: /'";
            $seo['score'] -= 20;
        }
    } else {
        $seo['robots_txt_valid'] = false;
        $seo['warnings'][] = "robots.txt not found — consider creating one at /robots.txt.";
    }

    // ===== SITEMAP.XML =====
    $sitemapCandidates = [$baseUrl . '/sitemap.xml', $baseUrl . '/sitemap_index.xml'];
    $robotsSitemapRef  = null;
    if (!empty($rc)) {
        preg_match_all('/^Sitemap:\s*(.+)$/im', $rc, $smMatches);
        foreach ($smMatches[1] as $smUrl) {
            $smUrl = trim($smUrl);
            if ($smUrl && !in_array($smUrl, $sitemapCandidates)) $sitemapCandidates[] = $smUrl;
            if ($smUrl && !$robotsSitemapRef) $robotsSitemapRef = $smUrl;
        }
    }
    $seo['robots_sitemap_ref'] = $robotsSitemapRef;
    foreach ($sitemapCandidates as $smCandidate) {
        $chSm = curl_init();
        curl_setopt_array($chSm, [CURLOPT_URL=>$smCandidate, CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0, CURLOPT_TIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>true]);
        curl_exec($chSm);
        $smCode = curl_getinfo($chSm, CURLINFO_HTTP_CODE);
        curl_close($chSm);
        if ($smCode >= 200 && $smCode < 400) {
            $seo['sitemap_valid'] = true;
            $seo['sitemap_url']   = $smCandidate;
            if ($robotsSitemapRef) {
                $seo['robots_sitemap_match'] = (rtrim(strtolower($robotsSitemapRef),'/') === rtrim(strtolower($smCandidate),'/'));
            }
            break;
        }
    }
    if (!$seo['sitemap_valid']) {
        $seo['warnings'][] = 'No sitemap.xml found — submit one in Google Search Console for better indexing.';
    }

    // ===== LLMS.TXT =====
    $llmsUrl = $baseUrl . '/llms.txt';
    $chLlms  = curl_init();
    curl_setopt_array($chLlms, [CURLOPT_URL=>$llmsUrl, CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0, CURLOPT_TIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>true]);
    curl_exec($chLlms);
    $llmsCode = curl_getinfo($chLlms, CURLINFO_HTTP_CODE);
    curl_close($chLlms);
    if ($llmsCode >= 200 && $llmsCode < 400) {
        $seo['llms_txt_valid'] = true;
        $seo['llms_txt_url']   = $llmsUrl;
    }

    // ===== TRACKING TOOLS =====
    $trackingDefs = [
        ['id'=>'ga4','name'=>'Google Analytics 4','category'=>'Analytics','color'=>'#e37400','icon'=>'bar-chart-2',
         'regex'=>['/googletagmanager\.com\/gtag\/js\?id=G-[A-Z0-9]+/i','/gtag\s*\(\s*[\'"]config[\'"]\s*,\s*[\'"]G-[A-Z0-9]{4,}[\'"]/i']],
        ['id'=>'ua','name'=>'Universal Analytics (UA)','category'=>'Analytics','color'=>'#f59e0b','icon'=>'trending-up',
         'regex'=>['/google-analytics\.com\/analytics\.js/i','/ga\s*\(\s*[\'"]create[\'"]\s*,\s*[\'"]UA-[0-9]+-[0-9]+[\'"]/i','/_gaq\.push\s*\(\s*\[\s*[\'"]_setAccount[\'"]/i']],
        ['id'=>'gtm','name'=>'Google Tag Manager','category'=>'Tag Manager','color'=>'#4285f4','icon'=>'tag',
         'regex'=>['/googletagmanager\.com\/gtm\.js\?id=GTM-[A-Z0-9]+/i','/googletagmanager\.com\/ns\.html\?id=GTM-/i','/\(function\s*\(w,d,s,l,i\)\s*\{w\[l\]=w\[l\]\|\|\[\]/i']],
        ['id'=>'segment','name'=>'Segment','category'=>'Analytics','color'=>'#52bd95','icon'=>'git-branch',
         'regex'=>['/cdn\.segment\.com\/analytics\.js/i','/analytics\.load\s*\(\s*[\'"][a-zA-Z0-9]{10,}[\'"]/i']],
        ['id'=>'mixpanel','name'=>'Mixpanel','category'=>'Analytics','color'=>'#7856ff','icon'=>'activity',
         'regex'=>['/cdn\.mxpnl\.com/i','/cdn\.mixpanel\.com/i','/mixpanel\.init\s*\(\s*[\'"][a-f0-9]{20,}[\'"]/i']],
        ['id'=>'fbpixel','name'=>'Meta / Facebook Pixel','category'=>'Advertising','color'=>'#0866ff','icon'=>'facebook',
         'regex'=>['/connect\.facebook\.net\/[a-z_]+\/fbevents\.js/i','/fbq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"]\d{10,}[\'"]/i']],
        ['id'=>'googleads','name'=>'Google Ads','category'=>'Advertising','color'=>'#34a853','icon'=>'megaphone',
         'regex'=>['/googleadservices\.com\/pagead\/conversion/i','/gtag\s*\(\s*[\'"]config[\'"]\s*,\s*[\'"]AW-[0-9]{7,}[\'"]/i','/googlesyndication\.com\/pagead\/js\/adsbygoogle\.js/i']],
        ['id'=>'tiktok','name'=>'TikTok Pixel','category'=>'Advertising','color'=>'#010101','icon'=>'music',
         'regex'=>['/analytics\.tiktok\.com\/i18n\/pixel\/events\.js/i','/ttq\.load\s*\(\s*[\'"][A-Z0-9]{10,}[\'"]/i']],
        ['id'=>'linkedin','name'=>'LinkedIn Insight','category'=>'Advertising','color'=>'#0a66c2','icon'=>'linkedin',
         'regex'=>['/snap\.licdn\.com\/li\.lms-analytics\/insight\.min\.js/i','/_linkedin_partner_id\s*=\s*[\'"]?\d{4,}/i']],
        ['id'=>'twitter','name'=>'X / Twitter Pixel','category'=>'Advertising','color'=>'#000000','icon'=>'twitter',
         'regex'=>['/static\.ads-twitter\.com\/uwt\.js/i','/twq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"][a-z0-9]{5,}[\'"]/i']],
        ['id'=>'pinterest','name'=>'Pinterest Tag','category'=>'Advertising','color'=>'#e60023','icon'=>'image',
         'regex'=>['/ct\.pinterest\.com\/v3\/\?tid=/i','/pintrk\s*\(\s*[\'"]load[\'"]\s*,\s*[\'"]\d{10,}[\'"]/i','/s\.pinimg\.com\/ct\/core\.js/i']],
        ['id'=>'snapchat','name'=>'Snapchat Pixel','category'=>'Advertising','color'=>'#FFFC00','icon'=>'zap',
         'regex'=>['/sc-static\.net\/scevent\.min\.js/i','/snaptr\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"][a-f0-9\-]{30,}[\'"]/i','/tr\.snapchat\.com\/p\.svg\?id=/i']],
        ['id'=>'hotjar','name'=>'Hotjar','category'=>'Heatmap / UX','color'=>'#fd3a5c','icon'=>'flame',
         'regex'=>['/static\.hotjar\.com\/c\/hotjar-\d+\.js/i','/hjid\s*:\s*\d+\s*,\s*hjsv\s*:/i']],
        ['id'=>'clarity','name'=>'Microsoft Clarity','category'=>'Heatmap / UX','color'=>'#0067b8','icon'=>'eye',
         'regex'=>['/www\.clarity\.ms\/tag\/[a-z0-9]+/i','/clarity\s*\(\s*[\'"]set[\'"]/i']],
        ['id'=>'intercom','name'=>'Intercom','category'=>'Chat / Support','color'=>'#286efa','icon'=>'message-circle',
         'regex'=>['/widget\.intercom\.io\/widget\/[a-z0-9]+/i','/intercomSettings\s*=\s*\{/i','/Intercom\s*\(\s*[\'"]boot[\'"]/i']],
        ['id'=>'crisp','name'=>'Crisp','category'=>'Chat / Support','color'=>'#1fb980','icon'=>'message-square',
         'regex'=>['/client\.crisp\.chat\/l\.js/i','/CRISP_WEBSITE_ID\s*=\s*[\'"][a-f0-9\-]{30,}[\'"]/i']],
        ['id'=>'zendesk','name'=>'Zendesk','category'=>'Chat / Support','color'=>'#03363d','icon'=>'headphones',
         'regex'=>['/static\.zdassets\.com\/ekr\/snippet\.js/i','/zE\s*\(\s*[\'"]webWidget[\'"]/i']],
        ['id'=>'hubspot','name'=>'HubSpot','category'=>'CRM / Marketing','color'=>'#ff7a59','icon'=>'hub',
         'regex'=>['/js\.hs-scripts\.com\/\d{5,}\.js/i','/js\.hsforms\.net\/forms\/v2\.js/i','/_hsq\s*=\s*window\._hsq\s*\|\|/i']],
    ];

    $detectedTools = [];
    foreach ($trackingDefs as $tool) {
        $found = false;
        foreach ($tool['regex'] as $pattern) {
            if (@preg_match($pattern, $html)) { $found = true; break; }
        }
        $detectedTools[] = [
            'id' => $tool['id'], 'name' => $tool['name'], 'category' => $tool['category'],
            'color' => $tool['color'], 'icon' => $tool['icon'], 'detected' => $found,
        ];
    }
    $seo['tracking_tools'] = $detectedTools;

    // ===== SERP PREVIEW =====
    $serpTitle = htmlspecialchars(mb_substr($seo['title'], 0, 60));
    $serpDesc  = htmlspecialchars(mb_substr($seo['description'], 0, 160));
    $serpHost  = htmlspecialchars($baseDomain);
    $seo['serp_preview'] = "
<div style='font-family: Arial, sans-serif; padding: 16px 20px; background:#fff; border: 1px solid #dfe1e5; border-radius: 8px; line-height:1.4;'>
    <div style='color:#202124; font-size:12px; margin-bottom:2px; display:flex; align-items:center; gap:8px;'>
        <img src='{$seo['favicon_url']}' width='16' height='16' style='border-radius:2px;' onerror=\"this.style.display='none'\">
        <span>{$serpHost}</span>
    </div>
    <div style='color:#1a0dab; font-size:20px; margin:2px 0 4px; font-weight:normal; cursor:pointer;'>{$serpTitle}</div>
    <div style='color:#4d5156; font-size:14px;'>{$serpDesc}</div>
</div>";

    // ===== FEATURED SNIPPET =====
    $seo['featured_snippet_potential'] = strpos($html,'<ul') !== false || strpos($html,'<ol') !== false || strpos($html,'<table') !== false || strpos($html,'<dl') !== false;
    if ($seo['featured_snippet_potential']) {
        $seo['suggestions'][] = "Page has featured snippet potential — structured content (lists/tables) detected.";
    }

    // ===== NAP CONSISTENCY =====
    preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $html, $emails);
    $seo['nap_consistency'] = ['emails' => array_unique(array_slice($emails[0], 0, 3))];

    // ===== GOOGLE MAPS =====
    $seo['maps_presence'] = strpos($html, 'maps.google.com') !== false || strpos($html, 'google.com/maps') !== false;

    // ===== PEOPLE ALSO ASK =====
    $ts = mb_substr($seo['title'], 0, 25);
    $seo['people_also_ask'] = [
        "What is {$ts}?", "How to use {$ts}?", "Benefits of {$ts}?",
        "Is {$ts} worth it?", "How much does {$ts} cost?",
    ];

    // ===== RELATED SEARCHES =====
    $tw = explode(' ', mb_substr($seo['title'], 0, 50));
    if (count($tw) > 2) {
        $seo['related_searches'] = [
            ($tw[0]??'') . ' ' . ($tw[1]??'') . ' guide',
            'best ' . ($tw[0]??'') . ' ' . ($tw[1]??''),
            ($tw[0]??'') . ' vs alternatives',
            'how to ' . ($tw[0]??'') . ' ' . ($tw[1]??''),
        ];
    }

    // ===== LINK EXTRACTION =====
    // Collect all document and HTML extensions for routing
    global $docExtensions, $imageExtensions, $staticExtensions;
    $allDocExts    = array_keys($docExtensions);
    $skipExts      = array_merge($imageExtensions, $staticExtensions);

    foreach ($dom->getElementsByTagName('a') as $node) {
        $href = $node->getAttribute('href');
        if (!$href || preg_match('/^(javascript|mailto|tel|#)/i', $href)) continue;

        // Build absolute URL
        if (strpos($href, 'http') !== 0) {
            if (strpos($href, '//') === 0) {
                $href = ($parsedUrl['scheme'] ?? 'https') . ':' . $href;
            } else {
                $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
            }
        }

        // Strip fragment, normalize
        $href = strtok($href, '#');
        if (!$href) continue;
        $href = rtrim($href, '/');

        if (preg_match('/^(https?:\/\/)([^\/]+)(.*)/i', $href, $hm)) {
            $clean = strtolower($hm[1]) . strtolower($hm[2]) . $hm[3];
        } else {
            $clean = $href;
        }

        $ext       = getUrlExtension($clean);
        $cleanLower = strtolower($clean);

        // Skip pure image/static assets — don't crawl or list
        if (in_array($ext, $skipExts)) continue;

        // Determine if internal
        $isInternal = $baseDomain && strpos($cleanLower, strtolower($baseDomain)) !== false
                      && strpos($cleanLower, strtolower($baseUrl)) === 0;

        if ($isInternal) {
            // Include both extensionless pages AND known document extensions
            if ($ext === '' || in_array($ext, $allDocExts) || preg_match('/^[a-z0-9]{1,5}$/', $ext)) {
                // Exclude query-only URLs for deduplication but keep them if unique
                $internalLinks[] = $clean;
                $links[] = $clean;
            }
        } elseif (strpos($cleanLower, 'http') === 0) {
            $externalLinks[] = $clean;
        }
    }

    $seo['internal_links'] = count(array_unique($internalLinks));
    $seo['external_links'] = count(array_unique($externalLinks));

    // ===== BROKEN LINKS =====
    $checkLinks = array_slice(array_unique($externalLinks), 0, 15);
    $mh = curl_multi_init();
    $handles = [];
    foreach ($checkLinks as $i => $cl) {
        $ch2 = curl_init();
        curl_setopt_array($ch2, [CURLOPT_URL=>$cl, CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>0, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>2]);
        $handles[$i] = $ch2;
        curl_multi_add_handle($mh, $ch2);
    }
    do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running);
    foreach ($handles as $i => $ch2) {
        if (curl_getinfo($ch2, CURLINFO_HTTP_CODE) >= 400) $brokenLinks[] = $checkLinks[$i];
        curl_multi_remove_handle($mh, $ch2);
        curl_close($ch2);
    }
    curl_multi_close($mh);
    $seo['broken_links'] = $brokenLinks;

    // ===== PAGE SIZE =====
    if ($seo['page_size_kb'] > 2000) {
        $seo['warnings'][] = "Page size is large ({$seo['page_size_kb']} KB) — optimize images and minify code.";
    }
}

$urlPath = parse_url($url, PHP_URL_PATH);
$depth   = substr_count(trim($urlPath ?? '', '/'), '/');
$priority = max(0.1, 1.0 - ($depth * 0.1));
$seo['score'] = max(0, min(100, $seo['score']));

echo json_encode([
    'url'         => $url,
    'seo'         => $seo,
    'links'       => array_values(array_unique($links)),
    'priority'    => $priority,
    'change_freq' => determineChangeFrequency($url, $depth),
    'depth'       => $depth,
]);
exit;