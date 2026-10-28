<?php
/**
 * Helper Functions
 * یدک کی پی - توابع کمکی سیستم انبارداری
 */

require_once __DIR__ . '/jdf.php';

/**
 * فرمت‌بندی مبالغ به تومان همراه با جداکننده سه رقمی
 */
function formatToman($amount, $showUnit = true, $persianDigits = true) {
    $amount = (int) $amount;
    $formatted = number_format($amount);
    if ($persianDigits) {
        $formatted = tr_num($formatted, 'fa');
    }
    return $showUnit ? $formatted . ' تومان' : $formatted;
}

/**
 * پاکسازی ورودی‌های کاربر برای امنیت
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * دریافت آی‌پی کلاینت
 */
function getClientIp() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    return '127.0.0.1';
}

/**
 * تشخیص نام دستگاه و مرورگر از User-Agent
 */
function parseUserAgent($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    $platform = 'دستگاه نامشخص';
    $browser = 'مرورگر نامشخص';

    // تشخیص سیستم عامل
    if (preg_match('/windows|win32/i', $userAgent)) {
        $platform = 'ویندوز (Windows)';
    } elseif (preg_match('/android/i', $userAgent)) {
        $platform = 'اندروید (Android)';
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $platform = 'آیفون / آیپد (iOS)';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $platform = 'مک (macOS)';
    } elseif (preg_match('/linux/i', $userAgent)) {
        $platform = 'لینوکس (Linux)';
    }

    // تشخیص مرورگر
    if (preg_match('/edg/i', $userAgent)) {
        $browser = 'مایکروسافت اج (Edge)';
    } elseif (preg_match('/chrome|crios/i', $userAgent)) {
        $browser = 'گوگل کروم (Chrome)';
    } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
        $browser = 'فایرفاکس (Firefox)';
    } elseif (preg_match('/safari/i', $userAgent)) {
        $browser = 'سافاری (Safari)';
    } elseif (preg_match('/opera|opr/i', $userAgent)) {
        $browser = 'اوپرا (Opera)';
    }

    return "{$platform} - {$browser}";
}

/**
 * تنظیم پیام فلش (Toast/Alert)
 */
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * دریافت و پاک کردن پیام فلش
 */
function getFlash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * تبدیل تاریخ میلادی دیتابیس به شمسی خوانا
 */
function toShamsiDate($datetimeStr, $withTime = true) {
    if (empty($datetimeStr)) {
        return '-';
    }
    $timestamp = strtotime($datetimeStr);
    if (!$timestamp) {
        return '-';
    }
    $format = $withTime ? 'Y/m/d ساعت H:i' : 'Y/m/d';
    return jdate($format, $timestamp, '', 'Asia/Tehran', 'fa');
}

/**
 * محاسبه زمان گذشته به صورت خوانا (مثلاً چند دقیقه پیش)
 */
function timeElapsedString($datetime) {
    $now = new DateTime('now', new DateTimeZone('Asia/Tehran'));
    $ago = new DateTime($datetime, new DateTimeZone('Asia/Tehran'));
    $diff = $now->diff($ago);

    if ($diff->d > 0) {
        return tr_num($diff->d) . ' روز پیش';
    }
    if ($diff->h > 0) {
        return tr_num($diff->h) . ' ساعت پیش';
    }
    if ($diff->i > 0) {
        return tr_num($diff->i) . ' دقیقه پیش';
    }
    return 'لحظاتی پیش';
}
