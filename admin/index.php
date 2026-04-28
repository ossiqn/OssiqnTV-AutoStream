<?php
session_start();
error_reporting(E_ALL); // Tüm hataları görelim ki kör dövüşü yapmayalım
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// --- AKILLI RADAR VERİTABANI BAĞLANTISI ---
$db_yollari = ['../config.php', '../../config.php', 'config.php', '../includes/config.php'];
$aktif_db = null;

foreach($db_yollari as $yol) {
    if(file_exists($yol)) {
        require_once $yol;
        // Senin sistemin hangi değişkeni kullanıyorsa onu yakalıyoruz
        if (isset($db) && $db instanceof PDO) $aktif_db = $db;
        elseif (isset($pdo) && $pdo instanceof PDO) $aktif_db = $pdo;
        elseif (isset($conn) && $conn instanceof PDO) $aktif_db = $conn;
        elseif (isset($baglanti) && $baglanti instanceof PDO) $aktif_db = $baglanti;
        
        if($aktif_db) break; // Bulduysak döngüyü kır
    }
}

// Eğer config.php dosyasından bulamadıysa, son çare elle bağlantı:
if (!$aktif_db) {
    try {
        // DİKKAT: EĞER HALA BAĞLANMAZSA BURADAKİ BİLGİLERİ KENDİ VERİTABANINA GÖRE DÜZENLE!
        $db_host = "localhost";
        $db_name = "ossiqntv"; // Senin veritabanı adın
        $db_user = "root";     // Senin veritabanı kullanıcı adın
        $db_pass = "";         // Senin veritabanı şifren
        
        $aktif_db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $aktif_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $aktif_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("<div style='background:#111; color:#ef4444; padding:30px; text-align:center; font-family:sans-serif;'>
                <h2><i class='fa-solid fa-triangle-exclamation'></i> KRİTİK VERİTABANI HATASI</h2>
                <p>Sistemin veritabanına ulaşılamıyor. Lütfen <b>admin/index.php</b> dosyasının 32. satırındaki veritabanı bilgilerini kontrol edin.</p>
                <p style='color:#a1a1aa; font-size:0.9rem;'>Hata Detayı: " . $e->getMessage() . "</p>
             </div>");
    }
}

// Tüm kod boyunca $aktif_db'yi kullanacağız
$db = $aktif_db;

$swal_admin = "";

// ZORUNLU TABLO İNŞASI
try {
    $db->exec("CREATE TABLE IF NOT EXISTS promo_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        package_name VARCHAR(100) NOT NULL,
        max_uses INT DEFAULT 1,
        current_uses INT DEFAULT 0
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $db->exec("ALTER TABLE promo_codes ADD COLUMN current_uses INT DEFAULT 0");
    $db->exec("ALTER TABLE promo_codes ADD COLUMN max_uses INT DEFAULT 1");
} catch(Throwable $e) {}

// SİLME İŞLEMLERİ
if (isset($_GET['del_code'])) {
    try {
        $stmt = $db->prepare("DELETE FROM promo_codes WHERE id = ?");
        $stmt->execute([$_GET['del_code']]);
    } catch(Throwable $e) {}
    header("Location: index.php");
    exit;
}

if (isset($_GET['del_user'])) {
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['del_user']]);
        $stmt2 = $db->prepare("DELETE FROM pf_licenses WHERE user_id = ?");
        $stmt2->execute([$_GET['del_user']]);
    } catch(Throwable $e) {}
    header("Location: index.php");
    exit;
}

// YENİ KOD EKLEME
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['code'])) {
    $code = preg_replace('/\s+/', '', $_POST['code']);
    $package = trim($_POST['package'] ?? '');
    $max_uses = (int)($_POST['max_uses'] ?? 0);
    
    if(!empty($code) && !empty($package) && $max_uses > 0) {
        try {
            $stmt = $db->prepare("INSERT INTO promo_codes (code, package_name, max_uses, current_uses) VALUES (?, ?, ?, 0)");
            $stmt->execute([$code, $package, $max_uses]);
            $swal_admin = "Swal.fire({icon: 'success', title: 'Harika!', text: 'VIP Kod başarıyla oluşturuldu.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#10b981'});";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $swal_admin = "Swal.fire({icon: 'error', title: 'Çakışma!', text: 'Bu kod sistemde zaten var.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
            } else {
                $hata_detayi = addslashes($e->getMessage());
                $swal_admin = "Swal.fire({icon: 'error', title: 'SQL Hatası', text: 'Detay: $hata_detayi', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
            }
        }
    } else {
        $swal_admin = "Swal.fire({icon: 'warning', title: 'Eksik Alan', text: 'Lütfen tüm alanları doldurun.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#f59e0b'});";
    }
}

// İSTATİSTİKLERİ ÇEK
$stat_users = 0; $stat_codes = 0; $stat_vips = 0;
$users = []; $codes = [];

try {
    $stat_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stat_codes = $db->query("SELECT COUNT(*) FROM promo_codes")->fetchColumn();
    $stat_vips = $db->query("SELECT COUNT(*) FROM pf_licenses WHERE status = 'Aktif' AND expires_at > NOW()")->fetchColumn();

    $users = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 50")->fetchAll();
    $codes = $db->query("SELECT * FROM promo_codes ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $err = addslashes($e->getMessage());
    $swal_admin = "Swal.fire({icon: 'error', title: 'Veri Çekme Hatası', text: '$err', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSSIQN Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #030303; font-family: 'Inter', sans-serif; color: #fff; margin:0; }
        .sidebar { width: 280px; background: #0a0a0a; border-right: 1px solid rgba(255,255,255,0.05); height: 100vh; position: fixed; padding: 30px 20px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; min-height: 100vh; }
        .brand { font-size: 2rem; font-weight: 900; letter-spacing: -1.5px; margin-bottom: 40px; display: block; text-decoration: none; color: #fff; }
        .brand span { color: #e50914; }
        .nav-btn { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: #a1a1aa; text-decoration: none; font-weight: 600; border-radius: 12px; transition: 0.3s; margin-bottom: 10px; }
        .nav-btn.active, .nav-btn:hover { background: rgba(229,9,20,0.1); color: #e50914; }
        .nav-btn.logout { color: #ef4444; margin-top: auto; position: absolute; bottom: 30px; width: calc(100% - 40px); background: rgba(239,68,68,0.1); }
        .stat-card { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 30px; display: flex; align-items: center; gap: 25px; transition: 0.3s; }
        .stat-value { font-size: 2.5rem; font-weight: 900; line-height: 1; margin-bottom: 5px; }
        .stat-label { color: #a1a1aa; font-weight: 600; font-size: 0.95rem; }
        .glass-panel { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 30px; margin-bottom: 30px; }
        .panel-title { font-weight: 800; font-size: 1.4rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; color: #fff; }
        
        .custom-input { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 14px 20px; color: #fff; width: 100%; transition: 0.3s; }
        .custom-input:focus { border-color: #e50914; outline: none; background: rgba(255,255,255,0.05); }
        
        .custom-select { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 14px 20px; color: #fff; width: 100%; transition: 0.3s; appearance: none; cursor: pointer; }
        .custom-select:focus { border-color: #e50914; outline: none; background: rgba(255,255,255,0.05); }
        .custom-select option { background-color: #111; color: #fff; font-weight: 600; }
        
        .btn-action { background: #3b82f6; color: #fff; border: none; padding: 14px 30px; border-radius: 8px; font-weight: 700; transition: 0.3s; }
        .btn-action:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59,130,246,0.3); }
        
        .table { width: 100%; color: #e5e5e5; vertical-align: middle; --bs-table-bg: transparent; border-collapse: separate; border-spacing: 0 10px; }
        .table th { color: #a1a1aa !important; font-weight: 600; border: none; padding: 10px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
        .table td { background-color: rgba(255,255,255,0.02) !important; border: none; padding: 15px 10px; color: #fff !important; }
        .table td:first-child { border-radius: 12px 0 0 12px; }
        .table td:last-child { border-radius: 0 12px 12px 0; }
        
        .badge-limit { background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); padding: 5px 12px; border-radius: 100px; font-size: 0.8rem; font-weight: 700; }
        .btn-del { background: rgba(239,68,68,0.1); color: #ef4444; border: none; width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; cursor: pointer;}
        .btn-del:hover { background: #ef4444; color: #fff; transform: rotate(90deg); }
        
        @media (max-width: 992px) { .sidebar { display:none; } .main-content { margin-left:0; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="index.php" class="brand">OSSIQN<span>ADMIN</span></a>
        <a href="index.php" class="nav-btn active"><i class="fa-solid fa-chart-pie"></i> Kontrol Paneli</a>
        <a href="../index.php" target="_blank" class="nav-btn"><i class="fa-solid fa-up-right-from-square"></i> Siteye Git</a>
        <a href="logout.php" class="nav-btn logout"><i class="fa-solid fa-power-off"></i> Güvenli Çıkış</a>
    </div>

    <div class="main-content">
        
        <div class="row g-4 mb-5">
            <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= $stat_users ?></div><div class="stat-label">Toplam Kullanıcı</div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= $stat_vips ?></div><div class="stat-label">Aktif VIP Üye</div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= $stat_codes ?></div><div class="stat-label">Oluşturulan Kod</div></div></div>
        </div>

        <div class="glass-panel">
            <h3 class="panel-title"><i class="fa-solid fa-wand-magic-sparkles text-primary"></i> VIP Kod ve Paket Oluşturucu</h3>
            <form action="" method="POST" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="code" class="custom-input" placeholder="Promosyon Kodu (Örn: VIP2026)" required autocomplete="off">
                </div>
                <div class="col-md-4">
                    <select name="package" class="custom-select" required>
                        <option value="" disabled selected>Paket Seçin</option>
                        <option value="VIP Promosyon">VIP Promosyon (Deneme)</option>
                        <option value="Premium VIP">Premium VIP (Standart)</option>
                        <option value="Ultra VIP">Ultra VIP (Gelişmiş)</option>
                        <option value="Mega VIP">Mega VIP (Özel)</option>
                        <option value="Sınırsız VIP">Sınırsız VIP (Limitsiz)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="max_uses" class="custom-input" placeholder="Kişi Sınırı" required min="1" value="50">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-action w-100">Kodu Oluştur</button>
                </div>
            </form>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="glass-panel">
                    <h3 class="panel-title"><i class="fa-solid fa-ticket text-success"></i> Aktif Kodlar</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Kod</th><th>Paket</th><th>Kullanım</th><th>İşlem</th></tr></thead>
                            <tbody>
                                <?php foreach($codes as $c): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($c['code']) ?></td>
                                    <td><?= htmlspecialchars($c['package_name']) ?></td>
                                    <td><span class="badge-limit"><?= $c['current_uses'] ?> / <?= $c['max_uses'] ?></span></td>
                                    <td><a href="?del_code=<?= $c['id'] ?>" class="btn-del" onclick="return confirm('Kodu silmek istediğinize emin misiniz?')"><i class="fa-solid fa-trash"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($codes)): ?><tr><td colspan="4" class="text-center text-muted py-4">Henüz bir kod oluşturulmadı.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="glass-panel">
                    <h3 class="panel-title"><i class="fa-solid fa-users text-info"></i> Son Kullanıcılar</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Ad Soyad</th><th>E-Posta</th><th>İşlem</th></tr></thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['name']) ?></td>
                                    <td style="color:#a1a1aa; font-size:0.9rem;"><?= htmlspecialchars($u['email']) ?></td>
                                    <td><a href="?del_user=<?= $u['id'] ?>" class="btn-del" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')"><i class="fa-solid fa-user-xmark"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($users)): ?><tr><td colspan="3" class="text-center text-muted py-4">Kayıtlı kullanıcı bulunamadı.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?= $swal_admin ?>
    </script>
</body>
</html>