<?php
// Session cleanup — called via navigator.sendBeacon on tab close
http_response_code(204);

$sessionId = basename($_POST['session'] ?? '');
if (!$sessionId || !preg_match('/^[a-zA-Z0-9_\-]+$/', $sessionId)) exit;

$baseWork   = __DIR__ . '/../../downloads';
$sessionDir = $baseWork . '/' . $sessionId;
$zipPattern = $baseWork . '/dl_' . $sessionId . '.zip';

// Update session expiration to 5 minutes from now
if (is_dir($sessionDir)) {
    file_put_contents($sessionDir . '.expire', (string)(time() + 300));
}

// Update ZIP expiration to 5 minutes from now
if (file_exists($zipPattern)) {
    file_put_contents($zipPattern . '.expire', (string)(time() + 300));
}

exit;
