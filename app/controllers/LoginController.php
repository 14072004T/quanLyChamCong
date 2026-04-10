<?php
require_once 'app/models/ketNoi.php';

class LoginController {
    private function getDefaultPageForRole($role) {
        return in_array($role, ['hr', 'manager'], true) ? 'cham-cong-dashboard' : 'home';
    }

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user'])) {
            $role = $_SESSION['role'] ?? 'nhanvien';
            header("Location: index.php?page=" . $this->getDefaultPageForRole($role));
            exit;
        }
        require_once 'app/views/login.php';
    }

    public function handleLogin() {
        session_start();

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            header("Location: index.php?page=login&error=1");
            exit;
        }

        $db = new KetNoi();
        $conn = $db->connect();

        // Kiểm tra tài khoản (không filter theo trangThai để kiểm tra riêng)
        $sql = "SELECT tk.*, nd.hoTen, nd.chucVu, nd.phongBan, nd.maND, nd.trangThai as trangThaiND, tk.trangThai as trangThaiTK
        FROM taikhoan tk 
        LEFT JOIN nguoidung nd ON tk.maTK = nd.maTK 
        WHERE tk.tenDangNhap = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra mật khẩu trước
            if (md5($password) !== $user['matKhau']) {
                header("Location: index.php?page=login&error=1");
                exit;
            }

            // ✅ Kiểm tra trạng thái tài khoản (taikhoan hoặc nguoidung)
            $trangThaiTK = $user['trangThaiTK'] ?? '';
            $trangThaiND = $user['trangThaiND'] ?? 1;
            
            if ($trangThaiTK !== 'Hoạt động' || $trangThaiND != 1) {
                // Tài khoản bị ngừng hoạt động
                header("Location: index.php?page=login&error=inactive");
                exit;
            }

            // Map chucVu sang role
            $roleMapping = [
                'Nhân viên' => 'nhanvien',
                'Bộ phận Nhân sự' => 'hr',
                'Quản lý / Ban lãnh đạo' => 'manager',
                'Bộ phận Kỹ thuật' => 'tech'
            ];
            
            $chucVu = $user['chucVu'] ?? 'Nhân viên';
            $role = $roleMapping[$chucVu] ?? 'nhanvien';

            // Đăng nhập thành công
            $_SESSION['user'] = [
                'maTK' => $user['maTK'],
                'maND' => $user['maND'] ?? null,
                'tenDangNhap' => $user['tenDangNhap'],
                'hoTen' => $user['hoTen'] ?? '',
                'chucVu' => $chucVu,
                'phongBan' => $user['phongBan'] ?? ''
            ];
            
            $_SESSION['role'] = $role;

            header("Location: index.php?page=" . $this->getDefaultPageForRole($role));
            exit;
        }

        // Sai tài khoản hoặc mật khẩu
        header("Location: index.php?page=login&error=1");
        exit;
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        header("Location: index.php?page=login");
        exit;
    }
}
?>
