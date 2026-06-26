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
     * Lấy profile khuôn mặt của người dùng
     * @param int $maND
     * @return array|null
     */
    public function getFaceProfile($maND)
    {
        $sql = "SELECT id, maND, embedding, ngayTao, ngayCapNhat FROM face_profiles WHERE maND = ?";
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
     * Đăng ký hoặc cập nhật embedding khuôn mặt cho người dùng
     * @param int $maND
     * @param string $embeddingJson
     * @return bool
     */
    public function saveFaceProfile($maND, $embeddingJson)
    {
        $existing = $this->getFaceProfile($maND);
        if ($existing) {
            $sql = "UPDATE face_profiles SET embedding = ? WHERE maND = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("si", $embeddingJson, $maND);
        } else {
            $sql = "INSERT INTO face_profiles (maND, embedding) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("is", $maND, $embeddingJson);
        }
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Xóa profile khuôn mặt của người dùng
     * @param int $maND
     * @return bool
     */
    public function deleteFaceProfile($maND)
    {
        $sql = "DELETE FROM face_profiles WHERE maND = ?";
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
     * Lấy danh sách tất cả profile khuôn mặt ngoại trừ của maND hiện tại
     * @param int|null $excludeMaND
     * @return array
     */
    public function getAllFaceProfiles($excludeMaND = null)
    {
        $profiles = [];
        if ($excludeMaND !== null) {
            $sql = "SELECT id, maND, embedding FROM face_profiles WHERE maND != ?";
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
            $sql = "SELECT id, maND, embedding FROM face_profiles";
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
     * Lấy họ tên nhân viên từ bảng nguoidung
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
        $stmt->close();
        return $res ? $res['hoTen'] : 'ID: ' . $maND;
    }
}
?>
