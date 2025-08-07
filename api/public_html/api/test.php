<?php
/**
 * Simple diagnostic test for API directory
 */

header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'API directory is accessible',
    'php_version' => PHP_VERSION,
    'timestamp' => date('c'),
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown'
]);
?>
