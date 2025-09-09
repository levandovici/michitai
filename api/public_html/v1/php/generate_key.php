<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html?error=Please log in');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = trim($_POST['project_name']);

    if (empty($project_name)) {
        header('Location: ../cabinet.html?error=Project name is required');
        exit;
    }

    try {
        $api_key = generate_uuid();
        $stmt = $pdo->prepare("INSERT INTO api_keys (user_id, project_name, api_key) VALUES (:user_id, :project_name, :api_key)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'project_name' => $project_name,
            'api_key' => $api_key
        ]);
        header('Location: ../cabinet.html?success=API key created');
        exit;
    } catch (PDOException $e) {
        header('Location: ../cabinet.html?error=Database error: ' . urlencode($e->getMessage()));
        exit;
    }
}
?>