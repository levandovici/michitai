<?php
/**
 * Test the new API structure
 */

header('Content-Type: application/json');

// Test if we can load the new structure
$results = [
    'structure_test' => 'success',
    'timestamp' => date('c'),
    'tests' => []
];

// Test 1: Check if ErrorCodes can be loaded
try {
    require_once __DIR__ . '/config/ErrorCodes.php';
    $results['tests']['error_codes'] = [
        'status' => 'success',
        'message' => 'ErrorCodes class loaded successfully'
    ];
    
    // Test ErrorCodes functionality
    $testResponse = ErrorCodes::createSuccessResponse(['test' => 'data'], 'Test successful');
    $results['tests']['error_codes']['sample_response'] = $testResponse;
    
} catch (Exception $e) {
    $results['tests']['error_codes'] = [
        'status' => 'error',
        'message' => 'Failed to load ErrorCodes: ' . $e->getMessage()
    ];
}

// Test 2: Check if Auth can be loaded
try {
    require_once __DIR__ . '/classes/Auth.php';
    $results['tests']['auth_class'] = [
        'status' => 'success',
        'message' => 'Auth class loaded successfully'
    ];
    
    // Test Auth instantiation
    $auth = new Auth();
    $results['tests']['auth_instantiation'] = [
        'status' => 'success',
        'message' => 'Auth class instantiated successfully'
    ];
    
} catch (Exception $e) {
    $results['tests']['auth_class'] = [
        'status' => 'error',
        'message' => 'Failed to load Auth: ' . $e->getMessage()
    ];
}

// Test 3: Check directory structure
$directories = ['config', 'classes', 'data', 'logs'];
foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    $results['tests']['directory_' . $dir] = [
        'status' => is_dir($path) ? 'success' : 'created',
        'path' => $path,
        'exists' => is_dir($path)
    ];
    
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Test 4: Test registration simulation
if (isset($auth)) {
    try {
        $regResult = $auth->register('structure_test@example.com', 'TestPassword123', false);
        $results['tests']['registration_simulation'] = [
            'status' => 'success',
            'message' => 'Registration simulation completed',
            'result' => $regResult
        ];
    } catch (Exception $e) {
        $results['tests']['registration_simulation'] = [
            'status' => 'error',
            'message' => 'Registration simulation failed: ' . $e->getMessage()
        ];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
