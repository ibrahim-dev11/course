<?php
// ==========================================
// تنظیمات دیتابەیس
// ==========================================

define('DB_HOST', 'sql301.infinityfree.com');
define('DB_USER', 'if0_41903352');
define('DB_PASS', '9zxCb59k8D');
define('DB_NAME', 'if0_41903352_course');
define('DB_PORT', 3306);

// تنظیمات سایت
define('SITE_NAME', 'ڕاپرسی کۆرس');
define('SITE_URL', 'http://localhost/course');
define('VOTE_BY_IP', true); // یەک دەنگ لە هەر ئای پی

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'کێشەی پەیوەندی بە دیتابەیس: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function getUserIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : '0.0.0.0';
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
