<?php
session_start();

// Veritabanı Bağlantı Ayarları
$host = 'localhost';
$dbname = 'ossiqntv';
$username = 'root'; // Localhost kullanıyorsan genelde 'root'tur.
$password = '';     // Localhost şifresi genelde boştur.

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Giriş İşlemi
if (isset($_POST['login'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if (empty($user) || empty($pass)) {
        $error = "Lütfen tüm alanları doldurun.";
    } else {
        // SQL Injection korumalı sorgu
        // Not: Şifreleri düz metin (plain text) olarak kaydettiğin için direkt karşılaştırıyoruz.
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username AND password = :password");
        $stmt->execute(['username' => $user, 'password' => $pass]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($account) {
            // Oturum Değişkenlerini Ata
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = $account['username'];
            $_SESSION['admin_id'] = $account['id'];
            
            // Yönlendirme
            header("Location: index.php");
            exit;
        } else {
            $error = "Kullanıcı adı veya şifre hatalı!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Giriş | OSSIQN TV</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }
    .login-container {
        background: rgba(255, 255, 255, 0.05);
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }
    .logo {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 10px;
        background: linear-gradient(to right, #00c6ff, #0072ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: 2px;
    }
    .subtitle {
        color: #aaa;
        font-size: 0.9rem;
        margin-bottom: 30px;
    }
    .form-group {
        margin-bottom: 20px;
        text-align: left;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.85rem;
        color: #ccc;
    }
    .input-wrapper {
        position: relative;
    }
    .input-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #00c6ff;
    }
    .form-control {
        width: 100%;
        padding: 12px 12px 12px 45px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.2);
        color: #fff;
        font-size: 0.95rem;
        transition: 0.3s;
    }
    .form-control:focus {
        outline: none;
        border-color: #00c6ff;
        background: rgba(0,0,0,0.4);
    }
    .btn-login {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: none;
        background: linear-gradient(to right, #00c6ff, #0072ff);
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 10px;
    }
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 114, 255, 0.4);
    }
    .alert {
        background: rgba(231, 76, 60, 0.2);
        border: 1px solid #e74c3c;
        color: #ffcccc;
        padding: 10px;
        border-radius: 8px;
        font-size: 0.85rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>
</head>
<body>

<div class="login-container">
    <div class="logo">OSSIQN TV</div>
    <p class="subtitle">Yönetim Paneli Girişi</p>

    <?php if(isset($error)): ?>
        <div class="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Kullanıcı Adı</label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="username" class="form-control" placeholder="Kullanıcı adınız" required autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label>Şifre</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-control" placeholder="Şifreniz" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn-login">Giriş Yap <i class="fas fa-arrow-right" style="margin-left:5px"></i></button>
    </form>
</div>

</body>
</html>