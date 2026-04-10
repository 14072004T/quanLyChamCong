<?php
// Tên file: app/core/Controller.php

/*
 * Đây là LỚP CONTROLLER CƠ SỞ (BASE CONTROLLER)
 * Tất cả các controller khác sẽ kế thừa (extends) từ lớp này.
 */

class Controller {

    /**
     * Tải (load) một Model
     * Hàm này sẽ tìm file model trong app/models/
     */
    public function model($model) {
        // Kiểm tra xem file model có tồn tại không
        $modelPath = 'app/models/' . $model . '.php'; // Đường dẫn có thể cần điều chỉnh
        
        // Hoặc dùng đường dẫn tuyệt đối (tốt hơn) nếu bạn đã định nghĩa BASE_PATH
        // $modelPath = BASE_PATH . '/app/models/' . $model . '.php'; 

        if (file_exists($modelPath)) {
            require_once $modelPath;
            
            // Khởi tạo và trả về đối tượng Model
            // (Đảm bảo tên lớp Model khớp với tên file, ví dụ: KeHoachSanXuatModel)
            $modelName = $model; // Giả sử tên lớp = tên file
            if(class_exists($modelName)) {
                return new $modelName();
            }
        }
        
        // Nếu không tìm thấy, báo lỗi
        die("Model '{$model}' không tồn tại tại: {$modelPath}");
    }

    /**
     * Tải (load) một View
     * Hàm này sẽ tìm file view trong app/views/
     * $data là một mảng chứa dữ liệu để truyền cho view
     */
    public function view($view, $data = []) {
        // Biến các key của mảng $data thành các biến riêng lẻ
        // Ví dụ: $data['pageTitle'] sẽ trở thành biến $pageTitle
        extract($data);

        // Đường dẫn đến file view
        $viewPath = 'app/views/' . $view . '.php'; // Đường dẫn có thể cần điều chỉnh
        
        // Hoặc dùng đường dẫn tuyệt đối (tốt hơn)
        // $viewPath = BASE_PATH . '/app/views/' . $view . '.php';

        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            // Nếu không tìm thấy, báo lỗi
            die("View '{$view}' không tồn tại tại: {$viewPath}");
        }
    }
}