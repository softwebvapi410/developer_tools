<?php
header('Content-Type: application/json');

        header('Content-Type: application/json');
        $domain = trim($_GET['domain'] ?? '');
        $type   = strtoupper(trim($_GET['type'] ?? 'ALL'));
        if (!$domain) { echo json_encode(['error' => 'No domain provided']); exit; }
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = strtolower(trim(explode('/', $domain)[0]));
        $domain = rtrim($domain, '.');

        // Helper: query Google DNS-over-HTTPS
        function doh_query($name, $type) {
            $url = 'https://dns.google/resolve?name=' . urlencode($name) . '&type=' . urlencode($type) . '&do=1';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => ['Accept: application/dns-json'],
                CURLOPT_USERAGENT      => 'Mozilla/5.0',
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            if (!$body) return null;
            return json_decode($body, true);
        }

        // Parse DoH answer into normalised record array
        function parse_doh_answer($answers, $record_type) {
            $out = [];
            foreach ((array)$answers as $a) {
                $r = [
                    'record_type' => $record_type,
                    'host'        => rtrim($a['name'] ?? '', '.'),
                    'ttl'         => $a['TTL'] ?? null,
                    'raw'         => $a['data'] ?? '',
                ];
                $data = trim($a['data'] ?? '');
                switch ($record_type) {
                    case 'A':    $r['ip']     = $data; break;
                    case 'AAAA': $r['ipv6']   = $data; break;
                    case 'CNAME':
                    case 'NS':
                    case 'PTR':  $r['target'] = rtrim($data, '.'); break;
                    case 'MX':
                        if (preg_match('/^(\d+)\s+(.+)$/', $data, $m)) {
                            $r['pri']    = (int)$m[1];
                            $r['target'] = rtrim($m[2], '.');
                        } else { $r['target'] = $data; $r['pri'] = 0; }
                        break;
                    case 'TXT':
                        $r['txt'] = trim($data, '"');
                        // DoH sometimes wraps multi-part in quotes
                        $r['txt'] = str_replace('" "', '', $r['txt']);
                        break;
                    case 'SOA':
                        // "ns1.example.com. admin.example.com. 2024010101 3600 900 604800 300"
                        $parts = preg_split('/\s+/', $data);
                        $r['mname']      = rtrim($parts[0] ?? '', '.');
                        $r['rname']      = rtrim($parts[1] ?? '', '.');
                        $r['serial']     = $parts[2] ?? '';
                        $r['refresh']    = $parts[3] ?? '';
                        $r['retry']      = $parts[4] ?? '';
                        $r['expire']     = $parts[5] ?? '';
                        $r['minimum-ttl']= $parts[6] ?? '';
                        break;
                    case 'CAA':
                        // "0 issue \"letsencrypt.org\""
                        if (preg_match('/^(\d+)\s+(\S+)\s+"?([^"]*)"?$/', $data, $m)) {
                            $r['flags'] = $m[1]; $r['tag'] = $m[2]; $r['value'] = $m[3];
                        } else { $r['value'] = $data; }
                        break;
                    case 'SRV':
                        $parts = preg_split('/\s+/', $data);
                        $r['priority'] = $parts[0] ?? ''; $r['weight'] = $parts[1] ?? '';
                        $r['port'] = $parts[2] ?? ''; $r['target'] = rtrim($parts[3] ?? '', '.');
                        break;
                    default: $r['value'] = $data;
                }
                $out[] = $r;
            }
            return $out;
        }

        $lookupTypes = $type === 'ALL'
            ? ['A','AAAA','MX','NS','TXT','CNAME','SOA','CAA']
            : [$type];

        $results = [];
        $errors  = [];

        foreach ($lookupTypes as $t) {
            $resp = doh_query($domain, $t);
            if ($resp === null) { $errors[] = "Failed to query $t for $domain"; continue; }
            if (!empty($resp['Answer'])) {
                // Filter to matching type only (DoH may return CNAMEs in A queries)
                $typeNum = ['A'=>1,'NS'=>2,'CNAME'=>5,'SOA'=>6,'MX'=>15,'TXT'=>16,'AAAA'=>28,'SRV'=>33,'CAA'=>257,'PTR'=>12];
                $wanted  = $typeNum[$t] ?? null;
                $answers = $wanted ? array_filter($resp['Answer'], fn($a) => ($a['type'] ?? 0) == $wanted) : $resp['Answer'];
                $parsed  = parse_doh_answer(array_values($answers), $t);
                $results = array_merge($results, $parsed);
            }
            // NXDOMAIN / SERVFAIL surfacing
            if (($resp['Status'] ?? 0) === 3 && $t === 'A') {
                $errors[] = "NXDOMAIN — domain $domain does not exist.";
            }
        }

        // Always check _dmarc TXT
        $dmarcResp = doh_query('_dmarc.' . $domain, 'TXT');
        if (!empty($dmarcResp['Answer'])) {
            $dr = parse_doh_answer($dmarcResp['Answer'], 'TXT');
            foreach ($dr as &$d) { $d['host'] = '_dmarc.' . $domain; }
            $results = array_merge($results, $dr);
        }

        // Email health
        $spfFound = false; $dmarcFound = false; $dkimHint = false; $mxIssues = [];
        foreach ($results as $r) {
            if ($r['record_type'] === 'TXT') {
                $txt = $r['txt'] ?? $r['raw'] ?? '';
                if (stripos($txt, 'v=spf1')   !== false) $spfFound   = true;
                if (stripos($txt, 'v=DMARC1') !== false) $dmarcFound = true;
                if (stripos($txt, 'v=DKIM1')  !== false) $dkimHint   = true;
            }
        }
        // MX A-record validation
        foreach ($results as $r) {
            if ($r['record_type'] === 'MX' && !empty($r['target'])) {
                $aResp = doh_query($r['target'], 'A');
                if (empty($aResp['Answer'])) {
                    $mxIssues[] = "MX host '{$r['target']}' has no A record — may not receive email.";
                }
            }
        }

        echo json_encode([
            'domain'       => $domain,
            'type'         => $type,
            'records'      => array_values($results),
            'count'        => count($results),
            'email_health' => ['spf'=>$spfFound,'dmarc'=>$dmarcFound,'dkim'=>$dkimHint,'mx_issues'=>$mxIssues],
            'errors'       => $errors,
            'queried_at'   => date('Y-m-d H:i:s T'),
            'source'       => 'Google DNS-over-HTTPS',
        ]);
        exit;
