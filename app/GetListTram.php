<?php
// Kết nối đến cơ sở dữ liệu
include '../ConnectDB.php';

// Lấy mảng du_lieu từ request POST
$du_lieu_array = $_POST['du_lieu'];

// Kiểm tra xem mảng đã được cung cấp chưa
if (!empty($du_lieu_array)) {
    // Khởi tạo mảng bind_params
    $bind_params = array();
    // Khởi tạo mảng dữ liệu chung
    $data = array();

    // Tạo chuỗi dấu hỏi và chuỗi định nghĩa kiểu dữ liệu
    $placeholders_ma_tram = '';
    $placeholders_thoi_gian_chup = '';
    $types = '';

    foreach ($du_lieu_array as $du_lieu) {
        // Thêm giá trị vào mảng bind_params
        $ma_tram = $du_lieu['ma_tram'];
        $thoi_gian_chup = $du_lieu['thoi_gian_chup'];
        $bind_params[] = $ma_tram;
        $bind_params[] = $thoi_gian_chup;
        
        // Thêm placeholder và kiểu dữ liệu
        $placeholders_ma_tram .= '?,';
        $placeholders_thoi_gian_chup .= '?,';
        $types .= 'ss';
    }

    // Loại bỏ dấu ',' cuối cùng từ chuỗi placeholders
    $placeholders_ma_tram = rtrim($placeholders_ma_tram, ',');
    $placeholders_thoi_gian_chup = rtrim($placeholders_thoi_gian_chup, ',');

    // Tạo truy vấn gộp (JOIN)
    $sql = "SELECT 
            DISTINCT ha.ma_tram,
            ha.ten_hang_muc,
            ha.hinhanh_id,
            ha.HopDong_Id,
            ha.loc_dau_viec_trung,
            ha.thoi_gian_chup,
            ha.ten_anh_da_xong,
            ha.ten_anh_chuan_bi,
            ha.anh_bo_sung_1,
            ha.anh_bo_sung_2,
            ha.anh_bo_sung_3,
            ha.anh_bo_sung_4,
            ha.anh_bo_sung_5,
            ha.anh_bo_sung_6,            
            cv.CongViec_Gia
        FROM tbl_hinhanh AS ha
        LEFT JOIN tbl_congviec AS cv 
             ON REGEXP_REPLACE(ha.ten_hang_muc, '[^a-zA-Z0-9]', '') = REGEXP_REPLACE(cv.CongViec_Ten, '[^a-zA-Z0-9]', '') AND ha.da_gui_len_drive = '1'
        WHERE (ha.ma_tram = ? AND ha.thoi_gian_chup = ?)";

    // Thêm điều kiện cho các cặp ma_tram và thoi_gian_chup khác nếu có
    for ($i = 1; $i < count($du_lieu_array); $i++) {
        $sql .= " OR (ha.ma_tram = ? AND ha.thoi_gian_chup = ?)";
    }
    $sql .= " GROUP BY CONCAT(ha.ma_tram, '-', ha.thoi_gian_chup, '-', SUBSTRING_INDEX(ha.ten_anh_da_xong, '-', 1), '-', SUBSTRING_INDEX(ha.ten_anh_chuan_bi, '-', 1))";

    // Tạo và thực thi truy vấn
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$bind_params);
        $stmt->execute();
        $result = $stmt->get_result();

        // Kiểm tra và xử lý kết quả
        if ($result->num_rows > 0) {
            // Lặp qua từng hàng kết quả
            while ($row = $result->fetch_assoc()) 
			{
				// Lưu thông tin của mỗi hàng vào mảng dữ liệu chung
                $data[] = $row;

            }
			 // Trả về dữ liệu dưới dạng JSON
			echo json_encode($data);
        }
        $stmt->close();
    }
}

// Đóng kết nối đến cơ sở dữ liệu
$conn->close();
?>
