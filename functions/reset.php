<?php
/**
 * Reset conversion flag
 * 
 * Removes the conversion flag to allow running conversion again
 * WARNING: Use with extreme caution!
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function reset_conversion_flag($oc_root_path) {
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
    
    echo str_repeat('=', 60) . "\n";
    echo "ВНИМАНИЕ: ПРЕМАХВАНЕ НА ФЛАГ ЗА КОНВЕРСИЯ\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // Check if flag exists
    $check_query = "SELECT value FROM {$prefix}setting WHERE `key` = 'bgn_eur_converted'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (!$check_result || mysqli_num_rows($check_result) === 0) {
        mysqli_close($conn);
        echo "Флагът за конверсия не е намерен. Конверсията не е била изпълнявана.\n";
        return [
            'success' => true,
            'message' => 'Няма нужда от reset'
        ];
    }
    
    // Remove conversion flag
    $delete_query = "DELETE FROM {$prefix}setting WHERE `key` = 'bgn_eur_converted'";
    
    if (!mysqli_query($conn, $delete_query)) {
        mysqli_close($conn);
        return ['error' => 'Грешка при премахване на флаг: ' . mysqli_error($conn)];
    }
    
    mysqli_close($conn);
    
    echo "✓ Флагът за конверсия е премахнат.\n";
    echo "\nВече можете да изпълните конверсия отново.\n";
    echo "ВНИМАНИЕ: Уверете се, че наистина искате да конвертирате цените повторно!\n";
    
    return [
        'success' => true,
        'message' => 'Флагът е премахнат успешно'
    ];
}
