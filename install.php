<?php
// ===== UMMA Voting System - Database Installer =====

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'umma_voting';

echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>UMMA Voting System - Install</title>";
echo "<link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>";
echo "<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
    .card { background: #fff; border-radius: 16px; padding: 40px; max-width: 600px; width: 100%; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
    h1 { font-size: 1.6rem; color: #0A2A66; margin-bottom: 8px; }
    p { color: #6c757d; margin-bottom: 25px; font-size: 0.95rem; }
    .step { padding: 12px 16px; border-radius: 8px; margin-bottom: 10px; font-size: 0.9rem; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .btn { display: inline-block; padding: 14px 32px; background: #198754; color: #fff; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; margin-top: 20px; }
    .btn:hover { background: #146c43; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
    .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
</style></head><body><div class='card'>";
echo "<h1>UMMA Voting System</h1>";
echo "<p>Database Installation Wizard</p>";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "<div class='step success'> Connected to MySQL server successfully.</div>";

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='step success'> Database '<strong>$dbname</strong>' created or already exists.</div>";

    $pdo->exec("USE `$dbname`");

    $sql = file_get_contents(__DIR__ . '/database.sql');
    $statements = explode(';', $sql);
    $count = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
            $count++;
        }
    }

    echo "<div class='step success'> All database tables created successfully. ($count statements executed)</div>";
    echo "<div class='step info'> Default admin account: <strong>admin@gmail.com</strong> / <strong>123456</strong></div>";
    echo "<div class='step info'> Empty data tables are ready for users, candidates, elections, votes, and messages.</div>";
    echo "<a href='login.html' class='btn'><i class='fas fa-arrow-right'></i> Go to Login</a> ";
    echo "<a href='admin.html' class='btn btn-danger'> Admin Panel</a>";

} catch (PDOException $e) {
    echo "<div class='step error'> Connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='step warning'> Please make sure MySQL is running and try again.</div>";
    echo "<button onclick='location.reload()' class='btn' style='background:#0A2A66;'> Retry Installation</button>";
}

echo "</div></body></html>";
