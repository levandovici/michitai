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

// Check if key ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'API key ID is required']);
    exit;
}

$key_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // First, verify that the key exists and belongs to the current user
    $stmt = $pdo->prepare("SELECT id, user_id, api_key, project_name FROM api_keys WHERE id = :key_id FOR UPDATE");
    $stmt->execute(['key_id' => $key_id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$key) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'API key not found']);
        exit;
    }
    
    if ($key['user_id'] != $user_id) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Delete the key
    $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = :key_id AND user_id = :user_id");
    $stmt->execute(['key_id' => $key_id, 'user_id' => $user_id]);
    
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'API key not found or already deleted']);
        exit;
    }
    
    // Commit the transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'API key deleted successfully',
        'deleted_key' => [
            'id' => $key_id,
            'project_name' => $key['project_name']
        ]
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
    exit;
}
?>
