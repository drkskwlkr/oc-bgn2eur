<?php
/**
 * Create backup copies of price-related tables
 * 
 * Clones product, product_option_value, product_discount, and product_special tables
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function backup_tables($oc_root_path) {
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
    
    // Tables to backup
    $tables = [
        'product',
        'product_option_value',
        'product_discount',
        'product_special'
    ];
    
    echo "Създаване на резервни копия на таблици...\n\n";
    
    // Temporarily disable strict mode to allow copying legacy table structures
    mysqli_query($conn, "SET SESSION sql_mode = ''");
    
    foreach ($tables as $table) {
        $source_table = $prefix . $table;
        $backup_table = $prefix . 'backup_' . $table;
        
        // Check if backup table already exists
        $check_query = "SHOW TABLES LIKE '{$backup_table}'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo "⚠ Таблица {$backup_table} вече съществува. Изтриване...\n";
            $drop_query = "DROP TABLE {$backup_table}";
            if (!mysqli_query($conn, $drop_query)) {
                mysqli_close($conn);
                return ['error' => "Грешка при изтриване на {$backup_table}: " . mysqli_error($conn)];
            }
        }
        
        // Create backup table structure
        $create_query = "CREATE TABLE {$backup_table} LIKE {$source_table}";
        if (!mysqli_query($conn, $create_query)) {
            mysqli_close($conn);
            return ['error' => "Грешка при създаване на структура за {$backup_table}: " . mysqli_error($conn)];
        }
        
        // Copy data to backup table
        $copy_query = "INSERT INTO {$backup_table} SELECT * FROM {$source_table}";
        if (!mysqli_query($conn, $copy_query)) {
            mysqli_close($conn);
            return ['error' => "Грешка при копиране на данни в {$backup_table}: " . mysqli_error($conn)];
        }
        
        // Get row count
        $count_query = "SELECT COUNT(*) as total FROM {$backup_table}";
        $count_result = mysqli_query($conn, $count_query);
        $count_row = mysqli_fetch_assoc($count_result);
        
        echo "✓ {$source_table} → {$backup_table} (" . number_format($count_row['total'], 0, '.', ' ') . " записа)\n";
    }
    
    // Restore original SQL mode
    mysqli_query($conn, "SET SESSION sql_mode = @@GLOBAL.sql_mode");
    
    mysqli_close($conn);
    
    echo "\nВсички таблици са архивирани успешно.\n";
    
    return [
        'success' => true,
        'message' => "Резервните копия са създадени"
    ];
}
