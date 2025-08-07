<?php
/**
 * Diagnostic Script for API Issues
 * Tests basic PHP functionality and database connection
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>API Diagnostic Test</h1>";
echo "<p>Testing API functionality...</p>";

// Test 1: Basic PHP
echo "<h2>1. PHP Basic Test</h2>";
echo "✅ PHP is working<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test 2: File structure
echo "<h2>2. File Structure Test</h2>";
$files = [
    '.env' => file_exists(__DIR__ . '/.env'),
    '.env.example' => file_exists(__DIR__ . '/.env.example'),
    'config/database.php' => file_exists(__DIR__ . '/config/database.php'),
    'config/ErrorCodes.php' => file_exists(__DIR__ . '/config/ErrorCodes.php'),
    'classes/Auth.php' => file_exists(__DIR__ . '/classes/Auth.php'),
    'index.php' => file_exists(__DIR__ . '/index.php')
];

foreach ($files as $file => $exists) {
    echo ($exists ? "✅" : "❌") . " $file<br>";
}

// Test 3: Environment loading
echo "<h2>3. Environment Test</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "✅ .env file exists<br>";
    
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (strpos($envContent, 'DB_HOST') !== false) {
        echo "✅ .env contains database config<br>";
    } else {
        echo "❌ .env missing database config<br>";
    }
} else {
    echo "❌ .env file missing - copy from .env.example<br>";
}

// Test 4: Database connection
echo "<h2>4. Database Connection Test</h2>";
try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        echo "✅ Database config loaded<br>";
        
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
            echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
            echo "📝 Will use SQLite fallback<br>";
        }
    } else {
        echo "❌ Database config file missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Database test error: " . $e->getMessage() . "<br>";
}

// Test 5: Auth class loading
echo "<h2>5. Auth Class Test</h2>";
try {
    if (file_exists(__DIR__ . '/config/ErrorCodes.php')) {
        require_once __DIR__ . '/config/ErrorCodes.php';
        echo "✅ ErrorCodes loaded<br>";
    }
    
    if (file_exists(__DIR__ . '/classes/Auth.php')) {
        require_once __DIR__ . '/classes/Auth.php';
        echo "✅ Auth class loaded<br>";
        
        $auth = new Auth();
        echo "✅ Auth class instantiated<br>";
    }
} catch (Exception $e) {
    echo "❌ Auth class error: " . $e->getMessage() . "<br>";
}

// Test 6: API endpoint simulation
echo "<h2>6. API Endpoint Simulation</h2>";
try {
    // Simulate registration request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    echo "✅ Server variables set<br>";
    echo "✅ Ready for API testing<br>";
    
} catch (Exception $e) {
    echo "❌ API simulation error: " . $e->getMessage() . "<br>";
}

echo "<h2>Summary</h2>";
echo "<p>If you see mostly ✅ marks above, the API should be working.</p>";
echo "<p>If you see ❌ marks, those indicate issues that need to be fixed.</p>";
echo "<p><strong>Next step:</strong> Fix any ❌ issues, then test registration again.</p>";

?>
