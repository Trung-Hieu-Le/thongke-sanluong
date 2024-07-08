<?php
// Kết nối đến cơ sở dữ liệu
include '../ConnectDB.php';

// Lấy ngày hôm nay
$today = date('dmY');

// Truy vấn để lấy tất cả các cặp ma_tram và ten_hang_muc
$sql = "SELECT DISTINCT ma_tram, ten_hang_muc FROM tbl_hinhanh
    WHERE ten_anh_da_xong <> '' AND thoi_gian_chup=$today";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ma_tram = $row['ma_tram'];
        $ten_hang_muc = $row['ten_hang_muc'];

        // Truy vấn để lấy thời gian chụp nhỏ nhất
        // $sql_min_time = $conn->prepare("SELECT MIN(STR_TO_DATE(thoi_gian_chup, '%d%m%Y %H%i%s')) as min_time 
        //                                 FROM tbl_hinhanh 
        //                                 WHERE ma_tram = ? AND ten_hang_muc = ? AND DATE_FORMAT(STR_TO_DATE(thoi_gian_chup, '%d%m%Y %H%i%s'), '%d%m%Y') = ?");
        $sql_min_time = $conn->prepare("SELECT MIN(thoi_gian_chup) as min_time 
                                        FROM tbl_hinhanh 
                                        WHERE ma_tram = ? AND ten_hang_muc = ?");
        $sql_min_time->bind_param("ss", $ma_tram, $ten_hang_muc);
        $sql_min_time->execute();
        $result_min_time = $sql_min_time->get_result();

        if ($result_min_time->num_rows > 0) {
            $row_min_time = $result_min_time->fetch_assoc();
            $min_time = $row_min_time['min_time'];

            // Cập nhật các ảnh có thời gian chụp khác thời gian nhỏ nhất
            $sql_update = $conn->prepare("UPDATE tbl_hinhanh 
                                          SET loc_dau_viec_trung = CASE 
                                              WHEN thoi_gian_chup = ? THEN 'Khong Trung' 
                                              ELSE 'Trung' 
                                          END 
                                          WHERE ma_tram = ? AND ten_hang_muc = ? AND thoi_gian_chup = ?");
            $sql_update->bind_param("ssss", $min_time, $ma_tram, $ten_hang_muc, $today);
            $sql_update->execute();
        } else {
            echo "Không tồn tại ảnh";
        }

        $sql_min_time->close();
    }
} else {
    echo "Vui lòng cung cấp đủ thông tin";
}

$conn->close();
?>
