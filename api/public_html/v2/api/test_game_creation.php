<?php
/**
 * Test script for debugging game creation issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'classes/GameManager.php';
require_once 'config/ErrorCodes.php';

echo "<h1>Game Creation Test</h1>\n";
echo "<pre>\n";

try {
    echo "1. Testing GameManager initialization...\n";
    $gameManager = new GameManager(true); // Enable debug mode
    echo "✓ GameManager initialized successfully\n\n";
    
    echo "2. Testing database connection...\n";
    // Use reflection to access private db property
    $reflection = new ReflectionClass($gameManager);
    $dbProperty = $reflection->getProperty('db');
    $dbProperty->setAccessible(true);
    $db = $dbProperty->getValue($gameManager);
    
    if ($db) {
        echo "✓ Database connection established\n";
        echo "Database driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n\n";
    } else {
        echo "✗ Database connection failed\n";
        exit;
    }
    
    echo "3. Checking games table structure...\n";
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if ($driver === 'mysql') {
        $stmt = $db->prepare("DESCRIBE games");
    } else {
        $stmt = $db->prepare("PRAGMA table_info(games)");
    }
    
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "✗ Games table does not exist or is empty\n";
        echo "Attempting to create table...\n";
        
        // Try to trigger table creation
        $reflection = new ReflectionClass($gameManager);
        $method = $reflection->getMethod('createGamesTables');
        $method->setAccessible(true);
        $method->invoke($gameManager);
        
        echo "Table creation attempted\n\n";
        
        // Check again after creation
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (!empty($columns)) {
        echo "✓ Games table exists with columns:\n";
        foreach ($columns as $column) {
            if ($driver === 'mysql') {
                echo "  - {$column['Field']} ({$column['Type']}) {$column['Null']}\n";
            } else {
                echo "  - {$column['name']} ({$column['type']}) " . ($column['notnull'] ? 'NOT NULL' : 'NULL') . "\n";
            }
        }
        echo "\n";
    }
    
    echo "4. Testing anonymous game creation...\n";
    $testGameData = [
        'name' => 'Test Game ' . date('Y-m-d H:i:s'),
        'description' => 'This is a test game created by the debug script'
    ];
    
    echo "Test data: " . json_encode($testGameData) . "\n";
    
    $result = $gameManager->createGameAnonymous($testGameData);
    
    echo "Creation result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($result['success']) {
        echo "✓ Game created successfully!\n";
        echo "Game ID: " . $result['data']['game_id'] . "\n";
        echo "API Token: " . $result['data']['api_token'] . "\n\n";
        
        echo "5. Verifying game in database...\n";
        $gameId = $result['data']['game_id'];
        $stmt = $db->prepare("SELECT * FROM games WHERE game_id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($game) {
            echo "✓ Game found in database:\n";
            echo json_encode($game, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "✗ Game not found in database!\n\n";
        }
        
        echo "6. Testing game listing...\n";
        $stmt = $db->prepare("SELECT COUNT(*) FROM games");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "Total games in database: $count\n\n";
        
    } else {
        echo "✗ Game creation failed!\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n\n";
    }
    
    echo "7. Testing API endpoint directly...\n";
    echo "You can test the API endpoint by making a POST request to:\n";
    echo "URL: " . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/game/create\n";
    echo "Method: POST\n";
    echo "Headers: Content-Type: application/json\n";
    echo "Body: " . json_encode($testGameData) . "\n\n";
    
    echo "8. Testing direct API call simulation...\n";
    
    // Simulate the API call
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/game/create';
    
    // Capture the output
    ob_start();
    
    // Include the API router
    include 'index.php';
    
    $apiOutput = ob_get_clean();
    
    echo "API Response: $apiOutput\n\n";
    
} catch (Exception $e) {
    echo "✗ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<h2>Test Complete</h2>\n";
echo "<p>Check the output above for any issues with game creation.</p>\n";
echo "<p><a href='../game_constructor.html'>Back to Game Constructor</a></p>\n";
?>
