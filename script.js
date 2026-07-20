let tgUserId = 0; // Legacy
let tgUsername = 'User'; // Legacy

let allProducts = [];
let userRank = 'bth';
let userBalance = 0;
let selectedGame = null;
let selectedPackage = null;
let groupedGames = {};

document.addEventListener('DOMContentLoaded', () => {
    initApp();
});

async function initApp() {
    try {
        let res = await fetch('api.php?action=init', {
            method: 'POST'
        });
        
        let data = await res.json();
        if (data.status === 'success') {
            document.getElementById('user-avatar').innerText = data.user.username.charAt(0).toUpperCase();
            document.getElementById('user-name').innerText = data.user.username;
            
            userRank = data.user.rank;
            userBalance = data.user.balance;
            allProducts = data.products;
            
            // Render thống kê (Mocked for now since API doesn't return full stats yet)
            document.getElementById('stat-total').innerText = data.stats?.total || 0;
            document.getElementById('stat-active').innerText = data.stats?.active || 0;
            document.getElementById('stat-expired').innerText = data.stats?.expired || 0;
            
            // Group games
            groupProductsByGame();
            renderKeys(data.keys || []);
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast("Lỗi kết nối máy chủ!", 'error');
    }
}

function groupProductsByGame() {
    groupedGames = {};
    const gameNames = {
        'pato': 'Pato Hack',
        'flu': 'Flu iOS',
        'drip': 'Drip',
        'xthaxx': 'Xthaxx',
        'brpc': 'Br PC',
        'prime': 'Prime APK'
    };
    
    allProducts.forEach(p => {
        if (!groupedGames[p.category]) {
            groupedGames[p.category] = {
                id: p.category,
                name: gameNames[p.category] || p.category,
                packages: [],
                type: 'Root & NoRoot',
                badge: 'NORMAL'
            };
        }
        groupedGames[p.category].packages.push(p);
    });
    
    // Add fake badge to some games for UI
    if (groupedGames['pato']) groupedGames['pato'].badge = 'VIP';
    
    renderGameSheet();
}

function formatVND(amount) {
    return parseInt(amount).toLocaleString('vi-VN') + 'đ';
}

function showToast(msg, type = 'success') {
    let container = document.getElementById('toast-container');
    let toast = document.createElement('div');
    toast.style.background = type === 'success' ? '#34c759' : '#ff3b30';
    toast.style.color = '#fff';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.fontSize = '14px';
    toast.style.fontWeight = '600';
    toast.innerText = msg;
    
    container.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3000);
}

// Bottom Sheet Logic
function openGameSelector() {
    document.getElementById('sheet-overlay').classList.add('show');
    document.getElementById('game-sheet').classList.add('show');
    if (tg.HapticFeedback) tg.HapticFeedback.impactOccurred('light');
}

function closeGameSelector() {
    document.getElementById('sheet-overlay').classList.remove('show');
    document.getElementById('game-sheet').classList.remove('show');
}

function renderGameSheet() {
    let html = '';
    for (let cat in groupedGames) {
        let game = groupedGames[cat];
        let badgeCls = game.badge === 'VIP' ? 'vip' : '';
        // Random thumbnail
        let iconUrl = `https://ui-avatars.com/api/?name=${game.name}&background=random&color=fff&size=100`;
        
        html += `
        <div class="game-item" onclick="selectGame('${cat}')">
            <img src="${iconUrl}" alt="">
            <div class="game-info">
                <h4>${game.name}</h4>
                <p>com.megapixel.game.${cat}</p>
                <span>${game.type}</span>
            </div>
            <div class="game-badge ${badgeCls}">${game.badge}</div>
        </div>`;
    }
    document.getElementById('games-list-sheet').innerHTML = html;
}

function selectGame(cat) {
    selectedGame = groupedGames[cat];
    selectedPackage = null;
    
    document.getElementById('selected-game-title').innerText = selectedGame.name;
    document.getElementById('selected-game-sub').innerText = 'com.megapixel.game.' + cat;
    document.getElementById('selected-game-icon').innerHTML = `<img src="https://ui-avatars.com/api/?name=${selectedGame.name}&background=random&color=fff&size=40" style="border-radius:8px; width:100%; height:100%;">`;
    
    closeGameSelector();
    renderPackages();
    updateBuyButton();
}

function getActualPrice(p) {
    if (userRank === 'seller') return p.price_seller;
    if (userRank === 'sellervip') return p.price_sellervip;
    return p.price_bth;
}

function renderPackages() {
    if (!selectedGame) return;
    
    let html = '';
    selectedGame.packages.forEach(p => {
        let price = getActualPrice(p);
        
        // Extract duration from name like "Pato Đỏ (7 Ngày)"
        let match = p.name.match(/\((.*?)\)/);
        let duration = match ? match[1] : p.name;
        
        html += `
        <div class="package-item" id="pkg-${p.code}" onclick="selectPackage('${p.code}')">
            <div>
                <div class="pkg-name">Gói ${duration}</div>
                <div class="pkg-desc">Chế độ Normal key</div>
            </div>
            <div class="pkg-price">${formatVND(price)}</div>
        </div>`;
    });
    document.getElementById('packages-list').innerHTML = html;
}

function selectPackage(code) {
    if (tg.HapticFeedback) tg.HapticFeedback.selectionChanged();
    
    document.querySelectorAll('.package-item').forEach(el => el.classList.remove('selected'));
    document.getElementById('pkg-' + code).classList.add('selected');
    
    selectedPackage = selectedGame.packages.find(p => p.code === code);
    updateBuyButton();
}

function updateBuyButton() {
    let btn = document.getElementById('buy-button');
    let hint = document.getElementById('buy-price-hint');
    
    if (selectedPackage) {
        let price = getActualPrice(selectedPackage);
        let match = selectedPackage.name.match(/\((.*?)\)/);
        let duration = match ? match[1] : selectedPackage.name;
        
        btn.classList.add('active');
        hint.innerText = `${duration} | ${formatVND(price)}`;
    } else {
        btn.classList.remove('active');
        hint.innerText = 'chưa chọn gói';
    }
}

async function executeBuy() {
    if (!selectedPackage) return;
    
    let price = getActualPrice(selectedPackage);
    
    // Auto payment logic check
    if (userBalance < price) {
        if (!confirm(`Tài khoản của bạn không đủ (${formatVND(userBalance)} / ${formatVND(price)}).\nBạn có muốn nạp thêm tiền không?`)) return;
        
        // Show deposit modal or redirect to deposit
        window.location.href = 'deposit.php';
        return;
    }
    
    if (!confirm(`Xác nhận mua ${selectedPackage.name} với giá ${formatVND(price)}?`)) return;
    
    let btn = document.getElementById('buy-button');
    btn.innerHTML = 'Đang xử lý...';
    
    try {
        let res = await fetch('api.php?action=buy', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `product_code=${selectedPackage.code}`
        });
        
        let data = await res.json();
        if (data.status === 'success') {
            alert(`Mua thành công!\nKey của bạn:\n${data.key}`);
            initApp();
        } else {
            showToast(data.message, 'error');
        }
    } catch (e) {
        showToast("Lỗi hệ thống", 'error');
    }
    
    updateBuyButton(); // Reset button text
}

function renderKeys(keys) {
    let list = document.getElementById('keys-list');
    if (keys.length === 0) {
        list.innerHTML = `
        <div class="empty-state">
            <div class="icon"><i class="fas fa-lock"></i></div>
            <h3>Chưa có Key</h3>
            <p>Mua thêm key để sử dụng</p>
        </div>`;
        return;
    }
    
    let html = '';
    keys.forEach(k => {
        html += `
        <div style="background: var(--bg-input); padding: 15px; border-radius: 10px; margin-bottom: 10px; border: 1px solid var(--border-color);">
            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                <strong style="color: var(--primary-color);">${k.item_name}</strong>
                <span style="font-size:11px; color:var(--text-muted);">${k.buy_date}</span>
            </div>
            <div style="font-family:monospace; background:var(--bg-main); padding:8px; border-radius:6px; font-size:13px; word-break:break-all; user-select:all;">
                ${k.key_code}
            </div>
        </div>`;
    });
    list.innerHTML = html;
}
