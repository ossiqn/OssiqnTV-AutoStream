<?php require_once 'includes/header.php'; ?>
<style>
    body { background-color: #030303; font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .library-header { padding: 120px 5% 20px 5%; max-width: 1600px; margin: 0 auto; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px; }
    .library-title { font-size: 2.8rem; font-weight: 900; color: #fff; letter-spacing: -1px; margin: 0; display: flex; align-items: center; gap: 15px; }
    .library-title i { color: #3b82f6; text-shadow: 0 0 20px rgba(59,130,246,0.5); }
    .section-header { display: flex; justify-content: space-between; align-items: center; padding: 0 5%; max-width: 1600px; margin: 0 auto 15px auto; }
    .section-title { font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px; }
    .btn-clear { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; outline: none; font-size: 0.9rem; }
    .btn-clear:hover, .btn-clear:focus { background: #ef4444; color: #fff; box-shadow: 0 5px 15px rgba(239,68,68,0.4); }
    .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; padding: 0 5% 50px 5%; max-width: 1600px; margin: 0 auto; min-height: 25vh; }
    .media-card { aspect-ratio: 2/3; position: relative; border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); background: #0a0a0a; border: 1px solid rgba(255,255,255,0.05); outline: none; }
    .media-card:hover, .media-card:focus { transform: scale(1.05) translateY(-5px); z-index: 20; border-color: #e50914; box-shadow: 0 20px 40px rgba(0,0,0,0.8); }
    .media-card img { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; transition: 0.4s; }
    .media-card:hover img, .media-card:focus img { opacity: 1; }
    .card-info { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, transparent 70%); display: flex; flex-direction: column; justify-content: flex-end; padding: 15px; }
    .card-title { color: #fff; font-weight: 800; font-size: 0.95rem; line-height: 1.2; margin-bottom: 5px; text-shadow: 0 2px 10px rgba(0,0,0,0.9); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .badge-type { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.65rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1); z-index: 5; }
    .remove-btn { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.2); transition: 0.3s; z-index: 10; backdrop-filter: blur(5px); }
    .remove-btn:hover { background: #e50914; border-color: #e50914; transform: scale(1.1); }
    .empty-state { text-align: center; padding: 60px 20px; color: #a1a1aa; grid-column: 1 / -1; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.05); }
    .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.3; }
    .empty-state h3 { font-weight: 800; color: #fff; margin-bottom: 5px; font-size: 1.2rem; }
    @media (max-width: 768px) { .library-header { padding-top: 100px; } .library-title { font-size: 2rem; } .media-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; } .section-title { font-size: 1.2rem; } }
</style>

<div class="library-header">
    <h1 class="library-title"><i class="fa-solid fa-layer-group"></i> Kütüphanem</h1>
</div>

<div class="section-header">
    <h2 class="section-title"><i class="fa-solid fa-heart text-danger"></i> Favorilerim & Listem</h2>
</div>
<div class="media-grid" id="favoritesGrid"></div>

<div class="section-header" style="margin-top: 20px;">
    <h2 class="section-title"><i class="fa-solid fa-clock-rotate-left text-secondary"></i> İzleme Geçmişim</h2>
    <button class="btn-clear" id="clearHistoryBtn" onclick="clearHistory()"><i class="fa-solid fa-trash-can me-1"></i> Temizle</button>
</div>
<div class="media-grid" id="historyGrid"></div>

<script>
    function loadLibraryPage() {
        loadFavorites();
        loadHistory();
    }

    function loadFavorites() {
        const list = JSON.parse(localStorage.getItem('ossiqn_mylist') || '[]');
        const grid = document.getElementById('favoritesGrid');
        
        if (list.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-bookmark"></i>
                    <h3>Listeniz Boş</h3>
                    <p style="margin:0; font-size:0.9rem;">İzlemek istediğiniz içerikleri listenize ekleyin.</p>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = list.map(item => `
            <div class="media-card" tabindex="0" onkeydown="if(event.key==='Enter') window.location.href='watch.php?id=${item.id}&type=${item.type}'" onclick="window.location.href='watch.php?id=${item.id}&type=${item.type}'">
                <div class="badge-type">${item.type === 'movie' ? 'FİLM' : (item.type === 'sport' ? 'SPOR' : 'DİZİ')}</div>
                <div class="remove-btn" onclick="event.stopPropagation(); removeFromFavorites('${item.id}')"><i class="fa-solid fa-xmark"></i></div>
                <img src="${item.poster}" onerror="this.src='https://placehold.co/300x450/111/e50914?text=Resim+Yok'">
                <div class="card-info">
                    <div class="card-title">${item.title}</div>
                </div>
            </div>
        `).join('');
    }

    function loadHistory() {
        const list = JSON.parse(localStorage.getItem('ossiqn_continue') || '[]');
        const grid = document.getElementById('historyGrid');
        const btn = document.getElementById('clearHistoryBtn');
        
        if (list.length === 0) {
            btn.style.display = 'none';
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-ghost"></i>
                    <h3>Geçmiş Tertemiz</h3>
                    <p style="margin:0; font-size:0.9rem;">İzlediğiniz içerikler burada listelenir.</p>
                </div>
            `;
            return;
        }
        
        btn.style.display = 'block';
        grid.innerHTML = list.map(item => `
            <div class="media-card" tabindex="0" onkeydown="if(event.key==='Enter') window.location.href='watch.php?id=${item.id}&type=${item.type}'" onclick="window.location.href='watch.php?id=${item.id}&type=${item.type}'">
                <div class="badge-type" style="background: rgba(229,9,20,0.8);">${item.type === 'movie' ? 'FİLM' : (item.type === 'sport' ? 'SPOR' : 'DİZİ')}</div>
                <img src="${item.poster}" onerror="this.src='https://placehold.co/300x450/111/e50914?text=Resim+Yok'">
                <div class="card-info">
                    <div class="card-title">${item.title}</div>
                </div>
            </div>
        `).join('');
    }

    function removeFromFavorites(id) {
        let list = JSON.parse(localStorage.getItem('ossiqn_mylist') || '[]');
        list = list.filter(i => i.id !== id);
        localStorage.setItem('ossiqn_mylist', JSON.stringify(list));
        loadFavorites();
        Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Listeden çıkarıldı.', showConfirmButton: false, timer: 1500, background: '#0a0a0a', color: '#fff' });
    }

    function clearHistory() {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Tüm izleme geçmişiniz silinecek ve geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e50914',
            cancelButtonColor: '#334155',
            confirmButtonText: 'Evet, Temizle',
            cancelButtonText: 'Vazgeç',
            background: '#0a0a0a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                localStorage.removeItem('ossiqn_continue');
                loadHistory();
                Swal.fire({
                    title: 'Temizlendi!',
                    text: 'İzleme geçmişiniz başarıyla silindi.',
                    icon: 'success',
                    background: '#0a0a0a',
                    color: '#fff',
                    confirmButtonColor: '#10b981'
                });
            }
        });
    }

    window.addEventListener('load', loadLibraryPage);
</script>
<?php require_once 'includes/footer.php'; ?>