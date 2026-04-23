<?php
header('Content-Type: application/json');

        header('Content-Type: application/json');
        $url = strtolower(filter_var(trim($_GET['url'] ?? ''), FILTER_SANITIZE_URL));
        if (!$url) { echo json_encode(['error' => 'No URL']); exit; }
        $parsedUrl = parse_url($url);
        $baseUrl   = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');

        $candidates = [
            $baseUrl . '/sitemap.xml',
            $baseUrl . '/sitemap_index.xml',
            $baseUrl . '/sitemap.xml.gz',
            $baseUrl . '/sitemap/',
            $baseUrl . '/sitemap.php',
        ];

        // Also check robots.txt for Sitemap: directive
        $robotsFound = [];
        $chR = curl_init();
        curl_setopt_array($chR, [CURLOPT_URL=>$baseUrl.'/robots.txt', CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_TIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>true]);
        $robotsBody = curl_exec($chR);
        curl_close($chR);
        if ($robotsBody) {
            preg_match_all('/^Sitemap:\s*(.+)$/im', $robotsBody, $matches);
            foreach ($matches[1] as $sm) {
                $sm = trim($sm);
                if ($sm && !in_array($sm, $candidates)) $candidates[] = $sm;
                $robotsFound[] = $sm;
            }
        }

        $found = [];
        foreach ($candidates as $sitemapUrl) {
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL=>$sitemapUrl, CURLOPT_RETURNTRANSFER=>true, CURLOPT_NOBODY=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_TIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>true]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 200 && $code < 400) {
                $found[] = $sitemapUrl;
            }
        }

        echo json_encode([
            'sitemaps'       => $found,
            'from_robots'    => $robotsFound,
            'base_url'       => $baseUrl,
            'checked'        => $candidates,
        ]);
        exit;
