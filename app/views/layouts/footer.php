<footer class="footer">
    <p>© 2026 RFT &mdash; Hệ Thống Quản Lý Chấm Công &nbsp;&middot;&nbsp; <?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : 'v2.4.26') ?></p>
</footer>

<?php $mainJsVersion = @filemtime('public/js/main.js') ?: '1.0.3'; ?>
<script src="public/js/main.js?v=<?= (int)$mainJsVersion ?>"></script>
</body>
</html>
