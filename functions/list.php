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
        $product_count++;
    }
    
    echo str_repeat('-', 80) . "\n";
    echo "Общо продукти: {$product_count}\n";
    
    mysqli_close($conn);
    
    return [
        'success' => true,
        'message' => "Изброени {$product_count} продукта"
    ];
}
