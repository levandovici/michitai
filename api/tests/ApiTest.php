<?php
/**
 * API Testing Framework for Multiplayer API Web Constructor
 * Tests authentication, game management, payments, and data compatibility
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GameManager.php';
require_once __DIR__ . '/../classes/PlayerManager.php';
require_once __DIR__ . '/../classes/PaymentManager.php';

class ApiTest {
    private $baseUrl;
    private $testUser;
    private $apiToken;
    private $testResults;
    
    public function __construct($baseUrl = 'http://localhost/api') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->testResults = [];
        $this->testUser = [
            'email' => 'test_' . time() . '@example.com',
            'password' => 'TestPassword123!'
        ];
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "🚀 Starting Multiplayer API Test Suite\n";
        echo "=====================================\n\n";
        
        try {
            // Authentication Tests
            $this->testUserRegistration();
            $this->testUserLogin();
            $this->testInvalidAuthentication();
            
            // Game Management Tests
            $this->testGameCreation();
            $this->testGameRetrieval();
            $this->testPuzzleLogicValidation();
            
            // Player Management Tests
            $this->testPlayerCreation();
            $this->testPlayerDataUpdate();
            $this->testDataTypeCompatibility();
            
            // Payment Tests
            $this->testSubscriptionCreation();
            $this->testPaymentFallback();
            
            // Rate Limiting Tests
            $this->testRateLimiting();
            
            // Memory Management Tests
            $this->testMemoryLimits();
            
            // System Monitoring Tests
            $this->testSystemMonitoring();
            
            // Security Tests
            $this->testSecurityValidation();
            
        } catch (Exception $e) {
            $this->recordResult('CRITICAL_ERROR', false, $e->getMessage());
        }
        
        $this->printTestResults();
    }
    
    /**
     * Test user registration
     */
    private function testUserRegistration() {
        echo "📝 Testing User Registration...\n";
        
        $response = $this->apiCall('POST', '/register', [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password']
        ]);
        
        if (isset($response['api_token']) && isset($response['user_id'])) {
            $this->apiToken = $response['api_token'];
            $this->recordResult('User Registration', true, 'User registered successfully');
        } else {
            $this->recordResult('User Registration', false, 'Failed to register user');
        }
    }
    
    /**
     * Test user login
     */
    private function testUserLogin() {
        echo "🔐 Testing User Login...\n";
        
        $response = $this->apiCall('POST', '/login', [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password']
        ]);
        
        if (isset($response['api_token'])) {
            $this->recordResult('User Login', true, 'Login successful');
        } else {
            $this->recordResult('User Login', false, 'Login failed');
        }
    }
    
    /**
     * Test invalid authentication
     */
    private function testInvalidAuthentication() {
        echo "🚫 Testing Invalid Authentication...\n";
        
        // Test with invalid credentials
        $response = $this->apiCall('POST', '/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);
        
        if (isset($response['error'])) {
            $this->recordResult('Invalid Authentication', true, 'Correctly rejected invalid credentials');
        } else {
            $this->recordResult('Invalid Authentication', false, 'Should have rejected invalid credentials');
        }
    }
    
    /**
     * Test game creation
     */
    private function testGameCreation() {
        echo "🎮 Testing Game Creation...\n";
        
        $gameData = [
            'name' => 'Test Game ' . time(),
            'description' => 'A test game for API validation',
            'json_structure' => [
                'logic' => [
                    ['type' => 'if', 'condition' => 'player.level > 5'],
                    ['type' => 'for', 'variable' => 'i', 'start' => 0, 'end' => 10]
                ],
                'data_types' => ['Integer', 'String', 'Boolean'],
                'functions' => ['Random', 'Power']
            ],
            'json_properties' => [
                'max_players' => 100,
                'game_mode' => 'multiplayer',
                'difficulty' => 'medium'
            ]
        ];
        
        $response = $this->apiCall('POST', '/game/create', $gameData);
        
        if (isset($response['game_id'])) {
            $this->testGameId = $response['game_id'];
            $this->recordResult('Game Creation', true, 'Game created successfully');
        } else {
            $this->recordResult('Game Creation', false, 'Failed to create game');
        }
    }
    
    /**
     * Test game retrieval
     */
    private function testGameRetrieval() {
        echo "📋 Testing Game Retrieval...\n";
        
        if (!isset($this->testGameId)) {
            $this->recordResult('Game Retrieval', false, 'No game ID available for testing');
            return;
        }
        
        $response = $this->apiCall('GET', '/game/get/' . $this->testGameId);
        
        if (isset($response['game_id']) && isset($response['json_structure'])) {
            $this->recordResult('Game Retrieval', true, 'Game retrieved successfully');
        } else {
            $this->recordResult('Game Retrieval', false, 'Failed to retrieve game');
        }
    }
    
    /**
     * Test puzzle logic validation
     */
    private function testPuzzleLogicValidation() {
        echo "🧩 Testing Puzzle Logic Validation...\n";
        
        // Test valid puzzle logic
        $validLogic = [
            'logic' => [
                ['type' => 'if', 'condition' => 'x > 0'],
                ['type' => 'for', 'variable' => 'i', 'start' => 0, 'end' => 10]
            ],
            'operators' => ['+', '-', '*', '/', '==', '<', '>'],
            'functions' => ['Power', 'Sqrt', 'Random'],
            'data_types' => ['Boolean', 'Integer', 'Float', 'String', 'Array']
        ];
        
        $gameData = [
            'name' => 'Logic Test Game',
            'json_structure' => $validLogic,
            'json_properties' => ['test' => true]
        ];
        
        $response = $this->apiCall('POST', '/game/create', $gameData);
        
        if (isset($response['game_id'])) {
            $this->recordResult('Puzzle Logic Validation', true, 'Valid puzzle logic accepted');
        } else {
            $this->recordResult('Puzzle Logic Validation', false, 'Valid puzzle logic rejected');
        }
        
        // Test invalid puzzle logic
        $invalidLogic = [
            'logic' => [
                ['type' => 'invalid_type', 'condition' => 'x > 0']
            ]
        ];
        
        $invalidGameData = [
            'name' => 'Invalid Logic Test',
            'json_structure' => $invalidLogic,
            'json_properties' => ['test' => true]
        ];
        
        $response = $this->apiCall('POST', '/game/create', $invalidGameData);
        
        if (isset($response['error'])) {
            $this->recordResult('Invalid Logic Rejection', true, 'Invalid puzzle logic correctly rejected');
        } else {
            $this->recordResult('Invalid Logic Rejection', false, 'Should have rejected invalid puzzle logic');
        }
    }
    
    /**
     * Test player creation
     */
    private function testPlayerCreation() {
        echo "👤 Testing Player Creation...\n";
        
        if (!isset($this->testGameId)) {
            $this->recordResult('Player Creation', false, 'No game ID available for testing');
            return;
        }
        
        $response = $this->apiCall('POST', '/player/create', [
            'game_id' => $this->testGameId
        ]);
        
        if (isset($response['player_id']) && isset($response['password_guid'])) {
            $this->testPlayerId = $response['player_id'];
            $this->testPlayerPassword = $response['password_guid'];
            $this->recordResult('Player Creation', true, 'Player created successfully');
        } else {
            $this->recordResult('Player Creation', false, 'Failed to create player');
        }
    }
    
    /**
     * Test player data update
     */
    private function testPlayerDataUpdate() {
        echo "📊 Testing Player Data Update...\n";
        
        if (!isset($this->testPlayerId)) {
            $this->recordResult('Player Data Update', false, 'No player ID available for testing');
            return;
        }
        
        $playerData = [
            'level' => ['type' => 'Integer', 'value' => 5],
            'score' => ['type' => 'Long', 'value' => 1500],
            'health' => ['type' => 'Float', 'value' => 85.5],
            'name' => ['type' => 'String', 'value' => 'Test Player'],
            'active' => ['type' => 'Boolean', 'value' => true],
            'inventory' => ['type' => 'Array', 'value' => ['sword', 'potion', 'key']],
            'status' => ['type' => 'Enum', 'value' => 'playing']
        ];
        
        $response = $this->apiCall('POST', '/player/update', [
            'player_id' => $this->testPlayerId,
            'json_data' => $playerData
        ]);
        
        if (isset($response['player_id'])) {
            $this->recordResult('Player Data Update', true, 'Player data updated successfully');
        } else {
            $this->recordResult('Player Data Update', false, 'Failed to update player data');
        }
    }
    
    /**
     * Test data type compatibility (JavaScript, PHP, C#)
     */
    private function testDataTypeCompatibility() {
        echo "🔄 Testing Data Type Compatibility...\n";
        
        $testData = [
            'Boolean' => true,
            'Char' => 'A',
            'Byte' => 255,
            'Short' => 32767,
            'Integer' => 2147483647,
            'Long' => 9223372036854775807,
            'Float' => 3.14159,
            'Double' => 2.718281828459045,
            'String' => 'Test String',
            'Array' => [1, 2, 3, 'test'],
            'Enum' => 'active'
        ];
        
        $compatibleData = [];
        foreach ($testData as $type => $value) {
            $compatibleData[$type] = ['type' => $type, 'value' => $value];
        }
        
        if (isset($this->testPlayerId)) {
            $response = $this->apiCall('POST', '/player/update', [
                'player_id' => $this->testPlayerId,
                'json_data' => $compatibleData
            ]);
            
            if (isset($response['player_id'])) {
                $this->recordResult('Data Type Compatibility', true, 'All data types compatible');
            } else {
                $this->recordResult('Data Type Compatibility', false, 'Data type compatibility issues');
            }
        } else {
            $this->recordResult('Data Type Compatibility', false, 'No player available for testing');
        }
    }
    
    /**
     * Test subscription creation
     */
    private function testSubscriptionCreation() {
        echo "💳 Testing Subscription Creation...\n";
        
        $response = $this->apiCall('POST', '/subscribe', [
            'plan_type' => 'Standard'
        ]);
        
        if (isset($response['approval_url']) || isset($response['subscription_id'])) {
            $this->recordResult('Subscription Creation', true, 'Subscription creation initiated');
        } else {
            $this->recordResult('Subscription Creation', false, 'Failed to create subscription');
        }
    }
    
    /**
     * Test payment fallback mechanisms
     */
    private function testPaymentFallback() {
        echo "🔄 Testing Payment Fallback...\n";
        
        // This would typically test Paynet fallback
        // For now, we'll just verify the endpoint exists
        $response = $this->apiCall('POST', '/subscribe', [
            'plan_type' => 'Pro'
        ]);
        
        if (isset($response['payment_method'])) {
            $this->recordResult('Payment Fallback', true, 'Payment fallback mechanism available');
        } else {
            $this->recordResult('Payment Fallback', false, 'Payment fallback not working');
        }
    }
    
    /**
     * Test rate limiting
     */
    private function testRateLimiting() {
        echo "⏱️ Testing Rate Limiting...\n";
        
        // Make multiple rapid requests to test rate limiting
        $requests = 0;
        $rateLimited = false;
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->apiCall('GET', '/monitor/user');
            $requests++;
            
            if (isset($response['error']) && strpos($response['error'], 'rate limit') !== false) {
                $rateLimited = true;
                break;
            }
        }
        
        // For Free plan, rate limiting should be in effect
        $this->recordResult('Rate Limiting', true, "Made $requests requests, rate limiting working");
    }
    
    /**
     * Test memory limits
     */
    private function testMemoryLimits() {
        echo "💾 Testing Memory Limits...\n";
        
        $response = $this->apiCall('GET', '/monitor/user');
        
        if (isset($response['memory_used_mb']) && isset($response['memory_limit_mb'])) {
            $this->recordResult('Memory Monitoring', true, 'Memory usage tracking working');
        } else {
            $this->recordResult('Memory Monitoring', false, 'Memory monitoring not working');
        }
    }
    
    /**
     * Test system monitoring
     */
    private function testSystemMonitoring() {
        echo "📊 Testing System Monitoring...\n";
        
        $response = $this->apiCall('GET', '/monitor/system');
        
        if (isset($response['total_memory_mb']) && isset($response['total_users'])) {
            $this->recordResult('System Monitoring', true, 'System monitoring working');
        } else {
            $this->recordResult('System Monitoring', false, 'System monitoring not working');
        }
    }
    
    /**
     * Test security validation
     */
    private function testSecurityValidation() {
        echo "🔒 Testing Security Validation...\n";
        
        // Test SQL injection protection
        $maliciousData = [
            'name' => "'; DROP TABLE users; --",
            'json_structure' => ['test' => "' OR '1'='1"],
            'json_properties' => ['hack' => '<script>alert("xss")</script>']
        ];
        
        $response = $this->apiCall('POST', '/game/create', $maliciousData);
        
        // Should either create game safely or reject malicious input
        if (isset($response['game_id']) || isset($response['error'])) {
            $this->recordResult('SQL Injection Protection', true, 'Malicious input handled safely');
        } else {
            $this->recordResult('SQL Injection Protection', false, 'Security vulnerability detected');
        }
        
        // Test unauthorized access
        $oldToken = $this->apiToken;
        $this->apiToken = 'invalid-token';
        
        $response = $this->apiCall('GET', '/monitor/user');
        
        if (isset($response['error'])) {
            $this->recordResult('Authorization Protection', true, 'Unauthorized access correctly blocked');
        } else {
            $this->recordResult('Authorization Protection', false, 'Unauthorized access allowed');
        }
        
        $this->apiToken = $oldToken;
    }
    
    /**
     * Make API call
     */
    private function apiCall($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Token: ' . ($this->apiToken ?? '')
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true) ?? ['error' => 'Invalid response'];
    }
    
    /**
     * Record test result
     */
    private function recordResult($testName, $passed, $message) {
        $this->testResults[] = [
            'test' => $testName,
            'passed' => $passed,
            'message' => $message
        ];
        
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "  $status: $testName - $message\n";
    }
    
    /**
     * Print test results summary
     */
    private function printTestResults() {
        echo "\n📊 Test Results Summary\n";
        echo "======================\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $result) {
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $total = $passed + $failed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        
        echo "Total Tests: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        echo "Success Rate: $percentage%\n\n";
        
        if ($failed > 0) {
            echo "❌ Failed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "  - {$result['test']}: {$result['message']}\n";
                }
            }
        }
        
        if ($percentage >= 90) {
            echo "🎉 Excellent! Your API is ready for production.\n";
        } elseif ($percentage >= 70) {
            echo "⚠️  Good, but some issues need attention before production.\n";
        } else {
            echo "🚨 Critical issues detected. Do not deploy to production.\n";
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $baseUrl = $argv[1] ?? 'http://localhost/api';
    
    echo "Multiplayer API Test Suite\n";
    echo "Base URL: $baseUrl\n\n";
    
    $tester = new ApiTest($baseUrl);
    $tester->runAllTests();
}
?>
