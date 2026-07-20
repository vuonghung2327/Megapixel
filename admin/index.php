<?php
require_once '../config.php';
checkLogin();
checkAdmin();

// Xử lý các Form Hành Động
$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Thêm Sản Phẩm
    if ($action == 'add_product') {
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $category = trim($_POST['category']);
        $price_bth = (int)$_POST['price_bth'];
        $price_seller = (int)$_POST['price_seller'];
        $price_sellervip = (int)$_POST['price_sellervip'];
        $badge = trim($_POST['badge']);
        
        $stmt = $db->prepare("INSERT INTO products (code, name, category, badge, price_bth, price_seller, price_sellervip) VALUES (?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$code, $name, $category, $badge, $price_bth, $price_seller, $price_sellervip]);
            $msg = "<div style='color:#34c759; margin-bottom:15px;'>Thêm sản phẩm thành công!</div>";
        } catch(Exception $e) {
            $msg = "<div style='color:#ff3b30; margin-bottom:15px;'>Lỗi thêm sản phẩm (Có thể trùng Code)</div>";
        }
    }
    
    // Thêm Keys
    if ($action == 'add_keys') {
        $product_code = trim($_POST['product_code']);
        $key_list = explode("\n", trim($_POST['key_list']));
        
        $count = 0;
        $stmt = $db->prepare("INSERT INTO keys_store (product_code, key_code) VALUES (?, ?)");
        foreach($key_list as $k) {
            $k = trim($k);
            if ($k) {
                $stmt->execute([$product_code, $k]);
                $count++;
            }
        }
        $msg = "<div style='color:#34c759; margin-bottom:15px;'>Đã thêm $count Key thành công!</div>";
    }
    
    // Cộng / Trừ / Sửa User
    if ($action == 'edit_user') {
        $uid = (int)$_POST['user_id'];
        $new_balance = (int)$_POST['balance'];
        $new_rank = trim($_POST['rank']);
        
        $db->prepare("UPDATE users SET balance = ?, rank = ? WHERE id = ?")->execute([$new_balance, $new_rank, $uid]);
        $msg = "<div style='color:#34c759; margin-bottom:15px;'>Cập nhật User ID $uid thành công!</div>";
    }
}

// Lấy thông kê
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalKeys = $db->query("SELECT COUNT(*) FROM keys_store WHERE status = 0")->fetchColumn(); // Key còn lại
$totalRevenue = $db->query("SELECT SUM(price) FROM history_buy")->fetchColumn() ?: 0;

// Lấy danh sách
$users = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 20")->fetchAll();
$products = $db->query("SELECT code, name, category FROM products ORDER BY id DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { padding: 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .card { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; }
        th { color: var(--text-muted); }
        .btn-sm { padding: 5px 10px; background: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h2>Admin Dashboard</h2>
        <a href="../index.php" style="color: var(--primary-color);">Quay lại trang chủ</a>
    </div>

    <?php if ($msg) echo $msg; ?>

    <div class="grid-4">
        <div class="card">
            <h3 style="color:var(--text-muted); font-size:13px;">Tổng User</h3>
            <div style="font-size:24px; font-weight:bold; margin-top:10px;"><?php echo $totalUsers; ?></div>
        </div>
        <div class="card">
            <h3 style="color:var(--text-muted); font-size:13px;">Tổng Sản Phẩm</h3>
            <div style="font-size:24px; font-weight:bold; margin-top:10px;"><?php echo $totalProducts; ?></div>
        </div>
        <div class="card">
            <h3 style="color:var(--text-muted); font-size:13px;">Tổng Key</h3>
            <div style="font-size:24px; font-weight:bold; margin-top:10px;"><?php echo $totalKeys; ?></div>
        </div>
        <div class="card">
            <h3 style="color:var(--text-muted); font-size:13px;">Doanh thu</h3>
            <div style="font-size:24px; font-weight:bold; margin-top:10px; color:#34c759;"><?php echo number_format($totalRevenue); ?>đ</div>
        </div>
    </div>

    <div class="grid-4" style="grid-template-columns: 1fr 1fr; margin-bottom: 30px;">
        <!-- Thêm Sản Phẩm -->
        <div class="card">
            <h3>Thêm Gói Game</h3>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="action" value="add_product">
                <input type="text" name="category" placeholder="Mã Game (vd: freefire)" required style="width:100%; padding:8px; margin-bottom:10px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                <input type="text" name="code" placeholder="Mã Gói (vd: ff_1d)" required style="width:100%; padding:8px; margin-bottom:10px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                <input type="text" name="name" placeholder="Tên Gói (vd: Gói 1 Ngày)" required style="width:100%; padding:8px; margin-bottom:10px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                
                <div style="display:flex; gap:10px; margin-bottom:10px;">
                    <input type="number" name="price_bth" placeholder="Giá Bình Thường" required style="width:100%; padding:8px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                    <input type="number" name="price_seller" placeholder="Giá Seller" required style="width:100%; padding:8px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                    <input type="number" name="price_sellervip" placeholder="Giá Seller VIP" required style="width:100%; padding:8px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                </div>
                
                <select name="badge" style="width:100%; padding:8px; margin-bottom:10px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                    <option value="NORMAL">NORMAL</option>
                    <option value="VIP">VIP</option>
                </select>
                <button type="submit" class="btn-sm" style="width:100%; padding:10px;">THÊM SẢN PHẨM</button>
            </form>
        </div>

        <!-- Thêm Key -->
        <div class="card">
            <h3>Thêm List Key</h3>
            <form method="POST" style="margin-top: 15px;">
                <input type="hidden" name="action" value="add_keys">
                <select name="product_code" required style="width:100%; padding:8px; margin-bottom:10px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px;">
                    <option value="">-- Chọn Gói Sản Phẩm --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?php echo $p['code']; ?>"><?php echo $p['category'] . ' - ' . $p['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="key_list" rows="7" placeholder="Mỗi dòng 1 key..." required style="width:100%; padding:8px; margin-bottom:10px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:5px; resize:none;"></textarea>
                <button type="submit" class="btn-sm" style="width:100%; padding:10px; background:#34c759;">THÊM KEYS VÀO KHO</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h3>Quản lý Người Dùng (20 mới nhất)</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Tài khoản</th>
                <th>Sửa Rank</th>
                <th>Sửa Số dư</th>
                <th>Hành động</th>
            </tr>
            <?php foreach($users as $u): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td>
                        <select name="rank" style="padding:5px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:4px;">
                            <option value="bth" <?php if($u['rank']=='bth') echo 'selected'; ?>>BTH</option>
                            <option value="seller" <?php if($u['rank']=='seller') echo 'selected'; ?>>SELLER</option>
                            <option value="sellervip" <?php if($u['rank']=='sellervip') echo 'selected'; ?>>SELLER VIP</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="balance" value="<?php echo $u['balance']; ?>" style="padding:5px; background:var(--bg-input); border:1px solid var(--border-color); color:white; border-radius:4px; width:100px;">
                    </td>
                    <td>
                        <button type="submit" class="btn-sm">Lưu</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
