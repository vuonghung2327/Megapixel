<?php
error_reporting(0);
date_default_timezone_set('Asia/Ho_Chi_Minh');

 $token = "8775062713:AAG0QQullB9ExP2piYIG7AJPm2MgxCwEw5w";
 $admin_ids = [6449935441, 6675824018];
 $stk = "342866666";
 $bank_name = "MBBank";
 $chu_tk = "NGUYEN MINH PHUC";
 $api_bank = "https://thueapibank.vn/historyapitpbv2/7f6cc7b4f1893d49eea8247f4f35773a";

 $db_host = "localhost";
 $db_name = "fdquanc_phuc";
 $db_user = "fdquanc_phuc";
 $db_pass = "fdquanc_phuc";

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS users (chat_id BIGINT PRIMARY KEY, balance INT DEFAULT 0, rank VARCHAR(50) DEFAULT 'bth', status INT DEFAULT 0, action VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS keys_store (id INT AUTO_INCREMENT PRIMARY KEY, game_type VARCHAR(50), key_code VARCHAR(255) UNIQUE, status INT DEFAULT 0, added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS nap_tien (id INT AUTO_INCREMENT PRIMARY KEY, chat_id BIGINT, amount INT, content VARCHAR(255) UNIQUE, status INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, exp_time DATETIME)");
    $db->exec("CREATE TABLE IF NOT EXISTS history_buy (id INT AUTO_INCREMENT PRIMARY KEY, chat_id BIGINT, item_name VARCHAR(255), key_code VARCHAR(255), price INT, buy_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS products (code VARCHAR(50) PRIMARY KEY, name VARCHAR(255), price_bth INT, price_seller INT, price_sellervip INT, category VARCHAR(50) DEFAULT 'general')");
    
    if ($db->query("SELECT COUNT(*) FROM products")->fetchColumn() == 0) {
        $default_prods = [
            ['pato_red_1d','Pato Đỏ (1 Ngày)',40000,20000,15000,'pato'],
            ['pato_red_7d','Pato Đỏ (7 Ngày)',90000,50000,40000,'pato'],
            ['pato_red_15d','Pato Đỏ (15 Ngày)',140000,80000,65000,'pato'],
            ['pato_red_30d','Pato Đỏ (30 Ngày)',170000,110000,90000,'pato'],
            ['pato_blue_1d','Pato Xanh (1 Ngày)',30000,15000,12000,'pato'],
            ['pato_blue_7d','Pato Xanh (7 Ngày)',80000,40000,35000,'pato'],
            ['pato_blue_15d','Pato Xanh (15 Ngày)',120000,70000,55000,'pato'],
            ['pato_blue_30d','Pato Xanh (30 Ngày)',150000,95000,80000,'pato'],
            ['flu_ios_1d','Flu iOS (1 Ngày)',80000,55000,45000,'flu'],
            ['flu_ios_7d','Flu iOS (7 Ngày)',260000,180000,150000,'flu'],
            ['flu_ios_30d','Flu iOS (30 Ngày)',400000,280000,240000,'flu'],
            ['drip_1d','Drip (1 Ngày)',50000,25000,20000,'drip'],
            ['drip_7d','Drip (7 Ngày)',110000,60000,50000,'drip'],
            ['drip_15d','Drip (15 Ngày)',150000,85000,70000,'drip'],
            ['drip_30d','Drip (30 Ngày)',200000,120000,100000,'drip'],
            ['xthaxx_1d','Xthaxx (1 Ngày)',20000,12000,10000,'xthaxx'],
            ['xthaxx_7d','Xthaxx (7 Ngày)',60000,35000,28000,'xthaxx'],
            ['xthaxx_30d','Xthaxx (30 Ngày)',120000,75000,60000,'xthaxx'],
            ['br_pc_1d','Br PC (1 Ngày)',50000,35000,30000,'brpc'],
            ['br_pc_7d','Br PC (7 Ngày)',120000,85000,70000,'brpc'],
            ['br_pc_30d','Br PC (30 Ngày)',200000,145000,120000,'brpc'],
            ['prime_5d','Prime APK Mod (5 Ngày)',70000,45000,38000,'prime'],
            ['prime_10d','Prime APK Mod (10 Ngày)',120000,85000,70000,'prime']
        ];
        $st = $db->prepare("INSERT INTO products (code, name, price_bth, price_seller, price_sellervip, category) VALUES (?, ?, ?, ?, ?, ?)");
        foreach($default_prods as $p) $st->execute($p);
    }
} catch (PDOException $e) {
    exit("Lỗi kết nối CSDL");
}

 $update = json_decode(file_get_contents("php://input"), true);
checkAutoBank($api_bank, $db, $admin_ids, $token);
if (!$update) exit;

 $message = $update['message'] ?? null;
 $callback = $update['callback_query'] ?? null;
 $chat_id = $callback ? $callback['from']['id'] : ($message['chat']['id'] ?? null);
 $text = $message ? trim($message['text'] ?? "") : "";

if (!$chat_id) exit;

 $is_admin = in_array($chat_id, $admin_ids);

 $stmt = $db->prepare("SELECT * FROM users WHERE chat_id = ?");
 $stmt->execute([$chat_id]);
 $user = $stmt->fetch();
if (!$user) {
    $rank = $is_admin ? "ADMIN" : "bth";
    $db->prepare("INSERT INTO users (chat_id, rank) VALUES (?, ?)")->execute([$chat_id, $rank]);
    $user = ['chat_id' => $chat_id, 'balance' => 0, 'rank' => $rank, 'status' => 0, 'action' => null];
}

if ($user['status'] == 1 && !$is_admin) {
    send($chat_id, "🚫 *Tài khoản của bạn đã bị khóa.*\nVui lòng liên hệ Admin để được hỗ trợ.", $token);
    exit;
}

if ($callback) {
    $data = $callback['data'];
    answerCallback($callback['id'], $token);
    
    if ($data == "cancel_nap") {
        $db->prepare("UPDATE users SET action = NULL WHERE chat_id = ?")->execute([$chat_id]);
        send($chat_id, "❌ Đã hủy lệnh nạp tiền.", $token);
    } elseif ($data == "home") {
        showHome($chat_id, $user, $is_admin, $token);
    } elseif (strpos($data, "cat_") === 0) {
        showCategory($chat_id, str_replace("cat_", "", $data), $db, $user, $token);
    } elseif (strpos($data, "buy_") === 0) {
        muaHang($chat_id, str_replace("buy_", "", $data), $db, $user, $admin_ids, $token);
    }
    exit;
}

if ($user['action'] == "WAIT_AMOUNT") {
    if (!is_numeric($text) || (int)$text < 2000) {
        $cancel_btn = json_encode(['inline_keyboard' => [[['text' => '❌ Hủy Lệnh Nạp', 'callback_data' => 'cancel_nap']]]]);
        send($chat_id, "⚠️ *Số tiền không hợp lệ!*\nVui lòng nhập số tiền tối thiểu là *2,000đ*:", $token, $cancel_btn);
    } else {
        $amount = (int)$text;
        $random_hash = strtoupper(substr(md5(time() . $chat_id . rand(1000, 9999)), 0, 8));
        $nd = "NAP" . $chat_id . $random_hash;
        $exp = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $db->prepare("UPDATE users SET action = NULL WHERE chat_id = ?")->execute([$chat_id]);
        $db->prepare("INSERT INTO nap_tien (chat_id, amount, content, exp_time) VALUES (?, ?, ?, ?)")->execute([$chat_id, $amount, $nd, $exp]);
        
        $qr = "https://img.vietqr.io/image/$bank_name-$stk-compact2.png?amount=$amount&addInfo=$nd&accountName=".urlencode($chu_tk);
        $caption = "💳 *THÔNG TIN CHUYỂN KHOẢN TỰ ĐỘNG*\n━━━━━━━━━━━━━━━━━━\n💰 Số tiền: *".number_format($amount)."đ*\n📝 Nội dung: `$nd`\n🏦 Ngân hàng: *$bank_name*\n🔢 STK: `$stk`\n👤 Chủ TK: *$chu_tk*\n━━━━━━━━━━━━━━━━━━\n⏱ Hiệu lực trong *15 phút*.\n📌 Chuyển *ĐÚNG SỐ TIỀN* & *ĐÚNG NỘI DUNG*.\n✅ Hệ thống tự động cộng tiền sau 30s.";
        sendPhoto($chat_id, $qr, $caption, $token);
    }
    exit;
}

 $db->prepare("UPDATE users SET action = NULL WHERE chat_id = ?")->execute([$chat_id]);

if ($text == "/start" || $text == "🏠 Home") {
    showHome($chat_id, $user, $is_admin, $token);
} elseif ($text == "🛒 Mua Sản Phẩm") {
    showCategories($chat_id, $db, $token);
} elseif ($text == "💰 Nạp Tiền") {
    $db->prepare("UPDATE users SET action = 'WAIT_AMOUNT' WHERE chat_id = ?")->execute([$chat_id]);
    $cancel_btn = json_encode(['inline_keyboard' => [[['text' => '❌ Hủy', 'callback_data' => 'cancel_nap']]]]);
    send($chat_id, "💰 *VUI LÒNG NHẬP SỐ TIỀN CẦN NẠP:*\n_(Tối thiểu 2,000đ)_", $token, $cancel_btn);
} elseif ($text == "👤 Tài Khoản") {
    showAccountInfo($chat_id, $user, $token);
} elseif ($text == "🕒 Lịch Sử Mua") {
    showBuyHistory($chat_id, $db, $token);
} elseif ($text == "💳 Lịch Sử Nạp") {
    showDepositHistory($chat_id, $db, $token);
}

if ($is_admin) {
    handleAdminCommands($chat_id, $text, $db, $admin_ids, $token);
}

function showHome($chat_id, $user, $is_admin, $token) {
    $rank_icon = getRankIcon($user['rank']);
    $welcome = "🛍 *SHOP KEY TELEGRAM BOT PRO* 🛍\n━━━━━━━━━━━━━━━━━━\n👤 Chào mừng bạn đến với hệ thống mua bán Key tự động!\n🔥 Uy tín - Nhanh chóng - Bảo mật\n━━━━━━━━━━━━━━━━━━\n🔹 Rank: $rank_icon *{$user['rank']}*\n💸 Số dư: *".number_format($user['balance'])."đ*\n\n👇 Hãy chọn một tính năng bên dưới:";
    
    $kbd = [
        [['text' => "🛒 Mua Sản Phẩm"], ['text' => "💰 Nạp Tiền"]],
        [['text' => "👤 Tài Khoản"], ['text' => "🕒 Lịch Sử Mua"]],
        [['text' => "💳 Lịch Sử Nạp"]]
    ];
    if ($is_admin) $kbd[] = [['text' => "⚙️ Panel Admin"]];
    $kbd[] = [['text' => "🏠 Home"]];
    
    send($chat_id, $welcome, $token, json_encode(['keyboard' => $kbd, 'resize_keyboard' => true]));
}

function showCategories($chat_id, $db, $token) {
    $categories = $db->query("SELECT DISTINCT category, COUNT(*) as count FROM products GROUP BY category")->fetchAll();
    $btns = [];
    $cat_names = [
        'pato' => '🔴 Pato',
        'flu' => '📱 Flu iOS',
        'drip' => '💧 Drip',
        'xthaxx' => '⚡ Xthaxx',
        'brpc' => '💻 Br PC',
        'prime' => '🎮 Prime APK',
        'general' => '📦 Khác'
    ];
    
    foreach ($categories as $cat) {
        $name = $cat_names[$cat['category']] ?? $cat['category'];
        $btns[] = [['text' => "$name ({$cat['count']} SP)", 'callback_data' => "cat_{$cat['category']}"]];
    }
    
    $btns[] = [['text' => "🔙 Quay Lại", 'callback_data' => "home"]];
    
    send($chat_id, "🛒 *DANH MỤC SẢN PHẨM:*\n━━━━━━━━━━━━━━━━━━\n⬇️ Chọn danh mục bạn muốn xem:", $token, json_encode(['inline_keyboard' => $btns]));
}

function showCategory($chat_id, $category, $db, $user, $token) {
    $st = $db->prepare("SELECT * FROM products WHERE category = ? ORDER BY code");
    $st->execute([$category]);
    $prods = $st->fetchAll();
    
    $btns = [];
    foreach ($prods as $p) {
        $st2 = $db->prepare("SELECT COUNT(*) FROM keys_store WHERE game_type = ? AND status = 0");
        $st2->execute([$p['code']]);
        $count = $st2->fetchColumn();
        $price = getPriceByRank($p, $user['rank']);
        $btns[] = [['text' => "{$p['name']}\n💰 ".number_format($price)."đ | Còn: $count", 'callback_data' => "buy_{$p['code']}"]];
    }
    
    $btns[] = [['text' => "🔙 Danh Mục", 'callback_data' => "cat_main"], ['text' => "🏠 Home", 'callback_data' => "home"]];
    
    send($chat_id, "📦 *SẢN PHẨM DANH MỤC:*\n━━━━━━━━━━━━━━━━━━", $token, json_encode(['inline_keyboard' => $btns]));
}

function showAccountInfo($chat_id, $user, $token) {
    $rank_icon = getRankIcon($user['rank']);
    $info = "👤 *THÔNG TIN TÀI KHOẢN*\n━━━━━━━━━━━━━━━━━━\n🔹 ID: `$chat_id`\n🔹 Thứ hạng: $rank_icon *{$user['rank']}*\n🔹 Số dư: 💸 *".number_format($user['balance'])."đ*\n🔹 Trạng thái: ".($user['status'] == 0 ? "🟢 Hoạt động tốt" : "🚫 Đã khóa")."\n━━━━━━━━━━━━━━━━━━";
    send($chat_id, $info, $token);
}

function showBuyHistory($chat_id, $db, $token) {
    $st = $db->prepare("SELECT * FROM history_buy WHERE chat_id = ? ORDER BY id DESC LIMIT 10");
    $st->execute([$chat_id]);
    $res = $st->fetchAll();
    $msg = "🕒 *LỊCH SỬ MUA KEY GẦN ĐÂY:*\n━━━━━━━━━━━━━━━━━━\n";
    if (!$res) $msg .= "❌ Bạn chưa có giao dịch mua nào.";
    foreach ($res as $r) {
        $msg .= "📦 *{$r['item_name']}*\n🔑 Key: `{$r['key_code']}`\n💸 Tiền: ".number_format($r['price'])."đ\n📅 {$r['buy_date']}\n\n";
    }
    send($chat_id, $msg, $token);
}

function showDepositHistory($chat_id, $db, $token) {
    $st = $db->prepare("SELECT * FROM nap_tien WHERE chat_id = ? AND status = 1 ORDER BY id DESC LIMIT 10");
    $st->execute([$chat_id]);
    $res = $st->fetchAll();
    $msg = "💳 *LỊCH SỬ NẠP TIỀN:*\n━━━━━━━━━━━━━━━━━━\n";
    if (!$res) $msg .= "❌ Bạn chưa có giao dịch nạp tiền nào.";
    foreach ($res as $r) {
        $msg .= "✅ +".number_format($r['amount'])."đ | `{$r['created_at']}`\n";
    }
    send($chat_id, $msg, $token);
}

function getRankIcon($rank) {
    switch(strtolower($rank)) {
        case 'admin': return "👑";
        case 'sellervip': return "🌟";
        case 'seller': return "💎";
        case 'vang': return "🟡";
        default: return "🔰";
    }
}

function getPriceByRank($prod, $rank) {
    switch(strtolower($rank)) {
        case 'admin':
        case 'sellervip': return $prod['price_sellervip'];
        case 'seller': return $prod['price_seller'];
        default: return $prod['price_bth'];
    }
}

function handleAdminCommands($chat_id, $text, $db, $admin_ids, $token) {
    if ($text == "⚙️ Panel Admin") {
        $u_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $n_sum = $db->query("SELECT SUM(amount) FROM nap_tien WHERE status = 1")->fetchColumn() ?: 0;
        
        $msg = "🛠 *HỆ THỐNG QUẢN TRỊ VIÊN*\n━━━━━━━━━━━━━━━━━━\n👥 Tổng User: *$u_count*\n💰 Doanh Thu: *".number_format($n_sum)."đ*\n\n📦 *TỒN KHO SẢN PHẨM:*\n";
        
        $prods = $db->query("SELECT * FROM products")->fetchAll();
        foreach ($prods as $p) {
            $st = $db->prepare("SELECT COUNT(*) FROM keys_store WHERE game_type = ? AND status = 0");
            $st->execute([$p['code']]);
            $msg .= "▫️ `{$p['code']}` (*{$p['name']}*): **".$st->fetchColumn()."** key\n";
        }
        
        $msg .= "\n⚡️ *LỆNH QUẢN LÝ SP:*\n🔹 `/addsp [ma] [gia_bth] [gia_sl] [gia_svip] [Ten]` - Thêm/Sửa\n🔹 `/delsp [ma]` - Xóa SP & Key\n🔹 `/setprice [ma] [bth/seller/sellervip] [gia]` - Sửa giá\n\n⚡️ *LỆNH QUẢN LÝ KHO:*\n🔹 `/up [ma] [key1, key2]` - Thêm Key\n🔹 `/danhsachkey [ma]` - Xem kho\n🔹 `/delkey [key]` - Xóa 1 Key\n🔹 `/delallkey [ma]` - Xóa hết key\n\n⚡️ *LỆNH QUẢN LÝ USER:*\n🔹 `/add_money [id] [so]` - Cộng tiền\n🔹 `/deduct_money [id] [so]` - Trừ tiền\n🔹 `/setrank [id] [Rank]` - Đổi Rank (bth/seller/sellervip)\n🔹 `/info [id]` - Xem Info\n🔹 `/ban [id]` | `/unban [id]` - Khóa/Mở\n🔹 `/sendmsg [id] [ND]` - Gửi TB riêng\n\n⚡️ *LỆNH LOGS:*\n🔹 `/donnap` - 10 đơn nạp gần nhất";
        
        send($chat_id, $msg, $token);
    } elseif ($text == "/donnap") {
        $st = $db->query("SELECT * FROM nap_tien WHERE status = 1 ORDER BY id DESC LIMIT 10");
        $logs = $st->fetchAll();
        $msg = "💳 *10 ĐƠN NẠP THÀNH CÔNG GẦN NHẤT:*\n━━━━━━━━━━━━━━━━━━\n";
        if (!$logs) $msg .= "✅ Chưa có đơn nạp nào.";
        foreach ($logs as $r) {
            $msg .= "🔹 ID: `{$r['chat_id']}` | 💰 *".number_format($r['amount'])."đ*\n📝 ND: `{$r['content']}` | 📅 `{$r['created_at']}`\n\n";
        }
        send($chat_id, $msg, $token);
    } elseif (preg_match('/^\/addsp (\w+) (\d+) (\d+) (\d+)\s+(.+)$/s', $text, $m)) {
        $db->prepare("REPLACE INTO products (code, price_bth, price_seller, price_sellervip, name) VALUES (?, ?, ?, ?, ?)")->execute([$m[1], $m[2], $m[3], $m[4], trim($m[5])]);
        send($chat_id, "✅ Đã thêm/sửa sản phẩm: *{$m[5]}* (`{$m[1]}`)", $token);
    } elseif (preg_match('/^\/delsp (\w+)$/', $text, $m)) {
        $db->prepare("DELETE FROM products WHERE code = ?")->execute([$m[1]]);
        $db->prepare("DELETE FROM keys_store WHERE game_type = ?")->execute([$m[1]]);
        send($chat_id, "✅ Đã xóa sản phẩm và toàn bộ key của mã `{$m[1]}`", $token);
    } elseif (preg_match('/^\/setprice (\w+) (bth|seller|sellervip) (\d+)$/i', $text, $m)) {
        $col = "price_" . strtolower($m[2]);
        $db->prepare("UPDATE products SET $col = ? WHERE code = ?")->execute([$m[3], $m[1]]);
        send($chat_id, "✅ Đã cập nhật giá *{$m[2]}* cho mã `{$m[1]}` thành *".number_format($m[3])."đ*", $token);
    } elseif (preg_match('/^\/up (\w+)\s+([\s\S]+)/', $text, $m)) {
        $keys = preg_split('/[,\n\r]+/', $m[2]);
        $c = 0;
        foreach ($keys as $k) { 
            if (trim($k) && $db->prepare("INSERT IGNORE INTO keys_store (game_type, key_code) VALUES (?, ?)")->execute([$m[1], trim($k)])) $c++; 
        }
        send($chat_id, "✅ Đã thêm thành công *$c* key vào mã `$m[1]`.", $token);
    } elseif (preg_match('/^\/danhsachkey (\w+)/', $text, $m)) {
        $st = $db->prepare("SELECT key_code FROM keys_store WHERE game_type = ? AND status = 0");
        $st->execute([$m[1]]);
        $keys = $st->fetchAll(PDO::FETCH_COLUMN);
        send($chat_id, $keys ? "🔑 *LIST KEY `$m[1]`:*\n\n" . implode("\n", $keys) : "❌ Hết hàng hoặc mã không tồn tại.", $token);
    } elseif (preg_match('/^\/delkey (.+)/', $text, $m)) {
        $st = $db->prepare("DELETE FROM keys_store WHERE key_code = ?");
        $st->execute([trim($m[1])]);
        send($chat_id, $st->rowCount() > 0 ? "✅ Đã xóa thành công Key: `{$m[1]}`" : "❌ Không tìm thấy Key.", $token);
    } elseif (preg_match('/^\/delallkey (\w+)/', $text, $m)) {
        $st = $db->prepare("DELETE FROM keys_store WHERE game_type = ?");
        $st->execute([$m[1]]);
        send($chat_id, "✅ Đã dọn sạch toàn bộ kho của mã `{$m[1]}` (Xóa {$st->rowCount()} key).", $token);
    } elseif (preg_match('/^\/add_money (\d+) (\d+)/', $text, $m)) {
        $db->prepare("UPDATE users SET balance = balance + ? WHERE chat_id = ?")->execute([$m[2], $m[1]]);
        send($chat_id, "✅ Đã cộng ".number_format($m[2])."đ cho ID `$m[1]`", $token);
    } elseif (preg_match('/^\/deduct_money (\d+) (\d+)/', $text, $m)) {
        $st = $db->prepare("SELECT balance FROM users WHERE chat_id = ?");
        $st->execute([$m[1]]);
        if ($st->fetchColumn() < $m[2]) send($chat_id, "❌ User không đủ tiền để trừ.", $token);
        else { 
            $db->prepare("UPDATE users SET balance = balance - ? WHERE chat_id = ?")->execute([$m[2], $m[1]]); 
            send($chat_id, "✅ Đã trừ ".number_format($m[2])."đ của ID `$m[1]`", $token); 
        }
    } elseif (preg_match('/^\/setrank (\d+) (\w+)/', $text, $m)) {
        $new_rank = strtolower($m[2]);
        if (!in_array($new_rank, ['bth', 'seller', 'sellervip', 'admin'])) send($chat_id, "❌ Rank không hợp lệ. (bth/seller/sellervip)", $token);
        else { 
            $db->prepare("UPDATE users SET rank = ? WHERE chat_id = ?")->execute([$new_rank, $m[1]]); 
            send($chat_id, "✅ Đổi Rank ID `$m[1]` → *$new_rank*", $token); 
        }
    } elseif (preg_match('/^\/info (\d+)/', $text, $m)) {
        $st = $db->prepare("SELECT * FROM users WHERE chat_id = ?");
        $st->execute([$m[1]]); $u = $st->fetch();
        if (!$u) send($chat_id, "❌ User không tồn tại.", $token);
        else {
            $buy_data = $db->query("SELECT COUNT(*), SUM(price) FROM history_buy WHERE chat_id = {$m[1]}")->fetch();
            $msg = "👤 *THÔNG TIN CHI TIẾT USER*\n━━━━━━━━━━━━━━━━━━\n🔹 ID: `{$u['chat_id']}`\n🔹 Rank: *{$u['rank']}*\n🔹 Số dư: *".number_format($u['balance'])."đ*\n🔹 Trạng thái: ".($u['status'] == 0 ? "🟢 Bình thường" : "🚫 Đã khóa")."\n🔹 Tổng đơn: *{$buy_data[0]}*\n🔹 Tổng chi: *".number_format($buy_data[1] ?: 0)."đ*";
            send($chat_id, $msg, $token);
        }
    } elseif (preg_match('/^\/ban (\d+)/', $text, $m)) {
        $db->prepare("UPDATE users SET status = 1 WHERE chat_id = ?")->execute([$m[1]]);
        send($chat_id, "🚫 Đã khóa tài khoản ID `$m[1]`", $token);
    } elseif (preg_match('/^\/unban (\d+)/', $text, $m)) {
        $db->prepare("UPDATE users SET status = 0 WHERE chat_id = ?")->execute([$m[1]]);
        send($chat_id, "✅ Đã mở khóa tài khoản ID `$m[1]`", $token);
    } elseif (preg_match('/^\/sendmsg (\d+)\s+(.+)$/s', $text, $m)) {
        send($m[1], "🔔 *THÔNG BÁO TỪ ADMIN:*\n\n" . $m[2], $token);
        send($chat_id, "✅ Đã gửi thông báo cho ID `$m[1]`", $token);
    }
}

function send($id, $txt, $token, $kbd = null) {
    $data = ['chat_id' => $id, 'text' => $txt, 'parse_mode' => 'Markdown', 'reply_markup' => $kbd];
    $ch = curl_init("https://api.telegram.org/bot$token/sendMessage");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 10]);
    curl_exec($ch); curl_close($ch);
}

function sendPhoto($id, $photo, $cap, $token) {
    $data = ['chat_id' => $id, 'photo' => $photo, 'caption' => $cap, 'parse_mode' => 'Markdown'];
    $ch = curl_init("https://api.telegram.org/bot$token/sendPhoto");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 10]);
    curl_exec($ch); curl_close($ch);
}

function answerCallback($callback_id, $token) {
    $ch = curl_init("https://api.telegram.org/bot$token/answerCallbackQuery");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POSTFIELDS => ['callback_query_id' => $callback_id], CURLOPT_TIMEOUT => 5]);
    curl_exec($ch); curl_close($ch);
}

function muaHang($id, $code, $db, $user, $admin_ids, $token) {
    $st = $db->prepare("SELECT * FROM products WHERE code = ?");
    $st->execute([$code]);
    $prod = $st->fetch();
    if (!$prod) { send($id, "❌ Sản phẩm không tồn tại hoặc đã bị xóa.", $token); return; }
    
    $price = getPriceByRank($prod, $user['rank']);
    
    try {
        $db->beginTransaction();
        $st = $db->prepare("SELECT balance FROM users WHERE chat_id = ? FOR UPDATE");
        $st->execute([$id]);
        if ($st->fetchColumn() < $price) {
            $db->rollBack();
            send($id, "❌ *SỐ DƯ KHÔNG ĐỦ!*\nBạn cần nạp thêm để mua sản phẩm này.\n💰 Giá: *".number_format($price)."đ*\n💸 Số dư hiện tại: *".number_format($user['balance'])."đ*", $token); 
            return;
        }
        
        $st = $db->prepare("SELECT id, key_code FROM keys_store WHERE game_type = ? AND status = 0 ORDER BY id ASC LIMIT 1 FOR UPDATE");
        $st->execute([$code]);
        $k = $st->fetch();
        if (!$k) {
            $db->rollBack();
            send($id, "❌ *SẢN PHẨM ĐÃ HẾT HÀNG!*\nVui lòng quay lại sau hoặc liên hệ Admin.", $token); 
            return;
        }
        
        $db->prepare("UPDATE users SET balance = balance - ? WHERE chat_id = ?")->execute([$price, $id]);
        $db->prepare("UPDATE keys_store SET status = 1 WHERE id = ?")->execute([$k['id']]);
        $db->prepare("INSERT INTO history_buy (chat_id, item_name, key_code, price) VALUES (?, ?, ?, ?)")->execute([$id, $prod['name'], $k['key_code'], $price]);
        $db->commit();
        
        $success_msg = "✅ *GIAO DỊCH THÀNH CÔNG*\n━━━━━━━━━━━━━━━━━━\n📦 Sản phẩm: *{$prod['name']}*\n🔑 Key: `{$k['key_code']}`\n💸 Số tiền: -".number_format($price)."đ\n━━━━━━━━━━━━━━━━━━\n⚠️ Vui lòng lưu lại key và kích hoạt ngay!";
        send($id, $success_msg, $token);
        
        foreach ($admin_ids as $admin_id) {
            send($admin_id, "🛒 *BÁO CÁO ĐƠN MỚI*\n━━━━━━━━━━━━━━━━━━\n👤 ID: `$id`\n📦 SP: *{$prod['name']}*\n🔑 Key: `{$k['key_code']}`\n💸 DT: +".number_format($price)."đ", $token);
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        send($id, "⚠️ Hệ thống đang bận, vui lòng thử lại sau.", $token);
    }
}

function checkAutoBank($api, $db, $admin_ids, $token) {
    $ch = curl_init($api);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (!isset($res['transactions']) || !is_array($res['transactions'])) return;
    
    foreach ($res['transactions'] as $tx) {
        if (($tx['type'] ?? '') != "IN") continue;
        $desc = $tx['description'] ?? '';
        if (!preg_match('/NAP(\d+)[A-Z0-9]{8}/i', $desc, $m)) continue;
        
        $content = $m[0];
        $chat_id_from = (int)$m[1];
        $amount = (int)str_replace(['.', ',', ' ', '-'], '', $tx['amount'] ?? '0');
        $tid = $tx['transactionID'] ?? '';
        $tran_date = $tx['transactionDate'] ?? date('Y-m-d H:i:s');
        
        if ($amount <= 0) continue;
        
        try {
            $db->beginTransaction();
            $st = $db->prepare("SELECT * FROM nap_tien WHERE content = ? AND status = 0 FOR UPDATE");
            $st->execute([$content]);
            $order = $st->fetch();
            
            if (!$order) { $db->rollBack(); continue; }
            
            if (strtotime($order['exp_time']) < time() || $order['chat_id'] != $chat_id_from) {
                $db->prepare("UPDATE nap_tien SET status = 3 WHERE id = ?")->execute([$order['id']]);
                $db->commit();
                continue;
            }
            
            $order_amount = (int)$order['amount'];
            
            if ($amount >= $order_amount) {
                $db->prepare("UPDATE nap_tien SET status = 1 WHERE id = ?")->execute([$order['id']]);
                $db->prepare("UPDATE users SET balance = balance + ? WHERE chat_id = ?")->execute([$amount, $order['chat_id']]);
                $db->commit();
                
                send($order['chat_id'], "🔔 *NẠP TIỀN TỰ ĐỘNG THÀNH CÔNG!*\n━━━━━━━━━━━━━━━━━━\n💰 Số tiền: +*".number_format($amount)."đ*\n✅ Số dư đã được cập nhật.\n📅 Thời gian: `$tran_date`", $token);
                
                foreach ($admin_ids as $admin_id) {
                    send($admin_id, "✅ *USER NẠP TIỀN THÀNH CÔNG*\n━━━━━━━━━━━━━━━━━━\n👤 ID: `{$order['chat_id']}`\n💰 Số tiền: +*".number_format($amount)."đ*\n📝 ND: `$content`\n🔢 TransID: `$tid`\n📅 `$tran_date`", $token);
                }
            } else {
                $db->prepare("UPDATE nap_tien SET status = 2 WHERE id = ?")->execute([$order['id']]);
                $db->commit();
                
                send($order['chat_id'], "❌ *NẠP TIỀN THẤT BẠI!*\nSố tiền chuyển không khớp yêu cầu.\nChuyển: *".number_format($amount)."đ* | Yêu cầu: *".number_format($order_amount)."đ*", $token);
                
                foreach ($admin_ids as $admin_id) {
                    send($admin_id, "🚨 *CẢNH BÁO NẠP SAI SỐ TIỀN!*\n━━━━━━━━━━━━━━━━━━\n👤 ID: `{$order['chat_id']}`\n💸 Chuyến: *".number_format($amount)."đ* | YC: *".number_format($order_amount)."đ*\n📝 ND: `$content`", $token);
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
        }
    }
}
?>

bỏ 
function showCategories($chat_id, $db, $token) {
    $categories = $db->query("SELECT DISTINCT category, COUNT(*) as count FROM products GROUP BY category")->fetchAll();
    $btns = [];
    $cat_names = [
        'pato' => '🔴 Pato',
        'flu' => '📱 Flu iOS',
        'drip' => '💧 Drip',
        'xthaxx' => '⚡ Xthaxx',
        'brpc' => '💻 Br PC',
        'prime' => '🎮 Prime APK',
        'general' => '📦 Khác'
    ];
    
    foreach ($categories as $cat) {
        $name = $cat_names[$cat['category']] ?? $cat['category'];
        $btns[] = [['text' => "$name ({$cat['count']} SP)", 'callback_data' => "cat_{$cat['category']}"]];
    }
    
    $btns[] = [['text' => "🔙 Quay Lại", 'callback_data' => "home"]];
    
    send($chat_id, "🛒 *DANH MỤC SẢN PHẨM:*\n━━━━━━━━━━━━━━━━━━\n⬇️ Chọn danh mục bạn muốn xem:", $token, json_encode(['inline_keyboard' => $btns]));
}
+ mỗi lần bấm button là sửa vào tn đấy kh gửi tn mới