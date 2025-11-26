<?php
/**
 * OpenCart BGN to EUR Price Converter
 * Main execution file
 */

// Load configuration
require_once 'config.local.php';

// Determine execution mode and extract command
if (php_sapi_name() === 'cli') {
    // CLI mode: php oc-bgn2eur.php discover
    $command = isset($argv[1]) ? $argv[1] : null;
    $param = isset($argv[2]) ? $argv[2] : null;
} else {
    // Web mode: oc-bgn2eur.php?cmd=discover
    $command = isset($_GET['cmd']) ? $_GET['cmd'] : null;
    $param = isset($_GET['param']) ? $_GET['param'] : null;
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

if (empty($command)) {
    die("Грешка: не е подадена команда\n");
}

// Command routing
switch ($command) {
    case 'discover':
        require_once 'functions/discover.php';
        $result = discover_oc_installation(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        // Output non-sensitive data only
        echo "Намерена е OpenCart инсталация.\n";
        echo "База данни: " . $result['database'] . "\n";
        echo "Потребител: " . $result['username'] . "\n";
        echo "Префикс:    " . $result['prefix'] . "\n";
        break;

    case 'currency':
        require_once 'functions/currency.php';
        $result = validate_currency_config(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        echo $result['message'] . "\n";
        break;
    
    case 'recalculate':
        require_once 'functions/recalculate.php';
        $result = recalculate_prices(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        echo $result['message'] . "\n";
        break;
    
    case 'list':
        require_once 'functions/list.php';
        $result = list_products(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        echo $result['message'] . "\n";
        break;
    
    case 'stat':
        require_once 'functions/stat.php';
        $result = display_statistics(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        echo $result['message'] . "\n";
        break;

    case 'backup':
        require_once 'functions/backup.php';
        $result = backup_tables(OC_ROOT_PATH);

        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }

        echo $result['message'] . "\n";
        break;

    case 'restore':
        require_once 'functions/restore.php';
        $result = restore_tables(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        echo $result['message'] . "\n";
        break;

    case 'cleanup':
        require_once 'functions/cleanup.php';
        $result = cleanup_backups(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Грешка: " . $result['error'] . "\n");
        }
        
        echo $result['message'] . "\n";
        break;
     
    default:
        die("Грешка: непозната команда '$command'\n");

    if (php_sapi_name() !== 'cli') {
        echo '</pre>';
    }
}
