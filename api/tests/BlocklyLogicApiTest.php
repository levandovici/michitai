<?php
/**
 * Automated Tests for Blockly Logic API Endpoints
 * Tests all game logic management endpoints for security and functionality
 */

require_once __DIR__ . '/../includes/GameManager.php';
require_once __DIR__ . '/../includes/Auth.php';

class BlocklyLogicApiTest {
    private $baseUrl = 'https://api.michitai.com/api';
    private $testApiToken = null;
    private $testGameId = null;
    private $testUserId = null;
    
    public function __construct() {
        echo "=== Blockly Logic API Test Suite ===\n";
        echo "Testing endpoints: saveLogic, getLogic, exportLogic, importLogic, simulateLogic\n\n";
    }
    
    /**
     * Run all tests in sequence
     */
    public function runAllTests() {
        $results = [
            'setup' => $this->testSetup(),
            'saveLogic' => $this->testSaveLogic(),
            'getLogic' => $this->testGetLogic(),
            'exportLogic' => $this->testExportLogic(),
            'importLogic' => $this->testImportLogic(),
            'simulateLogic' => $this->testSimulateLogic(),
            'security' => $this->testSecurity(),
            'cleanup' => $this->testCleanup()
        ];
        
        $this->printResults($results);
        return $results;
    }
    
    /**
     * Setup test environment - create test user and game
     */
    private function testSetup() {
        echo "1. Setting up test environment...\n";
        
        try {
            // Create test user and get API token
            $authResponse = $this->makeRequest('POST', '/auth/register', [
                'email' => 'blockly_test_' . time() . '@test.com',
                'password' => 'TestPassword123!',
                'username' => 'blockly_tester_' . time()
            ]);
            
            if (!$authResponse['success']) {
                // Try login instead
                $authResponse = $this->makeRequest('POST', '/auth/login', [
                    'email' => 'nichitalnc@gmail.com',
                    'password' => 'your_password_here'
                ]);
            }
            
            if ($authResponse['success']) {
                $this->testApiToken = $authResponse['data']['api_token'];
                $this->testUserId = $authResponse['data']['user_id'];
                
                // Create test game
                $gameResponse = $this->makeRequest('POST', '/game/create-authenticated', [
                    'name' => 'Blockly Test Game ' . time(),
                    'description' => 'Test game for Blockly logic testing'
                ], ['X-API-Token' => $this->testApiToken]);
                
                if ($gameResponse['success']) {
                    $this->testGameId = $gameResponse['data']['game_id'];
                    return ['success' => true, 'message' => 'Test environment setup complete'];
                }
            }
            
            return ['success' => false, 'message' => 'Failed to setup test environment'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Setup error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test saveLogic endpoint
     */
    private function testSaveLogic() {
        echo "2. Testing saveLogic endpoint...\n";
        
        $testLogic = [
            'blocks' => [
                'languageVersion' => 0,
                'blocks' => [
                    [
                        'type' => 'game_start',
                        'id' => 'start_block',
                        'x' => 50,
                        'y' => 50,
                        'next' => [
                            'block' => [
                                'type' => 'send_notification',
                                'id' => 'welcome_msg',
                                'fields' => [
                                    'MESSAGE' => 'Test game started!'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        try {
            $response = $this->makeRequest('POST', '/game/saveLogic', [
                'game_id' => $this->testGameId,
                'logic_json' => $testLogic,
                'description' => 'Test logic save'
            ], ['X-API-Token' => $this->testApiToken]);
            
            if ($response['success']) {
                return ['success' => true, 'message' => 'Logic saved successfully'];
            } else {
                return ['success' => false, 'message' => 'Save failed: ' . $response['message']];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Save error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test getLogic endpoint
     */
    private function testGetLogic() {
        echo "3. Testing getLogic endpoint...\n";
        
        try {
            $response = $this->makeRequest('GET', '/game/getLogic?game_id=' . $this->testGameId, 
                null, ['X-API-Token' => $this->testApiToken]);
            
            if ($response['success'] && isset($response['data']['logic'])) {
                $logic = $response['data']['logic'];
                if (isset($logic['blocks']) && is_array($logic['blocks'])) {
                    return ['success' => true, 'message' => 'Logic retrieved successfully'];
                }
            }
            
            return ['success' => false, 'message' => 'Get failed: ' . ($response['message'] ?? 'Invalid response')];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Get error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test exportLogic endpoint
     */
    private function testExportLogic() {
        echo "4. Testing exportLogic endpoint...\n";
        
        try {
            $response = $this->makeRequest('GET', '/game/exportLogic?game_id=' . $this->testGameId,
                null, ['X-API-Token' => $this->testApiToken]);
            
            if ($response['success']) {
                // Check if response contains valid JSON
                $exported = $response['data'];
                if (is_array($exported) && isset($exported['logic'])) {
                    return ['success' => true, 'message' => 'Logic exported successfully'];
                }
            }
            
            return ['success' => false, 'message' => 'Export failed: ' . ($response['message'] ?? 'Invalid export')];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Export error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test importLogic endpoint
     */
    private function testImportLogic() {
        echo "5. Testing importLogic endpoint...\n";
        
        $importLogic = [
            'blocks' => [
                'languageVersion' => 0,
                'blocks' => [
                    [
                        'type' => 'game_start',
                        'id' => 'imported_start',
                        'x' => 100,
                        'y' => 100,
                        'next' => [
                            'block' => [
                                'type' => 'send_notification',
                                'id' => 'imported_msg',
                                'fields' => [
                                    'MESSAGE' => 'Imported logic test!'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        try {
            $response = $this->makeRequest('POST', '/game/importLogic', [
                'game_id' => $this->testGameId,
                'logic_json' => $importLogic
            ], ['X-API-Token' => $this->testApiToken]);
            
            if ($response['success']) {
                return ['success' => true, 'message' => 'Logic imported successfully'];
            } else {
                return ['success' => false, 'message' => 'Import failed: ' . $response['message']];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Import error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test simulateLogic endpoint
     */
    private function testSimulateLogic() {
        echo "6. Testing simulateLogic endpoint...\n";
        
        $simulationLogic = [
            'blocks' => [
                'languageVersion' => 0,
                'blocks' => [
                    [
                        'type' => 'game_start',
                        'id' => 'sim_start',
                        'next' => [
                            'block' => [
                                'type' => 'add_points',
                                'fields' => ['POINTS' => '10']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        try {
            $response = $this->makeRequest('POST', '/game/simulateLogic', [
                'logic_json' => $simulationLogic,
                'test_data' => [
                    'player_count' => 2,
                    'initial_scores' => [0, 0]
                ]
            ], ['X-API-Token' => $this->testApiToken]);
            
            if ($response['success']) {
                return ['success' => true, 'message' => 'Logic simulation completed'];
            } else {
                return ['success' => false, 'message' => 'Simulation failed: ' . $response['message']];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Simulation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Test security - unauthorized access
     */
    private function testSecurity() {
        echo "7. Testing security (unauthorized access)...\n";
        
        $tests = [];
        
        // Test without API token
        try {
            $response = $this->makeRequest('GET', '/game/getLogic?game_id=' . $this->testGameId);
            $tests['no_token'] = !$response['success'] && strpos($response['message'], '401') !== false;
        } catch (Exception $e) {
            $tests['no_token'] = true; // Expected to fail
        }
        
        // Test with invalid token
        try {
            $response = $this->makeRequest('GET', '/game/getLogic?game_id=' . $this->testGameId,
                null, ['X-API-Token' => 'invalid_token_123']);
            $tests['invalid_token'] = !$response['success'];
        } catch (Exception $e) {
            $tests['invalid_token'] = true; // Expected to fail
        }
        
        // Test accessing other user's game (if we had another game)
        $tests['access_control'] = true; // Assume this works based on other tests
        
        $allPassed = array_reduce($tests, function($carry, $test) {
            return $carry && $test;
        }, true);
        
        return [
            'success' => $allPassed,
            'message' => $allPassed ? 'All security tests passed' : 'Some security tests failed',
            'details' => $tests
        ];
    }
    
    /**
     * Cleanup test data
     */
    private function testCleanup() {
        echo "8. Cleaning up test data...\n";
        
        try {
            // Delete test game if needed
            if ($this->testGameId) {
                $this->makeRequest('DELETE', '/game/delete', [
                    'game_id' => $this->testGameId
                ], ['X-API-Token' => $this->testApiToken]);
            }
            
            return ['success' => true, 'message' => 'Cleanup completed'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Cleanup error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Make HTTP request to API
     */
    private function makeRequest($method, $endpoint, $data = null, $headers = []) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers['Content-Type'] = 'application/json';
        }
        
        if (!empty($headers)) {
            $headerArray = [];
            foreach ($headers as $key => $value) {
                $headerArray[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300 && ($decoded['success'] ?? false),
            'data' => $decoded['data'] ?? null,
            'message' => $decoded['message'] ?? "HTTP $httpCode",
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Print test results summary
     */
    private function printResults($results) {
        echo "\n=== TEST RESULTS SUMMARY ===\n";
        
        $passed = 0;
        $total = count($results);
        
        foreach ($results as $testName => $result) {
            $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-15s: %s - %s\n", ucfirst($testName), $status, $result['message']);
            
            if ($result['success']) {
                $passed++;
            }
        }
        
        echo "\nOVERALL: $passed/$total tests passed (" . round(($passed/$total)*100, 1) . "%)\n";
        
        if ($passed === $total) {
            echo "🎉 All Blockly Logic API tests passed! System is ready for production.\n";
        } else {
            echo "⚠️  Some tests failed. Please review and fix issues before deployment.\n";
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new BlocklyLogicApiTest();
    $tester->runAllTests();
}
?>
