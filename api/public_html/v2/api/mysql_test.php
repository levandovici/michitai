<?php
/**
 * MySQL Connection Test for Hostinger Database
 */

header('Content-Type: application/json');

// Load the configuration
require_once __DIR__ . '/config/ErrorCodes.php';
require_once __DIR__ . '/config/database.php';

$results = [
    'mysql_test' => 'starting',
    'timestamp' => date('c'),
    'tests' => []
];

// Test 1: Database connection
try {
    $db = DatabaseConfig::getConnection();
    $dbType = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    $results['tests']['connection'] = [
        'status' => 'success',
        'database_type' => $dbType,
        'message' => "Connected successfully to $dbType database"
    ];
    
    // Test 2: Check if we can query the database
    try {
        if ($dbType === 'mysql') {
            $stmt = $db->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
            $info = $stmt->fetch();
            
            $results['tests']['database_info'] = [
                'status' => 'success',
                'current_database' => $info['current_db'],
                'mysql_version' => $info['mysql_version']
            ];
        } else {
            $stmt = $db->query("SELECT sqlite_version() as sqlite_version");
            $info = $stmt->fetch();
            
            $results['tests']['database_info'] = [
                'status' => 'success',
                'sqlite_version' => $info['sqlite_version'],
                'message' => 'Using SQLite fallback'
            ];
        }
        
    } catch (Exception $e) {
        $results['tests']['database_info'] = [
            'status' => 'error',
            'message' => 'Failed to get database info: ' . $e->getMessage()
        ];
    }
    
    // Test 3: Test table creation
    try {
        if ($dbType === 'mysql') {
            $sql = DatabaseConfig::getMySQLTableSQL();
        } else {
            $sql = DatabaseConfig::getSQLiteTableSQL();
        }
        
        // Execute table creation
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->exec($statement);
            }
        }
        
        $results['tests']['table_creation'] = [
            'status' => 'success',
            'message' => 'Tables created successfully',
            'statements_executed' => count($statements)
        ];
        
        // Test 4: Check if tables exist
        if ($dbType === 'mysql') {
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        $results['tests']['tables_check'] = [
            'status' => 'success',
            'tables_found' => $tables,
            'users_table_exists' => in_array('users', $tables),
            'api_logs_table_exists' => in_array('api_logs', $tables)
        ];
        
    } catch (Exception $e) {
        $results['tests']['table_creation'] = [
            'status' => 'error',
            'message' => 'Table creation failed: ' . $e->getMessage()
        ];
    }
    
    // Test 5: Test Auth class with database
    try {
        require_once __DIR__ . '/classes/Auth.php';
        $auth = new Auth();
        
        $results['tests']['auth_class'] = [
            'status' => 'success',
            'message' => 'Auth class instantiated successfully with database'
        ];
        
        // Test registration simulation
        $testResult = $auth->register('mysql_test@example.com', 'TestPassword123', false);
        
        $results['tests']['registration_test'] = [
            'status' => $testResult['success'] ? 'success' : 'expected_error',
            'message' => $testResult['success'] ? 'Registration test successful' : 'Registration test failed (expected if email exists)',
            'result' => $testResult
        ];
        
    } catch (Exception $e) {
        $results['tests']['auth_class'] = [
            'status' => 'error',
            'message' => 'Auth class failed: ' . $e->getMessage()
        ];
    }
    
} catch (Exception $e) {
    $results['tests']['connection'] = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// Test 6: Environment variables check
$envFile = __DIR__ . '/.env';
$results['tests']['environment'] = [
    'env_file_exists' => file_exists($envFile),
    'env_example_exists' => file_exists(__DIR__ . '/.env.example'),
    'message' => file_exists($envFile) ? 'Environment file found' : 'No .env file found, using defaults'
];

$results['mysql_test'] = 'completed';
echo json_encode($results, JSON_PRETTY_PRINT);
?>
