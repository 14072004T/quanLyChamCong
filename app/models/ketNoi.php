<?php
class KetNoi {
    // === THAY ĐỔI THÔNG TIN KẾT NỐI CỦA BẠN Ở ĐÂY ===
    private $host = "localhost";
    private $user = "root";
    private $pass = "";     // Mật khẩu XAMPP của bạn (thường là rỗng)
    
    // Tên CSDL bạn đã import (ví dụ: QLAo)

    private $dbname = "dl_final";
    // =============================================

    private $conn; // Biến để giữ kết nối

    /**
     * Hàm kết nối CSDL
     * @return mysqli
     */
    public function connect() {
        // Nếu đã kết nối rồi, dùng lại kết nối cũ
        if ($this->conn) {
            return $this->conn;
        }

        // Tạo kết nối mới
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        // Kiểm tra lỗi kết nối
        if ($this->conn->connect_error) {
            // Ghi log và ném exception để lớp gọi (controller/model) có thể bắt và xử lý
            error_log("Kết nối DB thất bại: " . $this->conn->connect_error);
            throw new Exception("Lỗi kết nối hệ thống. Vui lòng thử lại sau.");
        }

        // Thiết lập UTF-8 để lưu tiếng Việt
        $this->conn->set_charset("utf8mb4");

        // Trả về kết nối
        return $this->conn;
    }

    /**
     * Hàm đóng kết nối (nếu cần)
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>