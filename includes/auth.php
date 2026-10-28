<?php
/**
 * Authentication & Session Management
 * یدک کی پی - مدیریت احراز هویت و نشست‌های فعال با ماندگاری دقیق ۲ روز
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * بررسی احراز هویت کاربر
 * اگر معتبر نبود یا بیش از ۲ روز گذشته بود، اوتومات لاگ‌اوت می‌کند
 */
function checkAuth() {
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? $_SESSION['auth_token'] ?? null;
    if (!$token) {
        return false;
    }

    $db = getDB();
    $now = date('Y-m-d H:i:s');

    // پاکسازی توکن‌های منقضی شده عمومی
    try {
        $cleanStmt = $db->prepare("DELETE FROM active_sessions WHERE expires_at <= ?");
        $cleanStmt->execute([$now]);
    } catch (Exception $e) {}

    // جستجوی نشست فعال
    $stmt = $db->prepare("SELECT * FROM active_sessions WHERE session_token = ? AND expires_at > ?");
    $stmt->execute([$token, $now]);
    $session = $stmt->fetch();

    if (!$session) {
        // نشست منقضی شده یا نامعتبر است -> خروج خودکار
        terminateCurrentAuth();
        return false;
    }

    // به‌روزرسانی زمان آخرین فعالیت
    $upStmt = $db->prepare("UPDATE active_sessions SET last_activity = ? WHERE id = ?");
    $upStmt->execute([$now, $session['id']]);

    $_SESSION['auth_user'] = $session['username'];
    $_SESSION['auth_token'] = $session['session_token'];
    $_SESSION['auth_session_id'] = $session['id'];

    return $session;
}

/**
 * الزام ورود به سیستم برای صفحات محافظت‌شده
 */
function requireAuth() {
    $session = checkAuth();
    if (!$session) {
        setFlash('warning', 'اعتبار نشست کاری شما به پایان رسیده است یا وارد نشده‌اید. لطفاً ورود فرمایید.');
        header('Location: login.php');
        exit;
    }
    return $session;
}

/**
 * عملیات ورود با بررسی نام کاربری و رمز عبور (پشتیبانی از تنظیمات تغییر یافته توسط کاربر)
 */
function loginUser($username, $password) {
    $validUsername = getSetting('admin_username', ADMIN_USERNAME);
    $validPassword = getSetting('admin_password', ADMIN_PASSWORD);

    if ($username !== $validUsername || $password !== $validPassword) {
        return false;
    }

    $db = getDB();
    $token = bin2hex(random_bytes(32));
    $ip = getClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceName = parseUserAgent($userAgent);

    $expiresAt = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY_SECONDS);

    $sql = "INSERT INTO active_sessions (session_token, username, ip_address, user_agent, device_name, expires_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$token, $username, $ip, $userAgent, $deviceName, $expiresAt]);

    // ذخیره در کوکی مرورگر با اعتبار ۲ روز
    if (!headers_sent()) {
        setcookie(SESSION_COOKIE_NAME, $token, [
            'expires' => time() + TOKEN_EXPIRY_SECONDS,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    $_SESSION['auth_user'] = $username;
    $_SESSION['auth_token'] = $token;

    return true;
}

/**
 * خروج کاربر و ابطال توکن فعلی
 */
function logoutUser() {
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? $_SESSION['auth_token'] ?? null;
    if ($token) {
        try {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM active_sessions WHERE session_token = ?");
            $stmt->execute([$token]);
        } catch (Exception $e) {}
    }
    terminateCurrentAuth();
}

/**
 * ابطال کوکی و سشن
 */
function terminateCurrentAuth() {
    if (isset($_COOKIE[SESSION_COOKIE_NAME])) {
        if (!headers_sent()) {
            setcookie(SESSION_COOKIE_NAME, '', [
                'expires' => time() - 3600,
                'path' => '/',
            ]);
        }
        unset($_COOKIE[SESSION_COOKIE_NAME]);
    }
    unset($_SESSION['auth_user'], $_SESSION['auth_token'], $_SESSION['auth_session_id']);
}

/**
 * دریافت لیست تمامی دستگاه‌های فعال واقعی متصل به سیستم
 */
function getActiveSessions() {
    $db = getDB();
    $now = date('Y-m-d H:i:s');
    
    // پاکسازی نشست‌های منقضی شده
    try {
        $stmtClean = $db->prepare("DELETE FROM active_sessions WHERE expires_at <= ?");
        $stmtClean->execute([$now]);
    } catch (Exception $e) {}

    $stmt = $db->prepare("SELECT * FROM active_sessions WHERE expires_at > ? ORDER BY last_activity DESC");
    $stmt->execute([$now]);
    return $stmt->fetchAll();
}

/**
 * خاتمه دادن به یک نشست مشخص
 */
function terminateSessionById($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM active_sessions WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * خاتمه دادن به تمامی نشست‌ها به غیر از نشست فعلی
 */
function terminateOtherSessions() {
    $currentToken = $_COOKIE[SESSION_COOKIE_NAME] ?? $_SESSION['auth_token'] ?? '';
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM active_sessions WHERE session_token != ?");
    return $stmt->execute([$currentToken]);
}
