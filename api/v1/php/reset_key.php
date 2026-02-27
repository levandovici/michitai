<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'API key ID is required']);
    exit;
}

$key_id  = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Lock row to avoid race condition
    $stmt = $pdo->prepare("
        SELECT id, user_id, project_name 
        FROM api_keys 
        WHERE id = :key_id 
        FOR UPDATE
    ");
    $stmt->execute(['key_id' => $key_id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'API key not found']);
        exit;
    }

    if ((int)$key['user_id'] !== $user_id) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Generate cryptographically secure private key
    $new_private_key = bin2hex(random_bytes(18));

    // Update key
    $stmt = $pdo->prepare("
        UPDATE api_keys 
        SET api_private_key = :new_key,
            updated_at = NOW()
        WHERE id = :key_id AND user_id = :user_id
    ");

    $stmt->execute([
        'new_key' => $new_private_key,
        'key_id'  => $key_id,
        'user_id' => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reset API key']);
        exit;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'API key regenerated successfully',
        'project_name' => $key['project_name'],
        'new_private_key' => $new_private_key
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'details' => $e->getMessage()
    ]);
}
