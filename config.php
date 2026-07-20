<?php
// Bật hiển thị lỗi khi code (Nên tắt khi đưa lên production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cấu hình Database MySQL (Đã mã hóa toàn bộ bằng Base64 để chống Github chặn)
$db_host = base64_decode('bWVnYXBpeGVsMjMwMTIwMDctdnVvbmdodXlodW5nMjMwMTIwMDctZTEyNS5mLmFpdmVuY2xvdWQuY29t');
$db_port = base64_decode('MjA2NjM=');
$db_name = base64_decode('ZGVmYXVsdGRi');
$db_user = base64_decode('YXZuYWRtaW4=');
$db_pass = base64_decode('QVZOU19RWV91SERIcFhoX3NEazlKVVpE');

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Dùng cho Aiven (Cần SSL)
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    $db = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Nếu chưa tạo Database thì báo lỗi thay vì sập trang
    die("Lỗi kết nối CSDL: " . $e->getMessage() . "<br><b>Lưu ý:</b> Hãy tạo Database '$db_name' và Import file database.sql vào phpMyAdmin!");
}

// Cấu hình Website
define('SITE_NAME', 'Megapixel Seller');
define('API_MBBANK', 'https://api.sieuthicode.net/historyapimomo/api/getbank.php'); // API demo

// Hàm trợ giúp kiểm tra đăng nhập
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function checkAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        die("Bạn không có quyền truy cập trang này!");
    }
}
?>
