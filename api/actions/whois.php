<?php
header('Content-Type: application/json');

        header('Content-Type: application/json');
        $domain = trim($_GET['domain'] ?? '');
        if (!$domain) { echo json_encode(['error' => 'No domain provided']); exit; }
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = strtolower(trim(explode('/', $domain)[0]));

        $result = ['domain' => $domain, 'source' => 'rdap'];

        // Try RDAP (modern WHOIS replacement — JSON, no rate limits)
        $rdapUrl = 'https://rdap.org/domain/' . urlencode($domain);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $rdapUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $rdapBody = curl_exec($ch);
        $rdapCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rdapCode === 200 && $rdapBody) {
            $rdap = json_decode($rdapBody, true);
            if ($rdap) {
                // Registrar
                $registrar = '';
                foreach ((array)($rdap['entities'] ?? []) as $entity) {
                    $roles = $entity['roles'] ?? [];
                    if (in_array('registrar', $roles)) {
                        $registrar = $entity['vcardArray'][1][1][3] ?? ($entity['handle'] ?? '');
                        if (empty($registrar) && isset($entity['publicIds'])) {
                            foreach ($entity['publicIds'] as $pid) {
                                if ($pid['type'] === 'IANA Registrar ID') { $registrar = 'IANA ID: ' . $pid['identifier']; break; }
                            }
                        }
                    }
                }

                // Registrant
                $registrant = ''; $registrantEmail = ''; $registrantOrg = '';
                foreach ((array)($rdap['entities'] ?? []) as $entity) {
                    $roles = $entity['roles'] ?? [];
                    if (in_array('registrant', $roles)) {
                        $vcard = $entity['vcardArray'][1] ?? [];
                        foreach ($vcard as $v) {
                            if ($v[0] === 'fn')  $registrant = $v[3] ?? '';
                            if ($v[0] === 'org') $registrantOrg = $v[3] ?? '';
                            if ($v[0] === 'email') $registrantEmail = $v[3] ?? '';
                        }
                    }
                }

                // Dates
                $created = $updated = $expires = '';
                foreach ((array)($rdap['events'] ?? []) as $ev) {
                    switch ($ev['eventAction'] ?? '') {
                        case 'registration': $created = substr($ev['eventDate'] ?? '', 0, 10); break;
                        case 'last changed':
                        case 'last update of RDAP database':
                            if (!$updated) $updated = substr($ev['eventDate'] ?? '', 0, 10); break;
                        case 'expiration': $expires = substr($ev['eventDate'] ?? '', 0, 10); break;
                    }
                }

                // Status
                $statuses = array_map('strtolower', $rdap['status'] ?? []);

                // Nameservers
                $nameservers = [];
                foreach ((array)($rdap['nameservers'] ?? []) as $ns) {
                    $nameservers[] = strtolower(rtrim($ns['ldhName'] ?? '', '.'));
                }

                // DNSSEC
                $dnssec = !empty($rdap['secureDNS']['delegationSigned']) ? 'Signed' : 'Unsigned';

                $result = array_merge($result, [
                    'registrar'       => $registrar ?: 'Not disclosed',
                    'registrant'      => $registrant ?: 'Not disclosed (RDAP privacy)',
                    'registrant_org'  => $registrantOrg,
                    'registrant_email'=> $registrantEmail ?: 'Redacted for privacy',
                    'created'         => $created,
                    'updated'         => $updated,
                    'expires'         => $expires,
                    'status'          => $statuses,
                    'nameservers'     => $nameservers,
                    'dnssec'          => $dnssec,
                    'handle'          => $rdap['handle'] ?? '',
                    'tld'             => pathinfo($domain, PATHINFO_EXTENSION),
                    'raw_rdap'        => true,
                ]);

                // Days until expiry
                if ($expires) {
                    $diff = (new DateTime($expires))->diff(new DateTime());
                    $result['days_until_expiry'] = $expires >= date('Y-m-d') ? $diff->days : -$diff->days;
                }
            } else {
                $result['error'] = 'RDAP returned invalid JSON';
            }
        } else {
            $result['error'] = "RDAP lookup failed (HTTP $rdapCode). Domain may not exist or RDAP not supported for this TLD.";
        }

        // Also fetch NS records for this domain
        $nsResp = null;
        $nsUrl = 'https://dns.google/resolve?name=' . urlencode($domain) . '&type=NS';
        $ch2 = curl_init();
        curl_setopt_array($ch2, [CURLOPT_URL=>$nsUrl, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_HTTPHEADER=>['Accept: application/dns-json']]);
        $nsBody = curl_exec($ch2); curl_close($ch2);
        if ($nsBody) {
            $nsData = json_decode($nsBody, true);
            if (!empty($nsData['Answer'])) {
                $nsLive = [];
                foreach ($nsData['Answer'] as $a) {
                    if (($a['type'] ?? 0) == 2) $nsLive[] = strtolower(rtrim($a['data'], '.'));
                }
                $result['nameservers_live'] = array_unique($nsLive);
            }
        }

        echo json_encode($result);
        exit;
