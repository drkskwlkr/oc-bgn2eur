<?php
/**
 * Restore tables from backup copies
 * 
 * Renames backup tables to replace original tables
 * Drops the modified original tables
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function restore_tables($oc_root_path) {
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
    
    // Tables to restore
    $tables = [
        'product',
        'product_option_value',
        'product_discount',
        'product_special'
    ];
    
    echo "Проверка за наличие на резервни копия...\n\n";
    
    // First verify all backup tables exist
    foreach ($tables as $table) {
        $backup_table = $prefix . 'backup_' . $table;
        $check_query = "SHOW TABLES LIKE '{$backup_table}'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            mysqli_close($conn);
            return ['error' => "Резервното копие {$backup_table} не съществува. Не може да се извърши възстановяване."];
        }
        
        echo "✓ Намерено: {$backup_table}\n";
    }
    
    echo "\nВъзстановяване на таблици...\n\n";
    
    // Perform restore operation
    foreach ($tables as $table) {
        $source_table = $prefix . $table;
        $backup_table = $prefix . 'backup_' . $table;
        $temp_table = $prefix . 'old_' . $table;
        
        // Rename current table to temp name
        $rename_current_query = "RENAME TABLE {$source_table} TO {$temp_table}";
        if (!mysqli_query($conn, $rename_current_query)) {
            mysqli_close($conn);
            return ['error' => "Грешка при преименуване на {$source_table}: " . mysqli_error($conn)];
        }
        
        // Rename backup table to original name
        $rename_backup_query = "RENAME TABLE {$backup_table} TO {$source_table}";
        if (!mysqli_query($conn, $rename_backup_query)) {
            // Rollback: restore original table name
            mysqli_query($conn, "RENAME TABLE {$temp_table} TO {$source_table}");
            mysqli_close($conn);
            return ['error' => "Грешка при възстановяване на {$backup_table}: " . mysqli_error($conn)];
        }
        
        // Drop old modified table
        $drop_query = "DROP TABLE {$temp_table}";
        if (!mysqli_query($conn, $drop_query)) {
            mysqli_close($conn);
            return ['error' => "Грешка при изтриване на {$temp_table}: " . mysqli_error($conn)];
        }
        
        echo "✓ {$backup_table} → {$source_table}\n";
    }
    
    // Unset conversion flag since we're restoring to pre-conversion state
    $unset_flag_query = "UPDATE {$prefix}setting SET value = '0' WHERE `key` = 'bgn_eur_converted'";
    mysqli_query($conn, $unset_flag_query);
    
    mysqli_close($conn);
    
    echo "\nВсички таблици са възстановени успешно.\n";
    echo "Флагът за конверсия е нулиран.\n";
    
    return [
        'success' => true,
        'message' => "Таблиците са възстановени от резервни копия"
    ];
}
