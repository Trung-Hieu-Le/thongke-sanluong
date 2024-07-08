<?php
$servername = "localhost:3306";
$username = "root";
$password = "";
$dbname = "ec"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
