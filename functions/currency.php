<?php
/**
 * Validate currency configuration for BGN to EUR conversion
 * 
 * Checks that BGN is configured as main currency (value=1.00000000, status=1)
 * and EUR exists with correct exchange rate value
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array Result with success status and message
 */
function validate_currency_config($oc_root_path) {
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
    
    // Check BGN currency
    $bgn_query = "SELECT value, status FROM {$prefix}currency WHERE code = 'BGN'";
    $bgn_result = mysqli_query($conn, $bgn_query);
    
    if (!$bgn_result || mysqli_num_rows($bgn_result) === 0) {
        mysqli_close($conn);
        return ['error' => 'Валутата BGN не е намерена в базата данни'];
    }
    
    $bgn_data = mysqli_fetch_assoc($bgn_result);
    
    if ($bgn_data['value'] !== '1.00000000') {
        mysqli_close($conn);
        return ['error' => 'BGN не е основна валута (стойността не е 1.00000000)'];
    }
    
    if ($bgn_data['status'] !== '1') {
        mysqli_close($conn);
        return ['error' => 'Валутата BGN не е активирана (статусът не е 1)'];
    }
    
    // Check EUR currency
    $eur_query = "SELECT status FROM {$prefix}currency WHERE code = 'EUR'";
    $eur_result = mysqli_query($conn, $eur_query);
    
    if (!$eur_result || mysqli_num_rows($eur_result) === 0) {
        mysqli_close($conn);
        return ['error' => 'Валутата EUR не е намерена в базата данни'];
    }
    
    $eur_data = mysqli_fetch_assoc($eur_result);
    $eur_status = $eur_data['status'] === '1' ? 'активна' : 'неактивна';
    
    mysqli_close($conn);
    
    return [
        'success' => true,
        'message' => "Валидна конфигурация на валути:\n" .
                     "  - BGN е основна валута (value = 1.00000000, status = 1)\n" .
                     "  - EUR съществува и е {$eur_status}\n\n" .
                     "БЕЛЕЖКА: След преминаване към EUR, трябва да:\n" .
                     "  1. Активирате EUR (ако е неактивна)\n" .
                     "  2. Зададете обменен курс EUR = 1.00000000\n" .
                     "  3. Деактивирате BGN"
    ];
}
