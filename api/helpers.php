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
