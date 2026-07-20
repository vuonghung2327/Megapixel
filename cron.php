<?php
// CRONJOB: Xử lý cộng tiền tự động. Set chạy mỗi 1 phút trên Hosting.
require_once 'config.php';

// Lấy lịch sử ngân hàng từ API bên thứ 3 (Ví dụ SieuthiCode)
// URL chứa Token/API Key
$api_url = API_MBBANK . "?token=YOUR_API_TOKEN_HERE";

try {
    $response = @file_get_contents($api_url);
    if (!$response) die("Khong the ket noi API");
    
    $data = json_decode($response, true);
    
    if (isset($data['status']) && $data['status'] == 200) {
        $transactions = $data['data'] ?? [];
        
        foreach ($transactions as $tran) {
            $amount = $tran['amount'];
            $content = strtoupper($tran['content']);
            $tran_id = $tran['tranId'];
            
            // Tìm cú pháp NAP_USERNAME_ID trong nội dung (Ví dụ: NAP DEMO 2)
            if (preg_match('/NAP_([A-Z0-9]+)_(\d+)/', $content, $matches)) {
                $user_id = $matches[2];
                
                // Kiểm tra xem mã giao dịch này đã cộng chưa để tránh cộng đúp
                $stmt = $db->prepare("SELECT id FROM nap_tien WHERE code = ?");
                $stmt->execute([$tran_id]);
                
                if (!$stmt->fetch()) {
                    // Chưa xử lý -> Tiến hành cộng tiền
                    $db->beginTransaction();
                    
                    // 1. Lưu log giao dịch
                    $db->prepare("INSERT INTO nap_tien (user_id, amount, code, status, updated_at) VALUES (?, ?, ?, 1, NOW())")
                       ->execute([$user_id, $amount, $tran_id]);
                       
                    // 2. Cộng tiền cho User
                    $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
                       ->execute([$amount, $user_id]);
                       
                    $db->commit();
                    echo "Da cong $amount cho User ID: $user_id (Tran: $tran_id)<br>";
                }
            }
        }
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Lỗi: " . $e->getMessage();
}

echo "Cron hoan tat lúc " . date('Y-m-d H:i:s');
?>
