<?php
require_once 'app/models/ketNoi.php';

class LoginController {
    private function getDefaultPageForRole($role) {
        if ($role === 'manager') {
            return 'bao-cao-tong-hop';
        } elseif ($role === 'hr') {
            return 'cham-cong-dashboard';
        } else {
            return 'home';
        }
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = trim($_POST['username'] ?? '');
        $matKhau = trim($_POST['matKhau'] ?? '');

        if (empty($username) || empty($matKhau)) {
            header("Location: index.php?page=login&error=1");
            exit;
        }

        $db = new KetNoi();
        $conn = $db->connect();

        // Kiá»ƒm tra tÃ i khoáº£n (khÃ´ng filter theo trangThai Ä‘á»ƒ kiá»ƒm tra riÃªng)
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
            
            // Kiá»ƒm tra máº­t kháº©u trÆ°á»›c
            if (md5($matKhau) !== $user['matKhau']) {
                header("Location: index.php?page=login&error=1");
                exit;
            }

            // Kiểm tra trạng thái tài khoản (taikhoan hoặc nguoidung)
            $trangThaiTK = $user['trangThaiTK'] ?? '';
            $trangThaiND = $user['trangThaiND'] ?? 1;

            $activeStatusValues = [
                'hoạt động',
                'hoat dong',
                'hoáº¡t ä»™ng',
                'hoáº¡t Ä‘á»™ng',
                'hoạt dộng'
            ];
            $statusNormalized = mb_strtolower(trim($trangThaiTK), 'UTF-8');

            if (!in_array($statusNormalized, $activeStatusValues, true) || $trangThaiND != 1) {
                header("Location: index.php?page=login&error=inactive");
                exit;
            }

            // Map chucVu sang role
            $roleMapping = [
                'NhÃ¢n viÃªn' => 'nhanvien',
                'Bá»™ pháº­n NhÃ¢n sá»±' => 'hr',
                'Quáº£n lÃ½ / Ban lÃ£nh Ä‘áº¡o' => 'manager',
                'Bá»™ pháº­n Ká»¹ thuáº­t' => 'tech'
            ];
            
            $chucVu = $user['chucVu'] ?? 'NhÃ¢n viÃªn';
            $role = $roleMapping[$chucVu] ?? 'nhanvien';

            // MB chá»‰ role nhÃ¢n viÃªn thá»±c hiá»‡n cháº¥m cÃ´ng, cÃ¡c role cÃ²n láº¡i chá»‰ Ä‘Æ°á»£c thá»±c hiá»‡n trÃªn IB
            require_once 'app/middleware/AuthMiddleware.php';
            if (AuthMiddleware::isMobile() && $role !== 'nhanvien') {
                header("Location: index.php?page=login&error=ib_only");
                exit;
            }

            // ÄÄƒng nháº­p thÃ nh cÃ´ng
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

        // Sai tÃ i khoáº£n hoáº·c máº­t kháº©u
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

