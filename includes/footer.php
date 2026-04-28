<footer style="background: #0a0a0a; border-top: 1px solid rgba(255,255,255,0.05); padding: 40px 0; margin-top: 60px; text-align: center; color: #71717a; font-size: 0.9rem; padding-bottom: 90px;">
    <div class="container">
        <div style="font-weight: 900; font-size: 1.5rem; color: #fff; margin-bottom: 10px; letter-spacing: -1px;">
            OSSIQN<span style="color: #e50914;">TV</span>
        </div>
        <p style="margin-bottom: 0;">Tüm hakları saklıdır &copy; 2026 OSSIQN TV. Sadece Premium üyelere özeldir.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const VIP_STATUS = <?= $is_vip ? 'true' : 'false'; ?>;
    const LOGGED_IN = <?= $is_logged_in ? 'true' : 'false'; ?>;
    const FALLBACK_POSTER = 'https://placehold.co/500x750/111111/e50914.png?text=Afi%C5%9F+Yok';
    
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof buildAppIndex === 'function') buildAppIndex();
        if(typeof buildMoviesApp === 'function') buildMoviesApp();
        if(typeof buildSeriesApp === 'function') buildSeriesApp();
    });

    function actionPlay(id, sCode, typeFlag) {
        if(!LOGGED_IN || !VIP_STATUS) {
            Swal.fire({
                icon: 'lock',
                title: 'Premium Erişim',
                text: 'Bu içeriği izleyebilmek için aktif bir VIP paketine sahip olmalısınız.',
                background: '#0a0a0a',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#e50914',
                cancelButtonColor: '#3b82f6',
                confirmButtonText: 'Paketleri İncele',
                cancelButtonText: LOGGED_IN ? 'Kapat' : 'Giriş Yap / Kayıt Ol'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'paket.php';
                } else if (result.dismiss === Swal.DismissReason.cancel && !LOGGED_IN) {
                    openAuthModal();
                }
            });
        } else {
            let url = '';
            if(typeFlag === 'movie' || typeFlag === true) {
                url = `watch.php?id=${id}&type=movie`;
            } else if(typeFlag === 'series') {
                url = `watch.php?id=${id}&type=series`;
            } else {
                url = `watch.php?id=${id}&s=${sCode}&type=sport`;
            }
            window.location.href = url;
        }
    }
</script>
</body>
</html>