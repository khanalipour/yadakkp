</main>

<footer class="py-3 mt-4 text-center text-muted small">
    <div class="container-fluid px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div>
            تمامی حقوق متعلق به فروشگاه لوازم یدکی <strong>یدک کی پی (YadakKp)</strong> می‌باشد &copy; <?= jdate('Y') ?>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-light text-secondary border">نسخه <?= APP_VERSION ?></span>
            <span class="text-secondary"><i class="bi bi-clock-history me-1"></i>اعتبار نشست: ۴۸ ساعت</span>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- Custom App JS -->
<script src="assets/js/app.js?v=<?= time() ?>"></script>

</body>
</html>
