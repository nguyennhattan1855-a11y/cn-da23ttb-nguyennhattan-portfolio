<?php
// includes/footer.php - Phần cuối chung của các trang
?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> <?= e(config('app_name')) ?> &ndash; Đồ án chuyên ngành Công nghệ thông tin, Khoa CNTT.</p>
    </footer>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach (($extraScripts ?? []) as $script): ?>
    <script src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
