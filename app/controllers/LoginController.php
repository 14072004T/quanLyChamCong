<?php
require_once 'app/models/ketNoi.php';

class LoginController {
    /**
     * Remove Vietnamese diacritics (diacritical marks) from text.
     * Also handles mojibake encoding where UTF-8 bytes were interpreted as Latin1.
     * Converts "Nhân viên" → "nhan vien", "Bộ phận" → "bo phan", etc.
     * Also handles corrupted "NhÃ¢n viÃªn" → "nhan vien"
     */
    private function removeDiacritics($text) {
        // Chỉ sửa encoding khi chuỗi thực sự là mojibake. Không được ép
        // chuỗi UTF-8 hợp lệ qua ISO-8859-1 vì sẽ làm hỏng tên/chức vụ.
        if (preg_match('/(?:Ã.|Â.|á»|áº|Ä.|Ä‘|�)/u', $text)) {
            $fixed = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);
            if ($fixed !== false && $fixed !== '') {
                $text = $fixed;
            }
        }
        
        $map = [
            'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'đ' => 'd'
        ];
        
        // Map Vietnamese characters first. macOS iconv can transliterate
        // some Vietnamese characters incorrectly (for example "ộ" to "p").
        $result = strtr($text, $map);
        if (preg_match('/[^\x00-\x7F]/', $result)) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $result);
            if ($transliterated !== false && $transliterated !== '') {
                $result = $transliterated;
            }
        }
        // Remove any remaining non-ASCII characters that couldn't be mapped
        $result = preg_replace('/[^\x00-\x7F]/', '', $result);
        return $result;
    }
    
    /**
     * Map chucVu (job title) to role
     * Handles both properly encoded and mojibake-corrupted Vietnamese text
     */
    private function mapRoleFromChucVu($chucVu) {
        $roleMapping = [
            'nhan vien' => 'nhanvien',
            'bo phan nhan su' => 'hr',
            'quan ly / ban lanh dao' => 'manager',
            'bo phan ky thuat' => 'tech'
        ];
        
        // Try direct mapping with diacritic removal
        $normalized = $this->removeDiacritics(mb_strtolower($chucVu, 'UTF-8'));
        
        if (isset($roleMapping[$normalized])) {
            return $roleMapping[$normalized];
        }
        
        // If normalization failed (due to severe mojibake), try keyword matching
        $chucVuLower = mb_strtolower($chucVu, 'UTF-8');
        
        // Match against the original Vietnamese keywords in various encoding states
        // Check for specific role combinations first (more specific patterns first)
        
        // Check for "bộ phận nhân sự" (HR department) - must match both "phận" and "nhân"
        // Patterns include mojibake variants: pháºn, NhÃ¢n
        $hasPhanPattern = preg_match('/phân?|pháº?|pháºn|phá»?/i', $chucVu);
        $hasNhanPattern = preg_match('/NhÃ¢n|Nha?n|viên?|viÃ|Nhân|nhân/i', $chucVu);
        
        if ($hasPhanPattern && $hasNhanPattern) {
            return 'hr'; // "bộ phận nhân sự"
        }
        
        // Check for "quản lý / ban lãnh đạo" (Manager)
        if (preg_match('/Quá?n|lý?|lÃ|quan|ly/i', $chucVu)) {
            return 'manager'; // "quản lý / ban lãnh đạo"
        }
        
        // Check for "bộ phận kỹ thuật" (Tech department)
        if (preg_match('/kỹ?|thuá?|ky|thuat|phá»/i', $chucVu)) {
            return 'tech'; // "bộ phận kỹ thuật"
        }
        
        // Check for just "nhân viên" (Employee)
        if (preg_match('/NhÃ¢n|Nha?n|viên?|viÃ|Nhân|nhân/i', $chucVu)) {
            return 'nhanvien'; // Just "nhân viên"
        }
        
        return 'nhanvien'; // Default
    }
    
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
            
            if (md5($matKhau) !== $user['matKhau']) {
                header("Location: index.php?page=login&error=1");
                exit;
            }

            $trangThaiTK = trim($user['trangThaiTK'] ?? '');
            $trangThaiND = $user['trangThaiND'] ?? 1;

            $statusLower = mb_strtolower($trangThaiTK, 'UTF-8');
            
            $isBlocked = (strpos($statusLower, 'khoa') !== false || 
                         strpos($statusLower, 'inactive') !== false ||
                         strpos($statusLower, 'suspended') !== false);
            
            if ($trangThaiND != 1 || $isBlocked) {
                header("Location: index.php?page=login&error=inactive");
                exit;
            }

            $roleMapping = [
                'nhan vien' => 'nhanvien',
                'bo phan nhan su' => 'hr',
                'quan ly / ban lanh dao' => 'manager',
                'bo phan ky thuat' => 'tech'
            ];
            
            $chucVu = trim($user['chucVu'] ?? 'Nhan vien');
            
            // Map the chucVu to a role
            $role = $this->mapRoleFromChucVu($chucVu);
            
            // DEBUG LOG to file
            $debugFile = __DIR__ . '/../../login_debug.log';
            $debugMsg = date('Y-m-d H:i:s') . " | username=$username | chucVu=$chucVu | role=$role\n";
            file_put_contents($debugFile, $debugMsg, FILE_APPEND);

            require_once 'app/middleware/AuthMiddleware.php';
            if (AuthMiddleware::isMobile() && $role !== 'nhanvien') {
                header("Location: index.php?page=login&error=ib_only");
                exit;
            }

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
