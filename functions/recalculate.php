<?php
/**
 * Recalculate product prices from BGN to EUR
 * 
 * Main conversion function that orchestrates all validation and conversion steps
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @param bool $proceed Whether to proceed with actual conversion (false = dry run)
 * @return array Result with success status and message
 */
function recalculate_prices($oc_root_path, $proceed = false) {
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
    
    // Step 3: Display statistics
    require_once 'functions/stat.php';
    $stat_result = display_statistics($oc_root_path);
    
    if (isset($stat_result['error'])) {
        return ['error' => 'Статистика: ' . $stat_result['error']];
    }
    
    echo "\n";
    
    // Step 4: Check for backup tables
    $conn = mysqli_connect(
        $discovery['hostname'],
        $discovery['username'],
        $discovery['password'],
        $discovery['database'],
        $discovery['port']
    );
    
    if (!$conn) {
        return ['error' => 'Неуспешна връзка с базата данни: ' . mysqli_connect_error()];
    }
    
    $prefix = $discovery['prefix'];
    $backup_exists = true;
    $tables = ['product', 'product_option_value', 'product_discount', 'product_special'];
    
    foreach ($tables as $table) {
        $backup_table = $prefix . 'backup_' . $table;
        $check_query = "SHOW TABLES LIKE '{$backup_table}'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            $backup_exists = false;
            break;
        }
    }
    
    mysqli_close($conn);
    
    if ($backup_exists) {
        echo "✓ Налични са резервни копия на таблиците\n\n";
    } else {
        echo "⚠ ВНИМАНИЕ: Не са намерени резервни копия!\n";
        echo "  Препоръчва се да изпълните 'backup' преди конверсия.\n\n";
    }
    
    // Dry run vs actual execution
    if (!$proceed) {
        echo str_repeat('=', 60) . "\n";
        echo "РЕЖИМ НА ПРОВЕРКА (DRY RUN)\n";
        echo str_repeat('=', 60) . "\n";
        echo "Всички проверки са успешни.\n";
        echo "За да стартирате действителната конверсия, използвайте:\n";
        echo "  php oc-bgn2eur.php recalculate proceed\n";
        echo str_repeat('=', 60) . "\n";
        
        return [
            'success' => true,
            'message' => 'Проверките са завършени успешно'
        ];
    }
    
    // Actual conversion will happen here
    echo str_repeat('=', 60) . "\n";
    echo "СТАРТИРАНЕ НА КОНВЕРСИЯ\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // TODO: Implement actual conversion logic
    
    return [
        'success' => true,
        'message' => 'Конверсията е завършена (NOT IMPLEMENTED YET)'
    ];
}
