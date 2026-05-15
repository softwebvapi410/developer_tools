<?php
// Session cleanup — called via navigator.sendBeacon on tab close
http_response_code(204);

$sessionId = basename($_POST['session'] ?? '');
if (!$sessionId || !preg_match('/^[a-zA-Z0-9_\-]+$/', $sessionId)) exit;

$baseWork   = __DIR__ . '/../../downloads';
$sessionDir = $baseWork . '/' . $sessionId;
$zipPattern = $baseWork . '/dl_' . $sessionId . '.zip';

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
@unlink($sessionDir . '.expire');

// Delete ZIP
if (file_exists($zipPattern)) @unlink($zipPattern);
@unlink($zipPattern . '.expire');

exit;
