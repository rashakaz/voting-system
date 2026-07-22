<?php
// ===== UMMA Voting System - Database Configuration =====

define('DB_HOST', 'localhost');
define('DB_NAME', 'umma_voting');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function tableExists($pdo, $table) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function indexExists($pdo, $table, $indexName) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
    ");
    $stmt->execute([$table, $indexName]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureColumn($pdo, $table, $column, $definition) {
    if (!columnExists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensureIndex($pdo, $table, $indexName, $definition) {
    if (!indexExists($pdo, $table, $indexName)) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD $definition");
        } catch (PDOException $e) {
            error_log("Schema index repair skipped for $table.$indexName: " . $e->getMessage());
        }
    }
}

function ensureDefaultAdmin($pdo) {
    $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    if (!$admin) {
        $pdo->prepare("INSERT INTO admin_users (username, password, email, full_name) VALUES (?, ?, ?, ?)")
            ->execute(['admin', password_hash('123456', PASSWORD_BCRYPT), 'admin@gmail.com', 'Super Admin']);
    } elseif (empty($admin['password'])) {
        $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?")
            ->execute([password_hash('123456', PASSWORD_BCRYPT), $admin['id']]);
    }
}

function backfillStudentIds($pdo) {
    if (!columnExists($pdo, 'users', 'student_id')) {
        return;
    }

    $stmt = $pdo->query("SELECT id FROM users WHERE student_id IS NULL OR student_id = ''");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
        $studentId = 'UMMA-' . date('Y') . '-' . str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT);
        $update = $pdo->prepare("UPDATE users SET student_id = ? WHERE id = ?");
        $update->execute([$studentId, $user['id']]);
    }
}

function ensureDatabaseSchema($pdo) {
    try {
        if (!tableExists($pdo, 'admin_users')) {
            $pdo->exec(<<<'SQL'
                CREATE TABLE admin_users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    full_name VARCHAR(150) NOT NULL DEFAULT 'Super Admin',
                    last_login_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_admin_username (username),
                    UNIQUE KEY uq_admin_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                SQL);

            $pdo->prepare("INSERT INTO admin_users (username, password, email, full_name) VALUES (?, ?, ?, ?)")
                ->execute(['admin', password_hash('123456', PASSWORD_BCRYPT), 'admin@gmail.com', 'Super Admin']);
        } else {
            ensureColumn($pdo, 'admin_users', 'username', "VARCHAR(50) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'admin_users', 'password', "VARCHAR(255) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'admin_users', 'email', "VARCHAR(100) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'admin_users', 'full_name', "VARCHAR(150) NOT NULL DEFAULT 'Super Admin'");
            ensureColumn($pdo, 'admin_users', 'last_login_at', "DATETIME DEFAULT NULL");
            ensureColumn($pdo, 'admin_users', 'created_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            ensureColumn($pdo, 'admin_users', 'updated_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            ensureDefaultAdmin($pdo);
        }

        if (!tableExists($pdo, 'users')) {
            $pdo->exec(<<<'SQL'
                CREATE TABLE users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    full_name VARCHAR(150) NOT NULL,
                    national_id VARCHAR(50) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    username VARCHAR(50) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    student_id VARCHAR(50) NOT NULL,
                    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                    voted TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_users_national_id (national_id),
                    UNIQUE KEY uq_users_email (email),
                    UNIQUE KEY uq_users_username (username),
                    UNIQUE KEY uq_users_student_id (student_id),
                    KEY idx_users_status (status),
                    KEY idx_users_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                SQL);
        } else {
            ensureColumn($pdo, 'users', 'full_name', "VARCHAR(150) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'national_id', "VARCHAR(50) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'phone', "VARCHAR(20) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'email', "VARCHAR(100) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'username', "VARCHAR(50) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'password', "VARCHAR(255) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'student_id', "VARCHAR(50) NOT NULL DEFAULT ''");
            ensureColumn($pdo, 'users', 'status', "ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
            ensureColumn($pdo, 'users', 'voted', "TINYINT(1) NOT NULL DEFAULT 0");
            ensureColumn($pdo, 'users', 'created_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            ensureColumn($pdo, 'users', 'updated_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            backfillStudentIds($pdo);
        }

        ensureIndex($pdo, 'admin_users', 'uq_admin_username', 'UNIQUE KEY uq_admin_username (username)');
        ensureIndex($pdo, 'admin_users', 'uq_admin_email', 'UNIQUE KEY uq_admin_email (email)');
        ensureIndex($pdo, 'users', 'uq_users_national_id', 'UNIQUE KEY uq_users_national_id (national_id)');
        ensureIndex($pdo, 'users', 'uq_users_email', 'UNIQUE KEY uq_users_email (email)');
        ensureIndex($pdo, 'users', 'uq_users_username', 'UNIQUE KEY uq_users_username (username)');
        ensureIndex($pdo, 'users', 'uq_users_student_id', 'UNIQUE KEY uq_users_student_id (student_id)');
        ensureIndex($pdo, 'users', 'idx_users_status', 'KEY idx_users_status (status)');
        ensureIndex($pdo, 'users', 'idx_users_created_at', 'KEY idx_users_created_at (created_at)');
    } catch (PDOException $e) {
        error_log('Schema initialization failed: ' . $e->getMessage());
    }
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");
            ensureDatabaseSchema($pdo);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
        }
    }
    return $pdo;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data);
    exit;
}

function getJsonInput() {
    if (!empty($_POST)) {
        return $_POST;
    }

    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }

    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    parse_str($input, $formData);
    return is_array($formData) ? $formData : [];
}

function verifyAdmin() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        jsonResponse(['error' => 'Invalid token'], 401);
    }

    [$encodedPayload, $signature] = $parts;
    $expectedSignature = hash_hmac('sha256', $encodedPayload, 'umma-voting-secret-key-2026');
    if (!hash_equals($expectedSignature, $signature)) {
        jsonResponse(['error' => 'Invalid token signature'], 401);
    }

    $payload = json_decode(base64_decode($encodedPayload), true);
    if (!$payload || !isset($payload['admin_id']) || ($payload['type'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Invalid token'], 401);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, full_name FROM admin_users WHERE id = ?");
    $stmt->execute([$payload['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        jsonResponse(['error' => 'Admin not found'], 401);
    }

    return $admin;
}

function generateToken($payload) {
    $encoded = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', $encoded, 'umma-voting-secret-key-2026');
    return $encoded . '.' . $signature;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}
