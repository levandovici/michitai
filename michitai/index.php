<?php
// Set PHP timezone to Europe/Bucharest (EEST)
date_default_timezone_set('Europe/Bucharest');
try {
    // Load environment variables (if .env exists)
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
    // Database connection
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
    // Set session settings
    $pdo->exec("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
    $pdo->exec("SET time_zone = '+03:00'"); // EEST
    // Create table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL COLLATE utf8mb4_unicode_ci,
            visit_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    // Record visit
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO page_visits (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
} catch (Exception $e) {
    error_log('Error in index.php: ' . $e->getMessage());
    echo '<p class="text-red-600 text-center font-medium">An error occurred while fetching statistics.</p>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nichita Levandovici</title>
  <link rel="icon" href="logo.png" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .light-bg { background: linear-gradient(to bottom, #f3f4f6, #e5e7eb); }
    .dark-bg  { background: #111827; color: #e5e7eb; }

    .header-gradient {
      background: linear-gradient(135deg, #1e40af, #3b82f6);
      animation: gradientShift 8s ease infinite;
      background-size: 200% 200%;
    }
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .dark-header {
      background: #1f2937 !important;
      animation: none !important;
    }

    .project-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .project-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
    }
    .project-logo {
      width: 180px;
      height: 180px;
      object-fit: contain;
      border-radius: 12px;
      border: 2px solid #e5e7eb;
      transition: border-color 0.3s ease;
    }
    .project-card:hover .project-logo {
      border-color: #60a5fa;
    }
    .section-title {
      font-size: 2.5rem;
      font-weight: 700;
      text-align: center;
      margin: 3rem 0 2rem;
      color: #1e40af;
    }
    .dark .section-title { color: #60a5fa; }

    .fade-in { animation: fadeIn 1s ease-out; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    #extra-sections { display: none; }

    .dark #extra-sections { display: block; }

    .stream-container {
      position: relative;
      width: 100%;
      padding-bottom: 56.25%;
      height: 0;
      overflow: hidden;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
      background: #000;
    }
    .stream-container iframe {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
    }

    footer { cursor: pointer; user-select: none; }
    footer:hover p { text-decoration: underline; }
  </style>
</head>
<body class="min-h-screen flex flex-col light-bg" id="theme-body">

  <header class="w-full text-white text-center py-12 header-gradient" id="theme-header">
    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight fade-in">Nichita Levandovici</h1>
    <p class="mt-4 text-lg sm:text-xl text-blue-100 fade-in">Explore my portfolio of innovative and creative works</p>
  </header>

  <main class="flex-grow max-w-7xl mx-auto px-6 py-8">

    <!-- Main Projects Section – always visible -->
    <h2 class="section-title fade-in">Main Projects</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">

      <!-- Unity Lesson -->
      <div class="bg-white rounded-xl shadow-md p-8 text-center project-card fade-in">
        <h3 class="text-2xl sm:text-3xl font-semibold text-blue-700 mb-4">Unity Lesson</h3>
        <img src="logo.png" alt="Logo" class="project-logo mx-auto">
        <p class="mt-4 text-gray-600">Online Unity courses</p>
        <a href="https://unity.michitai.com" target="_blank" class="mt-6 inline-block bg-blue-600 text-white py-3 px-8 rounded-lg hover:bg-blue-700 transition font-medium">Visit Unity Lesson</a>
      </div>

      <!-- Games -->
      <div class="bg-white rounded-xl shadow-md p-8 text-center project-card fade-in">
        <h3 class="text-2xl sm:text-3xl font-semibold text-blue-700 mb-4">Games</h3>
        <img src="warland.png" alt="Logo" class="project-logo mx-auto">
        <p class="mt-4 text-gray-600">Exciting indie games</p>
        <a href="https://games.michitai.com" target="_blank" class="mt-6 inline-block bg-blue-600 text-white py-3 px-8 rounded-lg hover:bg-blue-700 transition font-medium">Visit Games</a>
      </div>

      <!-- API -->
      <div class="bg-white rounded-xl shadow-md p-8 text-center project-card fade-in">
        <h3 class="text-2xl sm:text-3xl font-semibold text-blue-700 mb-4">API</h3>
        <img src="levandovici_logo.png" alt="Logo" class="project-logo mx-auto">
        <p class="mt-4 text-gray-600">Multiplayer API</p>
        <a href="https://api.michitai.com/v1" target="_blank" class="mt-6 inline-block bg-blue-600 text-white py-3 px-8 rounded-lg hover:bg-blue-700 transition font-medium">Visit API</a>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────── -->
    <!-- Everything below is hidden by default (dark mode) -->
    <div id="extra-sections">

      <!-- Live Stream Section -->
      <div id="live-stream-wrapper" class="live-stream">
        <h2 class="section-title fade-in">📺 Live Stream</h2>
        <div class="stream-container mx-auto max-w-5xl px-4">
          <iframe
            src="https://player.twitch.tv/?channel=micfitai&parent=michitai.com"
            allowfullscreen
            title="Live Stream"
            allow="autoplay; fullscreen; picture-in-picture"
            scrolling="no"
            frameborder="0">
          </iframe>
        </div>
      </div>

    </div>

    </div>
    <!-- end of extra-sections -->

  </main>

  <!-- Footer – clickable toggle -->
  <footer class="w-full bg-gray-900 text-white text-center py-10" id="secret-toggle">
    <p class="text-sm font-medium" id="footer-text">© 2026 Nichita Levandovici. All rights reserved.</p>
  </footer>

  <!-- JavaScript toggle logic -->
  <script>
    let isDarkMode = false;
    const body = document.getElementById('theme-body');
    const header = document.getElementById('theme-header');
    const extra = document.getElementById('extra-sections');
    const footerText = document.getElementById('footer-text');
    const toggle = document.getElementById('secret-toggle');

    toggle.addEventListener('click', () => {
      isDarkMode = !isDarkMode;

      if (isDarkMode) {
        body.classList.remove('light-bg');
        body.classList.add('dark-bg', 'dark');
        header.classList.remove('header-gradient');
        header.classList.add('dark-header');
        footerText.textContent = "© 2026 Nichita Levandovici. All rights reserved.";
      } else {
        body.classList.remove('dark-bg', 'dark');
        body.classList.add('light-bg');
        header.classList.remove('dark-header');
        header.classList.add('header-gradient');
        footerText.textContent = "© 2026 Nichita Levandovici. All rights reserved.";
      }
    });

    // Keep twitch live check (unchanged)
    fetch('/twitch-status.php?' + Date.now())
      .then(r => r.text())
      .then(text => {
        if (text.trim() === 'live') {
          document.getElementById('live-stream-wrapper').style.display = 'block';
        }
      })
      .catch(() => {});
  </script>

</body>
</html>