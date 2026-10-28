<?php
/**
 * Logout Action
 * یدک کی پی - خروج از حساب و ابطال توکن نشست
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlash('info', 'با موفقیت از سیستم خارج شدید.');
header('Location: login.php');
exit;
