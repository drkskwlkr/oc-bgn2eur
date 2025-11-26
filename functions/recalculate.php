<?php
/**
 * Recalculate product prices from BGN to EUR
 * 
 * Main conversion function that orchestrates all validation and conversion steps
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @param bool $proceed Whether to proceed with actual conversion (false = dry run)
 * @param string|null $param Parameter value ('simulate' or 'proceed')
 * @return array Result with success status and message
 */
function recalculate_prices($oc_root_path, $proceed = false, $param = null) {
    // Step 1: Discover OpenCart installation
    require_once 'functions/discover.php';
    $discovery = discover_oc_installation($oc_root_path);
    
    if (isset($discovery['error'])) {
        return ['error' => 'Откриване: ' . $discovery['error']];
    }
    
    echo "✓ Открита OpenCart инсталация\n";
    echo "  База данни: " . $discovery['database'] . "\n";
    echo "  Префикс: " . $discovery['prefix'] . "\n";
    echo "  Обменен курс: 1 EUR = " . EUR_EXCHANGE_RATE . " BGN\n\n";
    
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
    
    // Dry run vs simulate vs actual execution
    if (!$proceed) {
        if ($param !== 'simulate') {
            // Dry run mode
            echo str_repeat('=', 60) . "\n";
            echo "РЕЖИМ НА ПРОВЕРКА (DRY RUN)\n";
            echo str_repeat('=', 60) . "\n";
            echo "Всички проверки са успешни.\n";
            echo "За да симулирате конверсията (без запис), използвайте:\n";
            echo "  php oc-bgn2eur.php recalculate simulate\n";
            echo "За да стартирате действителната конверсия, използвайте:\n";
            echo "  php oc-bgn2eur.php recalculate proceed\n";
            echo str_repeat('=', 60) . "\n";
            
            return [
                'success' => true,
                'message' => 'Проверките са завършени успешно'
            ];
        }
    }
    
    // Simulate or proceed mode - perform conversion
    $is_simulation = ($param === 'simulate');
    $mode_label = $is_simulation ? "СИМУЛАЦИЯ НА КОНВЕРСИЯ" : "СТАРТИРАНЕ НА КОНВЕРСИЯ";
    
    echo str_repeat('=', 60) . "\n";
    echo $mode_label . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    if ($is_simulation) {
        echo "ВНИМАНИЕ: Режим на симулация - промените НЯМА да бъдат записани!\n\n";
    }
    
    // Reconnect to database for conversion
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
    $converted_count = 0;
    
    // Convert product prices
    echo "Обработка на основни цени на продукти...\n";
    $product_query = "SELECT product_id, price FROM {$prefix}product WHERE price > 0";
    $product_result = mysqli_query($conn, $product_query);
    
    while ($product = mysqli_fetch_assoc($product_result)) {
        $old_price = (float)$product['price'];
        $new_price = round($old_price / EUR_EXCHANGE_RATE, 2);
        
        if ($is_simulation) {
            echo "  Продукт #{$product['product_id']}: {$old_price} BGN → {$new_price} EUR\n";
        } else {
            $update_query = "UPDATE {$prefix}product SET price = {$new_price} WHERE product_id = {$product['product_id']}";
            mysqli_query($conn, $update_query);
        }
        
        $converted_count++;
    }
    
    echo "✓ Обработени {$converted_count} цени на продукти\n\n";
    
    // Convert product option values
    $converted_count = 0;
    echo "Обработка на цени на опции...\n";
    $option_query = "SELECT product_option_value_id, price FROM {$prefix}product_option_value WHERE price != 0";
    $option_result = mysqli_query($conn, $option_query);
    
    while ($option = mysqli_fetch_assoc($option_result)) {
        $old_price = (float)$option['price'];
        $new_price = round($old_price / EUR_EXCHANGE_RATE, 2);
        
        if ($is_simulation) {
            echo "  Опция #{$option['product_option_value_id']}: {$old_price} BGN → {$new_price} EUR\n";
        } else {
            $update_query = "UPDATE {$prefix}product_option_value SET price = {$new_price} WHERE product_option_value_id = {$option['product_option_value_id']}";
            mysqli_query($conn, $update_query);
        }
        
        $converted_count++;
    }
    
    echo "✓ Обработени {$converted_count} цени на опции\n\n";
    
    // Convert product discounts
    $converted_count = 0;
    echo "Обработка на отстъпки...\n";
    $discount_query = "SELECT product_discount_id, price FROM {$prefix}product_discount WHERE price > 0";
    $discount_result = mysqli_query($conn, $discount_query);
    
    while ($discount = mysqli_fetch_assoc($discount_result)) {
        $old_price = (float)$discount['price'];
        $new_price = round($old_price / EUR_EXCHANGE_RATE, 2);
        
        if ($is_simulation) {
            echo "  Отстъпка #{$discount['product_discount_id']}: {$old_price} BGN → {$new_price} EUR\n";
        } else {
            $update_query = "UPDATE {$prefix}product_discount SET price = {$new_price} WHERE product_discount_id = {$discount['product_discount_id']}";
            mysqli_query($conn, $update_query);
        }
        
        $converted_count++;
    }
    
    echo "✓ Обработени {$converted_count} отстъпки\n\n";
    
    // Convert product specials
    $converted_count = 0;
    echo "Обработка на промоционални цени...\n";
    $special_query = "SELECT product_special_id, price FROM {$prefix}product_special WHERE price > 0";
    $special_result = mysqli_query($conn, $special_query);
    
    while ($special = mysqli_fetch_assoc($special_result)) {
        $old_price = (float)$special['price'];
        $new_price = round($old_price / EUR_EXCHANGE_RATE, 2);
        
        if ($is_simulation) {
            echo "  Промоция #{$special['product_special_id']}: {$old_price} BGN → {$new_price} EUR\n";
        } else {
            $update_query = "UPDATE {$prefix}product_special SET price = {$new_price} WHERE product_special_id = {$special['product_special_id']}";
            mysqli_query($conn, $update_query);
        }
        
        $converted_count++;
    }
    
    echo "✓ Обработени {$converted_count} промоционални цени\n\n";
    
    mysqli_close($conn);
    
    echo str_repeat('=', 60) . "\n";
    
    if ($is_simulation) {
        return [
            'success' => true,
            'message' => 'Симулацията е завършена. Промените НЕ са записани в базата.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Конверсията е завършена успешно!'
    ];
}
