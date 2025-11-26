<?php
/**
 * Remove backup tables after successful conversion
 * 
 * Drops all backup tables created by the backup operation
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function cleanup_backups($oc_root_path) {
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
    
    // Tables to cleanup
    $tables = [
        'product',
        'product_option_value',
        'product_discount',
        'product_special'
    ];
    
    echo "Изтриване на резервни копия...\n\n";
    
    $deleted_count = 0;
    
    foreach ($tables as $table) {
        $backup_table = $prefix . 'backup_' . $table;
        
        // Check if backup table exists
        $check_query = "SHOW TABLES LIKE '{$backup_table}'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            echo "⚠ Таблица {$backup_table} не съществува. Пропускане.\n";
            continue;
        }
        
        // Drop backup table
        $drop_query = "DROP TABLE {$backup_table}";
        if (!mysqli_query($conn, $drop_query)) {
            mysqli_close($conn);
            return ['error' => "Грешка при изтриване на {$backup_table}: " . mysqli_error($conn)];
        }
        
        echo "✓ Изтрита: {$backup_table}\n";
        $deleted_count++;
    }
    
    mysqli_close($conn);
    
    if ($deleted_count === 0) {
        echo "\nНяма резервни копия за изтриване.\n";
    } else {
        echo "\nИзтрити {$deleted_count} резервни таблици.\n";
    }
    
    return [
        'success' => true,
        'message' => "Почистването е завършено"
    ];
}
