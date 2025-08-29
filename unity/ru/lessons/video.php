<?php
// Set appropriate headers for video streaming
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');

// Get the video filename from the URL
$videoFile = isset($_GET['v']) ? basename($_GET['v']) : '';

// Validate the filename format (module-X-lesson-X.mp4)
if (!preg_match('/^module-\d+-lesson-\d+\.(mp4|webm|ogg)$/', $videoFile)) {
    http_response_code(400);
    die('Invalid video file');
}

$videoPath = __DIR__ . '/' . $videoFile;

// Check if file exists
if (!file_exists($videoPath)) {
    http_response_code(404);
    die('Video not found');
}

// Get file size for range requests
$fileSize = filesize($videoPath);
$file = fopen($videoPath, 'rb');

// Handle range requests for seeking
if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    $range = str_replace('bytes=', '', $range);
    $range = explode('-', $range);
    
    $start = intval($range[0]);
    $end = ($range[1] === '') ? $fileSize - 1 : intval($range[1]);
    $length = $end - $start + 1;
    
    fseek($file, $start);
    header('HTTP/1.1 206 Partial Content');
    header("Content-Length: $length");
    header("Content-Range: bytes $start-$end/$fileSize");
} else {
    header('Content-Length: ' . $fileSize);
}

// Output the file in chunks
$buffer = 1024 * 8;
while (!feof($file) && ($p = ftell($file)) <= $end) {
    if ($p + $buffer > $end) {
        $buffer = $end - $p + 1;
    }
    echo fread($file, $buffer);
    flush();
}

fclose($file);
?>
