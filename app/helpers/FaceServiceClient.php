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
    private static function getArcFaceServiceUrl()
    {
        return rtrim($_ENV['ARCFACE_EMBED_URL'] ?? 'https://arcface.acacy.com.vn/embed', '/');
    }

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

    /**
     * Gửi ảnh lên ArcFace service và trả về vector embedding 512 chiều.
     * @param string $imageBase64 Data URL hoặc chuỗi base64 của ảnh
     * @return array
     */
    public static function getArcFaceEmbedding($imageBase64)
    {
        if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $imageBase64, $matches)) {
            return ['success' => false, 'message' => 'Ảnh gửi tới ArcFace không hợp lệ.'];
        }

        $encodedImage = substr($imageBase64, strpos($imageBase64, ',') + 1);
        $imageBytes = base64_decode(str_replace(' ', '+', $encodedImage), true);
        if ($imageBytes === false || $imageBytes === '') {
            return ['success' => false, 'message' => 'Không thể giải mã ảnh gửi tới ArcFace.'];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'arcface_');
        if ($tempPath === false || file_put_contents($tempPath, $imageBytes) === false) {
            if ($tempPath !== false) @unlink($tempPath);
            return ['success' => false, 'message' => 'Không thể chuẩn bị ảnh gửi tới ArcFace.'];
        }

        $mimeType = strtolower($matches[1]) === 'png' ? 'image/png' : (strtolower($matches[1]) === 'webp' ? 'image/webp' : 'image/jpeg');
        $curl = curl_init(self::getArcFaceServiceUrl());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($tempPath, $mimeType, 'face.' . strtolower($matches[1]))
        ]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        @unlink($tempPath);

        if ($curlError) {
            return ['success' => false, 'message' => 'Không thể kết nối ArcFace service: ' . $curlError];
        }
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'ArcFace service báo lỗi (HTTP ' . $httpCode . ').'];
        }

        $data = json_decode($response, true);
        $embedding = $data;
        if (is_array($data) && isset($data['embedding'])) {
            $embedding = $data['embedding'];
        } elseif (is_array($data) && isset($data['data']['embedding'])) {
            $embedding = $data['data']['embedding'];
        } elseif (is_array($data) && isset($data['vector'])) {
            $embedding = $data['vector'];
        }

        if (is_string($embedding)) {
            $embedding = json_decode($embedding, true);
        }
        if (!is_array($embedding) || count($embedding) !== 512) {
            return ['success' => false, 'message' => 'ArcFace service trả về embedding không hợp lệ.'];
        }
        foreach ($embedding as $value) {
            if (!is_numeric($value) || !is_finite((float)$value)) {
                return ['success' => false, 'message' => 'ArcFace service trả về embedding không hợp lệ.'];
            }
        }

        return ['success' => true, 'embedding' => array_map('floatval', array_values($embedding))];
    }
}
