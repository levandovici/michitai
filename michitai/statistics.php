<?php
// Set PHP timezone to Europe/Bucharest (EEST)
date_default_timezone_set('Europe/Bucharest');

// Initialize default values
$viewsPerDay = 0;
$viewsPerMonth = 0;
$uniquePerDay = 0;
$uniquePerMonth = 0;
$uniqueLastYear = 0;
$uniquePerYear = 0;
$viewsLastYear = 0;
$viewsPerYear = 0;

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

    $pdo->exec("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
    $pdo->exec("SET time_zone = '+03:00'"); // EEST

    // Create table if not exists (optional here – already in main file)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS page_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL COLLATE utf8mb4_unicode_ci,
            visit_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    // ────────────────────────────────────────────────
    // Fetch stats (same queries as your original file)
    // ────────────────────────────────────────────────
    $today     = date('Y-m-d');
    $thisMonth = date('Y-m');
    $thisYear  = date('Y');
    $lastYear  = $thisYear - 1;

    // Views today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_visits WHERE DATE(visit_timestamp) = ?");
    $stmt->execute([$today]);
    $viewsPerDay = $stmt->fetch()['count'] ?? 0;

    // Views this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_visits WHERE DATE_FORMAT(visit_timestamp, '%Y-%m') = ?");
    $stmt->execute([$thisMonth]);
    $viewsPerMonth = $stmt->fetch()['count'] ?? 0;

    // Unique today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) as count FROM page_visits WHERE DATE(visit_timestamp) = ?");
    $stmt->execute([$today]);
    $uniquePerDay = $stmt->fetch()['count'] ?? 0;

    // Unique this month
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) as count FROM page_visits WHERE DATE_FORMAT(visit_timestamp, '%Y-%m') = ?");
    $stmt->execute([$thisMonth]);
    $uniquePerMonth = $stmt->fetch()['count'] ?? 0;

    // Last year
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) as count FROM page_visits WHERE YEAR(visit_timestamp) = ?");
    $stmt->execute([$lastYear]);
    $uniqueLastYear = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_visits WHERE YEAR(visit_timestamp) = ?");
    $stmt->execute([$lastYear]);
    $viewsLastYear = $stmt->fetch()['count'] ?? 0;

    // This year
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) as count FROM page_visits WHERE YEAR(visit_timestamp) = ?");
    $stmt->execute([$thisYear]);
    $uniquePerYear = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM page_visits WHERE YEAR(visit_timestamp) = ?");
    $stmt->execute([$thisYear]);
    $viewsPerYear = $stmt->fetch()['count'] ?? 0;

} catch (Exception $e) {
    error_log('Stats page error: ' . $e->getMessage());
    $errorMsg = "Database error – statistics unavailable right now.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistics • Nichita Levandovici</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: linear-gradient(to bottom, #f1f5f9, #e2e8f0); min-height: 100vh; }
    .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .number { font-size: 2.5rem; font-weight: 700; color: #1d4ed8; }
  </style>
</head>
<body class="text-gray-800">

  <header class="bg-gradient-to-r from-blue-700 to-blue-500 text-white py-10 text-center">
    <h1 class="text-4xl md:text-5xl font-bold">Statistics Dashboard</h1>
    <p class="mt-3 text-lg opacity-90">Page visits • michitai.com</p>
  </header>

  <main class="max-w-6xl mx-auto px-5 py-10">

    <?php if (isset($errorMsg)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-5 mb-8 rounded">
        <?= htmlspecialchars($errorMsg) ?>
      </div>
    <?php endif; ?>

    <!-- Key numbers -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
      <div class="card p-6 text-center">
        <div class="text-sm text-gray-600 uppercase tracking-wide">Views Today</div>
        <div class="number mt-2"><?= number_format($viewsPerDay) ?></div>
      </div>
      <div class="card p-6 text-center">
        <div class="text-sm text-gray-600 uppercase tracking-wide">Unique Today</div>
        <div class="number mt-2"><?= number_format($uniquePerDay) ?></div>
      </div>
      <div class="card p-6 text-center">
        <div class="text-sm text-gray-600 uppercase tracking-wide">Views This Month</div>
        <div class="number mt-2"><?= number_format($viewsPerMonth) ?></div>
      </div>
      <div class="card p-6 text-center">
        <div class="text-sm text-gray-600 uppercase tracking-wide">Unique This Month</div>
        <div class="number mt-2"><?= number_format($uniquePerMonth) ?></div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

      <!-- Yearly comparison bar chart -->
      <div class="card p-6">
        <h2 class="text-xl font-semibold mb-6 text-center">Year Comparison (Views & Unique)</h2>
        <canvas id="yearChart" height="280"></canvas>
      </div>

      <!-- Period overview bar chart -->
      <div class="card p-6">
        <h2 class="text-xl font-semibold mb-6 text-center">Recent Activity</h2>
        <canvas id="periodChart" height="280"></canvas>
      </div>

    </div>

  </main>

  <footer class="mt-16 py-8 text-center text-gray-600 text-sm border-t">
    © <?= date("Y") ?> Nichita Levandovici. All rights reserved.
  </footer>

  <script>
    // Colors
    const blue = '#3b82f6';
    const indigo = '#6366f1';

    // 1. Yearly comparison
    new Chart(document.getElementById('yearChart'), {
      type: 'bar',
      data: {
        labels: ['Last Year (<?= $thisYear-1 ?>)', 'This Year (<?= $thisYear ?>)'],
        datasets: [
          {
            label: 'Total Views',
            data: [<?= $viewsLastYear ?>, <?= $viewsPerYear ?>],
            backgroundColor: blue,
            borderRadius: 6
          },
          {
            label: 'Unique Visitors',
            data: [<?= $uniqueLastYear ?>, <?= $uniquePerYear ?>],
            backgroundColor: indigo,
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 500 } } }
      }
    });

    // 2. Recent periods (day / month)
    new Chart(document.getElementById('periodChart'), {
      type: 'bar',
      data: {
        labels: ['Today', 'This Month'],
        datasets: [
          {
            label: 'Page Views',
            data: [<?= $viewsPerDay ?>, <?= $viewsPerMonth ?>],
            backgroundColor: blue + 'cc',
            borderColor: blue,
            borderWidth: 2,
            borderRadius: 6
          },
          {
            label: 'Unique Visitors',
            data: [<?= $uniquePerDay ?>, <?= $uniquePerMonth ?>],
            backgroundColor: indigo + 'cc',
            borderColor: indigo,
            borderWidth: 2,
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>

</body>
</html>