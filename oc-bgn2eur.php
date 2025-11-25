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
} else {
    // Web mode: oc-bgn2eur.php?cmd=discover
    $command = isset($_GET['cmd']) ? $_GET['cmd'] : null;
}

if (empty($command)) {
    die("Error: No command specified\n");
}

// Command routing
switch ($command) {
    case 'discover':
        require_once 'functions/discover.php';
        $result = discover_oc_installation(OC_ROOT_PATH);
        
        if (isset($result['error'])) {
            die("Error: " . $result['error'] . "\n");
        }
        
        // Output non-sensitive data only
        echo "OpenCart installation discovered:\n";
        echo "Database: " . $result['database'] . "\n";
        echo "User: " . $result['username'] . "\n";
        echo "Prefix: " . $result['prefix'] . "\n";
        break;
        
    default:
        die("Error: Unknown command '$command'\n");
}
