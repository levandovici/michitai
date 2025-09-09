<?php
// Debug script to check timestamp calculations
echo "<h2>Timestamp Debug</h2>";

$currentTime = time();
$tokenExpires = $currentTime + (24 * 60 * 60); // 24 hours from now

echo "<p><strong>Current time():</strong> $currentTime</p>";
echo "<p><strong>Current date:</strong> " . date('Y-m-d H:i:s', $currentTime) . "</p>";
echo "<p><strong>Token expires timestamp:</strong> $tokenExpires</p>";
echo "<p><strong>Token expires date:</strong> " . date('Y-m-d H:i:s', $tokenExpires) . "</p>";

// Check your database value
$yourDbValue = 1754719276;
echo "<p><strong>Your DB value:</strong> $yourDbValue</p>";
echo "<p><strong>Your DB date:</strong> " . date('Y-m-d H:i:s', $yourDbValue) . "</p>";

// Check if it's expired
$isExpired = $currentTime > $yourDbValue;
echo "<p><strong>Is expired?</strong> " . ($isExpired ? 'YES' : 'NO') . "</p>";

// Calculate difference
$diff = $yourDbValue - $currentTime;
$hours = round($diff / 3600, 2);
echo "<p><strong>Hours until expiration:</strong> $hours</p>";
?>
