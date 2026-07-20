<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $repassword = $_POST['repassword'];
    
    if (empty($username) || empty($password) || empty($repassword)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif ($password !== $repassword) {
        $error = "Mật khẩu nhập lại không khớp!";
    } elseif (strlen($username) < 4 || strlen($password) < 6) {
        $error = "Tài khoản >= 4 ký tự và mật khẩu >= 6 ký tự!";
    } else {
        // Kiểm tra trùng
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Tên đăng nhập đã tồn tại!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if ($stmt->execute([$username, $hashed])) {
                $success = "Đăng ký thành công! Vui lòng đăng nhập.";
            } else {
                $error = "Đã xảy ra lỗi, vui lòng thử lại!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Đăng ký - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; }
        .auth-box { background: var(--bg-card); padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); width: 100%; max-width: 400px; margin: 20px; }
        .auth-title { text-align: center; font-size: 20px; font-weight: 700; margin-bottom: 25px; color: var(--text-main); }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 12px 15px; background: var(--bg-input); border: 1px solid var(--border-color); color: white; border-radius: 8px; outline: none; }
        .form-control:focus { border-color: var(--primary-color); }
        .btn-auth { width: 100%; padding: 12px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .error-msg { color: var(--danger-color); text-align: center; margin-bottom: 15px; font-size: 13px; }
        .success-msg { color: #34c759; text-align: center; margin-bottom: 15px; font-size: 13px; }
        .auth-link { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted); }
        .auth-link a { color: var(--primary-color); text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-box">
        <div class="auth-title">Đăng Ký Tài Khoản</div>
        <?php if ($error) echo "<div class='error-msg'>$error</div>"; ?>
        <?php if ($success) echo "<div class='success-msg'>$success</div>"; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
            </div>
            <div class="form-group">
                <input type="password" name="repassword" class="form-control" placeholder="Nhập lại mật khẩu" required>
            </div>
            <button type="submit" class="btn-auth">ĐĂNG KÝ</button>
        </form>
        <div class="auth-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </div>
    </div>
</body>
</html>
