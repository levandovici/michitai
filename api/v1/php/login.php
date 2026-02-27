<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        header('Location: ../login.html?error=All fields are required');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, email, password_hash, is_verified FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_verified']) {
                header('Location: ../login.html?error=Please verify your email before logging in');
                exit;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header('Location: ../cabinet.html');
            exit;
        } else {
            header('Location: ../login.html?error=Invalid email or password');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: ../login.html?error=Database error: ' . urlencode($e->getMessage()));
        exit;
    }
}
?>