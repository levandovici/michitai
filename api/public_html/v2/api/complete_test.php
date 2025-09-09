<?php
/**
 * Complete API Test - Verify all components work with new structure
 * Tests MySQL connection, Auth class, and registration flow
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set debug mode
define('DEBUG_MODE', true);

echo "<h1>Complete API Test</h1>";
echo "<p>Testing new public_html/api structure...</p>";

// Test 1: Check file structure
echo "<h2>1. File Structure Test</h2>";
$requiredFiles = [
    'config/ErrorCodes.php',
    'config/database.php', 
    'classes/Auth.php',
    'classes/GameManager.php',
    'classes/PlayerManager.php',
    'classes/PaymentManager.php',
    'classes/NotificationManager.php',
    '.env.example'
];

foreach ($requiredFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file - Found<br>";
    } else {
        echo "❌ $file - Missing<br>";
    }
}

// Test 2: Load configuration classes
echo "<h2>2. Configuration Loading Test</h2>";
try {
    require_once __DIR__ . '/config/ErrorCodes.php';
    echo "✅ ErrorCodes class loaded<br>";
    
    require_once __DIR__ . '/config/database.php';
    echo "✅ Database class loaded<br>";
    
} catch (Exception $e) {
    echo "❌ Configuration loading failed: " . $e->getMessage() . "<br>";
}

// Test 3: Load Auth class
echo "<h2>3. Auth Class Loading Test</h2>";
try {
    require_once __DIR__ . '/classes/Auth.php';
    echo "✅ Auth class loaded<br>";
    
} catch (Exception $e) {
    echo "❌ Auth class loading failed: " . $e->getMessage() . "<br>";
}

// Test 4: Database connection test
echo "<h2>4. Database Connection Test</h2>";
try {
    // Check if .env file exists
    if (file_exists(__DIR__ . '/.env')) {
        echo "✅ .env file found<br>";
        
        // Try MySQL connection
        try {
            $database = Database::getInstance();
            $connection = $database->getConnection();
            echo "✅ MySQL connection successful<br>";
            
            // Test query
            $stmt = $connection->query("SELECT 1 as test");
            $result = $stmt->fetch();
            if ($result['test'] == 1) {
                echo "✅ MySQL query test successful<br>";
            }
            
        } catch (Exception $e) {
            echo "⚠️ MySQL connection failed, will use SQLite fallback: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "⚠️ .env file not found - create from .env.example<br>";
        echo "📝 Copy .env.example to .env and update with your MySQL credentials<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database test failed: " . $e->getMessage() . "<br>";
}

// Test 5: Auth class initialization
echo "<h2>5. Auth Class Initialization Test</h2>";
try {
    $auth = new Auth();
    echo "✅ Auth class initialized successfully<br>";
    
    // Test registration with dummy data
    echo "<h3>Testing Registration Flow:</h3>";
    $testEmail = "test_" . time() . "@example.com";
    $testPassword = "TestPassword123!";
    
    $result = $auth->register($testEmail, $testPassword, false);
    
    if (isset($result['success']) && $result['success']) {
        echo "✅ Registration test successful<br>";
        echo "📧 Test user created: " . $testEmail . "<br>";
    } else {
        echo "❌ Registration test failed: " . ($result['message'] ?? 'Unknown error') . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Auth initialization failed: " . $e->getMessage() . "<br>";
}

// Test 6: API endpoint simulation
echo "<h2>6. API Endpoint Simulation</h2>";
try {
    // Simulate POST request to registration
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    $testData = [
        'email' => 'api_test_' . time() . '@example.com',
        'password' => 'ApiTest123!',
        'newsletter' => false
    ];
    
    // Simulate JSON input
    $jsonInput = json_encode($testData);
    
    echo "✅ API simulation data prepared<br>";
    echo "📝 Test email: " . $testData['email'] . "<br>";
    echo "📝 JSON payload: " . $jsonInput . "<br>";
    
} catch (Exception $e) {
    echo "❌ API simulation failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Summary</h2>";
echo "<p>✅ = Working correctly</p>";
echo "<p>⚠️ = Warning (may work with fallback)</p>";
echo "<p>❌ = Error (needs fixing)</p>";
echo "<p>📝 = Information/Next steps</p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Create .env file from .env.example with your MySQL credentials</li>";
echo "<li>Test registration from frontend: <a href='../register.html'>register.html</a></li>";
echo "<li>Check API endpoint: <a href='index.php'>index.php</a></li>";
echo "<li>Monitor logs in logs/ directory</li>";
echo "</ol>";

?>
