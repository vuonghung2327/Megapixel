<?php
require_once 'config.php';
checkLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Tạo mã nạp tiền (Cú pháp: NAP_USERNAME_ID)
$tranCode = "NAP_" . strtoupper($username) . "_" . $user_id;

$bankInfo = [
    'bank' => 'MBBank',
    'stk' => '0123456789',
    'name' => 'NGUYEN VAN A'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp tiền tự động - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .deposit-box { background: var(--bg-card); padding: 30px; border-radius: 12px; max-width: 400px; margin: 40px auto; border: 1px solid var(--border-color); text-align: center; }
        .qr-img { width: 200px; height: 200px; border-radius: 10px; margin: 20px auto; border: 3px solid var(--primary-color); padding: 5px; background: white; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-muted); }
        .info-value { font-weight: bold; color: var(--primary-color); }
        .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: var(--bg-input); color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="deposit-box">
        <h2 style="margin-bottom: 10px;">NẠP TIỀN TỰ ĐỘNG</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Quét mã QR dưới đây bằng app Ngân Hàng để nạp tiền.</p>
        
        <?php
        // API tạo mã VietQR
        $qrUrl = "https://img.vietqr.io/image/{$bankInfo['bank']}-{$bankInfo['stk']}-compact2.png?amount=0&addInfo={$tranCode}&accountName={$bankInfo['name']}";
        ?>
        <img src="<?php echo $qrUrl; ?>" class="qr-img" alt="QR Code">
        
        <div class="info-row">
            <span class="info-label">Ngân hàng</span>
            <span class="info-value"><?php echo $bankInfo['bank']; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Số tài khoản</span>
            <span class="info-value"><?php echo $bankInfo['stk']; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Tên người nhận</span>
            <span class="info-value"><?php echo $bankInfo['name']; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Nội dung (BẮT BUỘC)</span>
            <span class="info-value" style="color: #ffcc00; font-size: 16px;"><?php echo $tranCode; ?></span>
        </div>
        
        <p style="margin-top: 15px; font-size: 12px; color: #ff3b30;">Hệ thống sẽ tự động duyệt tiền từ 1-3 phút. Tuyệt đối không thay đổi nội dung chuyển khoản!</p>
        
        <a href="index.php" class="back-btn">Quay lại trang chủ</a>
    </div>
</body>
</html>
