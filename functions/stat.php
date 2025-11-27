<?php
/**
 * Display statistics about products in the database
 * 
 * Shows total products, active products, and deactivated products
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function display_statistics($oc_root_path) {
    require_once 'functions/discover.php';
    
    // Get database credentials
    $db_config = discover_oc_installation($oc_root_path);
    
    if (isset($db_config['error'])) {
        return ['error' => $db_config['error']];
    }
    
    // Connect to database
    $conn = mysqli_connect(
        $db_config['hostname'],
        $db_config['username'],
        $db_config['password'],
        $db_config['database'],
        $db_config['port']
    );
    
    if (!$conn) {
        return ['error' => 'Неуспешна връзка с базата данни: ' . mysqli_connect_error()];
    }
    
    $prefix = $db_config['prefix'];
    
    // Get total products
    $total_query = "SELECT COUNT(*) as total FROM {$prefix}product";
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_products = $total_row['total'];
    
    // Get active products
    $active_query = "SELECT COUNT(*) as total FROM {$prefix}product WHERE status = '1'";
    $active_result = mysqli_query($conn, $active_query);
    $active_row = mysqli_fetch_assoc($active_result);
    $active_products = $active_row['total'];
    
    // Get deactivated products
    $inactive_query = "SELECT COUNT(*) as total FROM {$prefix}product WHERE status = '0'";
    $inactive_result = mysqli_query($conn, $inactive_query);
    $inactive_row = mysqli_fetch_assoc($inactive_result);
    $inactive_products = $inactive_row['total'];
    
    // Get product option values count
    $options_query = "SELECT COUNT(*) as total FROM {$prefix}product_option_value";
    $options_result = mysqli_query($conn, $options_query);
    $options_row = mysqli_fetch_assoc($options_result);
    $option_values = $options_row['total'];
    
    // Get product discounts count
    $discounts_query = "SELECT COUNT(*) as total FROM {$prefix}product_discount";
    $discounts_result = mysqli_query($conn, $discounts_query);
    $discounts_row = mysqli_fetch_assoc($discounts_result);
    $discount_prices = $discounts_row['total'];
    
    // Get product special prices count
    $specials_query = "SELECT COUNT(*) as total FROM {$prefix}product_special";
    $specials_result = mysqli_query($conn, $specials_query);
    $specials_row = mysqli_fetch_assoc($specials_result);
    $special_prices = $specials_row['total'];
    
    // Check conversion flag status
    $flag_query = "SELECT value FROM {$prefix}setting WHERE `key` = 'bgn_eur_converted'";
    $flag_result = mysqli_query($conn, $flag_query);
    $conversion_status = "Не е изпълнявана";
    
    if ($flag_result && mysqli_num_rows($flag_result) > 0) {
        $flag_row = mysqli_fetch_assoc($flag_result);
        if ($flag_row['value'] === '1') {
            $conversion_status = "Изпълнена (bgn_eur_converted = 1)";
        } else {
            $conversion_status = "Възстановена/Нулирана (bgn_eur_converted = 0)";
        }
    }
    
    mysqli_close($conn);
    
    // Display statistics
    echo str_repeat('=', 50) . "\n";
    echo "СТАТИСТИКА ЗА ПРОДУКТИ\n";
    echo str_repeat('=', 50) . "\n\n";
    
    echo "Статус на конверсия: " . $conversion_status . "\n\n";
    
    echo "Общо продукти:       " . number_format($total_products, 0, '.', ' ') . "\n";
    echo "Активни продукти:    " . number_format($active_products, 0, '.', ' ') . "\n";
    echo "Неактивни продукти:  " . number_format($inactive_products, 0, '.', ' ') . "\n\n";
    
    echo "Ценови вариации:\n";
    echo "  Опции (variants):  " . number_format($option_values, 0, '.', ' ') . "\n";
    echo "  Отстъпки (tiers):  " . number_format($discount_prices, 0, '.', ' ') . "\n";
    echo "  Промоции:          " . number_format($special_prices, 0, '.', ' ') . "\n";
    
    // Calculate estimated memory usage for list command
    $estimated_memory_mb = (
        ($total_products * 0.5) +        // ~0.5KB per product
        ($option_values * 0.3) +         // ~0.3KB per option
        ($discount_prices * 0.3) +       // ~0.3KB per discount
        ($special_prices * 0.3)          // ~0.3KB per special
    ) / 1024;
    
    echo "\nОчаквано използване на RAM за 'list': ~" . number_format($estimated_memory_mb, 1) . " MB\n";
    
    if ($estimated_memory_mb > 64) {
        echo "⚠ ВНИМАНИЕ: Очакваното използване надхвърля 64 MB\n";
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    
    return [
        'success' => true,
        'message' => "Статистиката е изведена успешно"
    ];
}
