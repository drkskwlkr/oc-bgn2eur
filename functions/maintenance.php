<?php
/**
 * Toggle maintenance mode for the store
 * 
 * Enables or disables maintenance mode to prevent orders during conversion
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @param string $action Action to perform: 'enable' or 'disable'
 * @return array Result with success status and message
 */
function toggle_maintenance($oc_root_path, $action) {
    if (!in_array($action, ['enable', 'disable'])) {
        return ['error' => "Невалидно действие '{$action}'. Използвайте 'enable' или 'disable'."];
    }
    
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
    $value = ($action === 'enable') ? '1' : '0';
    
    // Update maintenance mode setting
    $update_query = "UPDATE {$prefix}setting SET value = '{$value}' WHERE `key` = 'config_maintenance'";
    
    if (!mysqli_query($conn, $update_query)) {
        mysqli_close($conn);
        return ['error' => 'Грешка при обновяване на настройка: ' . mysqli_error($conn)];
    }
    
    // Check if any rows were affected
    $affected_rows = mysqli_affected_rows($conn);
    
    mysqli_close($conn);
    
    if ($affected_rows === 0) {
        return ['error' => "Настройката 'config_maintenance' не е намерена в базата данни"];
    }
    
    $status_msg = ($action === 'enable') ? 'АКТИВИРАН' : 'ДЕАКТИВИРАН';
    
    return [
        'success' => true,
        'message' => "Режим на поддръжка: {$status_msg}"
    ];
}
