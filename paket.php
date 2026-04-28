<?php
require_once 'includes/header.php';

$swal_paket_script = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upgrade_code'])) {
    if (!$is_logged_in) {
        $swal_paket_script = "Swal.fire({icon: 'warning', title: 'Giriş Gerekli', text: 'Promosyon kodu kullanmak için önce oturum açmalısınız.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
    } else {
        // Boşlukları sil ve kodu temizle
        $code = preg_replace('/\s+/', '', $_POST['promo_code'] ?? '');
        
        try {
            // Büyük küçük harf fark etmeksizin eşleştir
            $stmt = $db->prepare("SELECT * FROM promo_codes WHERE LOWER(REPLACE(code, ' ', '')) = LOWER(?)");
            $stmt->execute([$code]);
            $promo_data = $stmt->fetch();

            if (!$promo_data) {
                $swal_paket_script = "Swal.fire({icon: 'error', title: 'Geçersiz Kod', text: 'Girdiğiniz promosyon kodu hatalı veya sistemde bulunamadı.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
            } else {
                $current_uses = (int)($promo_data['current_uses'] ?? 0);
                $max_uses = (int)($promo_data['max_uses'] ?? 1);

                if ($current_uses >= $max_uses) {
                    $swal_paket_script = "Swal.fire({icon: 'warning', title: 'Limit Doldu', text: 'Bu kodun kullanım limiti dolmuş, lütfen paketlere göz atın.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
                } else {
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    if (strpos(mb_strtolower($promo_data['package_name'], 'UTF-8'), 'sınırsız') !== false || strpos(mb_strtolower($promo_data['package_name'], 'UTF-8'), 'mega') !== false) {
                        $expires = date('Y-m-d H:i:s', strtotime('+3650 days'));
                    }
                    
                    $l_stmt = $db->prepare("INSERT INTO pf_licenses (user_id, product_name, status, expires_at) VALUES (?, ?, 'Aktif', ?)");
                    $l_stmt->execute([$_SESSION['user_id'], $promo_data['package_name'], $expires]);
                    
                    $u_stmt = $db->prepare("UPDATE promo_codes SET current_uses = current_uses + 1 WHERE id = ?");
                    $u_stmt->execute([$promo_data['id']]);

                    $swal_paket_script = "Swal.fire({icon: 'success', title: 'Tebrikler!', text: '" . $promo_data['package_name'] . " ayrıcalıkları hesabınıza başarıyla tanımlandı.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#10b981'}).then(() => { window.location.href = 'index.php'; });";
                }
            }
        } catch(PDOException $e) {
            $swal_paket_script = "Swal.fire({icon: 'error', title: 'Sistem Hatası', text: 'Veritabanı ile iletişim kurulamadı.', background: '#0a0a0a', color: '#fff', confirmButtonColor: '#e50914'});";
        }
    }
}
?>
    <style>
        body { background-color: #030303; font-family: 'Inter', sans-serif; overflow-x: hidden; color: #fff; }
        
        .vip-hero { position: relative; padding: 160px 5% 80px 5%; text-align: center; overflow: hidden; }
        .vip-hero::before { content: ''; position: absolute; top: -50%; left: 50%; transform: translateX(-50%); width: 800px; height: 800px; background: radial-gradient(circle, rgba(229,9,20,0.15) 0%, transparent 70%); z-index: -1; }
        .vip-title { font-size: clamp(3rem, 5vw, 4.5rem); font-weight: 900; letter-spacing: -2px; margin-bottom: 20px; text-shadow: 0 10px 30px rgba(229,9,20,0.3); }
        .vip-title span { color: #e50914; }
        .vip-desc { font-size: 1.2rem; color: #a1a1aa; max-width: 700px; margin: 0 auto 40px auto; line-height: 1.6; }

        .pricing-container { max-width: 1400px; margin: 0 auto 100px auto; padding: 0 5%; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        
        .pack-card { background: rgba(15,15,15,0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; padding: 40px 30px; position: relative; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; overflow: hidden; }
        .pack-card::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(255,255,255,0.02) 0%, transparent 100%); pointer-events: none; }
        .pack-card:hover { transform: translateY(-10px); background: rgba(20,20,20,0.9); }
        
        .pack-red:hover { border-color: rgba(229,9,20,0.4); box-shadow: 0 20px 50px rgba(229,9,20,0.15); }
        .pack-blue:hover { border-color: rgba(59,130,246,0.4); box-shadow: 0 20px 50px rgba(59,130,246,0.15); }
        .pack-gold:hover { border-color: rgba(245,158,11,0.4); box-shadow: 0 20px 50px rgba(245,158,11,0.15); }
        .pack-gold { border-color: rgba(245,158,11,0.2); }
        
        .pack-badge { position: absolute; top: 20px; right: 20px; padding: 6px 15px; border-radius: 100px; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .badge-red { background: rgba(229,9,20,0.1); color: #ef4444; border: 1px solid rgba(229,9,20,0.2); }
        .badge-blue { background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); }
        .badge-gold { background: linear-gradient(45deg, #f59e0b, #fbbf24); color: #000; box-shadow: 0 5px 15px rgba(245,158,11,0.4); border: none; animation: pulseGold 2s infinite; }
        
        @keyframes pulseGold { 0% { box-shadow: 0 0 0 0 rgba(245,158,11,0.7); } 70% { box-shadow: 0 0 0 10px rgba(245,158,11,0); } 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); } }

        .pack-name { font-size: 1.8rem; font-weight: 900; margin-bottom: 10px; }
        .pack-price { font-size: 3rem; font-weight: 900; margin-bottom: 30px; display: flex; align-items: baseline; gap: 5px; }
        .pack-price span { font-size: 1rem; color: #71717a; font-weight: 600; }
        
        .pack-features { list-style: none; padding: 0; margin: 0 0 40px 0; flex-grow: 1; }
        .pack-features li { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; color: #d4d4d8; font-weight: 500; font-size: 0.95rem; }
        .pack-features li i { color: #10b981; font-size: 1.1rem; }
        .pack-features li.disabled { color: #52525b; }
        .pack-features li.disabled i { color: #52525b; }
        
        .btn-pack { width: 100%; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 1.05rem; text-align: center; text-decoration: none; transition: 0.3s; cursor: pointer; border: none; display: inline-block; }
        .btn-red { background: #e50914; color: #fff; }
        .btn-red:hover { background: #dc2626; box-shadow: 0 10px 25px rgba(229,9,20,0.4); color: #fff; }
        .btn-blue { background: #3b82f6; color: #fff; }
        .btn-blue:hover { background: #2563eb; box-shadow: 0 10px 25px rgba(59,130,246,0.4); color: #fff; }
        .btn-gold { background: linear-gradient(45deg, #f59e0b, #fbbf24); color: #000; }
        .btn-gold:hover { transform: scale(1.05); box-shadow: 0 15px 30px rgba(245,158,11,0.4); color: #000; }

        .btn-buy-outline { width: 100%; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 1rem; text-align: center; text-decoration: none; transition: 0.3s; cursor: pointer; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin-top: 12px; }
        .btn-buy-outline:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.3); color: #fff; transform: translateY(-2px); }

        .promo-section { max-width: 600px; margin: 0 auto 100px auto; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 40px; border-radius: 24px; backdrop-filter: blur(20px); text-align: center; }
        .promo-input { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 18px 24px; color: #fff; width: 100%; font-size: 1.1rem; font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 2px; transition: 0.3s; margin-bottom: 20px; }
        .promo-input:focus { border-color: #e50914; outline: none; box-shadow: 0 0 20px rgba(229,9,20,0.2); }
        .promo-input::placeholder { color: #52525b; text-transform: none; letter-spacing: normal; font-weight: 500; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(15px); z-index: 9999; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; padding: 20px; }
        .modal-overlay.show { display: flex; opacity: 1; }
        .buy-modal { background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 30px 60px rgba(0,0,0,0.9); transform: translateY(30px); transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; }
        .modal-overlay.show .buy-modal { transform: translateY(0); }
        
        .contact-btn { display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; text-decoration: none; transition: 0.3s; margin-bottom: 15px; color: #fff; border: none; cursor: pointer; }
        .btn-wa { background: #25D366; }
        .btn-wa:hover { background: #128C7E; transform: scale(1.02); color: #fff; box-shadow: 0 10px 25px rgba(37,211,102,0.4); }
        .btn-tg { background: #0088cc; }
        .btn-tg:hover { background: #0077b5; transform: scale(1.02); color: #fff; box-shadow: 0 10px 25px rgba(0,136,204,0.4); }
        
        .close-modal { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.05); border: none; color: #a1a1aa; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; transition: 0.3s; }
        .close-modal:hover { background: rgba(239,68,68,0.1); color: #ef4444; transform: rotate(90deg); }
    </style>

    <div class="vip-hero">
        <div class="hero-tag-premium d-inline-block mb-3 px-4 py-2 rounded-pill" style="background: rgba(229,9,20,0.1); border: 1px solid rgba(229,9,20,0.3); color: #ef4444; font-weight: 800; font-size: 0.8rem; letter-spacing: 2px;"><i class="fa-solid fa-crown me-2"></i>OSSIQN PREMIUM</div>
        <h1 class="vip-title">Sınırları Kaldır. <span>Özgürce İzle.</span></h1>
        <p class="vip-desc">Reklamsız, kesintisiz ve en yüksek kalitede sinema & canlı spor deneyimi için hesabınızı hemen yükseltin. Eğlencenin yeni boyutuna geçiş yapın.</p>
    </div>

    <div class="pricing-container">
        <div class="pack-card pack-red">
            <div class="pack-badge badge-red">En Popüler</div>
            <div class="pack-name text-white">Premium VIP</div>
            <div class="pack-price">₺99<span>/ay</span></div>
            <ul class="pack-features">
                <li><i class="fa-solid fa-check"></i> Reklamsız İzleme Deneyimi</li>
                <li><i class="fa-solid fa-check"></i> 1080p Full HD Kalite</li>
                <li><i class="fa-solid fa-check"></i> Tüm Film ve Diziler</li>
                <li><i class="fa-solid fa-check"></i> Standart Canlı Yayınlar</li>
                <li class="disabled"><i class="fa-solid fa-xmark"></i> 4K Ultra HD Desteği</li>
                <li class="disabled"><i class="fa-solid fa-xmark"></i> Çoklu Ekran (1 Ekran)</li>
            </ul>
            <a href="#promoBox" class="btn-pack btn-red">Kodu Kullan</a>
            <button class="btn-buy-outline" onclick="openBuyModal('Premium VIP', '₺99/ay')"><i class="fa-solid fa-cart-shopping"></i> Paketi Satın Al</button>
        </div>

        <div class="pack-card pack-blue">
            <div class="pack-badge badge-blue">Gelişmiş</div>
            <div class="pack-name" style="color: #60a5fa;">Ultra VIP</div>
            <div class="pack-price">₺199<span>/ay</span></div>
            <ul class="pack-features">
                <li><i class="fa-solid fa-check"></i> Kusursuz Reklamsız Deneyim</li>
                <li><i class="fa-solid fa-check"></i> 4K Ultra HD & HDR10</li>
                <li><i class="fa-solid fa-check"></i> Tüm İçeriklere Erken Erişim</li>
                <li><i class="fa-solid fa-check"></i> Premium Kesintisiz Spor</li>
                <li><i class="fa-solid fa-check"></i> Aynı Anda 3 Ekran</li>
                <li class="disabled"><i class="fa-solid fa-xmark"></i> Ömür Boyu Erişim</li>
            </ul>
            <a href="#promoBox" class="btn-pack btn-blue">Kodu Kullan</a>
            <button class="btn-buy-outline" onclick="openBuyModal('Ultra VIP', '₺199/ay')"><i class="fa-solid fa-cart-shopping"></i> Paketi Satın Al</button>
        </div>

        <div class="pack-card pack-gold">
            <div class="pack-badge badge-gold"><i class="fa-solid fa-fire me-1"></i> Efsane</div>
            <div class="pack-name" style="color: #fbbf24;">Sınırsız VIP</div>
            <div class="pack-price">₺999<span>/tek sefer</span></div>
            <ul class="pack-features">
                <li><i class="fa-solid fa-check text-warning"></i> Sınırsız / Ömür Boyu Erişim</li>
                <li><i class="fa-solid fa-check text-warning"></i> 8K & 4K İnanılmaz Kalite</li>
                <li><i class="fa-solid fa-check text-warning"></i> Sıfır Gecikmeli Canlı Maçlar</li>
                <li><i class="fa-solid fa-check text-warning"></i> Özel Öncelikli Sunucu Ağı</li>
                <li><i class="fa-solid fa-check text-warning"></i> Aynı Anda Sınırsız Ekran</li>
                <li><i class="fa-solid fa-check text-warning"></i> VIP Müşteri Temsilcisi</li>
            </ul>
            <a href="#promoBox" class="btn-pack btn-gold">Kodu Kullan</a>
            <button class="btn-buy-outline" onclick="openBuyModal('Sınırsız VIP', '₺999/tek sefer')"><i class="fa-solid fa-cart-shopping"></i> Paketi Satın Al</button>
        </div>
    </div>

    <div class="promo-section" id="promoBox">
        <h2 style="font-weight: 900; margin-bottom: 10px;"><i class="fa-solid fa-ticket text-danger me-2"></i> VIP Kodunuz Var Mı?</h2>
        <p style="color: #a1a1aa; margin-bottom: 30px; font-size: 0.95rem;">Satın aldığınız veya size hediye edilen promosyon kodunu aşağıya girerek hesabınızı anında yükseltebilirsiniz.</p>
        <form method="POST">
            <input type="hidden" name="upgrade_code" value="1">
            <input type="text" name="promo_code" class="promo-input" placeholder="Promosyon Kodunu Girin" required autocomplete="off">
            <button type="submit" class="btn-pack btn-red w-100" style="padding: 18px;"><i class="fa-solid fa-unlock-keyhole me-2"></i> Hesabımı Yükselt</button>
        </form>
    </div>

    <div class="modal-overlay" id="buyModal">
        <div class="buy-modal">
            <button class="close-modal" onclick="closeBuyModal()"><i class="fa-solid fa-xmark"></i></button>
            <div style="width: 80px; height: 80px; background: rgba(59,130,246,0.1); color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 25px auto;">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <h3 style="font-weight: 900; font-size: 1.8rem; margin-bottom: 15px; color: #fff;">Hızlı Satın Al</h3>
            <p style="color: #a1a1aa; font-size: 1rem; margin-bottom: 30px; line-height: 1.6;">Yakında sitemize otomatik ödeme altyapısı eklenecektir. Şimdilik aşağıdaki iletişim kanallarımızdan birini seçerek VIP kodunuzu anında ve güvenle satın alabilirsiniz.</p>
            
            <a href="#" id="waLink" target="_blank" class="contact-btn btn-wa">
                <i class="fa-brands fa-whatsapp fs-4"></i> WhatsApp ile Satın Al
            </a>
            <a href="#" id="tgLink" target="_blank" class="contact-btn btn-tg">
                <i class="fa-brands fa-telegram fs-4"></i> Telegram ile Satın Al
            </a>
        </div>
    </div>

    <script>
        const WA_PHONE = "905000000000"; 
        const TG_USERNAME = "ossiqntv"; 

        function openBuyModal(pkgName, pkgPrice) {
            const msg = `Merhaba, OSSIQN TV üzerinden ${pkgName} (${pkgPrice}) paketini satın almak istiyorum. Yardımcı olabilir misiniz?`;
            
            document.getElementById('waLink').href = `https://wa.me/${WA_PHONE}?text=${encodeURIComponent(msg)}`;
            document.getElementById('tgLink').href = `https://t.me/${TG_USERNAME}?text=${encodeURIComponent(msg)}`;
            
            document.getElementById('buyModal').classList.add('show');
        }

        function closeBuyModal() {
            document.getElementById('buyModal').classList.remove('show');
        }

        document.getElementById('buyModal').addEventListener('click', function(e) {
            if (e.target === this) closeBuyModal();
        });

        // SWEETALERT POP-UP
        <?= $swal_paket_script ?>
    </script>

<?php require_once 'includes/footer.php'; ?>