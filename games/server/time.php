<?php
// time.php  – place it in the root of your site (or any folder you like)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');   // allow Unity WebGL

date_default_timezone_set('UTC');

echo json_encode([
    'utc'       => gmdate('c'),               // ISO-8601 with Z (e.g. 2025-11-15T10:32:45Z)
    'timestamp' => time(),                    // Unix seconds
    'readable'  => gmdate('Y-m-d H:i:s') . ' UTC'
], JSON_UNESCAPED_SLASHES);
?>