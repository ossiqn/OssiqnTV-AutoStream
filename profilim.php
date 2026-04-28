<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$host = 'localhost';
$dbname = 'ossiqntv';
$username = 'root';
$password = '';

try {
    $temp_db = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $temp_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $temp_db->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        password VARCHAR(255) NOT NULL
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $checkEmail = $db->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($checkEmail->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER name");
    }
    $checkPhone = $db->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($checkPhone->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
    }
} catch(PDOException $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}

require_once 'includes/header.php';

if (!$is_logged_in) {
    header("Location: index.php");
    exit;
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        if (!empty($password)) {
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $hashed_pw, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        }
        $_SESSION['user_name'] = $name; 
        $msg = "Profil bilgileriniz başarıyla güncellendi!";
        $msgType = "success";
        $user_name = $name; 
    } catch (Throwable $e) {
        $msg = "Hata oluştu: " . $e->getMessage();
        $msgType = "danger";
    }
}

$user_data = ['name' => $user_name, 'email' => '', 'phone' => ''];
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $user_data['name'] = $fetched['name'] ?? $user_name;
        $user_data['email'] = $fetched['email'] ?? '';
        $user_data['phone'] = $fetched['phone'] ?? '';
    }
} catch(Throwable $e) {}

$lic_name = 'Ücretsiz Plan (FREE)';
$lic_exp = '-';
$lic_status = 'Pasif';
$lic_color = '#a3a3a3';
$lic_icon = 'fa-user';

try {
    $stmt = $db->prepare("SELECT * FROM pf_licenses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $lic = $stmt->fetch();
    if ($lic) {
        $lic_name = $lic['product_name'];
        $lic_exp = date('d.m.Y H:i', strtotime($lic['expires_at']));
        $lic_status = $lic['status'];
        if (strpos($lic_name, 'Premium') !== false) {
            $lic_color = '#e50914';
            $lic_icon = 'fa-fire';
        } elseif (strpos($lic_name, 'Ultra') !== false) {
            $lic_color = '#eab308';
            $lic_icon = 'fa-crown';
        } else {
            $lic_color = '#22c55e';
            $lic_icon = 'fa-star';
        }
    }
} catch (Throwable $e) {}
?>
<style>
    .profile-container { margin-top: 130px; margin-bottom: 80px; max-width: 1200px; margin-left: auto; margin-right: auto; padding: 0 20px; }
    .profile-card { background: var(--card-base); border: 1px solid var(--border); border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 20px auto; }
    .avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid <?= $lic_color ?>; padding: 3px; }
    .avatar-badge { position: absolute; bottom: 0; right: 0; background: <?= $lic_color ?>; color: #fff; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid var(--card-base); font-size: 1rem; }
    .info-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    .info-value { font-size: 1.1rem; color: #fff; font-weight: 600; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
    .form-label-custom { color: #d4d4d8; font-weight: 600; font-size: 0.95rem; margin-bottom: 8px; }
    .input-glass { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 14px 16px; color: #fff; width: 100%; outline: none; transition: 0.3s; margin-bottom: 20px; font-size: 1rem; }
    .input-glass:focus { background: rgba(255,255,255,0.08); border-color: var(--primary); box-shadow: 0 0 0 4px rgba(229,9,20,0.1); }
    .btn-save { background: var(--primary); color: #fff; border: none; padding: 14px 30px; border-radius: 8px; font-weight: 700; font-size: 1.05rem; transition: 0.3s; width: 100%; box-shadow: 0 5px 15px var(--primary-glow); cursor: pointer; }
    .btn-save:hover { background: #c11119; transform: translateY(-2px); color: #fff; }
    .alert-custom { border-radius: 8px; border: none; padding: 15px 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .alert-custom.success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
    .alert-custom.danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
</style>
<div class="profile-container">
    <div class="section-header mb-4">
        <h2 class="section-title"><i class="fa-solid fa-user-gear text-primary me-2"></i> Hesap Ayarları</h2>
    </div>
    <?php if($msg): ?>
        <div class="alert-custom <?= $msgType ?> mb-4">
            <i class="fa-solid <?= $msgType == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> fs-5"></i> 
            <?= $msg ?>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="profile-card text-center h-100">
                <div class="avatar-wrapper">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=121212&color=fff&size=200" class="avatar-img" alt="Profil">
                    <div class="avatar-badge"><i class="fa-solid <?= $lic_icon ?>"></i></div>
                </div>
                <h3 class="fw-bold text-white mb-1"><?= $user_name ?></h3>
                <p class="text-muted mb-4" style="font-size: 0.9rem;"><?= htmlspecialchars($user_data['email'] ?: 'E-Posta Yok') ?></p>
                <div class="text-start mt-4 p-4" style="background: rgba(0,0,0,0.3); border-radius: 8px; border: 1px solid var(--border);">
                    <div class="info-label">Aktif Paketiniz</div>
                    <div class="info-value" style="color: <?= $lic_color ?>;"><?= $lic_name ?></div>
                    <div class="info-label mt-3">Durum</div>
                    <div class="info-value">
                        <?php if($lic_status == 'Aktif'): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= $lic_status ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="info-label mt-3">Bitiş Tarihi</div>
                    <div class="info-value mb-0 pb-0 border-0 fs-6"><?= $lic_exp ?></div>
                </div>
                <a href="paket.php" class="btn-ghost w-100 mt-4 py-3 text-center d-inline-block" style="text-decoration:none;"><i class="fa-solid fa-arrow-up me-2"></i> Paketi Yükselt</a>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="profile-card h-100">
                <h4 class="text-white fw-bold mb-4 border-bottom border-secondary pb-3">Bilgileri Güncelle</h4>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label-custom">Ad Soyad</label>
                            <input type="text" name="name" class="input-glass" value="<?= htmlspecialchars($user_data['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">E-Posta</label>
                            <input type="email" name="email" class="input-glass" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Telefon</label>
                            <input type="text" name="phone" class="input-glass" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">Yeni Şifre</label>
                            <input type="password" name="password" class="input-glass" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top border-secondary text-end">
                        <button type="submit" name="update_profile" class="btn-save" style="max-width: 250px;"><i class="fa-solid fa-floppy-disk me-2"></i> Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>