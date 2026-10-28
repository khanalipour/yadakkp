<?php
/**
 * Settings & Profile Management
 * یدک کی پی - تغییر نام کاربری و کلمه عبور مدیر سیستم
 */

$pageTitle = 'تنظیمات حساب کاربری و امنیت';
require_once __DIR__ . '/includes/auth.php';
$session = requireAuth();

$db = getDB();
$currentUsername = getSetting('admin_username', ADMIN_USERNAME);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $actualPassword = getSetting('admin_password', ADMIN_PASSWORD);

        if (empty($newUsername)) {
            setFlash('danger', 'نام کاربری نمی‌تواند خالی باشد.');
        } elseif ($currentPassword !== $actualPassword) {
            setFlash('danger', 'کلمه عبور فعلی وارد شده نادرست است.');
        } else {
            // به‌روزرسانی نام کاربری
            setSetting('admin_username', $newUsername);
            $_SESSION['auth_user'] = $newUsername;

            // به‌روزرسانی رمز در صورت وارد شدن رمز جدید
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 4) {
                    setFlash('warning', 'کلمه عبور جدید باید حداقل ۴ کاراکتر باشد.');
                } elseif ($newPassword !== $confirmPassword) {
                    setFlash('danger', 'کلمه عبور جدید با تکرار آن مطابقت ندارد.');
                } else {
                    setSetting('admin_password', $newPassword);
                    setFlash('success', 'نام کاربری و کلمه عبور جدید با موفقیت ذخیره شدند.');
                    header('Location: settings.php');
                    exit;
                }
            } else {
                setFlash('success', 'نام کاربری با موفقیت به‌روزرسانی شد.');
                header('Location: settings.php');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>امنیت و تنظیمات حساب</h3>
                <p class="text-muted small mb-0">تغییر نام کاربری و رمز عبور پنل انبارداری</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-right me-1"></i> بازگشت به پیشخوان
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="settings.php" autocomplete="off">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">نام کاربری فعلی / جدید <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control border-start-0" value="<?= htmlspecialchars($currentUsername) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">کلمه عبور فعلی <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" name="current_password" class="form-control border-start-0" placeholder="جهت تایید هویت، رمز فعلی را وارد کنید" required>
                        </div>
                        <div class="form-text small">رمز پیش‌فرض سیستم <code class="text-primary font-monospace">admin123</code> می‌باشد.</div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">کلمه عبور جدید (اختیاری)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="new_password" class="form-control border-start-0" placeholder="در صورت تمایل به تغییر، رمز جدید را وارد کنید">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary">تکرار کلمه عبور جدید</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control border-start-0" placeholder="تکرار رمز جدید">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold fs-6">
                        <i class="bi bi-check2-circle me-1"></i> ذخیره تغییرات مشخصات
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
