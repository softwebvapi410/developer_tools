<?php
// Serve a generated ZIP download
header('Content-Type: application/json'); // reset if no file found

$safe = basename($_GET['file'] ?? '');
if (!$safe || !preg_match('/^dl_[a-zA-Z0-9_]+\.zip$/', $safe)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file parameter']);
    exit;
}

$baseWork = __DIR__ . '/../../downloads';
$path     = $baseWork . '/' . $safe;

if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'zip') {
    http_response_code(404);
    echo json_encode(['error' => 'File not found or expired']);
    exit;
}

// Serve the file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $safe . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, no-store');
readfile($path);
exit;
