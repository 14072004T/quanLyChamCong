<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/nav.php';
?>

<style>
    .main-content {
        background: url('uploads/img/shirt-factory-bg.jpg') center/cover no-repeat;
        background-attachment: fixed;
        min-height: 100vh;
    }
</style>

<div class="main-layout-wrapper">
    <?php require_once 'app/views/layouts/sidebar.php'; ?>

    <main class="main-content">

        <h2 class="main-title" style="text-align:center; font-size: 1.5em; margin-bottom: 20px; color: #fff; text-shadow: 0 2px 8px rgba(0,0,0,0.5); font-weight: 700;">
            ĐĂNG NHẬP VÀO HỆ THỐNG
        </h2>

        <section class="login-section" style="display:flex; justify-content:center; align-items:center;">
            <div class="login-box" 
                style="background:#fff; padding:40px 50px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:100%; max-width:400px;">

                <form action="index.php?page=login-process" method="POST">
                    <div style="text-align:center; margin-bottom:25px;">
                        
        <p style="color:#666; font-size:0.9em; font-weight:600; white-space:nowrap; margin:0;">He Thong Quan Ly Cham Cong Doanh Nghiep</p>
                    </div>

                    <div style="margin-bottom:15px;">
                        <label for="username" style="display:block; text-align:left; font-weight:bold;">Tên Đăng Nhập</label>
                        <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required
                            style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    </div>

                    <div style="margin-bottom:15px;">
                        <label for="password" style="display:block; text-align:left; font-weight:bold;">Mật Khẩu</label>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required
                            style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.9em; margin-bottom:15px;">
                        <label><input type="checkbox" name="remember"> Ghi nhớ tôi</label>
                        <a href="#" style="color:#007bff; text-decoration:none;">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" 
                        style="width:100%; background:#000; color:#fff; border:none; border-radius:6px; padding:10px; font-weight:bold; cursor:pointer;">
                        Đăng Nhập
                    </button>
                </form>

                <p style="text-align:center; font-size:0.8em; color:#666; margin-top:25px;">
                    © 2026 He Thong Quan Ly Cham Cong
                </p>
            </div>
        </section>
    </main>
</div>

<!-- Modal thông báo đăng nhập -->
<div class="modal-overlay" id="login-fail-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" 
        style="background:white; padding:30px 40px; border-radius:10px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
        <p style="font-size:1.1em; font-weight:bold; color:#c0392b; margin-bottom:20px;">
            ❌ Sai tên đăng nhập hoặc mật khẩu!
        </p>
        <button id="btn-login-fail-close" 
            style="background:#f0f0f0; color:#333; border:1px solid #ccc; padding:8px 20px; border-radius:5px; cursor:pointer;">
            X | Đóng
        </button>
    </div>
</div>

<!-- Modal tài khoản ngừng hoạt động -->
<div class="modal-overlay" id="account-inactive-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" 
        style="background:white; padding:40px; border-radius:10px; text-align:center; max-width:500px; box-shadow:0 5px 20px rgba(0,0,0,0.3); position:relative;">
        <!-- Nút X đóng modal -->
        <button id="btn-inactive-close-x" 
            style="position:absolute; top:10px; right:10px; background:transparent; border:none; font-size:24px; color:#95a5a6; cursor:pointer; width:30px; height:30px; display:flex; align-items:center; justify-content:center; transition:color 0.3s;"
            title="Đóng">
            ×
        </button>
        
        <div style="font-size:64px; color:#e74c3c; margin-bottom:20px;">
            <i class="fas fa-lock"></i>
        </div>
        <div style="font-size:23px; font-weight:bold; color:#2c3e50; margin-bottom:15px;">
             TÀI KHOẢN ĐÃ NGỪNG HOẠT ĐỘNG
        </div>
 
    </div>
</div>

<?php
require_once 'app/views/layouts/footer.php';
?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const modalFail = document.getElementById('login-fail-modal');
    const modalInactive = document.getElementById('account-inactive-modal');
    const btnCloseFail = document.getElementById('btn-login-fail-close');
    const btnCloseInactiveX = document.getElementById('btn-inactive-close-x');
    const urlParams = new URLSearchParams(window.location.search);

    // Kiểm tra lỗi sai mật khẩu
    if (urlParams.has('error') && urlParams.get('error') === '1') {
        modalFail.style.display = 'flex';
        const newUrl = window.location.pathname + window.location.search.replace(/&?error=1/, '').replace(/\?$/, '');
        window.history.replaceState({ path: newUrl }, '', newUrl);
    }

    // Kiểm tra tài khoản ngừng hoạt động
    if (urlParams.has('error') && urlParams.get('error') === 'inactive') {
        modalInactive.style.display = 'flex';
        const newUrl = window.location.pathname + window.location.search.replace(/&?error=inactive/, '').replace(/\?$/, '');
        window.history.replaceState({ path: newUrl }, '', newUrl);
    }

    // Đóng modal sai mật khẩu
    btnCloseFail.addEventListener('click', () => modalFail.style.display = 'none');
    modalFail.addEventListener('click', e => { if (e.target === modalFail) modalFail.style.display = 'none'; });

    // Đóng modal tài khoản ngừng hoạt động (nút X)
    if (btnCloseInactiveX) {
        btnCloseInactiveX.addEventListener('click', () => {
            modalInactive.style.display = 'none';
        });

        // Hover effect cho nút X
        btnCloseInactiveX.addEventListener('mouseenter', function() {
            this.style.color = '#e74c3c';
            this.style.transform = 'scale(1.2)';
        });
        btnCloseInactiveX.addEventListener('mouseleave', function() {
            this.style.color = '#95a5a6';
            this.style.transform = 'scale(1)';
        });
    }

    // Click ngoài modal để đóng
    modalInactive.addEventListener('click', e => { 
        if (e.target === modalInactive) {
            modalInactive.style.display = 'none';
        }
    });
});
</script>
