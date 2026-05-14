<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║   Resource Downloader — Production Edition v3.0              ║
 * ║   Deep Scanner · CSS/JS Parsing · Smart CDN · Full Retry    ║
 * ╚══════════════════════════════════════════════════════════════╝
 */

// ─── CRITICAL: Set execution time to 1 hour ──────────────────────────────────
// Must be at the VERY TOP before any output or processing
ini_set('max_execution_time', 3600);     // 1 hour (3600 seconds)
ini_set('max_input_time', 3600);         // 1 hour for input parsing
ini_set('memory_limit', '512M');         // Increase memory limit for large pages
set_time_limit(3600);                    // PHP function to set time limit
ignore_user_abort(true);                 // Continue even if browser disconnects

// For CLI mode, remove time limit completely
if (php_sapi_name() === 'cli') {
    set_time_limit(0);                   // Unlimited for CLI
    ini_set('max_execution_time', 0);    // Unlimited for CLI
}

// ─── Tunables ────────────────────────────────────────────────────────────────
define('CONCURRENCY',     12);               // parallel downloads at once
define('MAX_RETRIES',      3);               // retries per file (non-429 errors)
define('MAX_FILE_SIZE',  200 * 1024 * 1024); // 200 MB hard cap
define('CONNECT_TIMEOUT',  10);
define('DL_TIMEOUT',       90);
define('PAGE_TIMEOUT',     30);
define('DEFAULT_OUTPUT',  'downloaded');
define('UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
define('DEEP_SCAN', true);
define('MAX_DEEP_SCAN_SIZE', 5 * 1024 * 1024);
define('SCAN_CSS_FILES', true);
define('SCAN_JS_FILES', true);

// ─── Allowed extensions ──────────────────────────────────────────────────────
const ALLOWED_EXT = [
    // Documents
    'pdf','doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp','txt','rtf','csv','md','epub','mobi',
    // Images
    'jpg','jpeg','png','gif','svg','webp','ico','bmp','tiff','tif','avif','heic','heif','psd','ai','eps','raw','cr2','nef',
    // Audio
    'mp3','wav','ogg','flac','aac','m4a','wma','opus','mid','midi',
    // Video
    'mp4','avi','mov','mkv','webm','m4v','wmv','flv','3gp','mpeg','mpg',
    // Archives
    'zip','rar','tar','gz','7z','bz2','xz','zst','lz','lz4',
    // Code / Web
    'js','mjs','cjs','ts','jsx','tsx','css','scss','sass','less','styl',
    'html','htm','xml','json','yaml','yml','toml','env','ini','conf',
    'php','py','rb','java','c','cpp','h','hpp','go','rs','sh','bash','pl','swift','kt',
    // Fonts
    'woff','woff2','ttf','eot','otf','fon',
    // Data / Misc
    'sql','db','sqlite','graphql','pem','crt','key','map','wasm','bin','dat','dll','so','dylib',
];

// File extensions that should be deep-scanned for nested resources
const DEEP_SCAN_EXTENSIONS = [
    'css', 'scss', 'sass', 'less', 'styl',
    'js', 'mjs', 'cjs', 'ts', 'jsx', 'tsx',
    'html', 'htm', 'xhtml', 'php',
];

// ─── Time Tracking & Safety ──────────────────────────────────────────────────
define('SCRIPT_START_TIME', microtime(true));
define('MAX_SCRIPT_RUNTIME', 3540); // 59 minutes (safety margin from 1 hour)

// Safety check function to prevent timeout
function check_timeout(): void {
    $elapsed = microtime(true) - SCRIPT_START_TIME;
    if ($elapsed > MAX_SCRIPT_RUNTIME) {
        log_msg("⚠️ Maximum script runtime approaching (59 min). Gracefully stopping...", 'warn');
        throw new RuntimeException("Script timeout safety reached");
    }
}

// ─── Logging ─────────────────────────────────────────────────────────────────
$isCli = (php_sapi_name() === 'cli');

function log_msg(string $msg, string $level = 'info'): void {
    global $isCli;
    if ($isCli) {
        static $c = [
            'info'=>"\033[36m",'ok'=>"\033[32m",'warn'=>"\033[33m",
            'err'=>"\033[31m",'head'=>"\033[35m",'dim'=>"\033[90m",'retry'=>"\033[93m",
            'cdn'=>"\033[96m",'debug'=>"\033[37m",'scan'=>"\033[94m",
        ];
        static $i = [
            'info'=>'→','ok'=>'✓','warn'=>'⚠','err'=>'✗','head'=>'◆','dim'=>'·',
            'retry'=>'↺','cdn'=>'⇢','debug'=>'🔍','scan'=>'🔎',
        ];
        echo ($c[$level]??"\033[0m") . ($i[$level]??'·') . " $msg\033[0m\n";
    } else {
        static $icons = [
            'info'=>'→','ok'=>'✓','warn'=>'⚠','err'=>'✗','head'=>'◆','dim'=>'·',
            'retry'=>'↺','cdn'=>'⇢','debug'=>'🔍','scan'=>'🔎',
        ];
        echo '<div class="log log-'.$level.'"><span>'.($icons[$level]??'·').'</span> '
             .htmlspecialchars($msg)."</div>\n";
        ob_flush(); flush();
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function fmt_size(int $b): string {
    if ($b>=1073741824) return round($b/1073741824,2).' GB';
    if ($b>=1048576)    return round($b/1048576,2).' MB';
    if ($b>=1024)       return round($b/1024,1).' KB';
    return $b.' B';
}

function fmt_time(float $s): string {
    if ($s<60)   return round($s,1).'s';
    if ($s<3600) return floor($s/60).'m '.($s%60).'s';
    return floor($s/3600).'h '.floor(($s%3600)/60).'m';
}

function resolve_url(string $url, string $base): ?string {
    // Normalize the URL first
    $url = trim($url);
    
    // Skip invalid URLs
    if (empty($url) || str_starts_with($url,'#') || str_starts_with($url,'data:')
              || str_starts_with($url,'mailto:') || str_starts_with($url,'javascript:')
              || str_starts_with($url,'tel:') || str_starts_with($url,'sms:')
              || str_starts_with($url,'blob:') || str_starts_with($url,'filesystem:')) {
        return null;
    }
    
    // Remove HTML entities and decode
    $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Handle escaped characters
    $url = str_replace(['\\/', '\\"', "\\'", '\\\\'], ['/', '"', "'", '\\'], $url);
    $url = stripslashes($url);
    
    // Remove fragment identifiers
    $hashPos = strpos($url, '#');
    if ($hashPos !== false) {
        $url = substr($url, 0, $hashPos);
    }
    
    // Handle query string in a way that preserves important params
    $queryPos = strpos($url, '?');
    $query = '';
    if ($queryPos !== false) {
        $query = substr($url, $queryPos);
        $url = substr($url, 0, $queryPos);
    }
    
    // Already absolute URL
    if (preg_match('#^https?://#i', $url)) {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) return null;
        
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        
        // Normalize path
        $path = str_replace('//', '/', $path);
        
        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }
    
    // Parse base URL
    $baseParts = parse_url($base);
    if (!$baseParts || !isset($baseParts['host'])) return null;
    
    $scheme = strtolower($baseParts['scheme'] ?? 'https');
    $host = strtolower($baseParts['host']);
    $port = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';
    $root = "{$scheme}://{$host}{$port}";
    
    // Protocol-relative URL
    if (str_starts_with($url, '//')) {
        return $scheme . ':' . $url;
    }
    
    // Root-relative URL
    if (str_starts_with($url, '/')) {
        $url = '/' . ltrim($url, '/');
        return $root . $url . $query;
    }
    
    // Relative URL - resolve against base path
    $basePath = $baseParts['path'] ?? '/';
    
    // If base path ends with a file extension, go to parent directory
    $pathInfo = pathinfo($basePath);
    $isFile = isset($pathInfo['extension']) && $pathInfo['extension'] !== '';
    
    if ($isFile) {
        $baseDir = dirname($basePath);
    } else {
        $baseDir = rtrim($basePath, '/');
    }
    
    // Ensure directory ends with /
    $baseDir = rtrim($baseDir, '/') . '/';
    
    // Resolve relative path components
    $resolved = $baseDir . $url;
    $parts = explode('/', $resolved);
    $absolutes = [];
    
    foreach ($parts as $part) {
        if ($part === '.' || $part === '') continue;
        if ($part === '..') {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    
    $resolved = '/' . implode('/', $absolutes);
    $resolved = str_replace('//', '/', $resolved);
    
    return $root . $resolved . $query;
}

function safe_seg(string $s): string {
    $s = strtok($s, '?#');
    $s = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $s);
    return $s ?: '_';
}

function local_path(string $url, string $outDir): string {
    $p = parse_url($url);
    $host = safe_seg($p['host'] ?? 'unknown');
    $path = trim($p['path'] ?? 'index', '/') ?: 'index';
    
    $pathInfo = pathinfo($path);
    if (!isset($pathInfo['extension']) || empty($pathInfo['extension'])) {
        $path = rtrim($path, '/') . '/index.dat';
    }
    
    $segs = array_map('safe_seg', explode('/', $path));
    $filename = array_pop($segs);
    
    if (!pathinfo($filename, PATHINFO_EXTENSION)) {
        $filename .= '.dat';
    }
    $segs[] = $filename;
    
    return $outDir . DIRECTORY_SEPARATOR . $host . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segs);
}

function allowed(string $url): bool {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    if (empty($extension)) return true;
    
    return in_array($extension, ALLOWED_EXT, true);
}

function is_deep_scannable(string $url): bool {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($extension, DEEP_SCAN_EXTENSIONS, true);
}

// ─── CSS/JS Deep Scanner ────────────────────────────────────────────────────
function scan_css_for_urls(string $cssContent, string $baseUrl): array {
    $urls = [];
    
    // Match url() functions with various quote styles
    if (preg_match_all('/url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match @import statements
    if (preg_match_all('/@import\s+(?:url\(\s*)?["\']([^"\'()\s]+)["\']\s*\)?/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match src() in @font-face
    if (preg_match_all('/src\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match background, background-image properties
    if (preg_match_all('/background(?:-image)?\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match filter: url()
    if (preg_match_all('/filter\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match cursor: url()
    if (preg_match_all('/cursor\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match mask and -webkit-mask
    if (preg_match_all('/(?:-webkit-)?mask(?:-image)?\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match sourceMappingURL in CSS
    if (preg_match_all('/\/[*@]#\s*sourceMappingURL=([^\s*]+)/i', $cssContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    return array_unique($urls);
}

function scan_js_for_urls(string $jsContent, string $baseUrl): array {
    $urls = [];
    
    // Match import statements
    if (preg_match_all('/import\s+(?:[\w*\s{},]*from\s+)?["\']([^"\']+)["\']/m', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match dynamic imports
    if (preg_match_all('/import\s*\(\s*["\']([^"\']+)["\']\s*\)/', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match require() calls
    if (preg_match_all('/require\s*\(\s*["\']([^"\']+)["\']\s*\)/', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match sourceMappingURL
    if (preg_match_all('/\/\/[@#]\s*sourceMappingURL=([^\s]+)/i', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match Webpack chunk loading
    if (preg_match_all('/__webpack_public_path__\s*\+\s*["\']([^"\']+)["\']/', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match lazy loading patterns
    if (preg_match_all('/["\']((?:https?:)?\/\/[^"\']+\.(?:chunk|bundle|vendor|app)\.[a-f0-9]+\.js)["\']/i', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match worker constructors
    if (preg_match_all('/new\s+Worker\s*\(\s*["\']([^"\']+)["\']\s*\)/', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match service worker registration
    if (preg_match_all('/navigator\.serviceWorker\.register\s*\(\s*["\']([^"\']+)["\']\s*\)/', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            $abs = resolve_url($url, $baseUrl);
            if ($abs && allowed($abs)) {
                $urls[] = $abs;
            }
        }
    }
    
    // Match image/asset URLs in JavaScript
    if (preg_match_all('/["\']([^"\']*\.(?:png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot))["\']/i', $jsContent, $matches)) {
        foreach ($matches[1] as $url) {
            if (str_starts_with($url, 'http') || str_starts_with($url, '/') || str_starts_with($url, './')) {
                $abs = resolve_url($url, $baseUrl);
                if ($abs && allowed($abs)) {
                    $urls[] = $abs;
                }
            }
        }
    }
    
    return array_unique($urls);
}

function deep_scan_file(string $filePath, string $baseUrl): array {
    $foundUrls = [];
    
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return $foundUrls;
    }
    
    $fileSize = filesize($filePath);
    if ($fileSize > MAX_DEEP_SCAN_SIZE || $fileSize === 0) {
        return $foundUrls;
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) return $foundUrls;
    
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'css':
        case 'scss':
        case 'sass':
        case 'less':
        case 'styl':
            if (SCAN_CSS_FILES) {
                $foundUrls = scan_css_for_urls($content, $baseUrl);
                log_msg("Deep scanned CSS: " . basename($filePath) . " → found " . count($foundUrls) . " resources", 'scan');
            }
            break;
            
        case 'js':
        case 'mjs':
        case 'cjs':
        case 'ts':
        case 'jsx':
        case 'tsx':
            if (SCAN_JS_FILES) {
                $foundUrls = scan_js_for_urls($content, $baseUrl);
                log_msg("Deep scanned JS: " . basename($filePath) . " → found " . count($foundUrls) . " resources", 'scan');
            }
            break;
            
        case 'html':
        case 'htm':
        case 'xhtml':
        case 'php':
            // For HTML files, use the same extract_links function
            $foundUrls = extract_links($content, $baseUrl);
            log_msg("Deep scanned HTML: " . basename($filePath) . " → found " . count($foundUrls) . " resources", 'scan');
            break;
    }
    
    return $foundUrls;
}

// ─── Page fetch ───────────────────────────────────────────────────────────────
function fetch_page(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT => PAGE_TIMEOUT,
        CURLOPT_USERAGENT => UA,
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ],
        CURLOPT_REFERER => $url,
        CURLOPT_AUTOREFERER => true,
    ]);
    
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    curl_close($ch);
    
    return compact('body', 'err', 'code', 'final', 'contentType');
}

function extract_links(string $html, string $baseUrl): array {
    $patterns = [
        // Standard HTML attributes
        '/\bhref\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bsrc\s*=\s*["\']([^"\'<>]+)["\']/i',
        
        // Lazy loading and data attributes
        '/\bdata-src\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-href\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-original\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-lazy-src\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-srcset\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-background\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-bg\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-image\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-desktop\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bdata-mobile\s*=\s*["\']([^"\'<>]+)["\']/i',
        
        // Video/Audio sources
        '/<source[^>]+src=["\']([^"\']+)["\']/i',
        '/<source[^>]+srcset=["\']([^"\']+)["\']/i',
        '/<video[^>]+poster=["\']([^"\']+)["\']/i',
        
        // Object and embed tags
        '/<object[^>]+data=["\']([^"\']+)["\']/i',
        '/<embed[^>]+src=["\']([^"\']+)["\']/i',
        
        // Meta tags
        '/<meta\s+property="og:image"\s+content="([^"]+)"/i',
        '/<meta\s+property="og:image:url"\s+content="([^"]+)"/i',
        '/<meta\s+property="og:image:secure_url"\s+content="([^"]+)"/i',
        '/<meta\s+name="twitter:image"\s+content="([^"]+)"/i',
        '/<meta\s+name="twitter:image:src"\s+content="([^"]+)"/i',
        '/<meta\s+name="thumbnail"\s+content="([^"]+)"/i',
        '/<meta\s+itemprop="image"\s+content="([^"]+)"/i',
        
        // Link tags
        '/<link[^>]+href=["\']([^"\']+)["\']/i',
        
        // Script and Style tags
        '/<script[^>]+src=["\']([^"\']+)["\']/i',
        
        // SVG image elements
        '/<image[^>]+href=["\']([^"\']+)["\']/i',
        '/<image[^>]+xlink:href=["\']([^"\']+)["\']/i',
        '/<use[^>]+href=["\']([^"\']+)["\']/i',
        '/<use[^>]+xlink:href=["\']([^"\']+)["\']/i',
        
        // Picture and source with srcset
        '/<source[^>]+srcset=["\']([^"\']+)["\']/i',
        '/\bsrcset\s*=\s*["\']([^"\'<>]+)["\']/i',
        
        // Inline styles
        '/style\s*=\s*["\'][^"\']*url\(["\']?([^"\'()\s]+)["\']?\)/i',
        
        // Background attributes
        '/background\s*=\s*["\']([^"\']+)["\']/i',
        '/bgcolor\s*=\s*["\']([^"\']+)["\']/i',
        
        // Favicon and icons
        '/<link[^>]+rel=["\'](?:shortcut\s+)?icon["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']apple-touch-startup-image["\'][^>]+href=["\']([^"\']+)["\']/i',
        
        // Manifest and web app
        '/<link[^>]+rel=["\']manifest["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']preload["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']prefetch["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']modulepreload["\'][^>]+href=["\']([^"\']+)["\']/i',
        
        // AMP images and assets
        '/<amp-img[^>]+src=["\']([^"\']+)["\']/i',
        '/<amp-img[^>]+srcset=["\']([^"\']+)["\']/i',
        '/<amp-video[^>]+poster=["\']([^"\']+)["\']/i',
        '/<amp-anim[^>]+src=["\']([^"\']+)["\']/i',
    ];
    
    $found = [];
    
    foreach ($patterns as $pat) {
        if (preg_match_all($pat, $html, $matches)) {
            foreach ($matches[1] as $raw) {
                // Handle srcset multiple URLs
                if (str_contains($raw, ',')) {
                    $srcsetUrls = explode(',', $raw);
                    foreach ($srcsetUrls as $srcsetUrl) {
                        $srcsetUrl = trim(preg_replace('/\s+\d+[wx]\s*$/', '', $srcsetUrl));
                        $abs = resolve_url(trim($srcsetUrl), $baseUrl);
                        if ($abs) {
                            $found[] = $abs;
                        }
                    }
                } else {
                    $abs = resolve_url(trim($raw), $baseUrl);
                    if ($abs) {
                        $found[] = $abs;
                    }
                }
            }
        }
    }
    
    // Remove duplicates
    $found = array_unique($found);
    
    // Filter out tracking and analytics
    $found = array_filter($found, function($url) {
        $skipPatterns = [
            'google-analytics.com', 'googletagmanager.com', 'facebook.com/tr',
            'doubleclick.net', 'pixel.quantserve.com', 'analytics.twitter.com',
            'bat.bing.com', 'connect.facebook.net/signals',
        ];
        
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        foreach ($skipPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    });
    
    return array_values($found);
}

// ─── CDN Fallback URL Generator ─────────────────────────────────────────────
function cdn_fallback_url(string $failedUrl, string $pageOrigin, string $pageBasePath = ''): ?array {
    $fallbacks = [];
    $fp = parse_url($failedUrl);
    $op = parse_url($pageOrigin);
    
    if (!$fp || !$op) return null;
    
    $failedHost = strtolower($fp['host'] ?? '');
    $originHost = strtolower($op['host'] ?? '');
    $failedPath = $fp['path'] ?? '/';
    $filename = basename($failedPath);
    $pathParts = array_values(array_filter(explode('/', trim($failedPath, '/'))));
    
    // Strategy 1: Same path on origin host
    if ($failedHost !== $originHost) {
        $scheme = $op['scheme'] ?? 'https';
        $port = isset($op['port']) ? ':'.$op['port'] : '';
        $fallbacks[] = "{$scheme}://{$originHost}{$port}{$failedPath}";
    }
    
    // Strategy 2: Common asset directories
    $assetDirs = [
        'assets', 'static', 'public', 'dist', 'build',
        'img', 'images', 'image', 'css', 'js', 'fonts',
        'resources', 'media', 'files', 'uploads',
    ];
    
    foreach ($assetDirs as $dir) {
        $testPath = "/{$dir}/{$filename}";
        if ($testPath !== $failedPath) {
            $scheme = $op['scheme'] ?? 'https';
            $port = isset($op['port']) ? ':'.$op['port'] : '';
            $fallbacks[] = "{$scheme}://{$originHost}{$port}{$testPath}";
        }
    }
    
    // Strategy 3: Try with subdirectories from the original path
    if (count($pathParts) > 1) {
        // Try just the last two path components
        $shortPath = '/' . implode('/', array_slice($pathParts, -2));
        $scheme = $op['scheme'] ?? 'https';
        $port = isset($op['port']) ? ':'.$op['port'] : '';
        $fallbacks[] = "{$scheme}://{$originHost}{$port}{$shortPath}";
        
        // Try with assets/ prefix + last two components
        $assetPath = '/assets/' . implode('/', array_slice($pathParts, -2));
        $fallbacks[] = "{$scheme}://{$originHost}{$port}{$assetPath}";
    }
    
    // Strategy 4: Try combining page base path with filename
    if ($pageBasePath) {
        $baseDir = dirname($pageBasePath);
        $scheme = $op['scheme'] ?? 'https';
        $port = isset($op['port']) ? ':'.$op['port'] : '';
        $fallbacks[] = "{$scheme}://{$originHost}{$port}{$baseDir}/{$filename}";
        $fallbacks[] = "{$scheme}://{$originHost}{$port}{$baseDir}/assets/{$filename}";
    }
    
    // Strategy 5: Try www subdomain if not already using it
    if (!str_starts_with($originHost, 'www.')) {
        $wwwHost = 'www.' . $originHost;
        $scheme = $op['scheme'] ?? 'https';
        $port = isset($op['port']) ? ':'.$op['port'] : '';
        $fallbacks[] = "{$scheme}://{$wwwHost}{$port}{$failedPath}";
    }
    
    // Remove duplicates and the original failed URL
    $fallbacks = array_unique($fallbacks);
    $fallbacks = array_values(array_filter($fallbacks, function($url) use ($failedUrl) {
        return $url !== $failedUrl;
    }));
    
    return !empty($fallbacks) ? array_slice($fallbacks, 0, 5) : null; // Limit to 5 fallbacks
}

// ─── Parallel batch downloader ──────────────────────────────────────────────
function download_batch(array $batch, string $outDir): array {
    $results = [];
    $handles = [];
    $fps = [];
    $dests = [];
    $headers = [];
    
    $mh = curl_multi_init();
    curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, count($batch));
    curl_multi_setopt($mh, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);
    
    foreach ($batch as $url) {
        $dest = local_path($url, $outDir);
        $dir = dirname($dest);
        
        // Create directory structure
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                $results[$url] = [
                    'ok' => false, 'code' => 0, 'retry_after' => null,
                    'size' => 0, 'dest' => $dest, 'err' => 'Cannot create directory: ' . $dir
                ];
                continue;
            }
        }
        
        // Check if file already exists and has content
        if (file_exists($dest) && filesize($dest) > 0) {
            $results[$url] = [
                'ok' => true, 'code' => 200, 'retry_after' => null,
                'size' => filesize($dest), 'dest' => $dest, 'err' => ''
            ];
            continue;
        }
        
        $fp = @fopen($dest, 'wb');
        if (!$fp) {
            $results[$url] = [
                'ok' => false, 'code' => 0, 'retry_after' => null,
                'size' => 0, 'dest' => $dest, 'err' => 'Cannot open file for writing'
            ];
            continue;
        }
        
        $headers[$url] = '';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => DL_TIMEOUT,
            CURLOPT_USERAGENT => UA,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: */*',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache',
                'Referer: ' . (parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/'),
            ],
            CURLOPT_HEADERFUNCTION => function($ch, $header) use ($url, &$headers) {
                $headers[$url] .= $header;
                return strlen($header);
            },
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function($ch, $downloadSize, $downloaded) {
                if ($downloaded > MAX_FILE_SIZE) return 1; // Abort
                return 0;
            },
        ]);
        
        $handles[$url] = $ch;
        $fps[$url] = $fp;
        $dests[$url] = $dest;
        curl_multi_add_handle($mh, $ch);
    }
    
    // Execute multi handle
    $active = null;
    $startTime = time();
    
    do {
        $status = curl_multi_exec($mh, $active);
        
        if (time() - $startTime > DL_TIMEOUT + 30) {
            log_msg("Batch timeout reached", 'warn');
            break;
        }
        
        if ($active) {
            curl_multi_select($mh, 0.1);
        }
    } while ($active && $status === CURLM_OK);
    
    // Collect results
    foreach ($handles as $url => $ch) {
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = (int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $err = curl_error($ch);
        
        $ok = !$err && $code >= 200 && $code < 400 && $size > 0;
        
        fclose($fps[$url]);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        
        $retryAfter = null;
        if (preg_match('/^Retry-After:\s*(\d+)/im', $headers[$url] ?? '', $m2)) {
            $retryAfter = (int)$m2[1];
        }
        
        if (!$ok) {
            @unlink($dests[$url]);
        } else if (filesize($dests[$url]) > MAX_FILE_SIZE) {
            @unlink($dests[$url]);
            $ok = false;
            $err = 'File exceeds maximum size limit';
        }
        
        $results[$url] = compact('ok', 'code', 'retryAfter', 'size', 'err') + [
            'retry_after' => $retryAfter,
            'dest' => $dests[$url],
        ];
    }
    
    curl_multi_close($mh);
    return $results;
}

// ─── Orchestrator ────────────────────────────────────────────────────────────
function run_downloader(string $pageUrl, string $outDir, int $concurrency): array {
    $t0 = microtime(true);
    $stats = [
        'total' => 0, 'ok' => 0, 'skip' => 0, 'fail' => 0,
        'retry' => 0, 'cdn' => 0, 'bytes' => 0, 'scanned' => 0,
    ];
    
    // 1. Fetch page
    log_msg("Fetching page: {$pageUrl}", 'head');
    $page = fetch_page($pageUrl);
    
    if ($page['err'] || !$page['body']) {
        log_msg("Page fetch failed: " . ($page['err'] ?: "HTTP {$page['code']}"), 'err');
        return $stats;
    }
    
    $base = $page['final'] ?: $pageUrl;
    log_msg("Page OK  HTTP {$page['code']}  " . fmt_size(strlen($page['body'])), 'ok');
    
    // Parse base URL
    $parsedBase = parse_url($base);
    $pageOrigin = ($parsedBase['scheme'] ?? 'https') . '://' . ($parsedBase['host'] ?? '');
    if (isset($parsedBase['port'])) $pageOrigin .= ':' . $parsedBase['port'];
    $pageBasePath = $parsedBase['path'] ?? '/';
    
    log_msg("Page origin: {$pageOrigin}", 'info');
    
    // 2. Extract links from HTML
    $links = extract_links($page['body'], $base);
    log_msg("Found " . count($links) . " links in HTML", 'info');
    
    // 3. Filter downloadable files
    $fileLinks = array_values(array_filter($links, 'allowed'));
    log_msg(count($fileLinks) . " downloadable files found", 'info');
    
    // 4. Build initial queue
    $queue = [];
    $processedUrls = []; // Track all processed URLs to avoid duplicates
    $retryMap = [];
    
    foreach ($fileLinks as $url) {
        $dest = local_path($url, $outDir);
        if (file_exists($dest) && filesize($dest) > 0) {
            log_msg("SKIP (cached) " . basename($dest), 'dim');
            $stats['skip']++;
        } else {
            $queue[] = $url;
            $processedUrls[$url] = true;
        }
    }
    
    $stats['total'] = count($fileLinks);
    log_msg(count($queue) . " files to download | {$stats['skip']} cached", 'info');
    
    if (!$queue) {
        log_msg("Nothing to download!", 'warn');
        return $stats;
    }
    
    // 5. Deep scan queue for CSS/JS files
    $deepScanQueue = []; // URLs that need deep scanning after download
    
    // 6. Parallel download loop with deep scanning
    $round = 0;
    $maxRounds = 10;
    
    while (!empty($queue) && $round < $maxRounds) {
        $round++;
        log_msg("═══ Round {$round} - " . count($queue) . " URLs ═══", 'head');
        
        $nextQueue = [];
        $rateLimited = [];
        $cdnQueue = [];
        $batches = array_chunk($queue, $concurrency);
        
        foreach ($batches as $bIdx => $batch) {
            log_msg("Batch " . ($bIdx + 1) . "/" . count($batches) . " - " . count($batch) . " downloads", 'info');
            
            $results = download_batch($batch, $outDir);
            
            foreach ($results as $url => $r) {
                if ($r['ok']) {
                    $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';
                    $path = parse_url($url, PHP_URL_PATH) ?? '/';
                    
                    log_msg("✓ " . basename($r['dest']) . " (" . fmt_size($r['size']) . ") ← {$host}{$path}", 'ok');
                    $stats['ok']++;
                    $stats['bytes'] += $r['size'];
                    
                    // Add to deep scan queue if applicable
                    if (DEEP_SCAN && is_deep_scannable($url)) {
                        $deepScanQueue[] = [
                            'url' => $url,
                            'file' => $r['dest'],
                        ];
                    }
                    
                } elseif ($r['code'] === 429) {
                    $wait = $r['retry_after'] ?? min(60, 5 * (($retryMap[$url] ?? 0) + 1));
                    $rateLimited[$url] = $wait;
                    log_msg("429 Rate-limited → waiting {$wait}s", 'warn');
                    
                } elseif (($retryMap[$url] ?? 0) < MAX_RETRIES) {
                    $retryMap[$url] = ($retryMap[$url] ?? 0) + 1;
                    $nextQueue[] = $url;
                    $stats['retry']++;
                    log_msg("↺ Retry {$retryMap[$url]}/" . MAX_RETRIES . " (HTTP {$r['code']})", 'retry');
                    
                } else {
                    // CDN fallback
                    $cdnFallbacks = cdn_fallback_url($url, $pageOrigin, $pageBasePath);
                    
                    if ($cdnFallbacks) {
                        log_msg("⇢ CDN fallback for " . basename($url), 'cdn');
                        foreach ($cdnFallbacks as $index => $cdnUrl) {
                            if (!isset($processedUrls[$cdnUrl])) {
                                $cdnQueue[] = [
                                    'original' => $url,
                                    'cdn' => $cdnUrl,
                                    'priority' => $index
                                ];
                            }
                        }
                    } else {
                        log_msg("✗ FAILED (HTTP {$r['code']}) ← " . basename($url), 'err');
                        $stats['fail']++;
                    }
                }
            }
            
            // Handle rate-limited URLs
            if ($rateLimited) {
                $groups = [];
                foreach ($rateLimited as $u => $w) $groups[$w][] = $u;
                
                foreach ($groups as $wait => $urls) {
                    $sleepTime = min($wait, 30);
                    log_msg("Sleeping {$sleepTime}s for " . count($urls) . " rate-limited URLs", 'warn');
                    sleep($sleepTime);
                    
                    foreach ($urls as $u) {
                        $retryMap[$u] = ($retryMap[$u] ?? 0) + 1;
                        if ($retryMap[$u] <= MAX_RETRIES) {
                            $nextQueue[] = $u;
                            $stats['retry']++;
                        } else {
                            log_msg("✗ FAILED (max retries) ← " . basename($u), 'err');
                            $stats['fail']++;
                        }
                    }
                }
                $rateLimited = [];
            }
            
            // Process CDN fallback queue
            if ($cdnQueue) {
                usort($cdnQueue, function($a, $b) { return $a['priority'] - $b['priority']; });
                
                $processedCdn = [];
                $cdnBatches = array_chunk($cdnQueue, $concurrency);
                
                foreach ($cdnBatches as $cdnBatch) {
                    $cdnToOrig = [];
                    $cdnUrls = [];
                    
                    foreach ($cdnBatch as $item) {
                        if (!in_array($item['cdn'], $processedCdn)) {
                            $cdnToOrig[$item['cdn']] = $item['original'];
                            $cdnUrls[] = $item['cdn'];
                            $processedCdn[] = $item['cdn'];
                        }
                    }
                    
                    if (empty($cdnUrls)) continue;
                    
                    $cdnResults = download_batch($cdnUrls, $outDir);
                    $foundWorking = false;
                    
                    foreach ($cdnResults as $cdnUrl => $cr) {
                        $origUrl = $cdnToOrig[$cdnUrl];
                        
                        if ($cr['ok'] && !$foundWorking) {
                            $cdnDest = local_path($cdnUrl, $outDir);
                            $origDest = local_path($origUrl, $outDir);
                            
                            if ($cdnDest !== $origDest) {
                                $origDir = dirname($origDest);
                                if (!is_dir($origDir)) @mkdir($origDir, 0755, true);
                                
                                if (@rename($cdnDest, $origDest)) {
                                    log_msg("⇢✓ CDN OK " . basename($origDest) . " (" . fmt_size($cr['size']) . ")", 'cdn');
                                    $stats['cdn']++;
                                    $stats['bytes'] += $cr['size'];
                                    $foundWorking = true;
                                    $processedUrls[$origUrl] = true;
                                }
                            } else {
                                log_msg("⇢✓ CDN OK " . basename($cdnDest) . " (" . fmt_size($cr['size']) . ")", 'cdn');
                                $stats['cdn']++;
                                $stats['bytes'] += $cr['size'];
                                $foundWorking = true;
                                $processedUrls[$origUrl] = true;
                            }
                        }
                    }
                    
                    if (!$foundWorking && !empty($cdnBatch)) {
                        log_msg("✗ All CDN fallbacks failed for " . basename($cdnBatch[0]['original']), 'err');
                        $stats['fail']++;
                    }
                }
                $cdnQueue = [];
            }
        }
        
        $queue = array_unique($nextQueue);
    }
    
    // 7. Process deep scan queue (Phase 2)
    if (DEEP_SCAN && !empty($deepScanQueue)) {
        log_msg("═══ Phase 2: Deep Scanning " . count($deepScanQueue) . " files ═══", 'head');
        
        $discoveredUrls = [];
        
        foreach ($deepScanQueue as $item) {
            $urls = deep_scan_file($item['file'], $item['url']);
            foreach ($urls as $newUrl) {
                if (!isset($processedUrls[$newUrl])) {
                    $discoveredUrls[] = $newUrl;
                    $processedUrls[$newUrl] = true;
                }
            }
        }
        
        $stats['scanned'] = count($deepScanQueue);
        $discoveredUrls = array_unique($discoveredUrls);
        $newFileLinks = array_values(array_filter($discoveredUrls, 'allowed'));
        
        log_msg("Deep scan found " . count($newFileLinks) . " new resources", 'scan');
        
        if (!empty($newFileLinks)) {
            // Download newly discovered files
            $newQueue = [];
            foreach ($newFileLinks as $url) {
                $dest = local_path($url, $outDir);
                if (file_exists($dest) && filesize($dest) > 0) {
                    log_msg("SKIP (cached) " . basename($dest), 'dim');
                    $stats['skip']++;
                } else {
                    $newQueue[] = $url;
                }
            }
            
            if (!empty($newQueue)) {
                log_msg("Downloading " . count($newQueue) . " newly discovered files", 'scan');
                
                $round = 0;
                $maxRounds = 5;
                
                while (!empty($newQueue) && $round < $maxRounds) {
                    $round++;
                    $nextNewQueue = [];
                    $batches = array_chunk($newQueue, $concurrency);
                    
                    foreach ($batches as $batch) {
                        $results = download_batch($batch, $outDir);
                        
                        foreach ($results as $url => $r) {
                            if ($r['ok']) {
                                log_msg("✓ " . basename($r['dest']) . " (" . fmt_size($r['size']) . ")", 'ok');
                                $stats['ok']++;
                                $stats['bytes'] += $r['size'];
                                $stats['total']++;
                            } elseif (($retryMap[$url] ?? 0) < MAX_RETRIES) {
                                $retryMap[$url] = ($retryMap[$url] ?? 0) + 1;
                                $nextNewQueue[] = $url;
                            } else {
                                log_msg("✗ FAILED ← " . basename($url), 'err');
                                $stats['fail']++;
                                $stats['total']++;
                            }
                        }
                    }
                    
                    $newQueue = array_unique($nextNewQueue);
                }
            }
        }
    }
    
    // 8. Summary
    $elapsed = microtime(true) - $t0;
    $avgSpeed = $elapsed > 0 ? (int)($stats['bytes'] / $elapsed) : 0;
    
    log_msg("══════════════════════════════════════════", 'head');
    log_msg("Download Summary:", 'head');
    log_msg("  Time: " . fmt_time($elapsed) . " | Speed: " . fmt_size($avgSpeed) . "/s", 'info');
    log_msg("  ✓ {$stats['ok']} downloaded successfully", 'ok');
    log_msg("  ⇢ {$stats['cdn']} via CDN fallback", 'cdn');
    log_msg("  ↺ {$stats['retry']} retries", 'retry');
    log_msg("  🔎 {$stats['scanned']} deep-scanned files", 'scan');
    log_msg("  ⊘ {$stats['skip']} cached", 'dim');
    log_msg("  ✗ {$stats['fail']} failed", 'err');
    log_msg("  Total: " . fmt_size($stats['bytes']), 'info');
    log_msg("  Output: " . realpath($outDir), 'ok');
    
    return $stats;
}

// ─── ZIP Creator ──────────────────────────────────────────────────────────────
function create_zip(string $sourceDir, string $zipPath): bool {
    if (!class_exists('ZipArchive')) { log_msg("ZipArchive not available", 'err'); return false; }
    if (!is_dir($sourceDir)) return false;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        log_msg("Cannot create ZIP: {$zipPath}", 'err'); return false;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $base = strlen(rtrim($sourceDir, DIRECTORY_SEPARATOR)) + 1;
    foreach ($it as $f) {
        if ($f->isReadable()) $zip->addFile($f->getRealPath(), substr($f->getRealPath(), $base));
    }
    $zip->close();
    return file_exists($zipPath) && filesize($zipPath) > 0;
}

// ─── Expire markers ───────────────────────────────────────────────────────────
// Always write markers at session start — covers both zip and non-zip runs.
function write_expire(string $sessionDir, string $zipPath = '', int $secs = 3600): void {
    $t = (string)(time() + $secs);
    if (!is_dir($sessionDir)) @mkdir($sessionDir, 0755, true);
    file_put_contents($sessionDir . '.expire', $t);
    if ($zipPath !== '') file_put_contents($zipPath . '.expire', $t);
}

function cleanup_expired(string $base): void {
    if (!is_dir($base)) return;
    foreach (glob($base . DIRECTORY_SEPARATOR . '*.expire') ?: [] as $marker) {
        if (substr($marker, -7) !== '.expire') continue;
        $expireAt = (int)@file_get_contents($marker);
        if ($expireAt === 0 || time() < $expireAt) continue;
        $target = substr($marker, 0, -7); // strip .expire
        if (is_dir($target)) {
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $f) {
                    $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
                }
            } catch (\Exception $e) {}
            @rmdir($target);
        } elseif (is_file($target)) {
            @unlink($target);
        }
        @unlink($marker);
    }
}

// ─── Routing ──────────────────────────────────────────────────────────────────
$baseWorkDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads';
if (!is_dir($baseWorkDir)) @mkdir($baseWorkDir, 0755, true);

cleanup_expired($baseWorkDir);

$action = $_GET['action'] ?? '';

// ── A) ZIP download ───────────────────────────────────────────────────────────
if ($action === 'zip' && !empty($_GET['file'])) {
    $safe = basename($_GET['file']);
    $path = $baseWorkDir . DIRECTORY_SEPARATOR . $safe;
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, no-store');
        readfile($path);
        exit;
    }
    http_response_code(404); exit('Not found');
}

// ── B) Beacon cleanup — called by browser on tab/window close ────────────────
if ($action === 'cleanup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // No output needed — beacon doesn't read the response
    http_response_code(204);

    $sessionId = basename($_POST['session'] ?? '');
    // Strict whitelist: only alphanumeric, underscores, hyphens — no path traversal
    if ($sessionId && preg_match('/^[a-zA-Z0-9_\-]+$/', $sessionId)) {
        $sessionDir = $baseWorkDir . DIRECTORY_SEPARATOR . $sessionId;
        $zipPattern = $baseWorkDir . DIRECTORY_SEPARATOR . 'download_' . $sessionId . '.zip';

        // Delete session directory
        if (is_dir($sessionDir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sessionDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
            }
            @rmdir($sessionDir);
        }
        // Delete .expire marker for session dir
        @unlink($sessionDir . '.expire');

        // Delete ZIP + its .expire marker
        if (file_exists($zipPattern)) @unlink($zipPattern);
        @unlink($zipPattern . '.expire');
    }
    exit;
}

// ── C) SSE stream — only runs when JS explicitly calls it ────────────────────
if ($action === 'stream' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Disable output buffering completely
    while (ob_get_level()) ob_end_clean();
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', 'off');

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-store');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');

    $url       = trim($_POST['url'] ?? '');
    $out       = preg_replace('/[^a-zA-Z0-9._\-\/]/', '_', trim($_POST['outdir'] ?? DEFAULT_OUTPUT)) ?: DEFAULT_OUTPUT;
    $conc      = max(1, min(32, (int)($_POST['concurrency'] ?? CONCURRENCY)));
    $deepScan  = !empty($_POST['deepscan']);
    $createZip = !empty($_POST['create_zip']);

    if (!$url) {
        echo "event: error\ndata: " . json_encode(['msg' => 'No URL provided']) . "\n\n";
        exit;
    }

    $sessionId  = date('Ymd_His') . '_' . substr(md5($url . $out . microtime()), 0, 6);
    $sessionDir = $baseWorkDir . DIRECTORY_SEPARATOR . $sessionId;
    $outFull    = $sessionDir . DIRECTORY_SEPARATOR . $out;
    $zipName    = $createZip ? ('download_' . $sessionId . '.zip') : '';
    $zipPath    = $zipName   ? ($baseWorkDir . DIRECTORY_SEPARATOR . $zipName) : '';

    // Write expire markers BEFORE download starts — always, not just for ZIP
    if (!is_dir($sessionDir)) @mkdir($sessionDir, 0755, true);
    write_expire($sessionDir, $zipPath, 3600);

    define('DEEP_SCAN_RUNTIME', $deepScan);

    // Override log_msg to emit SSE events
    // We capture output by redefining the global $isCli to false and using ob buffering per-message
    // Instead, we use a custom SSE emitter — patch via output buffering trick
    // Since log_msg() writes HTML divs, we capture them and reformat as SSE data

    // Run downloader — log_msg() will echo HTML divs, we wrap each flush in SSE format
    // To do this cleanly we buffer and parse between flushes
    function sse(string $event, array $data): void {
        echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (function_exists('fastcgi_finish_request')) {
            // noop — we flush below
        }
        flush();
    }

    // Monkey-patch: since log_msg echoes HTML, capture each call via output buffering
    // We run the downloader with a custom output handler
    ob_start(function(string $chunk) {
        // Each chunk is one or more <div class="log log-TYPE">... HTML lines
        // Parse them and emit as SSE
        preg_match_all('/<div class="log log-([^"]+)"><span>[^<]*<\/span> (.*?)<\/div>/s', $chunk, $m, PREG_SET_ORDER);
        $out = '';
        foreach ($m as $match) {
            $level = htmlspecialchars_decode($match[1]);
            $msg   = htmlspecialchars_decode(strip_tags($match[2]));
            $out  .= "event: log\ndata: " . json_encode(['level' => $level, 'msg' => $msg], JSON_UNESCAPED_UNICODE) . "\n\n";
        }
        if ($out) flush();
        return $out;
    }, 1, PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);

    $stats = run_downloader($url, $outFull, $conc);
    ob_end_flush();

    // Create ZIP
    $zipReady = false;
    if ($createZip && $stats['ok'] > 0) {
        sse('log', ['level' => 'head', 'msg' => 'Packaging ZIP archive...']);
        if (create_zip($sessionDir, $zipPath)) {
            $zipReady = true;
            sse('log', ['level' => 'ok', 'msg' => 'ZIP ready: ' . $zipName . ' — ' . fmt_size(filesize($zipPath))]);
        } else {
            sse('log', ['level' => 'err', 'msg' => 'ZIP creation failed']);
        }
    }

    // Final done event with all stats
    sse('done', [
        'ok'        => $stats['ok'],
        'fail'      => $stats['fail'],
        'retry'     => $stats['retry'],
        'bytes'     => $stats['bytes'],
        'zip'       => $zipReady ? $zipName : '',
        'zipSize'   => ($zipReady && file_exists($zipPath)) ? filesize($zipPath) : 0,
        'sessionId' => $sessionId,
    ]);
    exit;
}

// ── C) CLI ────────────────────────────────────────────────────────────────────
if ($isCli) {
    $url = $argv[1] ?? null;
    if (!$url) { echo "Usage: php index.php <url> [output_dir] [concurrency]\n"; exit(1); }
    $out  = $argv[2] ?? DEFAULT_OUTPUT;
    $conc = isset($argv[3]) ? max(1, min(32, (int)$argv[3])) : CONCURRENCY;
    define('DEEP_SCAN_RUNTIME', true);
    run_downloader($url, $out, $conc);
    exit(0);
}

// ── D) UI — always renders instantly, zero downloading ────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resource Downloader Pro</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{mono:['"JetBrains Mono"','ui-monospace','monospace'],sans:['Inter','ui-sans-serif','sans-serif']}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#f8fafc;--surface:#ffffff;--surface-2:#f3f4f6;--border:#e5e7eb;--ink:#0f172a;--ink-2:#1e293b;--ink-3:#334155;--muted:#64748b;--accent:#3b82f6;--accent-2:#6366f1;--success:#16a34a;--warning:#f59e0b;--danger:#ef4444;}
html{color-scheme:light}
body{background:var(--bg);color:var(--ink);font-family:'DM Sans',sans-serif;min-height:100vh}
::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
@keyframes fade-up{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.15}}
@keyframes spin-r{to{transform:rotate(360deg)}}
@keyframes log-in{from{opacity:0;transform:translateX(-5px)}to{opacity:1;transform:none}}
@keyframes drain{from{transform:scaleX(1)}to{transform:scaleX(0)}}
.fade-up{animation:fade-up .38s cubic-bezier(.22,1,.36,1) both}
.blink{animation:blink 1.3s ease-in-out infinite}
.spin{animation:spin-r .8s linear infinite}
.log-row{animation:log-in .16s ease both;display:flex;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);font-family:'DM Mono',monospace;font-size:.82rem;line-height:1.6;word-break:break-all;color:var(--ink-2)}
.log-row:last-child{border-bottom:none}
.l-ok{color:var(--success)}.l-err{color:var(--danger)}.l-warn{color:var(--warning)}
.l-head{color:var(--ink);font-weight:700}.l-info{color:var(--ink-3)}.l-dim{color:#94a3b8}
.l-retry{color:#64748b}.l-cdn{color:#475569;font-style:italic}.l-scan{color:#475569}
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;position:relative}
.card-sm{background:var(--surface);border:1px solid var(--border);border-radius:16px}
.inp{width:100%;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:14px 16px;color:var(--ink);font-family:'DM Sans',sans-serif;font-size:15px;outline:none;transition:border-color .2s,box-shadow .2s}
.inp:focus{border-color:var(--accent);box-shadow:0 0 0 4px rgba(59,130,246,.12)}
.inp::placeholder{color:#94a3b8}
.inp-l{padding-left:44px}
.tog-track{display:block;width:38px;height:21px;background:#e2e8f0;border-radius:999px;transition:background .2s;position:relative;cursor:pointer}
.tog-thumb{position:absolute;top:2.5px;left:2.5px;width:16px;height:16px;background:#ffffff;border-radius:50%;box-shadow:0 1px 2px rgba(15,23,42,.08);transition:transform .2s cubic-bezier(.68,-.55,.265,1.55),background .2s}
.tog-inp{position:absolute;opacity:0;width:0;height:0}
.tog-inp:checked~.tog-track{background:var(--accent)}
.tog-inp:checked~.tog-track .tog-thumb{transform:translateX(17px);background:#ffffff}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px 22px;background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;font-family:'DM Sans',sans-serif;font-weight:700;font-size:14px;letter-spacing:.01em;border:none;border-radius:14px;cursor:pointer;transition:all .15s;user-select:none}
.btn:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 8px 24px rgba(59,130,246,.2)}
.btn:active:not(:disabled){transform:translateY(0)}
.btn:disabled{opacity:.5;cursor:not-allowed;box-shadow:none}
.btn-outline{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px 20px;background:#fff;color:var(--accent);font-family:'DM Sans',sans-serif;font-weight:700;font-size:14px;border:1.5px solid var(--border);border-radius:14px;cursor:pointer;text-decoration:none;transition:all .15s}
.btn-outline:hover{background:var(--surface-2);border-color:var(--accent);color:var(--accent)}
.badge{display:inline-flex;align-items:center;gap:6px;font-family:'DM Sans',sans-serif;font-size:.72rem;font-weight:700;letter-spacing:.08em;padding:5px 12px;border-radius:999px;background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff}
.chip{display:inline-flex;align-items:center;gap:6px;font-family:'DM Sans',sans-serif;font-size:.75rem;padding:6px 10px;border-radius:12px;border:1px solid var(--border);color:var(--ink-3);background:var(--surface-2)}
.ftag{display:inline-flex;align-items:center;font-family:'DM Mono',monospace;font-size:.72rem;padding:4px 8px;border-radius:8px;background:#eef2ff;color:#2563eb;border:1px solid #dbeafe}
input[type=number]{-moz-appearance:textfield}
input::-webkit-outer-spin-button,input::-webkit-inner-spin-button{-webkit-appearance:none}
.drain-bar{transform-origin:left;animation:drain 3600s linear forwards}
</style>
</head>
<body>
<div class="max-w-[640px] mx-auto px-4 py-12 pb-24 space-y-3">

  <!-- HEADER -->
  <div class="text-center mb-10 fade-up">
    <div class="inline-flex items-center gap-2 badge mb-5">
      <i data-lucide="zap" class="w-3 h-3"></i> Resource Downloader Pro · v3.0
    </div>
    <h1 class="text-4xl sm:text-[2.8rem] font-black tracking-tight text-slate-900 leading-[1.05] mb-3">
      Download Any<br><span class="text-slate-500">Page Assets</span>
    </h1>
    <p class="font-mono text-[.65rem] text-slate-500 tracking-wide">Deep Scan · CSS/JS Parsing · CDN Fallback · ZIP · Auto-Cleanup</p>
    <div class="flex flex-wrap justify-center gap-1.5 mt-5">
      <?php foreach([
        ['zap',CONCURRENCY.'× parallel'],['refresh-cw',MAX_RETRIES.'× retry'],
        ['package','max '.fmt_size(MAX_FILE_SIZE)],['files',count(ALLOWED_EXT).' types'],
        ['scan-search','deep scan'],['archive','zip export'],['clock','1h auto-delete'],
      ] as [$ic,$lb]): ?>
      <span class="badge"><i data-lucide="<?=$ic?>" class="w-3 h-3"></i><?=$lb?></span>
      <?php endforeach ?>
    </div>
  </div>

  <!-- FORM CARD -->
  <div class="card p-6 fade-up" style="animation-delay:.05s">

    <!-- URL -->
    <div class="mb-4">
      <label class="block font-mono text-[.6rem] tracking-widest uppercase text-slate-600 mb-1.5">Target URL <span style="color:var(--danger);">*</span></label>
      <div class="relative">
        <i data-lucide="globe" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none"></i>
        <input id="fUrl" type="url" placeholder="https://example.com/page" class="inp inp-l" autocomplete="off" spellcheck="false">
      </div>
    </div>

    <!-- Row -->
    <div class="grid grid-cols-[1fr_68px_auto] gap-3 items-end mb-4">
      <div>
        <label class="block font-mono text-[.6rem] tracking-widest uppercase text-slate-600 mb-1.5">Output Folder</label>
        <div class="relative">
          <i data-lucide="folder" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none"></i>
          <input id="fOut" type="text" placeholder="<?=DEFAULT_OUTPUT?>" class="inp inp-l" value="<?=DEFAULT_OUTPUT?>">
        </div>
      </div>
      <div>
        <label class="block font-mono text-[.6rem] tracking-widest uppercase text-slate-600 mb-1.5">Threads</label>
        <input id="fConc" type="number" min="1" max="32" value="<?=CONCURRENCY?>" class="inp text-center w-full">
      </div>
      <div>
        <label class="block font-mono text-[.6rem] tracking-widest uppercase text-slate-600 mb-1.5">Deep Scan</label>
        <div class="flex items-center h-[40px]">
          <label class="relative cursor-pointer">
            <input id="fDeep" type="checkbox" checked class="tog-inp">
            <span class="tog-track"><span class="tog-thumb"></span></span>
          </label>
        </div>
      </div>
    </div>

    <!-- ZIP row -->
    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50 mb-5">
      <i data-lucide="archive" class="w-4 h-4 text-slate-500 shrink-0"></i>
      <div class="flex-1 min-w-0">
        <div class="text-xs font-medium text-slate-500">Package as ZIP</div>
        <div class="font-mono text-[.57rem] text-slate-600 mt-0.5">Expire marker written on start · deleted after 1 hour</div>
      </div>
      <label class="relative cursor-pointer shrink-0">
        <input id="fZip" type="checkbox" checked class="tog-inp">
        <span class="tog-track"><span class="tog-thumb"></span></span>
      </label>
    </div>

    <button id="startBtn" class="btn" onclick="startDownload()">
      <i data-lucide="download" class="w-4 h-4" id="btnIcon"></i>
      <span id="btnText">Start Download</span>
    </button>
  </div>

  <!-- FEATURES + FILE TYPES -->
  <div class="card p-5 fade-up" style="animation-delay:.09s">
    <div class="grid grid-cols-2 gap-1.5 mb-4">
      <?php foreach([
        ['check','Multi-layered URL resolution'],['scan','CSS url() &amp; @import'],
        ['braces','JS import/require scanning'],['layers','Webpack chunk detection'],
        ['cpu','Service Worker detection'],['type','Font-face scanning'],
        ['image','Lazy-load attributes'],['server','CDN fallback strategies'],
        ['shield-check','Rate-limit handling'],['map','Source map detection'],
      ] as [$ic,$lb]): ?>
      <div class="chip"><i data-lucide="<?=$ic?>" class="w-3 h-3 shrink-0"></i><?=$lb?></div>
      <?php endforeach ?>
    </div>
    <div class="border-t border-slate-200 pt-4">
      <div class="flex justify-between mb-2.5">
        <span class="font-mono text-[.58rem] uppercase tracking-widest text-slate-600">File Types</span>
        <span class="font-mono text-[.58rem] text-slate-600 border border-slate-200 px-2 py-px rounded"><?=count(ALLOWED_EXT)?> formats</span>
      </div>
      <div class="max-h-24 overflow-y-auto space-y-2 pr-0.5">
        <?php $cats=['Documents'=>['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','rtf','csv','md','epub'],
          'Images'=>['jpg','jpeg','png','gif','svg','webp','ico','avif','psd'],
          'Audio'=>['mp3','wav','ogg','flac','aac','m4a'],
          'Video'=>['mp4','avi','mov','mkv','webm','m4v'],
          'Fonts'=>['woff','woff2','ttf','eot','otf'],
          'Code'=>['js','ts','jsx','tsx','css','scss','html','json','php','py','go','rs'],
          'Archives'=>['zip','rar','tar','gz','7z','bz2'],
          'Data'=>['sql','db','sqlite','graphql','pem','wasm']];
        foreach($cats as $cat=>$exts):
          $exts=array_intersect($exts,ALLOWED_EXT); if(!$exts) continue; ?>
        <div>
          <div class="font-mono text-[.5rem] uppercase tracking-widest text-slate-700 mb-1"><?=$cat?></div>
          <div class="flex flex-wrap gap-1"><?php foreach($exts as $e): ?><span class="ftag">.<?=$e?></span><?php endforeach ?></div>
        </div>
        <?php endforeach ?>
      </div>
    </div>
  </div>

  <!-- CLI -->
  <div class="card-sm px-5 py-4 fade-up" style="animation-delay:.13s">
    <div class="flex items-center gap-2 font-mono text-[.58rem] uppercase tracking-widest text-slate-600 mb-2">
      <i data-lucide="terminal" class="w-3.5 h-3.5"></i> CLI
    </div>
    <pre class="font-mono text-[.7rem] text-slate-500 overflow-x-auto whitespace-pre leading-relaxed">php index.php &lt;url&gt; [output_dir] [concurrency]</pre>
  </div>

  <!-- LOG PANEL — hidden until download starts -->
  <div id="logPanel" class="card overflow-hidden fade-up hidden" style="animation-delay:.05s">
    <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-200 bg-slate-50">
      <span class="flex items-center gap-1.5 font-mono text-[.6rem] tracking-wider text-slate-500">
        <span id="liveDot" class="w-1.5 h-1.5 rounded-full bg-slate-400 blink"></span>
        <span id="liveLabel">CONNECTING</span>
      </span>
      <span id="logUrl" class="font-mono text-[.62rem] text-slate-600 truncate flex-1"></span>
      <i data-lucide="activity" class="w-3.5 h-3.5 text-slate-500 shrink-0"></i>
    </div>
    <div id="logBody" class="p-4 max-h-[480px] overflow-y-auto"></div>
  </div>

  <!-- RESULT PANEL — hidden until done -->
  <div id="resultPanel" class="hidden fade-up" style="animation-delay:.05s">
    <!-- Timer -->
    <div class="flex items-center justify-between mb-1.5">
      <div class="flex items-center gap-1.5 font-mono text-[.58rem] text-slate-600">
        <i data-lucide="clock" class="w-3 h-3"></i> Auto-delete in <span class="text-slate-500 ml-0.5">1 hour</span>
      </div>
      <span id="timerLabel" class="font-mono text-[.58rem] text-slate-600">60:00</span>
    </div>
    <div class="h-px w-full bg-slate-200 mb-4 overflow-hidden rounded-full">
      <div id="drainBar" class="h-full bg-slate-400 rounded-full drain-bar"></div>
    </div>
    <!-- Download button -->
    <a id="zipBtn" href="#" class="btn-outline mb-3">
      <i data-lucide="archive" class="w-4 h-4 shrink-0"></i>
      <span id="zipLabel">Download ZIP</span>
      <span id="zipSize" class="font-mono text-xs text-slate-500 ml-auto shrink-0"></span>
    </a>
    <!-- Stats -->
    <div class="grid grid-cols-4 gap-2">
      <?php foreach([
        ['check-circle','Downloaded','statOk','text-slate-300'],
        ['refresh-cw','Retries','statRetry','text-slate-500'],
        ['x-circle','Failed','statFail','text-slate-600'],
        ['hard-drive','Total','statBytes','text-slate-500'],
      ] as [$ic,$lb,$id,$tc]): ?>
      <div class="card-sm p-3 text-center">
        <i data-lucide="<?=$ic?>" class="w-3.5 h-3.5 mx-auto mb-1.5 <?=$tc?>"></i>
        <div id="<?=$id?>" class="font-mono text-sm font-bold <?=$tc?>">—</div>
        <div class="font-mono text-[.54rem] uppercase tracking-wide text-slate-600 mt-0.5"><?=$lb?></div>
      </div>
      <?php endforeach ?>
    </div>
  </div>

</div><!-- /container -->

<script>
(function(){
  if(window.lucide) lucide.createIcons();

  let es = null;
  let timerInterval = null;
  let timerSecs = 3600;

  function fmtSize(b){
    if(b>=1073741824) return (b/1073741824).toFixed(2)+' GB';
    if(b>=1048576)    return (b/1048576).toFixed(2)+' MB';
    if(b>=1024)       return (b/1024).toFixed(1)+' KB';
    return b+' B';
  }

  function addLog(level, msg){
    const body = document.getElementById('logBody');
    if(!body) return;
    const row = document.createElement('div');
    row.className = 'log-row l-' + level;
    const icons = {ok:'✓',err:'✗',warn:'▲',head:'◆',info:'→',dim:'·',retry:'↺',cdn:'⇢',scan:'⌕'};
    const icon = document.createElement('span');
    icon.style.cssText = 'flex-shrink:0;width:14px;text-align:center';
    icon.textContent = icons[level] || '·';
    const text = document.createElement('span');
    text.textContent = msg;
    row.appendChild(icon); row.appendChild(text);
    body.appendChild(row);
    body.scrollTop = body.scrollHeight;
  }

  function setLive(state){
    const dot   = document.getElementById('liveDot');
    const label = document.getElementById('liveLabel');
    if(state === 'live'){
      dot.className   = 'w-1.5 h-1.5 rounded-full bg-white blink';
      label.textContent = 'LIVE';
    } else if(state === 'done'){
      dot.className   = 'w-1.5 h-1.5 rounded-full bg-green-500';
      dot.classList.remove('blink');
      label.textContent = 'DONE';
    } else {
      dot.className   = 'w-1.5 h-1.5 rounded-full bg-slate-400 blink';
      label.textContent = 'CONNECTING';
    }
  }

  function startTimer(){
    timerSecs = 3600;
    clearInterval(timerInterval);
    timerInterval = setInterval(()=>{
      if(--timerSecs <= 0){ timerSecs = 0; clearInterval(timerInterval); }
      const m = String(Math.floor(timerSecs/60)).padStart(2,'0');
      const s = String(timerSecs%60).padStart(2,'0');
      const el = document.getElementById('timerLabel');
      if(el) el.textContent = m+':'+s;
    }, 1000);
  }

  window.startDownload = function(){
    const url  = document.getElementById('fUrl').value.trim();
    const out  = document.getElementById('fOut').value.trim() || '<?=DEFAULT_OUTPUT?>';
    const conc = document.getElementById('fConc').value || '<?=CONCURRENCY?>';
    const deep = document.getElementById('fDeep').checked ? '1' : '';
    const zip  = document.getElementById('fZip').checked  ? '1' : '';

    if(!url){ document.getElementById('fUrl').focus(); return; }

    // Reset UI
    const logBody = document.getElementById('logBody');
    logBody.innerHTML = '';
    document.getElementById('logPanel').classList.remove('hidden');
    document.getElementById('resultPanel').classList.add('hidden');
    document.getElementById('logUrl').textContent = url;
    setLive('connecting');

    // Lock button
    const btn  = document.getElementById('startBtn');
    const icon = document.getElementById('btnIcon');
    const txt  = document.getElementById('btnText');
    btn.disabled = true;
    icon.setAttribute('data-lucide','loader-2');
    icon.classList.add('spin');
    txt.textContent = 'Downloading…';
    if(window.lucide) lucide.createIcons();

    // Close any existing stream
    if(es){ es.close(); es = null; }

    // POST via fetch with FormData, receive SSE via ReadableStream
    const fd = new FormData();
    fd.append('url', url);
    fd.append('outdir', out);
    fd.append('concurrency', conc);
    if(deep) fd.append('deepscan', '1');
    if(zip)  fd.append('create_zip', '1');

    fetch('?action=stream', { method:'POST', body: fd })
      .then(res => {
        if(!res.ok) throw new Error('HTTP ' + res.status);
        setLive('live');
        const reader = res.body.getReader();
        const dec    = new TextDecoder();
        let buf = '';

        function pump(){
          reader.read().then(({done, value}) => {
            if(done){ setLive('done'); resetBtn(); return; }
            buf += dec.decode(value, {stream:true});
            // Parse SSE lines
            const parts = buf.split('\n\n');
            buf = parts.pop(); // keep incomplete chunk
            for(const part of parts){
              if(!part.trim()) continue;
              const lines = part.split('\n');
              let event = 'message', data = '';
              for(const l of lines){
                if(l.startsWith('event:')) event = l.slice(6).trim();
                if(l.startsWith('data:'))  data  = l.slice(5).trim();
              }
              if(!data) continue;
              let parsed;
              try{ parsed = JSON.parse(data); } catch(e){ continue; }

              if(event === 'log'){
                addLog(parsed.level || 'info', parsed.msg || '');
              } else if(event === 'done'){
                handleDone(parsed, zip);
              } else if(event === 'error'){
                addLog('err', parsed.msg || 'Unknown error');
                setLive('done'); resetBtn();
              }
            }
            pump();
          }).catch(e => { addLog('err', 'Stream error: '+e.message); setLive('done'); resetBtn(); });
        }
        pump();
      })
      .catch(e => { addLog('err', 'Fetch error: '+e.message); setLive('done'); resetBtn(); });
  };

  function handleDone(data, zip){
    setLive('done');
    resetBtn();

    // Store session id for beacon cleanup on tab close
    if (data.sessionId) {
      window._sessionId = data.sessionId;
    }

    // Stats
    document.getElementById('statOk').textContent    = data.ok    ?? 0;
    document.getElementById('statRetry').textContent  = data.retry ?? 0;
    document.getElementById('statFail').textContent   = data.fail  ?? 0;
    document.getElementById('statBytes').textContent  = fmtSize(data.bytes ?? 0);

    const rp = document.getElementById('resultPanel');

    if(data.zip && zip){
      document.getElementById('zipBtn').href  = '?action=zip&file=' + encodeURIComponent(data.zip);
      document.getElementById('zipLabel').textContent = 'Download ZIP — ' + data.zip;
      document.getElementById('zipSize').textContent  = fmtSize(data.zipSize || 0);

      // Reset and restart drain bar animation
      const bar = document.getElementById('drainBar');
      bar.style.animation = 'none';
      bar.offsetHeight; // reflow
      bar.style.animation = '';
      bar.classList.add('drain-bar');

      startTimer();
      rp.classList.remove('hidden');
    } else {
      // Hide timer/zip row, just show stats
      rp.querySelector('.drain-bar')?.closest('div')?.parentElement?.classList.add('hidden');
      document.getElementById('zipBtn').closest('a').classList.add('hidden');
      rp.classList.remove('hidden');
    }

    if(window.lucide) lucide.createIcons();
  }

  function resetBtn(){
    const btn  = document.getElementById('startBtn');
    const icon = document.getElementById('btnIcon');
    const txt  = document.getElementById('btnText');
    btn.disabled = false;
    icon.setAttribute('data-lucide','download');
    icon.classList.remove('spin');
    txt.textContent = 'Start Download';
    if(window.lucide) lucide.createIcons();
  }

  // ── Session cleanup on tab/window close ──────────────────────────────────────
  // sendBeacon is guaranteed to complete even if the page is closing.
  // We listen to both visibilitychange (tab hidden/switch) and pagehide (close/navigate away).
  function sendCleanupBeacon() {
    if (!window._sessionId) return;
    const fd = new FormData();
    fd.append('session', window._sessionId);
    navigator.sendBeacon('?action=cleanup', fd);
    window._sessionId = null; // prevent double-send
  }

  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') sendCleanupBeacon();
  });

  window.addEventListener('pagehide', function() {
    sendCleanupBeacon();
  });

})();
</script>
</body>
</html>