<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Tài khoản hoặc mật khẩu không chính xác!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Đăng nhập - <?php echo SITE_NAME; ?></title>
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
        .auth-link { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted); }
        .auth-link a { color: var(--primary-color); text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-box">
        <div class="auth-title">Đăng Nhập</div>
        <?php if ($error) echo "<div class='error-msg'>$error</div>"; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
            </div>
            <button type="submit" class="btn-auth">ĐĂNG NHẬP</button>
        </form>
        <div class="auth-link">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>
    </div>
</body>
</html>
