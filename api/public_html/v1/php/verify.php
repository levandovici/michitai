<?php
require_once __DIR__ . '/config.php';

if (!isset($_GET['token'])) {
    header('Location: ../login.html?error=Invalid verification link');
    exit;
}

$token = $_GET['token'];

try {
    $stmt = $pdo->prepare("SELECT user_id FROM verification_tokens WHERE token = :token");
    $stmt->execute(['token' => $token]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        header('Location: ../login.html?error=Invalid or expired verification link');
        exit;
    }

    // Mark user as verified
    $stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE WHERE id = :user_id");
    $stmt->execute(['user_id' => $token_data['user_id']]);

    // Delete token
    $stmt = $pdo->prepare("DELETE FROM verification_tokens WHERE token = :token");
    $stmt->execute(['token' => $token]);

    header('Location: ../login.html?success=Email verified successfully. Please log in.');
    exit;
} catch (PDOException $e) {
    header('Location: ../login.html?error=Database error: ' . urlencode($e->getMessage()));
    exit;
}
?>