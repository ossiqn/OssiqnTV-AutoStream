<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cur_page = basename($_SERVER['PHP_SELF']);
$db_paths = [__DIR__ . '/../config.php', 'config.php', __DIR__ . '/../config/database.php'];
$db = null;
foreach($db_paths as $path) {
    if(file_exists($path)) { require_once $path; break; }
}
if(!$db && class_exists('PDO')) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=ossiqntv;charset=utf8mb4", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}
$is_logged_in = isset($_SESSION['user_id']);
$user_name = "Kullanıcı";
$is_vip = false;
if ($is_logged_in && $db) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $user_name = $u['name'];
        $vip_check = $db->prepare("SELECT id FROM pf_licenses WHERE user_id = ? AND status = 'Aktif' AND expires_at > NOW()");
        $vip_check->execute([$u['id']]);
        if($vip_check->rowCount() > 0) $is_vip = true;
    }
}
$swal_script = "";
$show_auth_modal = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $swal_script = "Swal.fire({icon: 'success', title: 'Hoş Geldiniz!', text: 'Başarıyla giriş yapıldı.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#10b981'}).then(() => { window.location.href = 'index.php'; });";
        } else {
            $swal_script = "Swal.fire({icon: 'error', title: 'Giriş Başarısız', text: 'E-posta veya şifreniz hatalı.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
            $show_auth_modal = true;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $promo = preg_replace('/\s+/', '', $_POST['promo_code'] ?? '');
        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->rowCount() > 0) {
            $swal_script = "Swal.fire({icon: 'error', title: 'Hata', text: 'Bu e-posta adresi zaten kullanılıyor!', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
            $show_auth_modal = true;
        } else {
            $promo_data = null;
            $promo_error = false;
            if (!empty($promo)) {
                $p_stmt = $db->prepare("SELECT * FROM promo_codes WHERE LOWER(REPLACE(code, ' ', '')) = LOWER(?)");
                $p_stmt->execute([$promo]);
                $promo_data = $p_stmt->fetch();
                if (!$promo_data) {
                    $swal_script = "Swal.fire({icon: 'error', title: 'Geçersiz Kod', text: 'Girdiğiniz promosyon kodu hatalı veya sistemde bulunamadı.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
                    $show_auth_modal = true;
                    $promo_error = true;
                } elseif ((int)$promo_data['current_uses'] >= (int)$promo_data['max_uses']) {
                    $swal_script = "Swal.fire({icon: 'warning', title: 'Limit Doldu', text: 'Bu kodun kullanım limiti dolmuş, lütfen paketlere göz atın.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'}).then(() => { window.location.href = 'paket.php'; });";
                    $show_auth_modal = true;
                    $promo_error = true;
                }
            }
            if (!$promo_error) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                if ($ins->execute([$name, $email, $hash])) {
                    $new_user_id = $db->lastInsertId();
                    $_SESSION['user_id'] = $new_user_id;
                    $success_text = "Hesabınız başarıyla oluşturuldu ve giriş yapıldı.";
                    if ($promo_data) {
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        if (strpos(mb_strtolower($promo_data['package_name'], 'UTF-8'), 'sınırsız') !== false || strpos(mb_strtolower($promo_data['package_name'], 'UTF-8'), 'mega') !== false) {
                            $expires = date('Y-m-d H:i:s', strtotime('+3650 days'));
                        }
                        $l_stmt = $db->prepare("INSERT INTO pf_licenses (user_id, product_name, status, expires_at) VALUES (?, ?, 'Aktif', ?)");
                        $l_stmt->execute([$new_user_id, $promo_data['package_name'], $expires]);
                        $u_stmt = $db->prepare("UPDATE promo_codes SET current_uses = current_uses + 1 WHERE id = ?");
                        $u_stmt->execute([$promo_data['id']]);
                        $success_text = "Tebrikler! Hesabınız oluşturuldu ve [" . $promo_data['package_name'] . "] ayrıcalıkları aktif edildi!";
                    }
                    $swal_script = "Swal.fire({icon: 'success', title: 'Başarılı!', text: '$success_text', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#10b981'}).then(() => { window.location.href = 'index.php'; });";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>OSSIQN TV - Premium Yayın Platformu</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#e50914">
    <link rel="apple-touch-icon" href="https://placehold.co/192x192/e50914/ffffff?text=O">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #030303; }
        ::-webkit-scrollbar-thumb { background: #e50914; border-radius: 10px; }
        body { background-color: #030303; color: #fff; font-family: 'Inter', sans-serif; }
        .custom-navbar { background: rgba(3,3,3,0.85); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 5%; position: fixed; top: 0; width: 100%; z-index: 1000; transition: 0.3s; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { font-size: 1.8rem; font-weight: 900; color: #fff; text-decoration: none; letter-spacing: -1px; white-space: nowrap; }
        .nav-brand span { color: #e50914; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-link-item { color: #d4d4d8; text-decoration: none; font-weight: 600; transition: 0.3s; font-size: 0.95rem; }
        .nav-link-item:hover, .nav-link-item.active { color: #fff; text-shadow: 0 0 10px rgba(255,255,255,0.5); }
        .vip-nav-btn { color: #f59e0b; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 5px; transition: 0.3s; }
        .vip-nav-btn:hover { color: #fbbf24; text-shadow: 0 0 15px rgba(245,158,11,0.6); }
        .search-box { position: relative; width: 250px; }
        .search-box input { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 100px; padding: 10px 15px 10px 40px; color: #fff; font-size: 0.9rem; transition: 0.3s; }
        .search-box input:focus { background: rgba(255,255,255,0.1); border-color: #e50914; outline: none; box-shadow: 0 0 10px rgba(229,9,20,0.3); }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a1a1aa; }
        .user-area { position: relative; display: flex; align-items: center; }
        .user-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; cursor: pointer; transition: 0.3s; text-decoration: none; }
        .user-btn:hover { background: #e50914; border-color: #e50914; box-shadow: 0 0 15px rgba(229,9,20,0.5); }
        .profile-dropdown { position: absolute; top: 55px; right: 0; background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 10px; width: 220px; box-shadow: 0 15px 30px rgba(0,0,0,0.9); opacity: 0; visibility: hidden; transform: translateY(10px); transition: 0.3s; }
        .user-area.open .profile-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
        .drop-item { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #d4d4d8; text-decoration: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .drop-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .drop-item.logout { color: #ef4444; }
        .drop-item.logout:hover { background: rgba(239,68,68,0.1); }
        .mobile-bottom-nav { display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(10,10,10,0.95); backdrop-filter: blur(25px); border-top: 1px solid rgba(255,255,255,0.05); z-index: 99999; padding: 10px 5px; padding-bottom: calc(10px + env(safe-area-inset-bottom)); justify-content: space-around; align-items: center; box-shadow: 0 -10px 40px rgba(0,0,0,0.9); }
        .mobile-nav-item { display: flex; flex-direction: column; align-items: center; justify-content: center; color: #71717a !important; text-decoration: none !important; font-size: 0.65rem; font-weight: 700; gap: 4px; transition: 0.3s; cursor: pointer; flex: 1; user-select: none; }
        .mobile-nav-item i { font-size: 1.4rem; transition: 0.3s; color: inherit; }
        .mobile-nav-item.active { color: #fff !important; transform: translateY(-2px); }
        .mobile-nav-item.active i { color: #e50914 !important; text-shadow: 0 0 15px rgba(229,9,20,0.6); }
        .mobile-nav-item.vip-item { color: #d97706 !important; }
        .mobile-nav-item.vip-item.active i { color: #fbbf24 !important; text-shadow: 0 0 15px rgba(245,158,11,0.8); }
        .mobile-profile-dropdown { position: fixed; bottom: 85px; right: 15px; background: rgba(15,15,15,0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 10px; width: 220px; box-shadow: 0 15px 40px rgba(0,0,0,0.9); opacity: 0; visibility: hidden; transform: translateY(20px); transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 99999; }
        .mobile-profile-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .auth-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(15px); z-index: 99999; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.4s; padding: 20px; }
        .auth-overlay.show { display: flex; opacity: 1; }
        .auth-modal { background: rgba(10,10,10,0.95); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; padding: 40px; width: 100%; max-width: 420px; box-shadow: 0 30px 60px rgba(0,0,0,0.9); transform: translateY(30px) scale(0.95); transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; max-height: 90vh; overflow-y: auto; }
        .auth-overlay.show .auth-modal { transform: translateY(0) scale(1); }
        .auth-modal::-webkit-scrollbar { display: none; }
        .auth-close { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.05); border: none; color: #a1a1aa; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; }
        .auth-close:hover { background: rgba(239,68,68,0.1); color: #ef4444; transform: rotate(90deg); }
        .auth-icon { width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #e50914, #991b1b); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px auto; box-shadow: 0 10px 25px rgba(229,9,20,0.4); }
        .auth-title { font-weight: 900; font-size: 1.8rem; text-align: center; margin-bottom: 5px; color: #fff; }
        .auth-subtitle { color: #a1a1aa; text-align: center; margin-bottom: 30px; font-size: 0.95rem; }
        .auth-input { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 16px 20px; color: #fff; width: 100%; transition: 0.3s; font-size: 1rem; margin-bottom: 15px; }
        .auth-input:focus { border-color: #e50914; outline: none; background: rgba(255,255,255,0.05); box-shadow: 0 0 0 4px rgba(229,9,20,0.1); }
        .auth-btn { background: #e50914; color: #fff; border: none; width: 100%; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; margin-top: 5px; transition: 0.3s; cursor: pointer; }
        .auth-btn:hover { background: #dc2626; box-shadow: 0 10px 25px rgba(229,9,20,0.4); transform: translateY(-2px); }
        .auth-switch { text-align: center; margin-top: 25px; color: #a1a1aa; font-size: 0.95rem; }
        .auth-switch span { color: #e50914; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .auth-switch span:hover { text-decoration: underline; color: #f87171; }
        @media (max-width: 992px) {
            body { padding-bottom: 80px; }
            .nav-links, .user-area { display: none !important; }
            .custom-navbar { padding: 12px 20px; }
            .nav-brand { font-size: 1.4rem; }
            .search-box { width: 100%; max-width: 180px; margin-left: auto; }
            .search-box input { padding: 8px 15px 8px 35px; font-size: 0.8rem; }
            .mobile-bottom-nav { display: flex; }
        }
    </style>
</head>
<body>
<nav class="custom-navbar">
    <div class="d-flex align-items-center gap-4">
        <a href="index.php" class="nav-brand">OSSIQN<span>TV</span></a>
        <div class="nav-links">
            <a href="index.php" class="nav-link-item <?= $cur_page == 'index.php' ? 'active' : '' ?>">Ana Sayfa</a>
            <a href="filmler.php" class="nav-link-item <?= $cur_page == 'filmler.php' ? 'active' : '' ?>">Filmler</a>
            <a href="diziler.php" class="nav-link-item <?= $cur_page == 'diziler.php' ? 'active' : '' ?>">Diziler</a>
            <a href="animeler.php" class="nav-link-item <?= $cur_page == 'animeler.php' ? 'active' : '' ?>">Animeler</a>
            <a href="paket.php" class="vip-nav-btn <?= $cur_page == 'paket.php' ? 'active' : '' ?>"><i class="fa-solid fa-crown"></i> VIP Abonelik</a>
        </div>
    </div>
    <div class="d-flex align-items-center gap-4">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="navSearchInput" placeholder="Film, dizi ara..." autocomplete="off">
        </div>
        <div class="user-area" id="userArea">
            <?php if($is_logged_in): ?>
                <div class="user-btn" onclick="toggleProfile()"><i class="fa-solid fa-user"></i></div>
                <div class="profile-dropdown">
                    <div class="px-3 py-2 border-bottom" style="border-color: rgba(255,255,255,0.1)!important;">
                        <div style="color:#fff; font-weight:800; font-size:1rem;"><?= htmlspecialchars($user_name) ?></div>
                        <div style="color:<?= $is_vip ? '#f59e0b' : '#a1a1aa' ?>; font-size:0.8rem; font-weight:700;"><i class="fa-solid <?= $is_vip ? 'fa-crown' : 'fa-user' ?>"></i> <?= $is_vip ? 'VIP Üye' : 'Standart Üye' ?></div>
                    </div>
                    <a href="profilim.php" class="drop-item mt-2"><i class="fa-solid fa-user-pen text-info"></i> Profilimi Düzenle</a>
                    <a href="sonizlediklerim.php" class="drop-item"><i class="fa-solid fa-layer-group text-success"></i> Kütüphanem</a>
                    <a href="paket.php" class="drop-item"><i class="fa-solid fa-gem text-primary"></i> Paketi Yükselt</a>
                    <a href="logout.php" class="drop-item logout"><i class="fa-solid fa-right-from-bracket"></i> Güvenli Çıkış</a>
                </div>
            <?php else: ?>
                <div class="user-btn" onclick="openAuthModal()"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="mobile-bottom-nav">
    <a href="index.php" class="mobile-nav-item <?= $cur_page == 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i>
        <span>Ana Sayfa</span>
    </a>
    <a href="filmler.php" class="mobile-nav-item <?= $cur_page == 'filmler.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-film"></i>
        <span>Filmler</span>
    </a>
    <a href="diziler.php" class="mobile-nav-item <?= $cur_page == 'diziler.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-tv"></i>
        <span>Diziler</span>
    </a>
    <a href="animeler.php" class="mobile-nav-item <?= $cur_page == 'animeler.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-bolt"></i>
        <span>Animeler</span>
    </a>
    <a href="paket.php" class="mobile-nav-item vip-item <?= $cur_page == 'paket.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-crown"></i>
        <span>VIP</span>
    </a>
    <?php if($is_logged_in): ?>
        <div class="mobile-nav-item" id="mobileUserBtn" onclick="toggleMobileProfile()">
            <i class="fa-solid fa-user"></i>
            <span>Profil</span>
        </div>
    <?php else: ?>
        <div class="mobile-nav-item" onclick="openAuthModal()">
            <i class="fa-solid fa-right-to-bracket"></i>
            <span>Giriş</span>
        </div>
    <?php endif; ?>
</div>
<?php if($is_logged_in): ?>
<div class="mobile-profile-dropdown" id="mobileProfileDropdown">
    <div class="px-3 py-2 border-bottom" style="border-color: rgba(255,255,255,0.1)!important;">
        <div style="color:#fff; font-weight:800; font-size:1.1rem;"><?= htmlspecialchars($user_name) ?></div>
        <div style="color:<?= $is_vip ? '#f59e0b' : '#a1a1aa' ?>; font-size:0.85rem; font-weight:700;"><i class="fa-solid <?= $is_vip ? 'fa-crown' : 'fa-user' ?>"></i> <?= $is_vip ? 'VIP Üye' : 'Standart Üye' ?></div>
    </div>
    <a href="profilim.php" class="drop-item mt-2"><i class="fa-solid fa-user-pen text-info"></i> Profilimi Düzenle</a>
    <a href="sonizlediklerim.php" class="drop-item"><i class="fa-solid fa-layer-group text-success"></i> Kütüphanem</a>
    <a href="paket.php" class="drop-item"><i class="fa-solid fa-gem text-primary"></i> Paketi Yükselt</a>
    <a href="logout.php" class="drop-item logout"><i class="fa-solid fa-right-from-bracket"></i> Güvenli Çıkış</a>
</div>
<?php endif; ?>
<div class="auth-overlay <?= $show_auth_modal ? 'show' : '' ?>" id="authModalOverlay">
    <div class="auth-modal">
        <button class="auth-close" onclick="closeAuthModal()"><i class="fa-solid fa-xmark"></i></button>
        <div id="loginFormBox" style="display: <?= (isset($_POST['action']) && $_POST['action']=='register') ? 'none' : 'block' ?>;">
            <div class="auth-icon"><i class="fa-solid fa-user-astronaut"></i></div>
            <h2 class="auth-title">Tekrar Hoş Geldin!</h2>
            <p class="auth-subtitle">Premium içeriklere erişmek için giriş yap.</p>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div><input type="email" name="email" class="auth-input" placeholder="E-Posta Adresi" required autocomplete="off"></div>
                <div><input type="password" name="password" class="auth-input" placeholder="Şifre" required autocomplete="off"></div>
                <button type="submit" class="auth-btn"><i class="fa-solid fa-right-to-bracket me-2"></i> Oturum Aç</button>
            </form>
            <div class="auth-switch">Hesabın yok mu? <span onclick="switchAuth('register')">Hemen Kayıt Ol</span></div>
        </div>
        <div id="registerFormBox" style="display: <?= (isset($_POST['action']) && $_POST['action']=='register') ? 'block' : 'none' ?>;">
            <div class="auth-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 10px 25px rgba(59,130,246,0.4);"><i class="fa-solid fa-user-plus"></i></div>
            <h2 class="auth-title">Aramıza Katıl!</h2>
            <p class="auth-subtitle">Reklamsız eğlence dünyasına ilk adımını at.</p>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div><input type="text" name="name" class="auth-input" placeholder="Ad Soyad" required autocomplete="off"></div>
                <div><input type="email" name="email" class="auth-input" placeholder="E-Posta Adresi" required autocomplete="off"></div>
                <div><input type="password" name="password" class="auth-input" placeholder="Şifre Belirle" required autocomplete="off"></div>
                <div class="position-relative">
                    <input type="text" name="promo_code" class="auth-input" placeholder="VIP Kodunuz (İsteğe Bağlı)" autocomplete="off" style="border-color: rgba(245,158,11,0.3);">
                    <i class="fa-solid fa-ticket position-absolute" style="right: 15px; top: 18px; color: #f59e0b;"></i>
                </div>
                <button type="submit" class="auth-btn" style="background: #3b82f6;"><i class="fa-solid fa-rocket me-2"></i> Kayıt Ol</button>
            </form>
            <div class="auth-switch">Zaten üye misin? <span style="color:#3b82f6;" onclick="switchAuth('login')">Giriş Yap</span></div>
        </div>
    </div>
</div>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js');
    }
    function toggleProfile() {
        const area = document.getElementById('userArea');
        if(area) area.classList.toggle('open');
    }
    function toggleMobileProfile() {
        const dropdown = document.getElementById('mobileProfileDropdown');
        if(dropdown) dropdown.classList.toggle('show');
    }
    function openAuthModal() {
        const overlay = document.getElementById('authModalOverlay');
        if(overlay) {
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    function closeAuthModal() {
        const overlay = document.getElementById('authModalOverlay');
        if(overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }
    function switchAuth(type) {
        const loginBox = document.getElementById('loginFormBox');
        const regBox = document.getElementById('registerFormBox');
        if(type === 'register') {
            if(loginBox) loginBox.style.display = 'none';
            if(regBox) regBox.style.display = 'block';
        } else {
            if(regBox) regBox.style.display = 'none';
            if(loginBox) loginBox.style.display = 'block';
        }
    }
    const authOverlay = document.getElementById('authModalOverlay');
    if(authOverlay) {
        authOverlay.addEventListener('click', function(e) {
            if (e.target === this) closeAuthModal();
        });
    }
    document.addEventListener('click', function(e) {
        const desktopArea = document.getElementById('userArea');
        if(desktopArea && !desktopArea.contains(e.target) && desktopArea.classList.contains('open')) {
            desktopArea.classList.remove('open');
        }
        const mobileDropdown = document.getElementById('mobileProfileDropdown');
        const mobileUserBtn = document.getElementById('mobileUserBtn');
        if(mobileDropdown && mobileDropdown.classList.contains('show')) {
            if(!mobileDropdown.contains(e.target) && (!mobileUserBtn || !mobileUserBtn.contains(e.target))) {
                mobileDropdown.classList.remove('show');
            }
        }
    });
    <?= $swal_script ?>
</script>