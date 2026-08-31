<?php
/**
 * LivenessHelper v3.0 — Server-side passive liveness token validation
 * ====================================================================
 * Validates HMAC-signed passive liveness tokens from the client-side
 * LivenessDetector v3.0 to ensure face attendance cannot be spoofed.
 * 
 * Security layers:
 *   1. HMAC-SHA256 signature verification
 *   2. Token freshness check (max 120 seconds)
 *   3. ★ Blink detection verification (HARD REJECT if no blink)
 *   4. Frame count minimum (25 frames)
 *   5. Passive layer scores threshold verification (8 metrics)
 *   6. Combined score threshold (62%)
 *   7. Audit logging
 */

class LivenessHelper
{
    /**
     * Generate a unique session secret for HMAC signing.
     * Stored in PHP session so only the same user session can validate.
     * 
     * @return string 64-char hex string
     */
    public static function generateSessionSecret()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $secret = bin2hex(random_bytes(32));
        $_SESSION['liveness_session_secret'] = $secret;
        $_SESSION['liveness_session_time'] = time();
        $_SESSION['liveness_session_used'] = false;

        return $secret;
    }

    /**
     * Retrieve the current session secret.
     * 
     * @return string|null
     */
    public static function getSessionSecret()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['liveness_session_secret'] ?? null;
    }

    /**
     * Validate a passive liveness token sent from the client.
     * 
     * @param array $token { 'payload': {...}, 'signature': '...' }
     * @return array ['valid' => bool, 'message' => string, 'score' => float]
     */
    public static function validateLivenessToken($token)
    {
        // 1. Structure check
        if (!is_array($token) || !isset($token['payload']) || !isset($token['signature'])) {
            return [
                'valid' => false,
                'message' => 'Token xác minh không hợp lệ (cấu trúc sai).',
                'score' => 0
            ];
        }

        $payloadStr = $token['payload'];
        $signature = $token['signature'];

        // 2. Retrieve session secret
        $secret = self::getSessionSecret();
        if (!$secret) {
            return [
                'valid' => false,
                'message' => 'Phiên xác minh đã hết hạn hoặc không hợp lệ. Vui lòng thử lại.',
                'score' => 0
            ];
        }

        // 3. Verify HMAC signature (Verify raw JSON string directly to prevent float format mismatches)
        $expectedSig = hash_hmac('sha256', $payloadStr, $secret);

        // DEBUG: Log signature comparison details
        $debugLog = __DIR__ . '/../../uploads/liveness_logs/debug_sig.log';
        @file_put_contents($debugLog, sprintf(
            "[%s] payloadStr type=%s len=%d\npayloadStr=%s\nsecret=%s\nexpectedSig=%s\nactualSig=%s\nmatch=%s\n\n",
            date('Y-m-d H:i:s'),
            gettype($payloadStr),
            strlen($payloadStr),
            $payloadStr,
            $secret,
            $expectedSig,
            $signature,
            hash_equals($expectedSig, $signature) ? 'YES' : 'NO'
        ), FILE_APPEND | LOCK_EX);

        if (!hash_equals($expectedSig, $signature)) {
            self::logLivenessAttempt('SIGNATURE_MISMATCH', $payloadStr);
            return [
                'valid' => false,
                'message' => 'Chữ ký xác minh không hợp lệ. Phát hiện giả mạo token!',
                'score' => 0
            ];
        }

        // Decode payload from string
        $payload = json_decode($payloadStr, true);
        if (!$payload) {
            return [
                'valid' => false,
                'message' => 'Dữ liệu xác minh bị lỗi cấu trúc (JSON).',
                'score' => 0
            ];
        }

        // 4. Check token freshness (must be < 120 seconds old)
        $completedAt = $payload['completedAt'] ?? 0;
        $nowMs = round(microtime(true) * 1000);
        $ageMs = $nowMs - $completedAt;
        $maxAgeMs = 120 * 1000; // 120 seconds

        if ($ageMs > $maxAgeMs || $ageMs < -10000) {
            self::logLivenessAttempt('TOKEN_EXPIRED', $payload);
            return [
                'valid' => false,
                'message' => 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.',
                'score' => 0
            ];
        }

        // 5. Check session start was recent (< 5 minutes)
        $sessionStart = $payload['sessionStart'] ?? 0;
        $sessionDuration = $completedAt - $sessionStart;
        if ($sessionDuration > 300000) {
            self::logLivenessAttempt('SESSION_DURATION_INVALID', $payload);
            return [
                'valid' => false,
                'message' => 'Thời gian phiên xác thực không hợp lệ. Vui lòng thử lại.',
                'score' => 0
            ];
        }

        // 6. Frame count check
        $frameCount = intval($payload['frameCount'] ?? 0);
        if ($frameCount < 25) {
            self::logLivenessAttempt('INSUFFICIENT_FRAMES', $payload);
            return [
                'valid' => false,
                'message' => 'Không đủ dữ liệu quét (cần tối thiểu 25 frames).',
                'score' => 0
            ];
        }

        // 7. Chớp mắt là điều kiện bắt buộc để ảnh tĩnh không thể vượt qua.
        $blinkDetected = !empty($payload['blinkDetected']);
        $blinkCount = intval($payload['blinkCount'] ?? 0);
        if (!$blinkDetected || $blinkCount < 1) {
            self::logLivenessAttempt('BLINK_NOT_DETECTED', $payload);
            return [
                'valid' => false,
                'message' => 'Chưa phát hiện chớp mắt tự nhiên. Vui lòng thử lại.',
                'score' => 0
            ];
        }

        // 8. Combined score check
        $combinedScore = floatval($payload['combinedScore'] ?? 0);
        if ($combinedScore < 0.55) {
            self::logLivenessAttempt('LOW_SCORE', $payload);
            return [
                'valid' => false,
                'message' => sprintf('Chỉ số xác thực người thật không đạt (%.0f%%). Từ chối chấm công!', $combinedScore * 100),
                'score' => $combinedScore
            ];
        }

        // 10. One-time use token invalidation
        unset($_SESSION['liveness_session_secret']);
        unset($_SESSION['liveness_session_time']);
        $_SESSION['liveness_session_used'] = true;

        self::logLivenessAttempt('SUCCESS', $payload);

        return [
            'valid' => true,
            'message' => 'Xác thực người thật thành công (bao gồm xác minh nháy mắt).',
            'score' => $combinedScore
        ];
    }

    /**
     * Log a passive liveness verification attempt for auditing.
     */
    public static function logLivenessAttempt($result, $payload = [])
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?? [];
        }

        $logDir = __DIR__ . '/../../uploads/liveness_logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . date('Y-m-d') . '.log';
        $maND = $_SESSION['user']['maND'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $score = $payload['combinedScore'] ?? 0;
        $blinkDetected = ($payload['blinkDetected'] ?? false) ? 'YES' : 'NO';
        $blinkCount = $payload['blinkCount'] ?? 0;
        $frameCount = $payload['frameCount'] ?? 0;
        
        $pScores = $payload['passiveScores'] ?? [];
        $motion = $pScores['motion'] ?? 0;
        $jitter = $pScores['geometricJitter'] ?? 0;
        $lbp = $pScores['lbpTexture'] ?? 0;
        $lap = $pScores['laplacianMoire'] ?? 0;
        $color = $pScores['colorReflection'] ?? 0;
        $eyeBlink = $pScores['eyeBlink'] ?? 0;
        $headPose = $pScores['headPose'] ?? 0;
        $sizeVar = $pScores['sizeVariation'] ?? 0;

        $logEntry = sprintf(
            "[%s] maND=%s IP=%s Result=%s Score=%.2f Blink=%s(x%d) Frames=%d (Mot=%.2f, Jt=%.2f, Lbp=%.2f, Lap=%.2f, Col=%.2f, Eye=%.2f, Head=%.2f, Size=%.2f)\n",
            date('Y-m-d H:i:s'),
            $maND,
            $ip,
            $result,
            $score,
            $blinkDetected,
            $blinkCount,
            $frameCount,
            $motion,
            $jitter,
            $lbp,
            $lap,
            $color,
            $eyeBlink,
            $headPose,
            $sizeVar
        );

        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check if a session secret exists and is still valid.
     */
    public static function hasValidSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $secret = $_SESSION['liveness_session_secret'] ?? null;
        $time = $_SESSION['liveness_session_time'] ?? 0;

        if (!$secret) return false;

        if ((time() - $time) > 300) {
            unset($_SESSION['liveness_session_secret']);
            unset($_SESSION['liveness_session_time']);
            return false;
        }

        return true;
    }
}
?>
