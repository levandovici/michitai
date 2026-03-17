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
    // We'll show a friendly message later in HTML if needed
}
?>
<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nichita Levandovici</title>
  <link rel="icon" href="logo.png" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --water-deep: #001f3f;
      --water-mid: #0c4a6e;
      --cyan: #22d3ee;
      --teal: #06b6d4;
      --glass-bg: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.12);
    }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      margin: 0;
      color: #e0f2fe;
      background: linear-gradient(to bottom, var(--water-mid), var(--water-deep));
      overflow-x: hidden;
      position: relative;
    }

    /* Subtle water caustics/light play */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: 
        radial-gradient(circle at 20% 30%, rgba(34, 211, 238, 0.07) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(6, 182, 212, 0.05) 0%, transparent 60%);
      pointer-events: none;
      animation: causticDrift 30s ease-in-out infinite;
      z-index: -2;
    }

    @keyframes causticDrift {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50%      { transform: translate(5%, 8%) scale(1.03); }
    }

    header {
      background: linear-gradient(to bottom, rgba(6, 182, 212, 0.35), rgba(2, 132, 199, 0.2));
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--glass-border);
      box-shadow: 0 4px 30px rgba(0,0,0,0.25);
      position: relative;
      overflow: hidden;
    }

    .bubbles-bg {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: -1;
      overflow: hidden;
    }

    .bubble {
      position: absolute;
      background: rgba(255,255,255,0.35);
      border-radius: 50%;
      box-shadow: 0 0 12px rgba(34,211,238,0.6);
      animation: rise linear infinite;
      will-change: transform, opacity;
    }

    @keyframes rise {
      0%   { transform: translateY(120vh); opacity: 0; }
      10%  { opacity: 0.7; }
      90%  { opacity: 0.7; }
      100% { transform: translateY(-20vh); opacity: 0; }
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 700;
      text-align: center;
      margin: 3rem 0 2rem;
      color: var(--cyan);
      text-shadow: 0 2px 12px rgba(34,211,238,0.5);
    }

    .project-card {
      background: var(--glass-bg);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      color: #e0f2fe;
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
    }

    .project-card:hover {
      transform: translateY(-14px) scale(1.04);
      box-shadow: 0 24px 48px rgba(6, 182, 212, 0.3);
      border-color: var(--cyan);
    }

    .project-logo {
      width: 180px;
      height: 180px;
      object-fit: contain;
      border-radius: 16px;
      border: 2px solid rgba(34, 211, 238, 0.5);
      background: rgba(0,0,0,0.25);
      transition: border-color 0.4s ease;
    }

    .project-card:hover .project-logo {
      border-color: var(--cyan);
    }

    .twitch-btn {
      background: #9146ff;
      color: white;
    }

    .twitch-btn:hover {
      background: #772ce8;
    }

    footer {
      background: rgba(0, 31, 63, 0.7);
      backdrop-filter: blur(8px);
      border-top: 1px solid var(--glass-border);
      cursor: pointer;
      user-select: none;
    }

    footer:hover p {
      text-decoration: underline;
    }

    /* Dark mode overrides (deeper ocean feel) */
    .dark body {
      background: linear-gradient(to bottom, #000d1a, #000814);
    }

    .dark header {
      background: linear-gradient(to bottom, rgba(2, 132, 199, 0.25), rgba(6, 182, 212, 0.12));
    }

    .dark .project-card {
      background: rgba(255, 255, 255, 0.05);
      border-color: rgba(34, 211, 238, 0.08);
    }
  </style>
</head>
<body class="flex flex-col" id="theme-body">

  <!-- Floating bubbles -->
  <div class="bubbles-bg" id="bubbles"></div>

  <header class="w-full text-white text-center py-12" id="theme-header">
    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight fade-in">Nichita Levandovici</h1>
    <p class="mt-4 text-lg sm:text-xl text-cyan-100 fade-in">Creative developer • Unity • Games • Multiplayer</p>

    <!-- Twitch Button -->
    <div class="mt-6 flex justify-center fade-in">
      <a href="https://www.twitch.tv/nichitai" 
         target="_blank" rel="noopener noreferrer"
         class="twitch-btn inline-flex items-center gap-2 font-medium py-3 px-8 rounded-xl transition shadow-lg hover:shadow-xl hover:scale-105">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
          <path d="M11.64 5.93H13.07V14.29H11.64M15.57 5.93H17V14.29H15.57M7 2L3.43 5.57v12.86h4V22l3.57-3.57h3L20.57 12V2m-1.43 9.29-3.57 3.57h-3l-2.71 2.71v-2.71H5.57V3.43h13.57Z"/>
        </svg>
        Watch on Twitch
      </a>
    </div>
  </header>

  <main class="flex-grow max-w-7xl mx-auto px-6 py-8">
    <h2 class="section-title fade-in">Main Projects</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
      <!-- Unity Lesson -->
      <div class="project-card p-8 text-center fade-in">
        <h3 class="text-2xl sm:text-3xl font-semibold mb-4">Unity Lesson</h3>
        <img src="logo.png" alt="Unity Lesson" class="project-logo mx-auto mb-4">
        <p class="text-cyan-100 mb-6">Online Unity courses & tutorials</p>
        <a href="https://unity.michitai.com" target="_blank" 
           class="inline-block bg-cyan-600 text-white py-3 px-8 rounded-lg hover:bg-cyan-700 transition font-medium">
          Visit Unity Lesson
        </a>
      </div>

      <!-- Games -->
      <div class="project-card p-8 text-center fade-in">
        <h3 class="text-2xl sm:text-3xl font-semibold mb-4">Games</h3>
        <img src="warland.png" alt="Games" class="project-logo mx-auto mb-4">
        <p class="text-cyan-100 mb-6">Exciting indie game projects</p>
        <a href="https://games.michitai.com" target="_blank" 
           class="inline-block bg-cyan-600 text-white py-3 px-8 rounded-lg hover:bg-cyan-700 transition font-medium">
          Visit Games
        </a>
      </div>

      <!-- API -->
      <div class="project-card p-8 text-center fade-in">
        <h3 class="text-2xl sm:text-3xl font-semibold mb-4">API</h3>
        <img src="levandovici_logo.png" alt="API" class="project-logo mx-auto mb-4">
        <p class="text-cyan-100 mb-6">Multiplayer backend API</p>
        <a href="https://api.michitai.com" target="_blank" 
           class="inline-block bg-cyan-600 text-white py-3 px-8 rounded-lg hover:bg-cyan-700 transition font-medium">
          Visit API
        </a>
      </div>
    </div>

    <!-- Hidden extra sections in light mode, visible in dark -->
    <div id="extra-sections" class="hidden dark:block">
      <!-- You can add more content here later if needed -->
    </div>
  </main>

  <footer class="w-full text-white text-center py-10" id="secret-toggle">
    <p class="text-sm font-medium" id="footer-text">© 2026 Nichita Levandovici. All rights reserved.</p>
  </footer>

  <!-- JavaScript -->
  <script>
    // Bubble generator
    function createBubbles() {
      const bubblesContainer = document.getElementById('bubbles');
      for (let i = 0; i < 18; i++) {
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        const size = Math.random() * 14 + 6;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${Math.random() * 20 + 15}s`;
        bubble.style.animationDelay = `${Math.random() * 15}s`;
        bubblesContainer.appendChild(bubble);
      }
    }
    createBubbles();

    // Dark mode toggle
    let isDarkMode = false;
    const body = document.getElementById('theme-body');
    const htmlRoot = document.getElementById('html-root');
    const header = document.getElementById('theme-header');
    const extra = document.getElementById('extra-sections');
    const footerText = document.getElementById('footer-text');
    const toggle = document.getElementById('secret-toggle');

    toggle.addEventListener('click', () => {
      isDarkMode = !isDarkMode;
      if (isDarkMode) {
        body.classList.add('dark');
        htmlRoot.classList.add('dark');
        header.classList.add('dark-header'); // optional extra class if needed
        extra.classList.remove('hidden');
        footerText.textContent = "© 2026 Nichita Levandovici. All rights reserved.";
      } else {
        body.classList.remove('dark');
        htmlRoot.classList.remove('dark');
        header.classList.remove('dark-header');
        extra.classList.add('hidden');
        footerText.textContent = "© 2026 Nichita Levandovici. All rights reserved.";
      }
    });
  </script>
</body>
</html>