<?php
/**
 * Website Asset Downloader — integrated API action
 * Streams SSE log events. Supports:
 *   - Deep scan (CSS url(), JS import/require, HTML links)
 *   - Proxy rotation on 429 (free public SOCKS5 pool + HTTP proxies)
 *   - CDN fallback on persistent failures
 *   - ZIP packaging with 1h auto-expire
 *   - Session cleanup via beacon
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

while (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 'off');

// ── Runtime limits ────────────────────────────────────────────
set_time_limit(3600);
ini_set('max_execution_time', 3600);
ini_set('memory_limit', '512M');
ignore_user_abort(true);

define('DL_CONCURRENCY',   12);
define('DL_MAX_RETRIES',    3);
define('DL_MAX_FILE',  200 * 1024 * 1024);
define('DL_CONN_TIMEOUT',  10);
define('DL_TIMEOUT',       90);
define('DL_PAGE_TIMEOUT',  20);
define('DL_UA', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36');
define('DL_MAX_DEEP_SIZE', 5 * 1024 * 1024);
define('DL_START', microtime(true));
define('DL_MAX_RUNTIME', 3540);

// ── SSE helpers ───────────────────────────────────────────────
function dl_sse(string $event, array $data): void {
    echo "event: {$event}\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
}

function dl_log(string $level, string $msg): void {
    dl_sse('log', ['level' => $level, 'msg' => $msg]);
}

function dl_check_timeout(): void {
    if ((microtime(true) - DL_START) > DL_MAX_RUNTIME) {
        dl_log('warn', '⚠ Approaching runtime limit — stopping gracefully.');
        throw new RuntimeException('timeout');
    }
}

// ── Proxy pool (free public proxies — best-effort, not guaranteed) ──
// These rotate on 429. You can extend this list or replace with a
// paid proxy service endpoint.
function dl_proxy_pool(): array {
    return [
        // Format: 'type://host:port'  (socks5, socks4, http)
        'socks5://98.162.25.7:31654',
        'socks5://98.162.25.29:31654',
        'socks5://184.178.172.5:15303',
        'socks5://184.178.172.14:4145',
        'socks5://72.195.34.42:4145',
        'socks5://70.166.167.38:57728',
        'http://8.219.97.248:80',
        'http://103.152.112.157:80',
        'http://195.23.57.78:80',
        // Direct (no proxy) — always included as last resort
        '',
    ];
}

// Pick next proxy for a URL (round-robin per host)
$_dlProxyIndex = [];
function dl_next_proxy(string $host): string {
    global $_dlProxyIndex;
    $pool = dl_proxy_pool();
    $idx = ($_dlProxyIndex[$host] ?? 0) % count($pool);
    $_dlProxyIndex[$host] = $idx + 1;
    return $pool[$idx];
}

// ── Format helpers ─────────────────────────────────────────────
function dl_fmt_size(int $b): string {
    if ($b >= 1073741824) return round($b / 1073741824, 2) . ' GB';
    if ($b >= 1048576)    return round($b / 1048576, 2) . ' MB';
    if ($b >= 1024)       return round($b / 1024, 1) . ' KB';
    return $b . ' B';
}

// ── Allowed file types ─────────────────────────────────────────
function dl_allowed(string $url): bool {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (empty($ext)) return true;
    static $ok = [
        'pdf','doc','docx','xls','xlsx','ppt','pptx','odt','txt','rtf','csv','md','epub',
        'jpg','jpeg','png','gif','svg','webp','ico','bmp','tiff','avif','heic','psd','ai',
        'mp3','wav','ogg','flac','aac','m4a','wma',
        'mp4','avi','mov','mkv','webm','m4v','wmv','flv',
        'zip','rar','tar','gz','7z','bz2','xz',
        'js','mjs','cjs','ts','jsx','tsx','css','scss','sass','less',
        'html','htm','xml','json','yaml','yml','toml',
        'php','py','rb','java','c','cpp','h','go','rs','sh',
        'woff','woff2','ttf','eot','otf',
        'sql','db','sqlite','graphql','pem','crt','key','map','wasm','bin',
    ];
    return in_array($ext, $ok, true);
}

function dl_is_deep_scannable(string $url): bool {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    return in_array($ext, ['css','scss','sass','less','styl','js','mjs','cjs','ts','jsx','tsx','html','htm','xhtml','php'], true);
}

function dl_should_skip(string $url): bool {
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    return in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tiff','avif','heic',
                            'mp4','avi','mov','mkv','webm','m4v','mp3','wav','ogg','flac',
                            'zip','rar','tar','gz'], true);
}

// ── URL resolution ─────────────────────────────────────────────
function dl_resolve(string $url, string $base): ?string {
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $url = str_replace(['\\/', '\\"'], ['/', '"'], $url);

    if (empty($url) || preg_match('/^(#|data:|mailto:|javascript:|tel:|blob:)/i', $url)) return null;

    $url = strtok($url, '#'); // strip fragments
    if (!$url) return null;

    if (preg_match('#^https?://#i', $url)) return $url;

    $bp = parse_url($base);
    if (!$bp || !isset($bp['host'])) return null;

    $scheme = strtolower($bp['scheme'] ?? 'https');
    $host   = strtolower($bp['host']);
    $port   = isset($bp['port']) ? ':' . $bp['port'] : '';
    $root   = "{$scheme}://{$host}{$port}";

    if (str_starts_with($url, '//'))  return $scheme . ':' . $url;
    if (str_starts_with($url, '/'))   return $root . $url;

    // Relative
    $basePath = $bp['path'] ?? '/';
    $dir = pathinfo($basePath, PATHINFO_EXTENSION) !== '' ? dirname($basePath) : rtrim($basePath, '/');
    $resolved = rtrim($dir, '/') . '/' . $url;

    // Collapse . and ..
    $parts = explode('/', $resolved);
    $out = [];
    foreach ($parts as $p) {
        if ($p === '' || $p === '.') continue;
        if ($p === '..') array_pop($out);
        else $out[] = $p;
    }
    return $root . '/' . implode('/', $out);
}

// ── Local file path ────────────────────────────────────────────
function dl_local_path(string $url, string $outDir): string {
    $p    = parse_url($url);
    $host = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $p['host'] ?? 'unknown');
    $path = trim($p['path'] ?? 'index', '/') ?: 'index';
    if (!pathinfo($path, PATHINFO_EXTENSION)) $path .= '/index.dat';
    $segs = array_map(fn($s) => preg_replace('/[^a-zA-Z0-9._\-]/', '_', $s), explode('/', $path));
    $file = array_pop($segs);
    if (!pathinfo($file, PATHINFO_EXTENSION)) $file .= '.dat';
    $segs[] = $file;
    return $outDir . DIRECTORY_SEPARATOR . $host . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segs);
}

// ── HTML link extraction ───────────────────────────────────────
function dl_extract_links(string $html, string $base): array {
    $patterns = [
        '/\bhref\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/\bsrc\s*=\s*["\']([^"\'<>]+)["\']/i',
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
        '/<source[^>]+src=["\']([^"\']+)["\']/i',
        '/<source[^>]+srcset=["\']([^"\']+)["\']/i',
        '/<video[^>]+poster=["\']([^"\']+)["\']/i',
        '/<object[^>]+data=["\']([^"\']+)["\']/i',
        '/<embed[^>]+src=["\']([^"\']+)["\']/i',
        '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+property=["\']og:image:url["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+property=["\']og:image:secure_url["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+name=["\']twitter:image:src["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+name=["\']thumbnail["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+itemprop=["\']image["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\'](?:shortcut\s+)?icon["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']apple-touch-startup-image["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']manifest["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']preload["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']prefetch["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<link[^>]+rel=["\']modulepreload["\'][^>]+href=["\']([^"\']+)["\']/i',
        '/<amp-img[^>]+src=["\']([^"\']+)["\']/i',
        '/<amp-img[^>]+srcset=["\']([^"\']+)["\']/i',
        '/<amp-video[^>]+poster=["\']([^"\']+)["\']/i',
        '/<amp-anim[^>]+src=["\']([^"\']+)["\']/i',
        '/<image[^>]+href=["\']([^"\']+)["\']/i',
        '/<image[^>]+xlink:href=["\']([^"\']+)["\']/i',
        '/<use[^>]+href=["\']([^"\']+)["\']/i',
        '/<use[^>]+xlink:href=["\']([^"\']+)["\']/i',
        '/\bsrcset\s*=\s*["\']([^"\'<>]+)["\']/i',
        '/style\s*=\s*["\'][^"\']*url\(["\']?([^"\'()\s]+)["\']?\)/i',
        '/background\s*=\s*["\']([^"\']+)["\']/i',
        '/bgcolor\s*=\s*["\']([^"\']+)["\']/i',
    ];

    $found = [];
    foreach ($patterns as $pat) {
        if (!preg_match_all($pat, $html, $m)) continue;
        foreach ($m[1] as $raw) {
            if (str_contains($raw, ',')) {
                foreach (explode(',', $raw) as $src) {
                    $src = trim(preg_replace('/\s+\d+[wx]\s*$/', '', $src));
                    $abs = dl_resolve(trim($src), $base);
                    if ($abs) $found[] = $abs;
                }
            } else {
                $abs = dl_resolve(trim($raw), $base);
                if ($abs) $found[] = $abs;
            }
        }
    }

    $found = array_unique($found);
    $found = array_filter($found, function(string $url): bool {
        $skipPatterns = [
            'google-analytics.com', 'googletagmanager.com', 'facebook.com/tr',
            'doubleclick.net', 'pixel.quantserve.com', 'analytics.twitter.com',
            'bat.bing.com', 'connect.facebook.net/signals',
        ];
        foreach ($skipPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return false;
            }
        }
        return true;
    });

    return array_values($found);
}

// ── CSS deep scan ──────────────────────────────────────────────
function dl_scan_css(string $css, string $base): array {
    $found = [];
    $patterns = [
        '/url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i',
        '/@import\s+(?:url\(\s*)?["\']([^"\'()\s]+)["\']\s*\)?/i',
        '/src\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i',
        '/background(?:-image)?\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i',
        '/filter\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i',
        '/cursor\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i',
        '/(?:-webkit-)?mask(?:-image)?\s*:\s*url\(\s*["\']?([^"\'()\s]+)["\']?\s*\)/i',
        '/\/[*@]#\s*sourceMappingURL=([^\s*]+)/i',
    ];
    foreach ($patterns as $p) {
        if (!preg_match_all($p, $css, $m)) continue;
        foreach ($m[1] as $url) {
            $abs = dl_resolve(trim($url), $base);
            if ($abs && dl_allowed($abs)) $found[] = $abs;
        }
    }
    return array_unique($found);
}

// ── JS deep scan ──────────────────────────────────────────────
function dl_scan_js(string $js, string $base): array {
    $found = [];
    $patterns = [
        '/import\s+(?:[\w*\s{},]*from\s+)?["\']([^"\']+)["\']/m',
        '/import\s*\(\s*["\']([^"\']+)["\']\s*\)/',
        '/require\s*\(\s*["\']([^"\']+)["\']\s*\)/',
        '/new\s+Worker\s*\(\s*["\']([^"\']+)["\']\s*\)/',
        '/navigator\.serviceWorker\.register\s*\(\s*["\']([^"\']+)["\']\s*\)/',
        '/\/\/[@#]\s*sourceMappingURL=([^\s]+)/i',
        '/["\']([^"\']*\.(?:png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|eot))["\']/',
        '/["\']((?:https?:)?\/\/[^"\']+\.(?:chunk|bundle|vendor|app)\.[a-f0-9]+\.js)["\']/i',
    ];
    foreach ($patterns as $p) {
        if (!preg_match_all($p, $js, $m)) continue;
        foreach ($m[1] as $url) {
            if (preg_match('#^https?://#i', $url) || str_starts_with($url, '/') || str_starts_with($url, './')) {
                $abs = dl_resolve(trim($url), $base);
                if ($abs && dl_allowed($abs)) $found[] = $abs;
            }
        }
    }
    return array_unique($found);
}

function dl_deep_scan_file(string $filePath, string $base): array {
    $found = [];
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return $found;
    }

    $size = filesize($filePath);
    if ($size === 0 || $size > DL_MAX_DEEP_SIZE) {
        return $found;
    }

    $content = @file_get_contents($filePath);
    if ($content === false) {
        return $found;
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (in_array($ext, ['css','scss','sass','less','styl'], true)) {
        $found = dl_scan_css($content, $base);
        dl_log('scan', "Deep scanned CSS: " . basename($filePath) . " → found " . count($found) . " resources");
    } elseif (in_array($ext, ['js','mjs','cjs','ts','jsx','tsx'], true)) {
        $found = dl_scan_js($content, $base);
        dl_log('scan', "Deep scanned JS: " . basename($filePath) . " → found " . count($found) . " resources");
    } else {
        $found = dl_extract_links($content, $base);
        dl_log('scan', "Deep scanned HTML: " . basename($filePath) . " → found " . count($found) . " resources");
    }

    return array_unique($found);
}

// ── Fetch page HTML ────────────────────────────────────────────
function dl_fetch_page(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 8,
        CURLOPT_CONNECTTIMEOUT => DL_CONN_TIMEOUT,
        CURLOPT_TIMEOUT        => DL_PAGE_TIMEOUT,
        CURLOPT_USERAGENT      => DL_UA,
        CURLOPT_ENCODING       => '',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Referer: ' . $url,
        ],
        CURLOPT_AUTOREFERER    => true,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $ct    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return compact('body', 'err', 'code', 'final', 'ct');
}

// ── CDN fallback URLs ──────────────────────────────────────────
function dl_cdn_fallbacks(string $failedUrl, string $origin): array {
    $fp = parse_url($failedUrl);
    $op = parse_url($origin);
    if (!$fp || !$op) return [];

    $failedHost = $fp['host'] ?? '';
    $originHost = $op['host'] ?? '';
    $path       = $fp['path'] ?? '/';
    $file       = basename($path);
    $scheme     = strtolower($op['scheme'] ?? 'https');
    $root       = "{$scheme}://{$originHost}";

    $dirs = ['assets','static','public','dist','build','img','images','css','js','fonts','media','files','uploads'];
    $out  = [];

    if ($failedHost !== $originHost) $out[] = "{$root}{$path}";
    foreach ($dirs as $d) $out[] = "{$root}/{$d}/{$file}";
    if (count(array_filter(explode('/', trim($path, '/')))) > 1) {
        $parts = array_values(array_filter(explode('/', trim($path, '/'))));
        $out[] = "{$root}/" . implode('/', array_slice($parts, -2));
        $out[] = "{$root}/assets/" . implode('/', array_slice($parts, -2));
    }
    if (!str_starts_with($originHost, 'www.')) $out[] = "{$scheme}://www.{$originHost}{$path}";

    return array_unique(array_values(array_filter($out, fn($u) => $u !== $failedUrl)));
}

// ── Parallel batch downloader ──────────────────────────────────
function dl_batch(array $urls, string $outDir, string $host = '', int $proxyAttempt = 0): array {
    $results = [];
    $handles = [];
    $fps     = [];
    $dests   = [];
    $headers = [];
    $mh      = curl_multi_init();
    curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, count($urls));

    foreach ($urls as $url) {
        $dest = dl_local_path($url, $outDir);
        $dir  = dirname($dest);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $results[$url] = ['ok' => false, 'code' => 0, 'size' => 0, 'dest' => $dest, 'err' => 'mkdir failed', 'retryAfter' => null];
            continue;
        }

        if (file_exists($dest) && filesize($dest) > 0) {
            $results[$url] = ['ok' => true, 'code' => 200, 'size' => filesize($dest), 'dest' => $dest, 'err' => '', 'retryAfter' => null, 'cached' => true];
            continue;
        }

        $fp = @fopen($dest, 'wb');
        if (!$fp) {
            $results[$url] = ['ok' => false, 'code' => 0, 'size' => 0, 'dest' => $dest, 'err' => 'fopen failed', 'retryAfter' => null];
            continue;
        }

        $parsed = parse_url($url);
        $referer = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . '/';
        $headers[$url] = '';

        $curlOpts = [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_CONNECTTIMEOUT => DL_CONN_TIMEOUT,
            CURLOPT_TIMEOUT        => DL_TIMEOUT,
            CURLOPT_USERAGENT      => DL_UA,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => ['Accept: */*', 'Accept-Language: en-US,en;q=0.5', 'Cache-Control: no-cache', 'Referer: ' . $referer],
            CURLOPT_HEADERFUNCTION => function($ch, $header) use ($url, &$headers) {
                $headers[$url] .= $header;
                return strlen($header);
            },
            CURLOPT_NOPROGRESS     => false,
            CURLOPT_PROGRESSFUNCTION => fn($ch, $dlTotal, $dlNow) => $dlNow > DL_MAX_FILE ? 1 : 0,
        ];

        // Attach proxy if we're in a proxy attempt
        if ($proxyAttempt > 0) {
            $proxy = dl_next_proxy(parse_url($url, PHP_URL_HOST) ?? '');
            if ($proxy) {
                $curlOpts[CURLOPT_PROXY] = $proxy;
                if (str_starts_with($proxy, 'socks5://')) {
                    $curlOpts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
                } elseif (str_starts_with($proxy, 'socks4://')) {
                    $curlOpts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS4;
                }
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $curlOpts);
        $handles[$url] = $ch;
        $fps[$url]     = $fp;
        $dests[$url]   = $dest;
        curl_multi_add_handle($mh, $ch);
    }

    // Execute
    $active = null;
    $tStart = time();
    do {
        $status = curl_multi_exec($mh, $active);
        if ($active && $status === CURLM_OK) curl_multi_select($mh, 0.1);
        if (time() - $tStart > DL_TIMEOUT + 30) break;
    } while ($active && $status === CURLM_OK);

    // Collect
    foreach ($handles as $url => $ch) {
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $err  = curl_error($ch);
        $retryAfter = null;

        if (preg_match('/^Retry-After:\s*(\d+)/im', $headers[$url] ?? '', $m)) {
            $retryAfter = (int) $m[1];
        }

        fclose($fps[$url]);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        $ok = !$err && $code >= 200 && $code < 400 && $size > 0;
        if ($code === 429) {
            $retryAfter = $retryAfter ?: 10;
            $ok = false;
        }
        if ($ok && file_exists($dests[$url]) && filesize($dests[$url]) > DL_MAX_FILE) {
            @unlink($dests[$url]); $ok = false; $err = 'exceeds max size';
        }
        if (!$ok && file_exists($dests[$url])) @unlink($dests[$url]);

        $results[$url] = compact('ok', 'code', 'size', 'err', 'retryAfter') + ['dest' => $dests[$url]];
    }

    curl_multi_close($mh);
    return $results;
}

// ── ZIP packaging ──────────────────────────────────────────────
function dl_make_zip(string $srcDir, string $zipPath): bool {
    if (!class_exists('ZipArchive') || !is_dir($srcDir)) return false;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
    $it  = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $base = strlen(rtrim($srcDir, DIRECTORY_SEPARATOR)) + 1;
    foreach ($it as $f) {
        if ($f->isReadable()) $zip->addFile($f->getRealPath(), substr($f->getRealPath(), $base));
    }
    $zip->close();
    return file_exists($zipPath) && filesize($zipPath) > 0;
}

// ── Expire marker ──────────────────────────────────────────────
// ════════════════════════════════════════════════════════════════
//  MAIN
// ════════════════════════════════════════════════════════════════

$url       = trim($_POST['url'] ?? '');
$outName   = preg_replace('/[^a-zA-Z0-9._\-\/]/', '_', trim($_POST['outdir'] ?? 'downloaded')) ?: 'downloaded';
$conc      = max(1, min(32, (int)($_POST['concurrency'] ?? DL_CONCURRENCY)));
$deepScan  = !empty($_POST['deepscan']);
$createZip = !empty($_POST['create_zip']);

if (!$url) {
    dl_sse('error', ['msg' => 'No URL provided']);
    exit;
}

$baseWork  = __DIR__ . '/../../downloads';
if (!is_dir($baseWork)) @mkdir($baseWork, 0755, true);

$sessionId  = date('Ymd_His') . '_' . substr(md5($url . microtime()), 0, 6);
$sessionDir = $baseWork . '/' . $sessionId;
$outDir     = $sessionDir . '/' . $outName;
$zipName    = 'dl_' . $sessionId . '.zip';
$zipPath    = $baseWork . '/' . $zipName;

if (!is_dir($sessionDir)) @mkdir($sessionDir, 0755, true);
dl_write_expire($sessionDir);
if ($createZip) dl_write_expire($zipPath);

// 1. Fetch page
dl_log('head', "Fetching page: {$url}");
$page = dl_fetch_page($url);

if ($page['err'] || !$page['body'] || $page['code'] < 200 || $page['code'] >= 400) {
    dl_sse('error', ['msg' => "Page fetch failed: " . ($page['err'] ?: "HTTP {$page['code']}")]);
    exit;
}

$base   = $page['final'] ?: $url;
$origin = (parse_url($base, PHP_URL_SCHEME) ?: 'https') . '://' . (parse_url($base, PHP_URL_HOST) ?: '');
dl_log('ok', "Page OK  HTTP {$page['code']}  " . dl_fmt_size(strlen($page['body'])));
dl_log('info', "Origin: {$origin}");

// 2. Extract links
$links     = dl_extract_links($page['body'], $base);
$fileLinks = array_values(array_filter($links, 'dl_allowed'));
dl_log('info', count($links) . " links found · " . count($fileLinks) . " downloadable");

// 3. Build queue
$processed = [];
$queue     = [];
$retryMap  = [];
$stats     = ['ok' => 0, 'fail' => 0, 'retry' => 0, 'cdn' => 0, 'bytes' => 0, 'skip' => 0, 'scanned' => 0];

foreach ($fileLinks as $u) {
    $dest = dl_local_path($u, $outDir);
    if (file_exists($dest) && filesize($dest) > 0) { $stats['skip']++; continue; }
    $queue[] = $u;
    $processed[$u] = true;
}

$stats['total'] = count($fileLinks);
dl_log('info', count($queue) . " to download · {$stats['skip']} cached");

if (empty($queue)) {
    dl_sse('done', array_merge($stats, ['zip' => '', 'zipSize' => 0, 'sessionId' => $sessionId]));
    exit;
}

// ── Download rounds ────────────────────────────────────────────
$deepScanQueue = [];
$round = 0;

while (!empty($queue) && $round < 12) {
    $round++;
    dl_log('head', "═ Round {$round} — " . count($queue) . " URLs");

    $nextQueue    = [];
    $rateLimited  = []; // url => wait_secs
    $cdnQueue     = []; // [original, cdn, priority]

    foreach (array_chunk($queue, $conc) as $bIdx => $batch) {
        try { dl_check_timeout(); } catch (RuntimeException $e) { break 2; }

        $results = dl_batch($batch, $outDir);

        foreach ($results as $u => $r) {
            if (!empty($r['cached'])) { $stats['skip']++; continue; }

            if ($r['ok']) {
                dl_log('ok', basename($r['dest']) . " (" . dl_fmt_size($r['size']) . ")");
                $stats['ok']++;
                $stats['bytes'] += $r['size'];
                if ($deepScan && dl_is_deep_scannable($u)) {
                    $deepScanQueue[] = ['url' => $u, 'file' => $r['dest']];
                }

            } elseif ($r['code'] === 429) {
                // 429 — rotate proxy
                $wait = $r['retryAfter'] ?? 5;
                $proxyAttempt = ($retryMap[$u . '_proxy'] ?? 0) + 1;
                $retryMap[$u . '_proxy'] = $proxyAttempt;

                if ($proxyAttempt <= count(dl_proxy_pool())) {
                    $errMsg = $r['err'] ? " ({$r['err']})" : '';
                    dl_log('warn', "429 for {$u}{$errMsg} — rotating proxy (attempt {$proxyAttempt})");
                    // Re-download immediately via proxy
                    $proxyResult = dl_batch([$u], $outDir, parse_url($u, PHP_URL_HOST) ?? '', $proxyAttempt);
                    $pr = $proxyResult[$u] ?? ['ok' => false, 'code' => 0, 'size' => 0, 'err' => 'proxy retry failed'];
                    if ($pr['ok']) {
                        dl_log('cdn', "Proxy OK: {$u} (" . dl_fmt_size($pr['size']) . ")");
                        $stats['ok']++;
                        $stats['bytes'] += $pr['size'];
                        if ($deepScan && dl_is_deep_scannable($u)) {
                            $deepScanQueue[] = ['url' => $u, 'file' => $pr['dest']];
                        }
                    } else {
                        $errMsg = $pr['err'] ? " ({$pr['err']})" : '';
                        dl_log('warn', "Proxy failed too{$errMsg} — queuing for CDN fallback: {$u}");
                        $rateLimited[$u] = $wait;
                    }
                } else {
                    $errMsg = $r['err'] ? " ({$r['err']})" : '';
                    dl_log('warn', "All proxies exhausted for {$u}{$errMsg} — CDN fallback");
                    $rateLimited[$u] = $wait;
                }

            } elseif (($retryMap[$u] ?? 0) < DL_MAX_RETRIES) {
                $retryMap[$u] = ($retryMap[$u] ?? 0) + 1;
                $errMsg = $r['err'] ? " ({$r['err']})" : '';
                dl_log('retry', "Retry {$retryMap[$u]}/" . DL_MAX_RETRIES . " (HTTP {$r['code']}){$errMsg} — {$u}");
                $nextQueue[] = $u;
                $stats['retry']++;

            } else {
                // Persistent failure → CDN fallback
                $fallbacks = dl_cdn_fallbacks($u, $origin);
                if ($fallbacks) {
                    foreach (array_slice($fallbacks, 0, 4) as $i => $cdnUrl) {
                        if (!isset($processed[$cdnUrl])) {
                            $cdnQueue[] = ['original' => $u, 'cdn' => $cdnUrl, 'priority' => $i];
                        }
                    }
                    dl_log('cdn', "CDN fallback queue for {$u} (" . count($fallbacks) . " candidates)");
                } else {
                    $errMsg = $r['err'] ? " ({$r['err']})" : '';
                    dl_log('err', "FAILED (HTTP {$r['code']}){$errMsg} — {$u}");
                    $stats['fail']++;
                }
            }
        }

        // Handle rate-limited batch (brief sleep + CDN fallback)
        if ($rateLimited) {
            $maxWait = min(max($rateLimited), 15);
            dl_log('warn', "Sleeping {$maxWait}s for " . count($rateLimited) . " rate-limited URLs");
            sleep($maxWait);
            // Move to CDN fallback after rate-limit sleep
            foreach ($rateLimited as $u => $w) {
                $fallbacks = dl_cdn_fallbacks($u, $origin);
                if ($fallbacks) {
                    foreach (array_slice($fallbacks, 0, 4) as $i => $cdnUrl) {
                        if (!isset($processed[$cdnUrl])) {
                            $cdnQueue[] = ['original' => $u, 'cdn' => $cdnUrl, 'priority' => $i];
                        }
                    }
                } else {
                    $errMsg = $r['err'] ? " ({$r['err']})" : '';
                    dl_log('err', "FAILED — {$u}{$errMsg}");
                    $stats['fail']++;
                }
            }
            $rateLimited = [];
        }

        // Process CDN fallback queue
        if ($cdnQueue) {
            usort($cdnQueue, fn($a, $b) => $a['priority'] - $b['priority']);
            $doneCdn = [];

            foreach (array_chunk($cdnQueue, $conc) as $cdnBatch) {
                $cdnUrls   = [];
                $cdnToOrig = [];
                foreach ($cdnBatch as $item) {
                    if (!in_array($item['cdn'], $doneCdn)) {
                        $cdnToOrig[$item['cdn']] = $item['original'];
                        $cdnUrls[] = $item['cdn'];
                        $doneCdn[] = $item['cdn'];
                        $processed[$item['cdn']] = true;
                    }
                }
                if (!$cdnUrls) continue;

                $cdnResults = dl_batch($cdnUrls, $outDir);
                $foundOk    = [];

                foreach ($cdnResults as $cdnUrl => $cr) {
                    $orig = $cdnToOrig[$cdnUrl];
                    if ($cr['ok'] && !isset($foundOk[$orig])) {
                        $foundOk[$orig] = true;
                        dl_log('cdn', "CDN ✓ " . basename($cr['dest']) . " (" . dl_fmt_size($cr['size']) . ")");
                        $stats['cdn']++;
                        $stats['bytes'] += $cr['size'];
                        if ($deepScan && dl_is_deep_scannable($cdnUrl)) {
                            $deepScanQueue[] = ['url' => $cdnUrl, 'file' => $cr['dest']];
                        }
                    }
                }

                // Any original that never resolved
                foreach ($cdnBatch as $item) {
                    if (!isset($foundOk[$item['original']])) {
                        dl_log('err', "All CDN fallbacks failed — " . $item['original']);
                        $stats['fail']++;
                    }
                }
            }
            $cdnQueue = [];
        }
    }

    $queue = array_unique($nextQueue);
}

// -- Phase 2: deep scan --
if ($deepScan && !empty($deepScanQueue)) {
    dl_log('head', 'Deep scanning ' . count($deepScanQueue) . ' files');
    $discovered = [];

    foreach ($deepScanQueue as $item) {
        if (!file_exists($item['file']) || filesize($item['file']) > DL_MAX_DEEP_SIZE) {
            dl_log('warn', "Skipped deep scan (missing/too-large) — {$item['url']}");
            continue;
        }
        $newUrls = dl_deep_scan_file($item['file'], $item['url']);
        foreach ($newUrls as $nu) {
            if (!isset($processed[$nu])) {
                $discovered[] = $nu;
                $processed[$nu] = true;
            }
        }
        $stats['scanned']++;
    }

    $discovered = array_values(array_filter(array_unique($discovered), 'dl_allowed'));
    dl_log('scan', count($discovered) . " new resources from deep scan");

    if (!empty($discovered)) {
        $dlNew = [];
        foreach ($discovered as $u) {
            $d = dl_local_path($u, $outDir);
            if (file_exists($d) && filesize($d) > 0) {
                $stats['skip']++;
                continue;
            }
            $dlNew[] = $u;
        }

        $subRound = 0;
        while (!empty($dlNew) && $subRound < 4) {
            $subRound++;
            $nextNew = [];
            $scanQueue = [];

            foreach (array_chunk($dlNew, $conc) as $batch) {
                $results = dl_batch($batch, $outDir);

                foreach ($results as $u => $r) {
                    if ($r['ok']) {
                        dl_log('ok', "⌕ " . basename($r['dest']) . " (" . dl_fmt_size($r['size']) . ")");
                        $stats['ok']++;
                        $stats['bytes'] += $r['size'];
                        if ($deepScan && dl_is_deep_scannable($u)) {
                            $scanQueue[] = ['url' => $u, 'file' => $r['dest']];
                        }
                    } elseif (($retryMap[$u] ?? 0) < 2) {
                        $retryMap[$u] = ($retryMap[$u] ?? 0) + 1;
                        $nextNew[] = $u;
                    } else {
                        $errMsg = $r['err'] ? " ({$r['err']})" : '';
                        dl_log('err', "Deep scan failed — {$u} (HTTP {$r['code']}){$errMsg}");
                        $stats['fail']++;
                    }
                }
            }

            if ($scanQueue) {
                foreach ($scanQueue as $item) {
                    $newUrls = dl_deep_scan_file($item['file'], $item['url']);
                    foreach ($newUrls as $nu) {
                        if (!isset($processed[$nu])) {
                            $processed[$nu] = true;
                            $nextNew[] = $nu;
                        }
                    }
                    $stats['scanned']++;
                }
            }

            $nextNew = array_values(array_filter(array_unique($nextNew), 'dl_allowed'));
            $dlNew = [];
            foreach ($nextNew as $u) {
                $d = dl_local_path($u, $outDir);
                if (file_exists($d) && filesize($d) > 0) {
                    $stats['skip']++;
                    continue;
                }
                $dlNew[] = $u;
            }
        }
    }
}

// ── ZIP ────────────────────────────────────────────────────────
$zipReady = false;
if ($createZip && $stats['ok'] > 0) {
    dl_log('head', "Packaging ZIP…");
    if (dl_make_zip($sessionDir, $zipPath)) {
        $zipReady = true;
        dl_log('ok', "ZIP ready — " . dl_fmt_size(filesize($zipPath)));
    } else {
        dl_log('err', "ZIP failed");
    }
}

// ── Summary ────────────────────────────────────────────────────
$elapsed = round(microtime(true) - DL_START, 1);
$speed   = $elapsed > 0 ? (int)($stats['bytes'] / $elapsed) : 0;
dl_log('head', "Done in {$elapsed}s · " . dl_fmt_size($speed) . "/s");
dl_log('ok',   "✓ {$stats['ok']} downloaded · ⇢ {$stats['cdn']} CDN · ↺ {$stats['retry']} retried · ✗ {$stats['fail']} failed");
dl_log('info', "Total: " . dl_fmt_size($stats['bytes']));

dl_sse('done', [
    'ok'        => $stats['ok'],
    'fail'      => $stats['fail'],
    'retry'     => $stats['retry'],
    'bytes'     => $stats['bytes'],
    'zip'       => $zipReady ? $zipName : '',
    'zipSize'   => ($zipReady && file_exists($zipPath)) ? filesize($zipPath) : 0,
    'sessionId' => $sessionId,
]);
exit;
