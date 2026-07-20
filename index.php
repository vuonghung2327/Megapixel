<?php
require_once 'config.php';
checkLogin();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Megapixel Seller</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <div class="header-top">
        <i class="fas fa-times" style="font-size: 18px; color: var(--text-main);"></i>
        <span style="font-weight: 700; font-size: 16px;">Megapixel Seller</span>
        <i class="fas fa-ellipsis-v" style="font-size: 18px; color: var(--text-main);"></i>
    </div>

    <!-- Promo Banner -->
    <div class="promo-banner">
        <div style="display: flex; align-items: center;">
            <div class="icon"><i class="fas fa-fire" style="font-size: 12px;"></i></div>
            <span style="font-weight: 600; font-size: 12px;">KHUYẾN MÃI HOT!</span>
        </div>
        <div style="background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 6px; font-size: 12px; display: flex; align-items: center;">
            7944098491 <i class="fas fa-copy" style="margin-left: 5px; color: var(--text-muted);"></i>
        </div>
    </div>

    <!-- Avatar Section -->
    <div class="avatar-section">
        <div class="avatar-ring">
            <div class="avatar-img" id="user-avatar">U</div>
        </div>
        <div class="username" id="user-name">Đang tải...</div>
    </div>

    <!-- Stats Box -->
    <div class="stats-box">
        <div class="stat-item">
            <div class="stat-value" id="stat-total">0</div>
            <div class="stat-label">Tổng key</div>
        </div>
        <div class="stat-item">
            <div class="stat-value active-c" id="stat-active">0</div>
            <div class="stat-label">Hoạt động</div>
        </div>
        <div class="stat-item">
            <div class="stat-value expired-c" id="stat-expired">0</div>
            <div class="stat-label">Hết hạn</div>
        </div>
    </div>

    <!-- Mua Key Section -->
    <div class="main-card">
        <div class="section-header" style="padding: 0; margin-bottom: 20px;">
            <i class="fas fa-shopping-cart"></i>
            <div>
                <h2 style="font-size: 15px;">Mua Key mới</h2>
                <p style="margin-left: 0; font-size: 11px;">Chọn ứng dụng và gói ngày</p>
            </div>
        </div>

        <div class="label-title"><i class="fas fa-box"></i> Chọn ứng dụng</div>
        
        <div class="selector-box" onclick="openGameSelector()">
            <div class="selector-left">
                <div class="game-icon" id="selected-game-icon">
                    <i class="fab fa-google-play" style="color: #34c759;"></i>
                </div>
                <div>
                    <div class="selector-title" id="selected-game-title">Nhấn chọn game</div>
                    <div class="selector-subtitle" id="selected-game-sub">Chưa chọn game</div>
                </div>
            </div>
            <i class="fas fa-chevron-right" style="color: var(--text-muted);"></i>
        </div>

        <div class="label-title"><i class="fas fa-gem"></i> Chọn gói</div>
        
        <div id="packages-list">
            <!-- Danh sách gói trống -->
            <div style="text-align: center; color: var(--text-muted); padding: 15px 0; border-bottom: 1px solid var(--border-color); font-weight: 500;">
                Danh sách trống
            </div>
        </div>

        <div class="action-row">
            <div class="icon-btn"><i class="fas fa-download"></i></div>
            <div class="icon-btn"><i class="fas fa-play"></i></div>
            <button class="buy-btn" id="buy-button" onclick="executeBuy()">
                <div style="margin-bottom: 2px;">Mua ngay</div>
                <div class="price-hint" id="buy-price-hint">chưa chọn gói</div>
            </button>
        </div>
    </div>
    
    <div class="notice-text">
        Không nhận card: Nếu không có tài khoản ngân hàng vui lòng tạo mã qr và nhờ người khác quét hộ để được nhận key ở đây
    </div>

    <!-- Quản lý Key Section -->
    <div class="section-header" style="margin-top: 30px; margin-bottom: 20px;">
        <i class="fas fa-key"></i>
        <h2>Key của bạn</h2>
    </div>

    <div class="main-card" style="padding: 15px;">
        <div class="tabs-row">
            <div class="tab-btn active">Tất cả</div>
            <div class="tab-btn">Hoạt động</div>
            <div class="tab-btn">Hết hạn</div>
            <div class="tab-btn">Bị khoá</div>
        </div>
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Tìm kiếm GKey...">
        </div>
    </div>

    <div class="main-card" style="padding: 40px 20px;" id="keys-list">
        <div class="empty-state">
            <div class="icon"><i class="fas fa-lock"></i></div>
            <h3>Chưa có Key</h3>
            <p>Mua thêm key để sử dụng</p>
        </div>
    </div>

    <div class="footer-text">
        By publishing this bot, you agree to the <span>Telegram Terms of Service for Developers</span>
    </div>

    <!-- Bottom Sheet Overlay -->
    <div class="bottom-sheet-overlay" id="sheet-overlay" onclick="closeGameSelector()"></div>

    <!-- Bottom Sheet Content -->
    <div class="bottom-sheet" id="game-sheet">
        <div class="sheet-header">
            <span>Chọn game</span>
            <i class="fas fa-times close-sheet" onclick="closeGameSelector()"></i>
        </div>
        
        <div id="games-list-sheet">
            <!-- Danh sách game sẽ render bằng JS -->
        </div>
    </div>

    <!-- Toast -->
    <div id="toast-container" style="position: fixed; top: 10px; left: 50%; transform: translateX(-50%); z-index: 10000; display: flex; flex-direction: column; gap: 10px; width: 90%;"></div>

    <script src="script.js"></script>
</body>
</html>
