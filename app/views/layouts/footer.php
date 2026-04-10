<footer class="footer">
    <p>© 2026 RFT &mdash; Hệ Thống Quản Lý Chấm Công &nbsp;&middot;&nbsp; v1.0.2</p>
</footer>

<?php $mainJsVersion = @filemtime('public/js/main.js') ?: '1.0.3'; ?>
<script src="public/js/main.js?v=<?= (int)$mainJsVersion ?>"></script>
</body>
</html>
