<?php
header('Content-Type: application/json');
require_once 'config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get and validate input
$project_name = trim($_POST['project_name'] ?? '');

if (empty($project_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Project name is required']);
    exit;
}

try {
    // Generate a new API key
    $api_key = bin2hex(random_bytes(32)); // More secure than UUID for API keys
    $created_at = date('Y-m-d H:i:s');
    
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO api_keys (user_id, project_name, api_key, created_at) VALUES (:user_id, :project_name, :api_key, :created_at)");
    $result = $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'project_name' => $project_name,
        'api_key' => $api_key,
        'created_at' => $created_at
    ]);
    
    if ($result) {
        // Return success with the new key info
        echo json_encode([
            'success' => true,
            'api_key' => $api_key,
            'project_name' => $project_name,
            'created_at' => $created_at
        ]);
    } else {
        throw new PDOException('Failed to create API key');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
    exit;
}
?>