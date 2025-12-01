<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nichita Levandovici</title>
  <link rel="icon" href="michitai-logo.jpg" type="image/jpeg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    /* Custom styles for enhanced visuals */
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(to bottom, #f3f4f6, #e5e7eb);
    }
    .header-gradient {
      background: linear-gradient(135deg, #1e40af, #3b82f6);
      animation: gradientShift 5s ease infinite;
      background-size: 200% 200%;
    }
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .project-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
      max-height: 360px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .project-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }
    .project-logo {
      width: 180px;
      height: 180px;
      aspect-ratio: 1/1;
      object-fit: contain;
      border-radius: 12px;
      border: 2px solid #e5e7eb;
      transition: border-color 0.3s ease;
    }
    .project-card:hover .project-logo {
      border-color: #3b82f6;
    }
    .fade-in {
      animation: fadeIn 1s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .metric-icon {
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    @media (max-width: 640px) {
      .project-card {
        max-height: 320px;
      }
      .project-logo {
        width: 140px;
        height: 140px;
      }
      .header-gradient h1 {
        font-size: 1.875rem;
      }
    }
  </style>
</head>
<body>

<?php
// Set PHP timezone to match EEST (October 18, 2025, 09:34 AM EEST)
date_default_timezone_set('Europe/Bucharest');

// Initialize default values
$viewsPerDay = 0;
$viewsPerMonth = 0;
$uniquePerDay = 0;
$uniquePerMonth = 0;

try {
    // Load environment variables
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } else {
        error_log('Composer autoload file not found. Please run "composer install".');
        throw new Exception('Composer dependencies are missing.');
    }

    // Database connection with explicit charset
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'portfolio_db') . ';charset=utf8mb4',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );

    // Set MySQL session collation and timezone
    $pdo->exec("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
    $pdo->exec("SET time_zone = '+03:00'"); // EEST timezone

    // Create table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL COLLATE utf8mb4_unicode_ci,
            visit_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    // Record the visit
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO page_visits (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
    error_log("Visit recorded: IP=$ip, Time=" . date('Y-m-d H:i:s'));

    // Fetch counts
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');

    // Views per day
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_visits WHERE DATE(visit_timestamp) = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    $viewsPerDay = $result['count'] ?? 0;
    error_log("Views Today (DATE=$today): $viewsPerDay");

    // Views per month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM page_visits 
        WHERE DATE_FORMAT(visit_timestamp, '%Y-%m') COLLATE utf8mb4_unicode_ci = ?
    ");
    $stmt->execute([$thisMonth]);
    $result = $stmt->fetch();
    $viewsPerMonth = $result['count'] ?? 0;
    error_log("Views This Month (DATE=$thisMonth): $viewsPerMonth");

    // Unique users per day
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ip_address COLLATE utf8mb4_unicode_ci) as count 
        FROM page_visits 
        WHERE DATE(visit_timestamp) = ?
    ");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    $uniquePerDay = $result['count'] ?? 0;
    error_log("Unique Users Today (DATE=$today): $uniquePerDay");

    // Unique users per month
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ip_address COLLATE utf8mb4_unicode_ci) as count 
        FROM page_visits 
        WHERE DATE_FORMAT(visit_timestamp, '%Y-%m') COLLATE utf8mb4_unicode_ci = ?
    ");
    $stmt->execute([$thisMonth]);
    $result = $stmt->fetch();
    $uniquePerMonth = $result['count'] ?? 0;
    error_log("Unique Users This Month (DATE=$thisMonth): $uniquePerMonth");

    // Debug: Log all visits and collations
    $stmt = $pdo->query("SELECT ip_address, visit_timestamp FROM page_visits ORDER BY visit_timestamp DESC");
    $allVisits = $stmt->fetchAll();
    error_log("All Visits: " . json_encode($allVisits));
    $stmt = $pdo->query("SELECT @@collation_connection, @@character_set_connection");
    $collationInfo = $stmt->fetch();
    error_log("Connection Collation: " . json_encode($collationInfo));

} catch (Exception $e) {
    error_log('Error in index.php: ' . $e->getMessage(), 3, __DIR__ . '/error.log');
    echo '<p class="text-red-600 text-center">An error occurred while fetching statistics. Please try again later.</p>';
}
?>

  <div class="flex flex-col min-h-screen">
    <header class="w-full text-white text-center py-8 header-gradient">
      <h1 class="text-4xl sm:text-5xl font-bold tracking-tight fade-in">Nichita Levandovici</h1>
      <p class="mt-2 text-lg sm:text-xl text-blue-100 fade-in">Explore my portfolio of innovative and creative works</p>
    </header>
    <main class="flex-grow max-w-7xl mx-auto p-6 sm:p-8">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-8">
        <div class="bg-white rounded-xl shadow-md p-6 text-center project-card fade-in">
          <h2 class="text-2xl sm:text-3xl font-semibold text-blue-700">Games</h2>
          <img src="warland.png" alt="Games Logo" class="project-logo mt-4">
          <p class="mt-3 text-base sm:text-lg text-gray-600">Available NOW on Google Play · Coming Soon on Steam!</p>
          <a href="https://games.michitai.com" target="_blank" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-base sm:text-lg font-medium">Visit Games</a>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 text-center project-card fade-in" style="animation-delay: 0.1s;">
          <h2 class="text-2xl sm:text-3xl font-semibold text-blue-700">Road Rules</h2>
          <img src="road-rules-logo.png" alt="Road Rules Logo" class="project-logo mt-4">
          <p class="mt-3 text-base sm:text-lg text-gray-600">Master traffic rules for safe driving</p>
          <a href="https://road.michitai.com" target="_blank" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-base sm:text-lg font-medium">Visit Road Rules</a>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 mt-6 sm:mt-8">
        <div class="bg-white rounded-xl shadow-md p-6 text-center project-card fade-in" style="animation-delay: 0.2s;">
          <h2 class="text-2xl sm:text-3xl font-semibold text-blue-700">Mind Sparky</h2>
          <img src="mind-sparky-logo.png" alt="Mind Sparky Logo" class="project-logo mt-4">
          <p class="mt-3 text-base sm:text-lg text-gray-600">Psychology-driven motivation</p>
          <a href="https://mindsparky.michitai.com" target="_blank" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-base sm:text-lg font-medium">Visit Mind Sparky</a>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 text-center project-card fade-in" style="animation-delay: 0.3s;">
          <h2 class="text-2xl sm:text-3xl font-semibold text-blue-700">Unity Lesson</h2>
          <img src="michitai-logo.jpg" alt="MICHITAI Logo" class="project-logo mt-4">
          <p class="mt-3 text-base sm:text-lg text-gray-600">Online Unity courses</p>
          <a href="https://unity.michitai.com" target="_blank" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-base sm:text-lg font-medium">Visit Unity Lesson</a>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 text-center project-card fade-in" style="animation-delay: 0.4s;">
          <h2 class="text-2xl sm:text-3xl font-semibold text-blue-700">MORGENESTRELA</h2>
          <img src="morgenestrela-logo.png" alt="MORGENESTRELA Logo" class="project-logo mt-4">
          <p class="mt-3 text-base sm:text-lg text-gray-600">A world of music and creativity</p>
          <a href="https://music.michitai.com" target="_blank" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-base sm:text-lg font-medium">Visit MORGENESTRELA</a>
        </div>
      </div>
    </main>
    <footer class="w-full bg-gray-900 text-white text-center py-6">
      <p class="text-sm font-medium">&copy; 2025 Nichita Levandovici. All rights reserved.</p>
      <div class="mt-3 text-sm grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-md mx-auto">
        <div class="metric-icon">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2 12h20"></path></svg>
          Views Today: <?php echo htmlspecialchars($viewsPerDay, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="metric-icon">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          Views This Month: <?php echo htmlspecialchars($viewsPerMonth, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="metric-icon">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          Unique Users Today: <?php echo htmlspecialchars($uniquePerDay, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="metric-icon">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
          Unique Users This Month: <?php echo htmlspecialchars($uniquePerMonth, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>