<?php
/**
 * Game Ownership and Authentication Test Interface
 * Web-based interface for testing the complete authentication flow and game ownership functionality
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is an AJAX request for running tests
if (isset($_POST['action']) && $_POST['action'] === 'run_tests') {
    header('Content-Type: application/json');
    
    $baseUrl = $_POST['baseUrl'] ?? '/api';
    $testEmail = $_POST['testEmail'] ?? 'test@example.com';
    $testPassword = $_POST['testPassword'] ?? 'Test@1234';
    
    $result = runAllTests($baseUrl, $testEmail, $testPassword);
    echo json_encode($result);
    exit;
}

// Default test configuration
$testEmail = 'test_' . uniqid() . '@example.com';
$testPassword = 'Test@1234';
$baseUrl = '/api';

// Helper functions for API testing
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
    } elseif ($method === 'GET' && $data) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        $options[CURLOPT_URL] = $url;
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        return [
            'status' => 0,
            'error' => curl_error($ch),
            'errno' => curl_errno($ch)
        ];
    }
    
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $responseHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    $jsonBody = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try to extract error from non-JSON response
        if (preg_match('/<title>(.*?)<\/title>/i', $body, $matches)) {
            $errorMessage = strip_tags($matches[1]);
        } else {
            $errorMessage = 'Invalid JSON response: ' . json_last_error_msg();
        }
        
        return [
            'status' => $statusCode,
            'headers' => $responseHeaders,
            'body' => [
                'error' => $errorMessage,
                'raw' => $body
            ]
        ];
    }
    
    return [
        'status' => $statusCode,
        'headers' => $responseHeaders,
        'body' => $jsonBody,
        'url' => $url
    ];
}

function runAllTests($baseUrl, $testEmail, $testPassword) {
    $results = [];
    $authToken = null;
    $gameId = null;
    
    // Test 1: Register user
    $registerResponse = makeRequest('/register', 'POST', [
        'email' => $testEmail,
        'password' => $testPassword,
        'newsletter' => false
    ], null, $baseUrl);
    
    // Registration passes if successful (200/201) OR if user already exists (400 with specific error)
    $registrationPassed = in_array($registerResponse['status'], [200, 201]) || 
                         ($registerResponse['status'] === 400 && 
                          strpos($registerResponse['body']['error_message'] ?? '', 'already exists') !== false);
    
    $results[] = [
        'test' => 'User Registration',
        'status' => $registerResponse['status'],
        'passed' => $registrationPassed,
        'response' => $registerResponse
    ];
    
    // Test 2: Login user
    $loginResponse = makeRequest('/login', 'POST', [
        'email' => $testEmail,
        'password' => $testPassword
    ], null, $baseUrl);
    
    $loginPassed = $loginResponse['status'] === 200 && !empty($loginResponse['body']['data']['session_token']);
    // Try to get api_token first, fall back to session_token
    $authToken = $loginResponse['body']['data']['api_token'] ?? $loginResponse['body']['data']['session_token'] ?? null;
    
    $results[] = [
        'test' => 'User Login',
        'status' => $loginResponse['status'],
        'passed' => $loginPassed,
        'response' => $loginResponse,
        'token' => $authToken
    ];
    
    if ($authToken) {
        // Test 3: Create game
        $createGameResponse = makeRequest('/game/create', 'POST', [
            'name' => 'Test Game ' . uniqid(),
            'description' => 'Test game description',
            'max_players' => 4
        ], $authToken, $baseUrl);
        
        $createPassed = in_array($createGameResponse['status'], [200, 201]);
        $gameId = $createGameResponse['body']['data']['game_id'] ?? null;
        
        $results[] = [
            'test' => 'Create Authenticated Game',
            'status' => $createGameResponse['status'],
            'passed' => $createPassed,
            'response' => $createGameResponse,
            'gameId' => $gameId
        ];
        
        // Test 4: List games
        $listGamesResponse = makeRequest('/game/list', 'GET', null, $authToken, $baseUrl);
        
        $listPassed = $listGamesResponse['status'] === 200 && 
                     is_array($listGamesResponse['body']['data']['games'] ?? null);
        
        $results[] = [
            'test' => 'List User Games',
            'status' => $listGamesResponse['status'],
            'passed' => $listPassed,
            'response' => $listGamesResponse,
            'gameCount' => count($listGamesResponse['body']['data']['games'] ?? [])
        ];
    }
    
    // Test 5: Unauthenticated game creation (should fail)
    $unauthCreateResponse = makeRequest('/game/create', 'POST', [
        'name' => 'Unauthorized Game',
        'description' => 'This should fail'
    ], null, $baseUrl);
    
    $results[] = [
        'test' => 'Prevent Unauthenticated Game Creation',
        'status' => $unauthCreateResponse['status'],
        'passed' => $unauthCreateResponse['status'] === 401,
        'response' => $unauthCreateResponse
    ];
    
    // Test 6: Unauthenticated game listing (should fail)
    $unauthListResponse = makeRequest('/game/list', 'GET', null, null, $baseUrl);
    
    $results[] = [
        'test' => 'Prevent Unauthenticated Game Listing',
        'status' => $unauthListResponse['status'],
        'passed' => $unauthListResponse['status'] === 401,
        'response' => $unauthListResponse
    ];
    
    return [
        'success' => true,
        'testEmail' => $testEmail,
        'baseUrl' => $baseUrl,
        'results' => $results,
        'summary' => [
            'total' => count($results),
            'passed' => count(array_filter($results, function($r) { return $r['passed']; })),
            'failed' => count(array_filter($results, function($r) { return !$r['passed']; }))
        ]
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Michitai API - Authentication & Game Ownership Test</title>
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
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .config-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #4f46e5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .results-section {
            margin-top: 30px;
        }
        
        .test-result {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #6b7280;
        }
        
        .test-result.passed {
            border-left-color: #10b981;
            background: #ecfdf5;
        }
        
        .test-result.failed {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .test-name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .test-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pass {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-fail {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .response-details {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }
        
        .summary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .summary h3 {
            margin-bottom: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #4f46e5;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Michitai API Test</h1>
            <p>Authentication & Game Ownership Testing Interface</p>
        </div>
        
        <div class="content">
            <div class="config-section">
                <h3>Test Configuration</h3>
                <form id="testForm">
                    <div class="form-group">
                        <label for="baseUrl">API Base URL:</label>
                        <input type="text" id="baseUrl" value="<?php echo htmlspecialchars($baseUrl); ?>" placeholder="/api">
                    </div>
                    <div class="form-group">
                        <label for="testEmail">Test Email:</label>
                        <input type="email" id="testEmail" value="<?php echo htmlspecialchars($testEmail); ?>" placeholder="test@example.com">
                    </div>
                    <div class="form-group">
                        <label for="testPassword">Test Password:</label>
                        <input type="password" id="testPassword" value="<?php echo htmlspecialchars($testPassword); ?>" placeholder="Test@1234">
                    </div>
                    <button type="submit" class="btn" id="runTestsBtn">🚀 Run All Tests</button>
                </form>
            </div>
            
            <div id="loadingSection" class="loading" style="display: none;">
                <div class="spinner"></div>
                <p>Running authentication and game ownership tests...</p>
            </div>
            
            <div id="resultsSection" class="results-section" style="display: none;">
                <div id="testSummary" class="summary"></div>
                <div id="testResults"></div>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const baseUrl = document.getElementById('baseUrl').value;
            const testEmail = document.getElementById('testEmail').value;
            const testPassword = document.getElementById('testPassword').value;
            
            if (!testEmail || !testPassword) {
                alert('Please enter test email and password');
                return;
            }
            
            // Show loading
            document.getElementById('loadingSection').style.display = 'block';
            document.getElementById('resultsSection').style.display = 'none';
            document.getElementById('runTestsBtn').disabled = true;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'run_tests',
                        baseUrl: baseUrl,
                        testEmail: testEmail,
                        testPassword: testPassword
                    })
                });
                
                const data = await response.json();
                displayResults(data);
                
            } catch (error) {
                console.error('Error running tests:', error);
                alert('Error running tests: ' + error.message);
            } finally {
                document.getElementById('loadingSection').style.display = 'none';
                document.getElementById('runTestsBtn').disabled = false;
            }
        });
        
        function displayResults(data) {
            const resultsSection = document.getElementById('resultsSection');
            const summaryDiv = document.getElementById('testSummary');
            const resultsDiv = document.getElementById('testResults');
            
            // Display summary
            summaryDiv.innerHTML = `
                <h3>Test Results Summary</h3>
                <p><strong>Total Tests:</strong> ${data.summary.total} | 
                   <strong>Passed:</strong> ${data.summary.passed} | 
                   <strong>Failed:</strong> ${data.summary.failed}</p>
                <p><strong>Test Email:</strong> ${data.testEmail}</p>
                <p><strong>API Base URL:</strong> ${data.baseUrl}</p>
            `;
            
            // Display individual test results
            resultsDiv.innerHTML = '';
            data.results.forEach(result => {
                const testDiv = document.createElement('div');
                testDiv.className = `test-result ${result.passed ? 'passed' : 'failed'}`;
                
                testDiv.innerHTML = `
                    <div class="test-header">
                        <div class="test-name">${result.test}</div>
                        <div class="test-status ${result.passed ? 'status-pass' : 'status-fail'}">
                            ${result.passed ? 'PASS' : 'FAIL'} (${result.status})
                        </div>
                    </div>
                    <div class="response-details">
                        <strong>URL:</strong> ${result.response.url || 'N/A'}<br>
                        <strong>Status:</strong> ${result.status}<br>
                        ${result.token ? `<strong>Token:</strong> ${result.token.substring(0, 20)}...<br>` : ''}
                        ${result.gameId ? `<strong>Game ID:</strong> ${result.gameId}<br>` : ''}
                        ${result.gameCount !== undefined ? `<strong>Games Found:</strong> ${result.gameCount}<br>` : ''}
                        <strong>Response:</strong><br>
                        <pre>${JSON.stringify(result.response.body, null, 2)}</pre>
                    </div>
                `;
                
                resultsDiv.appendChild(testDiv);
            });
            
            resultsSection.style.display = 'block';
        }
    </script>
</body>
</html>
<?php

// Run tests
$allTestsPassed = true;
$lastResponse = null;

echo "=== Starting Game Ownership and Authentication Tests ===\n\n";

// Test 1: Register a new test user
$registerResponse = makeRequest("$baseUrl/register", 'POST', [
    'email' => $testEmail,
    'password' => $testPassword,
    'confirm_password' => $testPassword,
    'newsletter' => false
]);



// Test 2: Login with the new user
$loginResponse = makeRequest("$baseUrl/login", 'POST', [
    'email' => $testEmail,
    'password' => $testPassword
]);

$lastResponse = $loginResponse;
$testPassed = $loginResponse['status'] === 200 && !empty($loginResponse['body']['token']);
$allTestsPassed = $allTestsPassed && $testPassed;
printTestResult("User Login", $testPassed, "Status: {$loginResponse['status']}");

// Get the auth token
$authToken = $loginResponse['body']['token'] ?? null;

if (!$authToken) {
    // If login failed, try to get error details
    echo "\n\033[33mLogin failed. Response body:\033[0m\n";
    echo json_encode($loginResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
    exit(1);
}

// Test 3: Create a game while authenticated
$gameName = 'Test Game ' . uniqid();
$createGameResponse = makeRequest("$baseUrl/game/create", 'POST', [
    'name' => $gameName,
    'description' => 'Test game description',
    'max_players' => 4
], $authToken);

$testPassed = $createGameResponse['status'] === 200 && 
               !empty($createGameResponse['body']['data']['game_id']);
$allTestsPassed = $allTestsPassed && $testPassed;
printTestResult("Create Authenticated Game", $testPassed, "Status: {$createGameResponse['status']}");

$gameId = $createGameResponse['body']['data']['game_id'] ?? null;

// Test 4: List games and verify only the user's games are returned
$listGamesResponse = makeRequest("$baseUrl/game/list", 'GET', null, $authToken);
$lastResponse = $listGamesResponse;

$testPassed = $listGamesResponse['status'] === 200 && 
               is_array($listGamesResponse['body']['data']['games'] ?? null) &&
               count($listGamesResponse['body']['data']['games'] ?? []) > 0;

// If we have games, verify the first one belongs to our user
if ($testPassed && !empty($listGamesResponse['body']['data']['games'][0]['user_id'])) {
    $testPassed = $testPassed && ($listGamesResponse['body']['data']['games'][0]['user_id'] == $userId);
}

$allTestsPassed = $allTestsPassed && $testPassed;
printTestResult("List User's Games", $testPassed, "Found " . count($listGamesResponse['body']['data']['games'] ?? []) . " games");

if (!$testPassed) {
    echo "\n\033[33mGame list response:\033[0m\n";
    echo json_encode($listGamesResponse['body'] ?? $listGamesResponse, JSON_PRETTY_PRINT) . "\n\n";
}

// Test 5: Try to create a game without authentication (should fail)
$unauthGameResponse = makeRequest("$baseUrl/game/create", 'POST', [
    'name' => 'Unauthenticated Game',
    'description' => 'This should fail'
]);

$testPassed = $unauthGameResponse['status'] === 401;
$allTestsPassed = $allTestsPassed && $testPassed;
printTestResult("Prevent Unauthenticated Game Creation", $testPassed, "Status: {$unauthGameResponse['status']}");

// Test 6: Try to list games without authentication (should fail)
$unauthListResponse = makeRequest("$baseUrl/game/list", 'GET');

$testPassed = $unauthListResponse['status'] === 401;
$allTestsPassed = $allTestsPassed && $testPassed;
printTestResult("Prevent Unauthenticated Game Listing", $testPassed, "Status: {$unauthListResponse['status']}");

// Test 7: Create a second game and verify both are listed
$secondGameName = 'Second Test Game ' . uniqid();
$secondGameResponse = makeRequest("$baseUrl/game/create", 'POST', [
    'name' => $secondGameName,
    'description' => 'Second test game',
    'max_players' => 2
], $authToken);

$lastResponse = $secondGameResponse;
$secondGameCreated = in_array($secondGameResponse['status'], [200, 201]) && 
                   !empty($secondGameResponse['body']['data']['game_id']);

if ($secondGameCreated) {
    $listAfterSecondGame = makeRequest("$baseUrl/game/list", 'GET', null, $authToken);
    $lastResponse = $listAfterSecondGame;
    
    $testPassed = $listAfterSecondGame['status'] === 200 && 
                  count($listAfterSecondGame['body']['data']['games'] ?? []) >= 2;
    $allTestsPassed = $allTestsPassed && $testPassed;
    printTestResult("Multiple Games Per User", $testPassed, "Found " . count($listAfterSecondGame['body']['data']['games'] ?? []) . " games");
    
    if (!$testPassed) {
        echo "\n\033[33mExpected at least 2 games but found " . count($listAfterSecondGame['body']['data']['games'] ?? []) . ". Response body:\033[0m\n";
        echo json_encode($listAfterSecondGame['body'] ?? $listAfterSecondGame, JSON_PRETTY_PRINT) . "\n\n";
    }
} else {
    $testPassed = false;
    $allTestsPassed = false;
    printTestResult("Multiple Games Per User", false, "Failed to create second game");
    echo "\n\033[33mSecond game creation failed. Response body:\033[0m\n";
    echo json_encode($secondGameResponse['body'] ?? $secondGameResponse, JSON_PRETTY_PRINT) . "\n\n";
}

// Clean up: Delete test games (if they were created)
if (!empty($gameId)) {
    $deleteResponse = makeRequest("$baseUrl/game/delete/$gameId", 'DELETE', null, $authToken);
    if ($deleteResponse['status'] !== 200) {
        echo "\n\033[33mWarning: Failed to clean up test game $gameId\033[0m\n";
    } else {
        echo "\n✅ Cleaned up test game $gameId\n";
    }
}

if (!empty($secondGameResponse['body']['data']['game_id'])) {
    $secondGameId = $secondGameResponse['body']['data']['game_id'];
    $deleteResponse = makeRequest("$baseUrl/game/delete/$secondGameId", 'DELETE', null, $authToken);
    if ($deleteResponse['status'] !== 200) {
        echo "\n\033[33mWarning: Failed to clean up second test game $secondGameId\033[0m\n";
    } else {
        echo "✅ Cleaned up second test game $secondGameId\n";
    }
}

// Clean up: Delete test user (you'll need to implement this endpoint)
// makeRequest("$baseUrl/auth/delete-account", 'DELETE', null, $authToken);

echo "\n=== Test Summary ===\n";
echo $allTestsPassed ? "✅ ALL TESTS PASSED" : "❌ SOME TESTS FAILED";
echo "\n\n";

// Exit with appropriate status code
exit($allTestsPassed ? 0 : 1);
