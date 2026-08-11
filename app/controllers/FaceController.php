<?php
require_once 'app/models/FaceModel.php';
require_once 'app/models/ChamCongModel.php';
require_once 'app/controllers/Controller.php';
require_once 'app/helpers/LivenessHelper.php';

class FaceController extends Controller
{
    private $faceModel;
    private $chamCongModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->faceModel = new FaceModel();
        $this->chamCongModel = new ChamCongModel();
    }

    /**
     * Hiển thị trang đăng ký khuôn mặt
     */
    public function registerView()
    {
        $this->requireLogin();
        
        $maND = $_SESSION['user']['maND'] ?? null;
        $existingProfile = $this->faceModel->getFaceProfile($maND);
        
        // Chỉ HR mới có thể đăng ký khuôn mặt cho nhân viên
        $isHR = ($_SESSION['role'] ?? '') === 'hr';
        $employeesList = [];
        $departmentsList = [];
        
        if (!$isHR) {
            header('Location: index.php?page=home');
            exit();
        } else {
            // Chỉ đăng ký cho 4 phòng ban cố định: Sản xuất, Kho, QC, Bảo trì
            $departmentsList = ['Sản xuất', 'Kho', 'QC', 'Bảo trì'];
            
            // Lấy danh sách nhân viên đang hoạt động thuộc 4 phòng ban này
            $rawList = $this->chamCongModel->getEmployees('', true) ?? [];
            $registeredList = [];
            foreach ($rawList as $emp) {
                $empDept = trim($emp['phongBan'] ?? '');
                if (!in_array($empDept, $departmentsList)) {
                    continue; // Chỉ nhận 4 phòng ban: Sản xuất, Kho, QC, Bảo trì
                }
                
                $profile = $this->faceModel->getFaceProfile($emp['maND']);
                if ($profile === null) {
                    // Nhân viên chưa đăng ký khuôn mặt
                    $emp['hasFace'] = false;
                    $employeesList[] = $emp;
                } else {
                    // Nhân viên đã đăng ký khuôn mặt
                    $emp['hasFace'] = true;
                    $registeredList[] = $emp;
                }
            }
        }

        $data = [
            'existingProfile' => $existingProfile,
            'isHR' => $isHR,
            'employeesList' => $employeesList,
            'registeredList' => $registeredList ?? [],
            'departmentsList' => $departmentsList,
            'currentMaND' => $maND
        ];

        $this->view('face/register', $data);
    }

    /**
     * API Lưu thông tin đăng ký khuôn mặt [POST]
     */
    public function registerApi()
    {
        header('Content-Type: application/json');
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $embedding = $_POST['embedding'] ?? '';
        if (empty($embedding)) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy dữ liệu khuôn mặt.']);
            exit;
        }

        // Chỉ cho phép HR thực hiện đăng ký khuôn mặt
        $isHR = ($_SESSION['role'] ?? '') === 'hr';
        if (!$isHR) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.']);
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? null;
        if (isset($_POST['targetMaND']) && !empty($_POST['targetMaND'])) {
            $maND = intval($_POST['targetMaND']);
        }

        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'Mã người dùng không hợp lệ.']);
            exit;
        }

        // 1. Phân tích vector đặc trưng gửi lên
        $incomingEmbedding = json_decode($embedding, true);
        if (!is_array($incomingEmbedding)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu đặc trưng khuôn mặt không hợp lệ.']);
            exit;
        }

        // 2. Kiểm tra nhân viên này đã đăng ký khuôn mặt chưa (1 user chỉ được 1 face)
        $existingProfile = $this->faceModel->getFaceProfile($maND);
        if ($existingProfile) {
            $userName = $this->faceModel->getUserName($maND);
            echo json_encode([
                'success'    => false,
                'alreadyExists' => true,
                'message'    => '⚠️ Nhân viên "' . $userName . '" đã có dữ liệu khuôn mặt được đăng ký trước đó. Mỗi nhân viên chỉ được đăng ký 1 khuôn mặt duy nhất. Nếu cần cập nhật, HR phải xóa khuôn mặt cũ trước rồi đăng ký lại.'
            ]);
            exit;
        }

        // 3. Kiểm tra tính độc nhất: Khuôn mặt này có trùng với tài khoản khác không?
        $allProfiles = $this->faceModel->getAllFaceProfiles($maND);
        $threshold = 0.6; // Ngưỡng xác định cùng một người
        
        foreach ($allProfiles as $prof) {
            $otherEmbedding = json_decode($prof['embedding'], true);
            if (is_array($otherEmbedding)) {
                $dist = $this->euclideanDistance($incomingEmbedding, $otherEmbedding);
                
                // Write debug log to workspace file
                $logDir = __DIR__ . '/../../uploads/liveness_logs/';
                if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                $logMsg = sprintf("[%s] Register target=%s vs existing maND=%s, dist=%.4f (threshold=%s)\n", date('Y-m-d H:i:s'), $maND, $prof['maND'], $dist, $threshold);
                @file_put_contents($logDir . 'duplicate_debug.log', $logMsg, FILE_APPEND);

                if ($dist <= $threshold) {
                    $otherName = $this->faceModel->getUserName($prof['maND']);
                    echo json_encode([
                        'success' => false,
                        'message' => '🚫 Đăng ký thất bại! Khuôn mặt này trùng khớp với khuôn mặt đã đăng ký của nhân viên "' . $otherName . '" (ID: ' . $prof['maND'] . '). Mỗi người chỉ được sở hữu duy nhất 1 tài khoản chấm công khuôn mặt!'
                    ]);
                    exit;
                }
            }
        }

        // 4. Lưu khuôn mặt mới (INSERT)
        $ok = $this->faceModel->saveFaceProfile($maND, $embedding);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => '✅ Đăng ký khuôn mặt thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lưu dữ liệu khuôn mặt thất bại.']);
        }
        exit;
    }

    /**
     * API Xóa khuôn mặt đã đăng ký [POST]
     */
    public function deleteApi()
    {
        header('Content-Type: application/json');
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        // Chỉ HR mới được phép xóa
        $isHR = ($_SESSION['role'] ?? '') === 'hr';
        if (!$isHR) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.']);
            exit;
        }

        $maND = intval($_POST['targetMaND'] ?? 0);
        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'Mã nhân viên không hợp lệ.']);
            exit;
        }

        // Kiểm tra nhân viên có dữ liệu khuôn mặt hay không
        $profile = $this->faceModel->getFaceProfile($maND);
        if (!$profile) {
            echo json_encode(['success' => false, 'message' => 'Nhân viên này chưa đăng ký khuôn mặt.']);
            exit;
        }

        $ok = $this->faceModel->deleteFaceProfile($maND);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa dữ liệu khuôn mặt thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Xóa dữ liệu khuôn mặt thất bại.']);
        }
        exit;
    }

    /**
     * API Khởi tạo phiên liveness — tạo session secret cho HMAC [POST]
     */
    public function livenessSession()
    {
        header('Content-Type: application/json');
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $secret = LivenessHelper::generateSessionSecret();

        echo json_encode([
            'success' => true,
            'sessionSecret' => $secret
        ]);
        exit;
    }

    /**
     * API Xác thực khuôn mặt và Chấm công [POST]
     * Pipeline: Liveness Token Validation → Face Recognition → Attendance
     */
    public function verifyApi()
    {
        header('Content-Type: application/json');
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? null;
        $hanhDong = trim($_POST['hanhDong'] ?? 'IN');
        $tenWifi = trim($_POST['tenWifi'] ?? 'INTERNAL_NETWORK');
        $phuongThuc = trim($_POST['phuongThuc'] ?? 'LAN');
        $embedding = $_POST['embedding'] ?? '';
        $photo = $_POST['photo'] ?? '';
        $livenessTokenRaw = $_POST['livenessToken'] ?? '';

        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập hoặc phiên làm việc hết hạn.']);
            exit;
        }

        if (empty($embedding)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu đặc trưng khuôn mặt từ camera.']);
            exit;
        }

        if (empty($photo)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ảnh chụp minh chứng chấm công.']);
            exit;
        }

        // ═══ LAYER 0: LIVENESS TOKEN VALIDATION ═══
        // This MUST pass before any face recognition is performed.
        // The token proves the user completed the multi-layer liveness challenge.
        if (empty($livenessTokenRaw)) {
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu token xác minh liveness. Vui lòng hoàn thành quy trình xác minh khuôn mặt thật.'
            ]);
            exit;
        }

        $livenessToken = json_decode($livenessTokenRaw, true);
        if (!$livenessToken) {
            echo json_encode([
                'success' => false,
                'message' => 'Token xác minh liveness không hợp lệ (lỗi JSON).'
            ]);
            exit;
        }

        $livenessResult = LivenessHelper::validateLivenessToken($livenessToken);
        if (!$livenessResult['valid']) {
            echo json_encode([
                'success' => false,
                'message' => '⚠ ' . $livenessResult['message']
            ]);
            exit;
        }

        // ═══ LAYER 1: FACE RECOGNITION ═══
        // Only reached after liveness verification succeeds.

        // 1. Lấy profile khuôn mặt đã lưu của người dùng
        $profile = $this->faceModel->getFaceProfile($maND);
        if (!$profile) {
            echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng ký khuôn mặt trên hệ thống. Vui lòng đăng ký trước khi chấm công.']);
            exit;
        }

        // 2. Tính khoảng cách Euclidean giữa 2 vector embedding
        $storedEmbedding = json_decode($profile['embedding'], true);
        $incomingEmbedding = json_decode($embedding, true);

        if (!is_array($storedEmbedding) || !is_array($incomingEmbedding)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu khuôn mặt bị lỗi định dạng.']);
            exit;
        }

        $distance = $this->euclideanDistance($storedEmbedding, $incomingEmbedding);
        $threshold = 0.6; // Ngưỡng nhận diện (càng thấp càng nghiêm ngặt)

        if ($distance > $threshold) {
            echo json_encode([
                'success' => false, 
                'message' => sprintf('Nhận diện thất bại! Không trùng khớp khuôn mặt đã đăng ký (Khoảng cách: %.4f, yêu cầu <= %.1f).', $distance, $threshold)
            ]);
            exit;
        }

        // === 3. KIỂM TRA QUY TẮC CHẤM CÔNG (MẠNG + CA LÀM VIỆC + LẦN CHẤM) ===
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
            $clientIp = '192.168.1.129'; // IP nội bộ giả lập khi test localhost
        }

        if ($phuongThuc === 'LAN') {
            if (!$this->chamCongModel->isAllowedIp($clientIp)) {
                echo json_encode(['success' => false, 'message' => 'Bạn không ở trong mạng nội bộ công ty. Không thể chấm công.']);
                exit;
            }
            if (!$this->chamCongModel->checkWifi()) {
                echo json_encode(['success' => false, 'message' => 'Không có cấu hình WiFi nội bộ hợp lệ nào. Vui lòng liên hệ IT.']);
                exit;
            }
            if ($tenWifi === '' || strtoupper($tenWifi) === 'INTERNAL_NETWORK' || !$this->chamCongModel->isWifiAllowed($tenWifi)) {
                $fallback = $this->chamCongModel->getFirstActiveWifiName();
                if ($fallback) {
                    $tenWifi = $fallback;
                }
            }
            if (!$this->chamCongModel->isWifiAllowed($tenWifi)) {
                echo json_encode(['success' => false, 'message' => 'WiFi "' . $tenWifi . '" không được phép dùng chấm công.']);
                exit;
            }
        } else {
            $tenWifi = 'QR_FALLBACK';
        }

        // Kiểm tra số lần chấm công hôm nay
        $todayAttendance = $this->chamCongModel->getAttendanceByUser($maND, 1);
        $hasIn = false;
        $hasOut = false;
        if (!empty($todayAttendance) && $todayAttendance[0]['ngayLamViec'] === date('Y-m-d')) {
            $hasIn = !empty($todayAttendance[0]['gioVaoDau']);
            $hasOut = !empty($todayAttendance[0]['gioRaCuoi']);
        }

        if ($hanhDong === 'OUT' && !$hasIn) {
            echo json_encode(['success' => false, 'message' => 'Bạn chưa chấm công vào hôm nay. Vui lòng chấm vào trước.']);
            exit;
        }
        if ($hanhDong === 'OUT' && $hasOut) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã chấm công ra hôm nay rồi. Mỗi ngày chỉ được phép chấm ra 1 lần.']);
            exit;
        }
        if ($hanhDong === 'IN' && $hasIn) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã chấm công vào hôm nay rồi. Mỗi ngày chỉ được phép chấm vào 1 lần.']);
            exit;
        }

        // Kiểm tra giờ ca làm việc khi IN
        if ($hanhDong === 'IN') {
            $shift = $this->chamCongModel->getShiftForUser($maND);
            if (!$shift) {
                echo json_encode(['success' => false, 'message' => 'Bạn chưa được gán ca làm việc. Vui lòng liên hệ HR.']);
                exit;
            }
            $now = date('H:i:s');
            $start = $shift['gioBatDau'];
            $end = $shift['gioKetThuc'];
            $isOutsideShift = false;
            $isTooEarly = false;

            if ($start < $end) {
                if ($now < $start) {
                    $isOutsideShift = true;
                    $isTooEarly = true;
                } elseif ($now > $end) {
                    $isOutsideShift = true;
                }
            } else {
                if ($now > $end && $now < $start) {
                    $isOutsideShift = true;
                    $isTooEarly = true;
                }
            }

            if ($isOutsideShift) {
                $msg = $isTooEarly
                    ? 'Ca làm việc chưa bắt đầu. Ca của bạn bắt đầu lúc ' . substr($start, 0, 5) . '.'
                    : 'Ca làm việc đã kết thúc (' . substr($end, 0, 5) . '). Bạn không thể chấm công vào.';
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
        }

        // 4. Lưu ảnh minh chứng khuôn mặt
        $photoFilename = null;
        try {
            $photo = str_replace('data:image/jpeg;base64,', '', $photo);
            $photo = str_replace('data:image/png;base64,', '', $photo);
            $photo = str_replace(' ', '+', $photo);
            $decodedPhoto = base64_decode($photo);

            $uploadDir = 'uploads/attendance_faces/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $photoFilename = $maND . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
            file_put_contents($uploadDir . $photoFilename, $decodedPhoto);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi lưu trữ ảnh minh chứng: ' . $e->getMessage()]);
            exit;
        }

        // 5. Ghi nhận chấm công vào CSDL
        $ghiChu = ($hanhDong === 'IN') ? 'Chấm vào bằng khuôn mặt (Liveness verified)' : 'Chấm ra bằng khuôn mặt (Liveness verified)';
        $ok = $this->chamCongModel->chamCong($maND, $hanhDong, $phuongThuc, $tenWifi, $ghiChu, $clientIp, $photoFilename);

        if ($ok) {
            echo json_encode([
                'success' => true,
                'message' => 'Chấm công ' . ($hanhDong === 'IN' ? 'vào' : 'ra') . ' thành công qua xác thực khuôn mặt thật!',
                'distance' => $distance,
                'livenessScore' => $livenessResult['score']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Đã xác thực khuôn mặt khớp nhưng lưu chấm công vào hệ thống thất bại.']);
        }
        exit;
    }

    /**
     * Tính khoảng cách Euclidean giữa 2 vector 128 chiều
     */
    private function euclideanDistance($v1, $v2)
    {
        if (count($v1) !== count($v2)) {
            return 999.0;
        }
        $sum = 0.0;
        for ($i = 0; $i < count($v1); $i++) {
            $diff = floatval($v1[$i]) - floatval($v2[$i]);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    private function requireLogin()
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
            exit;
        }
    }
}
