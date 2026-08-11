<?php
/**
 * FaceServiceClient — PHP Client gọi Python FastAPI Face Service
 * =============================================================
 * Thực hiện:
 *   1. Send image (base64) to Python FastAPI /verify
 *   2. Python FastAPI executes: MTCNN → MiniFASNet Anti-Spoofing → ArcFace (512-dim)
 *   3. Trả về kết quả xác thực & embedding
 */

class FaceServiceClient
{
    private static function getServiceUrl()
    {
        return rtrim($_ENV['FACE_SERVICE_URL'] ?? 'http://127.0.0.1:8000', '/');
    }

    private static function getApiKey()
    {
        return $_ENV['FACE_SERVICE_API_KEY'] ?? '';
    }

    /**
     * Gọi Python FastAPI /verify
     * @param string $imageBase64 - Base64 encoded image
     * @return array
     */
    public static function verifyFace($imageBase64)
    {
        $url = self::getServiceUrl() . '/verify';
        $payload = json_encode(['image' => $imageBase64]);

        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ];

        $apiKey = self::getApiKey();
        if (!empty($apiKey)) {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'success' => false,
                'message' => 'Không thể kết nối đến Python Face Service (cổng 8000): ' . $curlErr
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'message' => 'Python Face Service báo lỗi (HTTP ' . $httpCode . '): ' . substr($response, 0, 200)
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'Phản hồi từ Python Face Service bị lỗi cấu trúc.'
            ];
        }

        return $data;
    }

    /**
     * Gọi Python FastAPI /embedding
     * @param string $imageBase64
     * @return array
     */
    public static function getEmbedding($imageBase64)
    {
        $url = self::getServiceUrl() . '/embedding';
        $payload = json_encode(['image' => $imageBase64]);

        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ];

        $apiKey = self::getApiKey();
        if (!empty($apiKey)) {
            $headers[] = 'X-API-Key: ' . $apiKey;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'message' => 'Lỗi kết nối Python Service khi lấy embedding.'
            ];
        }

        return json_decode($response, true) ?? ['success' => false, 'message' => 'Invalid JSON'];
    }
}
