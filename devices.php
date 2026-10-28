<?php
/**
 * Active Devices & Sessions Management
 * یدک کی پی - مدیریت دستگاه‌ها و نشست‌های متصل فعال
 */

$pageTitle = 'دستگاه‌های فعال و نشست‌ها';
require_once __DIR__ . '/includes/auth.php';
$currentSession = requireAuth();

$db = getDB();
$currentToken = $_COOKIE[SESSION_COOKIE_NAME] ?? $_SESSION['auth_token'] ?? '';

// پاکسازی خودکار نشست‌های منقضی شده یا اسکریپت‌های تست فاقد مرورگر واقعی
try {
    $now = date('Y-m-d H:i:s');
    $stmtClean = $db->prepare("DELETE FROM active_sessions WHERE expires_at <= ? OR user_agent = '' OR user_agent LIKE '%PowerShell%'");
    $stmtClean->execute([$now]);
} catch (Exception $e) {}

// عملیات خروج دستگاه مشخص
if (isset($_GET['terminate'])) {
    $terminateId = (int)$_GET['terminate'];
    if ($terminateId > 0) {
        if ($terminateId === (int)$currentSession['id']) {
            terminateSessionById($terminateId);
            logoutUser();
            setFlash('info', 'نشست این دستگاه با موفقیت خاتمه یافت.');
            header('Location: login.php');
            exit;
        } else {
            terminateSessionById($terminateId);
            setFlash('success', 'نشست دستگاه مورد نظر با موفقیت لغو و اتصال آن قطع گردید.');
            header('Location: devices.php');
            exit;
        }
    }
}

// خروج از تمامی دستگاه‌های دیگر
if (isset($_POST['terminate_others'])) {
    terminateOtherSessions();
    setFlash('success', 'تمام نشست‌های فعال روی سایر دستگاه‌ها با موفقیت خاتمه یافتند.');
    header('Location: devices.php');
    exit;
}

$sessions = getActiveSessions();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">
            <i class="bi bi-laptop text-primary me-2"></i>دستگاه‌ها و نشست‌های فعال واقعی
        </h3>
        <p class="text-muted small mb-0">
            فقط دستگاه‌ها و مرورگرهای واقعی متصل به سیستم با اعتبار ۲ روزه نمایش داده می‌شوند.
        </p>
    </div>
    <div>
        <?php if (count($sessions) > 1): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmTerminateOtherSessions()">
                <i class="bi bi-shield-x me-1"></i> خروج از تمامی دستگاه‌های دیگر
            </button>
            <form id="terminateOthersForm" method="POST" action="devices.php" class="d-none">
                <input type="hidden" name="terminate_others" value="1">
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($sessions as $session): 
        $isCurrent = ($session['session_token'] === $currentToken);
    ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm border <?= $isCurrent ? 'border-success bg-light' : 'border-light' ?>">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-3 <?= $isCurrent ? 'bg-success text-white' : 'bg-primary text-white' ?>" style="font-size: 1.4rem;">
                                <i class="bi bi-<?= strpos(strtolower($session['device_name']), 'آیفون') !== false || strpos(strtolower($session['device_name']), 'اندروید') !== false ? 'phone' : 'laptop' ?>"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($session['device_name'] ?: 'دستگاه نامشخص') ?></h6>
                                <span class="badge bg-secondary-subtle text-secondary small">کاربر: <?= htmlspecialchars($session['username']) ?></span>
                            </div>
                        </div>
                        <?php if ($isCurrent): ?>
                            <span class="badge bg-success text-white px-2 py-1">
                                <i class="bi bi-check-circle-fill me-1"></i> این دستگاه
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-2 py-1">
                                <i class="bi bi-broadcast me-1"></i> فعال
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="text-muted small space-y-2 mb-4 flex-grow-1">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="bi bi-geo-alt me-1"></i> آدرس IP:</span>
                            <span class="fw-semibold text-dark font-monospace"><?= htmlspecialchars($session['ip_address']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="bi bi-box-arrow-in-right me-1"></i> زمان ورود:</span>
                            <span class="text-dark"><?= toShamsiDate($session['login_time']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span><i class="bi bi-activity me-1"></i> آخرین فعالیت:</span>
                            <span class="text-primary fw-medium"><?= timeElapsedString($session['last_activity']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span><i class="bi bi-hourglass-split me-1"></i> انقضای خودکار:</span>
                            <span class="text-danger fw-semibold"><?= toShamsiDate($session['expires_at']) ?></span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <?php if ($isCurrent): ?>
                            <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-box-arrow-right me-1"></i> خروج از این حساب
                            </a>
                        <?php else: ?>
                            <button type="button" onclick="confirmTerminateSession(<?= $session['id'] ?>, '<?= htmlspecialchars(addslashes($session['device_name'])) ?>')" class="btn btn-danger btn-sm w-100">
                                <i class="bi bi-x-circle me-1"></i> قطع اتصال این دستگاه
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
