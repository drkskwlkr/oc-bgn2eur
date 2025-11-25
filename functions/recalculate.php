<?php
/**
 * Recalculate product prices from BGN to EUR
 * 
 * Main conversion function that orchestrates all validation and conversion steps
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function recalculate_prices($oc_root_path) {
    // Step 1: Discover OpenCart installation
    require_once 'functions/discover.php';
    $discovery = discover_oc_installation($oc_root_path);
    
    if (isset($discovery['error'])) {
        return ['error' => 'Откриване: ' . $discovery['error']];
    }
    
    echo "✓ Открита OpenCart инсталация\n";
    echo "  База данни: " . $discovery['database'] . "\n";
    echo "  Префикс: " . $discovery['prefix'] . "\n\n";
    
    // Step 2: Validate currency configuration
    require_once 'functions/currency.php';
    $currency_check = validate_currency_config($oc_root_path);
    
    if (isset($currency_check['error'])) {
        return ['error' => 'Валута: ' . $currency_check['error']];
    }
    
    echo "✓ " . $currency_check['message'] . "\n\n";
    
    // More steps will be added here
    
    return [
        'success' => true,
        'message' => 'Проверките са успешни. Готово за преизчисление.'
    ];
}
