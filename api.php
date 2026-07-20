<?php
require_once 'config.php';
checkLogin();

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Lấy thông tin User hiện tại
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi xác thực người dùng.']);
    exit;
}

if ($action === 'init') {
    // Lấy danh sách sản phẩm
    $stProd = $db->query("SELECT * FROM products ORDER BY price_bth ASC");
    $products = $stProd->fetchAll();
    
    // Lấy đơn nạp pending
    $stDep = $db->prepare("SELECT * FROM nap_tien WHERE user_id = ? AND status = 0 ORDER BY id DESC LIMIT 1");
    $stDep->execute([$user_id]);
    $pending_deposit = $stDep->fetch();
    
    // Lịch sử mua hàng
    $stKeys = $db->prepare("SELECT * FROM history_buy WHERE user_id = ? ORDER BY id DESC");
    $stKeys->execute([$user_id]);
    $keys = $stKeys->fetchAll() ?: [];
    
    $stats = [
        'total' => count($keys),
        'active' => count($keys), // Tương lai cần so sánh date
        'expired' => 0
    ];
    
    echo json_encode([
        'status' => 'success',
        'user' => [
            'username' => $user['username'],
            'rank' => $user['rank'],
            'balance' => $user['balance']
        ],
        'products' => $products,
        'keys' => $keys,
        'stats' => $stats,
        'pending_deposit' => $pending_deposit
    ]);
    exit;
}

if ($action === 'buy') {
    $product_code = $_POST['product_code'] ?? '';
    
    $stProd = $db->prepare("SELECT * FROM products WHERE code = ?");
    $stProd->execute([$product_code]);
    $product = $stProd->fetch();
    
    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại!']);
        exit;
    }
    
    $price = $product['price_bth'];
    if ($user['rank'] === 'seller') $price = $product['price_seller'];
    if ($user['rank'] === 'sellervip') $price = $product['price_sellervip'];
    
    if ($user['balance'] < $price) {
        echo json_encode(['status' => 'error', 'message' => 'Tài khoản không đủ tiền!']);
        exit;
    }
    
    // Tìm key chưa bán
    $stKey = $db->prepare("SELECT * FROM keys_store WHERE product_code = ? AND status = 0 LIMIT 1");
    $stKey->execute([$product_code]);
    $keyItem = $stKey->fetch();
    
    if (!$keyItem) {
        echo json_encode(['status' => 'error', 'message' => 'Sản phẩm đã hết Key!']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Trừ tiền
        $new_balance = $user['balance'] - $price;
        $db->prepare("UPDATE users SET balance = ? WHERE id = ?")->execute([$new_balance, $user_id]);
        
        // Cập nhật Key (Đã bán)
        $db->prepare("UPDATE keys_store SET status = 1, sold_to = ?, sold_at = NOW() WHERE id = ?")->execute([$user_id, $keyItem['id']]);
        
        // Lưu lịch sử
        $db->prepare("INSERT INTO history_buy (user_id, item_name, key_code, price) VALUES (?, ?, ?, ?)")
           ->execute([$user_id, $product['name'], $keyItem['key_code'], $price]);
        
        $db->commit();
        echo json_encode(['status' => 'success', 'key' => $keyItem['key_code']]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống trong quá trình mua!']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
