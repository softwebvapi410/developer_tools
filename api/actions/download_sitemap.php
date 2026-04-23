<?php
        if (empty($_POST['sitemap_data'])) { http_response_code(400); echo "No sitemap data."; exit; }
        $data = json_decode($_POST['sitemap_data'], true);
        if (!$data || !is_array($data)) { http_response_code(400); echo "Invalid data."; exit; }
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="sitemap.xml"');
        usort($data, fn($a,$b) => $b['priority'] <=> $a['priority']);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
        foreach ($data as $item) {
            $xml .= "    <url>\n";
            $xml .= "        <loc>" . htmlspecialchars($item['url']) . "</loc>\n";
            $xml .= "        <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "        <changefreq>" . htmlspecialchars($item['change_freq']) . "</changefreq>\n";
            $xml .= "        <priority>" . number_format($item['priority'], 1) . "</priority>\n";
            $xml .= "    </url>\n";
        }
        $xml .= '</urlset>';
        echo $xml;
        exit;
