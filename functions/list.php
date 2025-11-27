<?php
/**
 * List all products with their prices
 * 
 * Displays product ID, status, standard price, model, and name
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function list_products($oc_root_path) {
    $memory_start = memory_get_usage();
    
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
    
    // Get active language ID
    $lang_query = "SELECT language_id FROM {$prefix}language WHERE status = '1' LIMIT 1";
    $lang_result = mysqli_query($conn, $lang_query);
    
    if (!$lang_result || mysqli_num_rows($lang_result) === 0) {
        mysqli_close($conn);
        return ['error' => 'Не е намерен активен език в базата данни'];
    }
    
    $lang_row = mysqli_fetch_assoc($lang_result);
    $language_id = $lang_row['language_id'];
    
    // Get total product count for padding calculation
    $count_query = "SELECT COUNT(*) as total FROM {$prefix}product";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_products = $count_row['total'];
    $id_width = strlen((string)$total_products);
    
    // Get products with descriptions
    $products_query = "
        SELECT p.product_id, p.model, p.price, p.status, pd.name 
        FROM {$prefix}product p 
        LEFT JOIN {$prefix}product_description pd ON p.product_id = pd.product_id 
        WHERE pd.language_id = {$language_id}
        ORDER BY p.product_id
    ";
    
    $products_result = mysqli_query($conn, $products_query);
    
    if (!$products_result) {
        mysqli_close($conn);
        return ['error' => 'Грешка при извличане на продукти: ' . mysqli_error($conn)];
    }
    
    // Calculate maximum model length and store products
    $max_model_length = 0;
    $products = [];
    
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
        $model_length = strlen($product['model']);
        if ($model_length > $max_model_length && $model_length <= 30) {
            $max_model_length = $model_length;
        }
    }
    
    // Cap model width at 30 characters
    $model_width = min($max_model_length, 30);
    if ($model_width < 5) {
        $model_width = 5; // Minimum width for "Модел" header
    }
    
    // Preload all option values grouped by product_id
    $options_query = "SELECT product_id FROM {$prefix}product_option_value";
    $options_result = mysqli_query($conn, $options_query);
    $options_by_product = [];
    
    while ($option = mysqli_fetch_assoc($options_result)) {
        $pid = $option['product_id'];
        if (!isset($options_by_product[$pid])) {
            $options_by_product[$pid] = 0;
        }
        $options_by_product[$pid]++;
    }
    
    // Preload all discounts grouped by product_id
    $discounts_query = "SELECT product_id FROM {$prefix}product_discount";
    $discounts_result = mysqli_query($conn, $discounts_query);
    $discounts_by_product = [];
    
    while ($discount = mysqli_fetch_assoc($discounts_result)) {
        $pid = $discount['product_id'];
        if (!isset($discounts_by_product[$pid])) {
            $discounts_by_product[$pid] = 0;
        }
        $discounts_by_product[$pid]++;
    }
    
    // Preload all special prices grouped by product_id
    $specials_query = "SELECT product_id FROM {$prefix}product_special";
    $specials_result = mysqli_query($conn, $specials_query);
    $specials_by_product = [];
    
    while ($special = mysqli_fetch_assoc($specials_result)) {
        $pid = $special['product_id'];
        if (!isset($specials_by_product[$pid])) {
            $specials_by_product[$pid] = 0;
        }
        $specials_by_product[$pid]++;
    }
    
    // Check conversion flag status
    $flag_query = "SELECT value FROM {$prefix}setting WHERE `key` = 'bgn_eur_converted'";
    $flag_result = mysqli_query($conn, $flag_query);
    $conversion_status = "Не е изпълнявана";
    
    if ($flag_result && mysqli_num_rows($flag_result) > 0) {
        $flag_row = mysqli_fetch_assoc($flag_result);
        if ($flag_row['value'] === '1') {
            $conversion_status = "Изпълнена";
        } else {
            $conversion_status = "Възстановена/Нулирана";
        }
    }
    
    mysqli_close($conn);
    
    // Calculate indent for nested lines
    $indent = str_repeat(' ', $id_width + 3); // ID width + " | "
    
    // Display conversion status
    echo "Статус на конверсия: " . $conversion_status . "\n\n";
    
    // Output header
    echo str_repeat('-', 80) . "\n";
    echo str_pad('ID', $id_width) . " | Статус    | Цена      | " . str_pad('Модел', $model_width) . " | Име\n";
    echo str_repeat('-', 80) . "\n";
    
    $product_count = 0;
    
    // Output products
    foreach ($products as $product) {
        $product_id = str_pad($product['product_id'], $id_width, ' ', STR_PAD_LEFT);
        $status = $product['status'] === '1' ? 'Активен  ' : 'Неактивен';
        $price = number_format((float)$product['price'], 2, '.', '');
        $price_padded = str_pad($price, 9, ' ', STR_PAD_LEFT);
        $model = str_pad(substr($product['model'], 0, $model_width), $model_width, ' ', STR_PAD_RIGHT);
        $name = substr($product['name'], 0, 40);
        
        echo "{$product_id} | {$status} | {$price_padded} | {$model} | {$name}\n";
        
        // Display nested price variations if present
        $pid = $product['product_id'];
        
        if (isset($options_by_product[$pid]) && $options_by_product[$pid] > 0) {
            echo "{$indent}↪ Полета цени опции: {$options_by_product[$pid]}\n";
        }
        
        if (isset($discounts_by_product[$pid]) && $discounts_by_product[$pid] > 0) {
            echo "{$indent}↪ Полета цени за количества: {$discounts_by_product[$pid]}\n";
        }
        
        if (isset($specials_by_product[$pid]) && $specials_by_product[$pid] > 0) {
            echo "{$indent}↪ Полета промо цени: {$specials_by_product[$pid]}\n";
        }
        
        $product_count++;
    }
    
    echo str_repeat('-', 80) . "\n";
    echo "Общо продукти: {$product_count}\n";
    
    // Calculate memory usage
    $memory_end = memory_get_usage();
    $memory_used = ($memory_end - $memory_start) / 1024 / 1024;
    $memory_peak = memory_get_peak_usage() / 1024 / 1024;
    
    echo "\nИзползвана RAM: " . number_format($memory_used, 2) . " MB\n";
    echo "Пикова RAM: " . number_format($memory_peak, 2) . " MB\n";
    
    return [
        'success' => true,
        'message' => "Изброени {$product_count} продукта"
    ];
}
