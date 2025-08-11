<?php
/**
 * Blockly Logic API Test Interface
 * Web-based interface for testing Blockly logic endpoints
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is an AJAX request for running tests
if (isset($_POST['action']) && $_POST['action'] === 'run_blockly_tests') {
    header('Content-Type: application/json');
    
    $baseUrl = $_POST['baseUrl'] ?? '/api';
    $testEmail = $_POST['testEmail'] ?? 'nichitalnc@gmail.com';
    $testPassword = $_POST['testPassword'] ?? 'test_password';
    
    $result = runBlocklyTests($baseUrl, $testEmail, $testPassword);
    echo json_encode($result);
    exit;
}

// Default test configuration
$testEmail = 'nichitalnc@gmail.com';
$testPassword = 'test_password';
$baseUrl = '/api';

// Helper function to make API requests
function makeRequest($url, $method = 'GET', $data = null, $token = null, $baseUrl = '/api') {
    // If it's a relative URL, prepend the base URL
    if (strpos($url, 'http') !== 0) {
        $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'X-API-Token: ' . $token;
    }
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ];
    
    if ($method === 'POST' || $method === 'PUT') {
        $jsonData = json_encode($data);
        $options[CURLOPT_POSTFIELDS] = $jsonData;
        $headers[] = 'Content-Length: ' . strlen($jsonData);
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'data' => null,
            'message' => 'CURL Error: ' . $error,
            'http_code' => 0
        ];
    }
    
    curl_close($ch);
    
    $body = substr($response, $headerSize);
    $decoded = json_decode($body, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'data' => $decoded,
        'message' => $decoded['message'] ?? "HTTP $httpCode",
        'http_code' => $httpCode,
        'raw_response' => $body
    ];
}

function runBlocklyTests($baseUrl, $testEmail, $testPassword) {
    $results = [];
    $testApiToken = null;
    $testGameId = null;
    
    // Test 1: Login to get API token
    $results['login'] = testLogin($baseUrl, $testEmail, $testPassword);
    if ($results['login']['success']) {
        // Try multiple possible token locations based on API response structure
        $testApiToken = $results['login']['data']['api_token'] ?? 
                       $results['login']['data']['data']['api_token'] ?? 
                       $results['login']['data']['token'] ?? null;
        $testUserId = $results['login']['data']['user_id'] ?? 
                     $results['login']['data']['data']['user_id'] ?? null;
    }
    
    if (!$testApiToken) {
        return [
            'overall_success' => false,
            'message' => 'Failed to get API token - cannot continue tests',
            'results' => $results
        ];
    }
    
    // Test 2: Create test game
    $results['create_game'] = testCreateGame($baseUrl, $testApiToken);
    if ($results['create_game']['success']) {
        // Try multiple possible game ID locations
        $testGameId = $results['create_game']['data']['game_id'] ?? 
                     $results['create_game']['data']['data']['game_id'] ?? 
                     $results['create_game']['data']['id'] ?? null;
    }
    
    if (!$testGameId) {
        return [
            'overall_success' => false,
            'message' => 'Failed to create test game - cannot continue logic tests',
            'results' => $results
        ];
    }
    
    // Test 3: Save Logic
    $results['save_logic'] = testSaveLogic($baseUrl, $testApiToken, $testGameId);
    
    // Test 4: Get Logic
    $results['get_logic'] = testGetLogic($baseUrl, $testApiToken, $testGameId);
    
    // Test 5: Export Logic
    $results['export_logic'] = testExportLogic($baseUrl, $testApiToken, $testGameId);
    
    // Test 6: Import Logic
    $results['import_logic'] = testImportLogic($baseUrl, $testApiToken, $testGameId);
    
    // Test 7: Simulate Logic
    $results['simulate_logic'] = testSimulateLogic($baseUrl, $testApiToken);
    
    // Test 8: Security tests
    $results['security'] = testSecurity($baseUrl, $testGameId);
    
    // Calculate overall success
    $successCount = 0;
    $totalTests = count($results);
    
    foreach ($results as $result) {
        if ($result['success']) {
            $successCount++;
        }
    }
    
    return [
        'overall_success' => $successCount === $totalTests,
        'success_rate' => round(($successCount / $totalTests) * 100, 1),
        'passed' => $successCount,
        'total' => $totalTests,
        'results' => $results
    ];
}

function testLogin($baseUrl, $email, $password) {
    $response = makeRequest('login', 'POST', [
        'email' => $email,
        'password' => $password
    ], null, $baseUrl);
    
    // Check for API token in multiple possible locations
    $hasApiToken = isset($response['data']['api_token']) || 
                   isset($response['data']['data']['api_token']) || 
                   isset($response['data']['token']);
    
    return [
        'success' => $response['success'] && $hasApiToken,
        'message' => $response['success'] ? 
            ($hasApiToken ? 'Login successful' : 'Login successful but no API token found') : 
            'Login failed: ' . $response['message'],
        'data' => $response
    ];
}

function testCreateGame($baseUrl, $token) {
    $response = makeRequest('game/create', 'POST', [
        'name' => 'Blockly Test Game ' . time(),
        'description' => 'Test game for Blockly logic testing',
        'max_players' => 4
    ], $token, $baseUrl);
    
    return [
        'success' => $response['success'] && isset($response['data']['game_id']),
        'message' => $response['success'] ? 'Game created successfully' : 'Game creation failed: ' . $response['message'],
        'data' => $response
    ];
}

function testSaveLogic($baseUrl, $token, $gameId) {
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
    
    $response = makeRequest('game/saveLogic', 'POST', [
        'game_id' => $gameId,
        'logic_json' => $testLogic,
        'description' => 'Test logic save'
    ], $token, $baseUrl);
    
    return [
        'success' => $response['success'],
        'message' => $response['success'] ? 'Logic saved successfully' : 'Save logic failed: ' . $response['message'],
        'data' => $response
    ];
}

function testGetLogic($baseUrl, $token, $gameId) {
    $response = makeRequest("game/getLogic?game_id=$gameId", 'GET', null, $token, $baseUrl);
    
    return [
        'success' => $response['success'],
        'message' => $response['success'] ? 'Logic retrieved successfully' : 'Get logic failed: ' . $response['message'],
        'data' => $response
    ];
}

function testExportLogic($baseUrl, $token, $gameId) {
    $response = makeRequest("game/exportLogic?game_id=$gameId", 'GET', null, $token, $baseUrl);
    
    return [
        'success' => $response['success'],
        'message' => $response['success'] ? 'Logic exported successfully' : 'Export logic failed: ' . $response['message'],
        'data' => $response
    ];
}

function testImportLogic($baseUrl, $token, $gameId) {
    $importLogic = [
        'blocks' => [
            'languageVersion' => 0,
            'blocks' => [
                [
                    'type' => 'game_start',
                    'id' => 'imported_start',
                    'x' => 100,
                    'y' => 100
                ]
            ]
        ]
    ];
    
    $response = makeRequest('game/importLogic', 'POST', [
        'game_id' => $gameId,
        'logic_json' => $importLogic
    ], $token, $baseUrl);
    
    return [
        'success' => $response['success'],
        'message' => $response['success'] ? 'Logic imported successfully' : 'Import logic failed: ' . $response['message'],
        'data' => $response
    ];
}

function testSimulateLogic($baseUrl, $token) {
    $simulationLogic = [
        'blocks' => [
            'languageVersion' => 0,
            'blocks' => [
                [
                    'type' => 'game_start',
                    'id' => 'sim_start'
                ]
            ]
        ]
    ];
    
    $response = makeRequest('game/simulateLogic', 'POST', [
        'logic_json' => $simulationLogic,
        'test_data' => [
            'player_count' => 2,
            'initial_scores' => [0, 0]
        ]
    ], $token, $baseUrl);
    
    return [
        'success' => $response['success'],
        'message' => $response['success'] ? 'Logic simulation completed' : 'Simulation failed: ' . $response['message'],
        'data' => $response
    ];
}

function testSecurity($baseUrl, $gameId) {
    // Test without token
    $response = makeRequest("game/getLogic?game_id=$gameId", 'GET', null, null, $baseUrl);
    
    $unauthorizedBlocked = !$response['success'] && $response['http_code'] === 401;
    
    return [
        'success' => $unauthorizedBlocked,
        'message' => $unauthorizedBlocked ? 'Security test passed - unauthorized access blocked' : 'Security test failed - unauthorized access allowed',
        'data' => $response
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Blockly Logic API Test Interface</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .test-config {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .config-title {
            font-size: 1.5rem;
            color: #495057;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input {
            padding: 12px 16px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .results {
            margin-top: 30px;
        }

        .results-title {
            font-size: 1.5rem;
            color: #495057;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .overall-result {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px solid #dee2e6;
        }

        .overall-result.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
            color: #155724;
        }

        .overall-result.failure {
            background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%);
            border-color: #dc3545;
            color: #721c24;
        }

        .overall-result h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .test-results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 15px;
        }

        .test-result {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid #dee2e6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .test-result:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .test-result.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
        }

        .test-result.failure {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #ffffff 0%, #fff8f8 100%);
        }

        .test-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .test-message {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 1.2rem;
            color: #495057;
            margin-bottom: 10px;
        }

        .loading-subtext {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-success {
            background: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }

        .status-failure {
            background: #dc3545;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }

        .endpoint-info {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .endpoint-info h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .endpoint-info p {
            color: #856404;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .content {
                padding: 20px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .test-results {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Blockly Logic API Test Interface</h1>
            <p>Test all Blockly Logic endpoints with your own credentials</p>
        </div>

        <div class="content">
            <div class="endpoint-info">
                <h4>⚠️ Note: Blockly Logic Endpoints</h4>
                <p>• The Blockly Logic endpoints (saveLogic, getLogic, etc.) need to be implemented in the backend</p>
                <p>• Currently testing authentication and basic game endpoints</p>
                <p>• Once backend endpoints are ready, all tests will pass</p>
            </div>

            <div class="test-config">
                <div class="config-title">
                    🔧 Test Configuration
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="baseUrl">API Base URL</label>
                        <input type="text" id="baseUrl" value="<?php echo htmlspecialchars($baseUrl); ?>" placeholder="https://api.michitai.com/api">
                    </div>
                    
                    <div class="form-group">
                        <label for="testEmail">Your Email</label>
                        <input type="email" id="testEmail" value="<?php echo htmlspecialchars($testEmail); ?>" placeholder="your.email@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="testPassword">Your Password</label>
                        <input type="password" id="testPassword" value="<?php echo htmlspecialchars($testPassword); ?>" placeholder="Your password">
                    </div>
                </div>

                <div class="button-group">
                    <button class="btn btn-primary" onclick="runTests()" id="runButton">
                        🚀 Run All Tests
                    </button>
                    <button class="btn btn-secondary" onclick="clearResults()">
                        🗑️ Clear Results
                    </button>
                </div>
            </div>

            <div id="results" class="results" style="display: none;">
                <div class="results-title">
                    📊 Test Results
                </div>
                <div id="resultsContent"></div>
            </div>
        </div>
    </div>

    <script>
        async function runTests() {
            const button = document.getElementById('runButton');
            const results = document.getElementById('results');
            const resultsContent = document.getElementById('resultsContent');
            
            // Validate inputs
            const email = document.getElementById('testEmail').value.trim();
            const password = document.getElementById('testPassword').value.trim();
            const apiUrl = document.getElementById('baseUrl').value.trim();
            
            if (!email || !password) {
                alert('Please enter your email and password');
                return;
            }
            
            button.disabled = true;
            button.innerHTML = '⏳ Running Tests...';
            
            results.style.display = 'block';
            resultsContent.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <div class="loading-text">Running Blockly Logic API Tests</div>
                    <div class="loading-subtext">Testing authentication and endpoints...</div>
                </div>
            `;
            
            try {
                const formData = new FormData();
                formData.append('action', 'run_blockly_tests');
                formData.append('baseUrl', apiUrl.replace(/\/api$/, '') + '/api');
                formData.append('testEmail', email);
                formData.append('testPassword', password);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                displayResults(data);
                
            } catch (error) {
                resultsContent.innerHTML = `
                    <div class="test-result failure">
                        <div class="test-name">
                            <span class="status-indicator status-failure"></span>
                            Error Running Tests
                        </div>
                        <div class="test-message">Failed to run tests: ${error.message}</div>
                    </div>
                `;
            } finally {
                button.disabled = false;
                button.innerHTML = '🚀 Run All Tests';
            }
        }
        
        function displayResults(data) {
            const resultsContent = document.getElementById('resultsContent');
            
            // Overall result
            const overallClass = data.overall_success ? 'success' : 'failure';
            const overallIcon = data.overall_success ? '🎉' : '⚠️';
            
            let html = `
                <div class="overall-result ${overallClass}">
                    <h3>${overallIcon} Overall Result</h3>
                    <p><strong>${data.passed}/${data.total}</strong> tests passed (${data.success_rate}%)</p>
                </div>
            `;
            
            // Individual test results
            html += '<div class="test-results">';
            for (const [testName, result] of Object.entries(data.results)) {
                const resultClass = result.success ? 'success' : 'failure';
                const statusClass = result.success ? 'status-success' : 'status-failure';
                const icon = result.success ? '✅' : '❌';
                
                html += `
                    <div class="test-result ${resultClass}">
                        <div class="test-name">
                            <span class="status-indicator ${statusClass}"></span>
                            ${icon} ${formatTestName(testName)}
                        </div>
                        <div class="test-message">${result.message}</div>
                    </div>
                `;
            }
            html += '</div>';
            
            resultsContent.innerHTML = html;
        }
        
        function formatTestName(testName) {
            return testName.replace(/_/g, ' ')
                          .replace(/\b\w/g, l => l.toUpperCase());
        }
        
        function clearResults() {
            const results = document.getElementById('results');
            results.style.display = 'none';
        }

        // Auto-focus email field on load
        window.addEventListener('load', function() {
            document.getElementById('testEmail').focus();
        });
    </script>
</body>
</html>
