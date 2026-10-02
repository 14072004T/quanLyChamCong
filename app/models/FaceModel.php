<?php
require_once 'app/models/ketNoi.php';

class FaceModel
{
    private $conn;

    public function __construct()
    {
        $db = new KetNoi();
        $this->conn = $db->connect();
    }

    /**
     * Láº¥y profile khuÃ´n máº·t cá»§a ngÆ°á»i dÃ¹ng
     * @param int $maND
     * @return array|null
     */
    public function getFaceProfile($maND)
    {
        $sql = "SELECT id, maND, embedding, embedding_front, embedding_left, embedding_right, embedding_arcface, ngayTao, ngayCapNhat FROM face_profile WHERE maND = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * ÄÄƒng kÃ½ hoáº·c cáº­p nháº­t embedding khuÃ´n máº·t cho ngÆ°á»i dÃ¹ng
     * @param int $maND
     * @param string $embeddingJson
     * @return bool
     */
    public function saveFaceProfile($maND, $embeddingJson, $frontJson = null, $leftJson = null, $rightJson = null, $arcFaceJson = null)
    {
        $existing = $this->getFaceProfile($maND);
        if ($existing) {
            $sql = "UPDATE face_profile SET embedding = ?, embedding_front = ?, embedding_left = ?, embedding_right = ?, embedding_arcface = ? WHERE maND = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("sssssi", $embeddingJson, $frontJson, $leftJson, $rightJson, $arcFaceJson, $maND);
        } else {
            $sql = "INSERT INTO face_profile (maND, embedding, embedding_front, embedding_left, embedding_right, embedding_arcface) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("isssss", $maND, $embeddingJson, $frontJson, $leftJson, $rightJson, $arcFaceJson);
        }
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * XÃ³a profile khuÃ´n máº·t cá»§a ngÆ°á»i dÃ¹ng
     * @param int $maND
     * @return bool
     */
    public function deleteFaceProfile($maND)
    {
        $sql = "DELETE FROM face_profile WHERE maND = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $maND);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Láº¥y danh sÃ¡ch táº¥t cáº£ profile khuÃ´n máº·t ngoáº¡i trá»« cá»§a maND hiá»‡n táº¡i
     * @param int|null $excludeMaND
     * @return array
     */
    public function getAllFaceProfiles($excludeMaND = null)
    {
        $profiles = [];
        if ($excludeMaND !== null) {
            $sql = "SELECT id, maND, embedding, embedding_front, embedding_left, embedding_right, embedding_arcface FROM face_profile WHERE maND != ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param("i", $excludeMaND);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $profiles[] = $row;
            }
            $stmt->close();
        } else {
            $sql = "SELECT id, maND, embedding, embedding_front, embedding_left, embedding_right, embedding_arcface FROM face_profile";
            $result = $this->conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $profiles[] = $row;
                }
            }
        }
        return $profiles;
    }

    /**
     * Láº¥y há» tÃªn nhÃ¢n viÃªn tá»« báº£ng nguoidung
     * @param int $maND
     * @return string
     */
    public function getUserName($maND)
    {
        $sql = "SELECT hoTen FROM nguoidung WHERE maND = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 'ID: ' . $maND;
        }
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? $res['hoTen'] : 'ID: ' . $maND;
    }

    /**
     * Lấy profile khuôn mặt V2 (ArcFace 512-dim) của người dùng
     */
    public function getFaceProfileV2($maND)
    {
        $sql = "SELECT id, maND, embedding_arcface, ngayTao, ngayCapNhat FROM face_profile WHERE maND = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
    * Lưu hoặc cập nhật embedding ArcFace (512 chiều)
     */
    public function saveFaceProfileV2($maND, $embeddingV2Json)
    {
        $existing = $this->getFaceProfile($maND);
        if ($existing) {
            $sql = "UPDATE face_profile SET embedding_arcface = ? WHERE maND = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("si", $embeddingV2Json, $maND);
        } else {
            $sql = "INSERT INTO face_profile (maND, embedding, embedding_arcface) VALUES (?, '', ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param("is", $maND, $embeddingV2Json);
        }
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Lấy tất cả profile V2 (ArcFace 512-dim)
     */
    public function getAllFaceProfilesV2($excludeMaND = null)
    {
        $profiles = [];
        if ($excludeMaND !== null) {
            $sql = "SELECT id, maND, embedding_arcface FROM face_profile WHERE maND != ? AND embedding_arcface IS NOT NULL";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param("i", $excludeMaND);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $profiles[] = $row;
            }
            $stmt->close();
        } else {
            $sql = "SELECT id, maND, embedding_arcface FROM face_profile WHERE embedding_arcface IS NOT NULL";
            $result = $this->conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $profiles[] = $row;
                }
            }
        }
        return $profiles;
    }
}

