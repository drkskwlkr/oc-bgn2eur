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
    
    mysqli_close($conn);
    
    // Display statistics
    echo str_repeat('=', 50) . "\n";
    echo "СТАТИСТИКА ЗА ПРОДУКТИ\n";
    echo str_repeat('=', 50) . "\n\n";
    
    echo "Общо продукти:       " . number_format($total_products, 0, '.', ' ') . "\n";
    echo "Активни продукти:    " . number_format($active_products, 0, '.', ' ') . "\n";
    echo "Неактивни продукти:  " . number_format($inactive_products, 0, '.', ' ') . "\n";
    
    echo "\n" . str_repeat('=', 50) . "\n";
    
    return [
        'success' => true,
        'message' => "Статистиката е изведена успешно"
    ];
}
