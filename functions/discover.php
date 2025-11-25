<?php
/**
 * Discover OpenCart installation and extract database credentials
 * 
 * Verifies presence of config.php and admin/config.php files,
 * then extracts database connection parameters from config.php
 * 
 * @param string $oc_root_path Path to OpenCart root directory
 * @return array|false Array with db credentials on success, false on failure
 */
function discover_oc_installation($oc_root_path) {
    $config_file = $oc_root_path . '/config.php';
    $admin_config_file = $oc_root_path . '/admin/config.php';
    
    // Verify both config files exist
    if (!file_exists($config_file)) {
        return ['error' => 'config.php not found at: ' . $config_file];
    }
    
    if (!file_exists($admin_config_file)) {
        return ['error' => 'admin/config.php not found at: ' . $admin_config_file];
    }
    
    // Read and parse config.php
    $config_content = file_get_contents($config_file);
    
    if ($config_content === false) {
        return ['error' => 'Unable to read config.php'];
    }
    
    // Extract database credentials using regex
    $credentials = [
        'hostname' => extract_define_value($config_content, 'DB_HOSTNAME'),
        'username' => extract_define_value($config_content, 'DB_USERNAME'),
        'password' => extract_define_value($config_content, 'DB_PASSWORD'),
        'database' => extract_define_value($config_content, 'DB_DATABASE'),
        'port' => extract_define_value($config_content, 'DB_PORT'),
        'prefix' => extract_define_value($config_content, 'DB_PREFIX')
    ];
    
    // Verify all required credentials were found
    foreach ($credentials as $key => $value) {
        if ($value === false) {
            return ['error' => 'Unable to extract DB_' . strtoupper($key) . ' from config.php'];
        }
    }
    
    return $credentials;
}

/**
 * Extract value from PHP define() statement
 * 
 * @param string $content File content to search
 * @param string $constant_name Constant name to extract
 * @return string|false Extracted value or false if not found
 */
function extract_define_value($content, $constant_name) {
    $pattern = "/define\s*\(\s*['\"]" . preg_quote($constant_name, '/') . "['\"]\s*,\s*['\"](.*?)['\"]\s*\)/";
    
    if (preg_match($pattern, $content, $matches)) {
        return $matches[1];
    }
    
    return false;
}
