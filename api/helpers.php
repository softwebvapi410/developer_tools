<?php
// ==================== SHARED PHP HELPERS ====================

// ==================== HELPERS ====================
function determineChangeFrequency($url, $depth) {
    if (strpos($url,'/blog/')!==false || strpos($url,'/news/')!==false) return 'weekly';
    if (strpos($url,'/product/')!==false || strpos($url,'/shop/')!==false) return 'daily';
    if (strpos($url,'/category/')!==false || strpos($url,'/tag/')!==false) return 'weekly';
    if (strpos($url,'/about')!==false || strpos($url,'/contact')!==false || strpos($url,'/privacy')!==false || strpos($url,'/terms')!==false) return 'yearly';
    if ($depth===0) return 'daily';
    if ($depth===1) return 'weekly';
    if ($depth===2) return 'monthly';
    return 'yearly';
}

function dl_write_expire(string $path, int $secs = 300): void {
    file_put_contents($path . '.expire', (string)(time() + $secs));
}

function dl_cleanup_expired(string $base): void {
    foreach (glob($base . '/*.expire') ?: [] as $f) {
        $exp = (int)@file_get_contents($f);
        if ($exp === 0 || time() < $exp) continue;
        $target = substr($f, 0, -7);
        if (is_dir($target)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $ff) {
                $ff->isDir() ? @rmdir($ff->getRealPath()) : @unlink($ff->getRealPath());
            }
            @rmdir($target);
        } elseif (is_file($target)) {
            @unlink($target);
        }
        @unlink($f);
    }
}
