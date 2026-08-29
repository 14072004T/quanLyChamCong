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
    private function normalizeDisplayText($value)
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return '';
        }

        // Chỉ sửa chuỗi mojibake. Dữ liệu UTF-8 hợp lệ không được chạy qua
        // iconv vì thao tác đó sẽ biến "Nguyễn" thành "Nguyá»…".
        if (!preg_match('/(?:Ã.|Â.|á»|áº|Ä.|Ä‘)/u', $text)) {
            return $text;
        }

        // Chuỗi mojibake là UTF-8 của các byte ISO-8859-1. Đổi ngược
        // UTF-8 -> ISO-8859-1 sẽ khôi phục lại chuỗi tiếng Việt ban đầu.
        $fixed = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
        return ($fixed !== false && $fixed !== '') ? $fixed : $text;
    }

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
            // Lấy tất cả nhân viên đang hoạt động để hiển thị danh sách đầy đủ.
            // Không khóa theo 4 phòng ban cố định vì dữ liệu thực tế trong DB có thể khác nhau hoặc bị mojibake.
            $rawList = $this->chamCongModel->getEmployees('', true) ?? [];
            $departmentsList = [];
            $allEmployees = [];
            $unregisteredList = [];
            $registeredList = [];
            
            foreach ($rawList as $emp) {
                $emp['hoTen'] = $this->normalizeDisplayText($emp['hoTen'] ?? '');
                $emp['phongBan'] = $this->normalizeDisplayText($emp['phongBan'] ?? '');
                $emp['chucVu'] = $this->normalizeDisplayText($emp['chucVu'] ?? '');

                $empDept = trim((string)($emp['phongBan'] ?? ''));
                if ($empDept !== '' && !in_array($empDept, $departmentsList, true)) {
                    $departmentsList[] = $empDept;
                }
                
                $profile = $this->faceModel->getFaceProfile($emp['maND']);
                if ($profile === null) {
                    $emp['hasFace'] = false;
                    $unregisteredList[] = $emp;
                } else {
                    $emp['hasFace'] = true;
                    $registeredList[] = $emp;
                }
                
                $allEmployees[] = $emp;
            }
            
            // Sắp xếp dữ liệu để hiển thị nhân viên chưa đăng ký ở trên.
            $employeesList = array_merge($unregisteredList, $registeredList);
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

        $confidence = isset($_POST['confidence']) ? floatval($_POST['confidence']) : 0.0;
        $logDir = __DIR__ . '/../../uploads/liveness_logs/';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $requestLog = sprintf(
            "[%s] Register request target=%s confidence=%.4f embeddingBytes=%d\n",
            date('Y-m-d H:i:s'),
            $_POST['targetMaND'] ?? 'session',
            $confidence,
            strlen($embedding)
        );
        @file_put_contents($logDir . 'duplicate_debug.log', $requestLog, FILE_APPEND | LOCK_EX);

        if ($confidence > 0.0 && $confidence < 0.80) {
            @file_put_contents($logDir . 'duplicate_debug.log', sprintf(
                "[%s] Register rejected reason=low_confidence confidence=%.4f required=0.80\n",
                date('Y-m-d H:i:s'),
                $confidence
            ), FILE_APPEND | LOCK_EX);
            echo json_encode(['success' => false, 'message' => 'Khuôn mặt quét chưa đủ rõ nét. Hãy giữ khuôn mặt ở trung tâm khung hình và thử lại.']);
            exit;
        }

        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'Mã người dùng không hợp lệ.']);
            exit;
        }

        // 1. Phân tích vector đặc trưng gửi lên
        $incomingEmbedding = json_decode($embedding, true);
        if (!is_array($incomingEmbedding) || count($incomingEmbedding) !== 128) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu đặc trưng khuôn mặt không hợp lệ.']);
            exit;
        }
        $incomingEmbedding = $this->normalizeEmbedding($incomingEmbedding);
        $embedding = json_encode($incomingEmbedding);

        // 2. Kiểm tra tính độc nhất, bỏ qua profile cũ của chính nhân viên này.
        $allProfiles = $this->faceModel->getAllFaceProfiles($maND);
        // Face-API descriptors are L2-normalized before comparison. Use a
        // stricter boundary here so different employees are not treated as
        // the same face because of a loose cosine-only match.
        $threshold = 0.40;
        $cosineThreshold = 0.92;

        $duplicateProbeSessionKey = 'face_duplicate_probe_' . $maND;
        $duplicateMatchFound = false;

        foreach ($allProfiles as $prof) {
            $otherEmbedding = json_decode($prof['embedding'], true);
            if (is_array($otherEmbedding) && count($otherEmbedding) === 128) {
                $otherEmbedding = $this->normalizeEmbedding($otherEmbedding);
                $dist = $this->euclideanDistance($incomingEmbedding, $otherEmbedding);
                $cosine = $this->cosineSimilarity($incomingEmbedding, $otherEmbedding);

                $isDuplicate = $dist <= $threshold && $cosine >= $cosineThreshold;
                $logMsg = sprintf(
                    "[%s] Register compare target=%s existing=%s dist=%.4f cosine=%.4f confidence=%.4f thresholdDist=%.2f thresholdCosine=%.2f duplicate=%s\n",
                    date('Y-m-d H:i:s'),
                    $maND,
                    $prof['maND'],
                    $dist,
                    $cosine,
                    $confidence,
                    $threshold,
                    $cosineThreshold,
                    $isDuplicate ? 'YES' : 'NO'
                );
                @file_put_contents($logDir . 'duplicate_debug.log', $logMsg, FILE_APPEND | LOCK_EX);

                if ($isDuplicate) {
                    $duplicateMatchFound = true;
                    $otherName = $this->faceModel->getUserName($prof['maND']);
                    $now = time();
                    $pending = $_SESSION[$duplicateProbeSessionKey] ?? null;

                    // Chỉ chặn đăng ký khi phát hiện trùng 2 lần liên tiếp với cùng một nhân viên
                    // trong cửa sổ thời gian ngắn, giúp giảm báo trùng giả do snapshot nhiễu.
                    if (
                        is_array($pending)
                        && intval($pending['otherMaND'] ?? 0) === intval($prof['maND'])
                        && ($now - intval($pending['ts'] ?? 0)) <= 300
                    ) {
                        unset($_SESSION[$duplicateProbeSessionKey]);
                        @file_put_contents($logDir . 'duplicate_debug.log', sprintf(
                            "[%s] Register decision=BLOCK target=%s existing=%s reason=duplicate_confirmed\n",
                            date('Y-m-d H:i:s'),
                            $maND,
                            $prof['maND']
                        ), FILE_APPEND | LOCK_EX);
                        echo json_encode([
                            'success' => false,
                            'message' => '🚫 Đăng ký thất bại! Khuôn mặt này trùng khớp với khuôn mặt đã đăng ký của nhân viên "' . $otherName . '" (ID: ' . $prof['maND'] . '). Mỗi người chỉ được sở hữu duy nhất 1 tài khoản chấm công khuôn mặt!'
                        ]);
                        exit;
                    }

                    $_SESSION[$duplicateProbeSessionKey] = [
                        'otherMaND' => intval($prof['maND']),
                        'ts' => $now,
                        'dist' => (float)$dist,
                        'cosine' => (float)$cosine,
                    ];

                    @file_put_contents($logDir . 'duplicate_debug.log', sprintf(
                        "[%s] Register decision=RETRY target=%s existing=%s reason=duplicate_probe_first_hit\n",
                        date('Y-m-d H:i:s'),
                        $maND,
                        $prof['maND']
                    ), FILE_APPEND | LOCK_EX);

                    echo json_encode([
                        'success' => false,
                        'message' => '⚠ Hệ thống phát hiện mức tương đồng cao với nhân viên "' . $otherName . '" (ID: ' . $prof['maND'] . '). Vui lòng quét lại lần nữa ở góc nhìn khác/ánh sáng tốt hơn để xác nhận.'
                    ]);
                    exit;
                }
            }
        }

        unset($_SESSION[$duplicateProbeSessionKey]);
        @file_put_contents($logDir . 'duplicate_debug.log', sprintf(
            "[%s] Register decision=ALLOW target=%s duplicateFound=%s reason=no_duplicate_match\n",
            date('Y-m-d H:i:s'),
            $maND,
            $duplicateMatchFound ? 'YES' : 'NO'
        ), FILE_APPEND | LOCK_EX);

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

    public function tabletView()
    {
        $this->requireLogin();
        if (($_SESSION['role'] ?? '') !== 'hr') {
            header('Location: index.php?page=home');
            exit;
        }
        require 'app/views/chamcong/tablet_cham_cong.php';
    }

    public function tabletVerifyApi()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireLogin();
        if (($_SESSION['role'] ?? '') !== 'hr' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện thao tác này.']);
            exit;
        }

        $embedding = json_decode($_POST['embedding'] ?? '', true);
        $token = json_decode($_POST['livenessToken'] ?? '', true);
        $photo = $_POST['photo'] ?? '';
        if (!is_array($embedding) || !is_array($token) || $photo === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu khuôn mặt, liveness hoặc ảnh minh chứng.']);
            exit;
        }
        $liveness = LivenessHelper::validateLivenessToken($token);
        if (!$liveness['valid']) {
            echo json_encode(['success' => false, 'message' => $liveness['message']]);
            exit;
        }

        $matchedId = 0;
        $bestDistance = 999.0;
        foreach ($this->faceModel->getAllFaceProfiles() as $profile) {
            $stored = json_decode($profile['embedding'], true);
            if (!is_array($stored)) continue;
            $distance = $this->euclideanDistance($stored, $embedding);
            $cosine = $this->cosineSimilarity($stored, $embedding);
            if (($distance <= 0.8 || $cosine >= 0.75) && $distance < $bestDistance) {
                $matchedId = (int)$profile['maND'];
                $bestDistance = $distance;
            }
        }
        if ($matchedId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Không nhận diện được nhân viên.']);
            exit;
        }

        $photo = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,', ' '], ['', '', '+'], $photo);
        $photoFilename = $matchedId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $uploadDir = 'uploads/attendance_faces/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        file_put_contents($uploadDir . $photoFilename, base64_decode($photo));

        // Ghi mọi lần quét vào bảng riêng — nguồn dữ liệu để tính giờ vào/ra.
        $this->chamCongModel->insertTabletScan($matchedId, $photoFilename);
        $scanRange = $this->chamCongModel->getTabletScanRangeToday($matchedId);
        $isFirstScanToday = (int)($scanRange['soLanQuet'] ?? 0) <= 1;
        $employeeName = $this->faceModel->getUserName($matchedId);

        if ($isFirstScanToday) {
            $shift = $this->chamCongModel->getShiftForUser($matchedId);
            if (!$shift) {
                echo json_encode(['success' => false, 'message' => 'Nhân viên chưa được gán ca làm việc.']);
                exit;
            }
            $ok = $this->chamCongModel->chamCong($matchedId, 'IN', 'LAN', 'TABLET', 'Chấm vào bằng tablet khuôn mặt', 'TABLET', $photoFilename);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Đã ghi nhận giờ vào cho ' . $employeeName . ' (Mã NV: ' . $matchedId . ').' : 'Không thể lưu chấm công.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Các lần quét sau trong ngày luôn cập nhật giờ ra thành lần quét gần nhất.
        $ok = $this->chamCongModel->chamCong($matchedId, 'OUT', 'LAN', 'TABLET', 'Cập nhật giờ ra bằng tablet khuôn mặt', 'TABLET', $photoFilename);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Đã cập nhật giờ ra cho ' . $employeeName . ' (Mã NV: ' . $matchedId . ').' : 'Không thể lưu chấm công.'], JSON_UNESCAPED_UNICODE);
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
        $cosine = $this->cosineSimilarity($storedEmbedding, $incomingEmbedding);
        $threshold = 0.8; // Cho phép khớp ổn hơn giữa descriptor đã đăng ký và frame chấm công
        $cosineThreshold = 0.75;

        if ($distance > $threshold && $cosine < $cosineThreshold) {
            echo json_encode([
                'success' => false,
                'message' => sprintf('Nhận diện thất bại! Không trùng khớp khuôn mặt đã đăng ký (Khoảng cách: %.4f, cosine: %.4f).', $distance, $cosine)
            ]);
            exit;
        }

        // === 3. KIỂM TRA QUY TẮC CHẤM CÔNG (MẠNG + CA LÀM VIỆC + LẦN CHẤM) ===
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
            $clientIp = '192.168.1.129'; // IP nội bộ giả lập khi test localhost
        }

        // Bỏ kiểm tra mạng nội bộ / WiFi để chấm công chỉ phụ thuộc vào xác thực khuôn mặt.
        if ($phuongThuc === 'LAN') {
            $tenWifi = 'OFFLINE_FALLBACK';
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
            if ($this->chamCongModel->isOffShift($shift)) {
                echo json_encode(['success' => false, 'message' => 'Hôm nay bạn được xếp ca OFF (nghỉ). Không thể chấm công.']);
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

    private function normalizeEmbedding($embedding)
    {
        $squaredNorm = 0.0;
        foreach ($embedding as $value) {
            $number = (float)$value;
            $squaredNorm += $number * $number;
        }

        $norm = sqrt($squaredNorm);
        if ($norm <= 0.0) {
            return [];
        }

        return array_map(static function ($value) use ($norm) {
            return (float)$value / $norm;
        }, $embedding);
    }

    private function cosineSimilarity($v1, $v2)
    {
        if (count($v1) !== count($v2)) {
            return 0.0;
        }

        $dot = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;
        for ($i = 0; $i < count($v1); $i++) {
            $a = floatval($v1[$i]);
            $b = floatval($v2[$i]);
            $dot += $a * $b;
            $norm1 += $a * $a;
            $norm2 += $b * $b;
        }

        if ($norm1 <= 0 || $norm2 <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($norm1) * sqrt($norm2));
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
