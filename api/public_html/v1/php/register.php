<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        header('Location: ../register.html?error=All fields are required');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../register.html?error=Invalid email format');
        exit;
    }

    try {
        // Check for existing user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            header('Location: ../register.html?error=Email already exists');
            exit;
        }

        // Insert user
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (:email, :password_hash)");
        $stmt->execute(['email' => $email, 'password_hash' => $password_hash]);
        $user_id = $pdo->lastInsertId();

        // Generate verification token
        $token = generate_uuid();
        $stmt = $pdo->prepare("INSERT INTO verification_tokens (user_id, token) VALUES (:user_id, :token)");
        $stmt->execute(['user_id' => $user_id, 'token' => $token]);

        // Send confirmation email
        $verify_url = $_ENV['BASE_URL'] . "/php/verify.php?token=$token";
        $subject = "Verify Your Levandovici API Account";
        $message = "Please verify your email by clicking this link: $verify_url";
        $headers = "From: " . $_ENV['EMAIL_FROM'] . "\r\n";
        if (!mail($email, $subject, $message, $headers)) {
            header('Location: ../register.html?error=Failed to send verification email');
            exit;
        }

        header('Location: ../login.html?success=Registration successful. Please check your email to verify your account.');
        exit;
    } catch (PDOException $e) {
        header('Location: ../register.html?error=Database error: ' . urlencode($e->getMessage()));
        exit;
    }
}
?>