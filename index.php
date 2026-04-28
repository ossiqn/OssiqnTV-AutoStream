<?php require_once 'includes/header.php'; ?>
    <style>
        body { background-color: #030303; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .hero-showcase { position: relative; height: 90vh; min-height: 650px; display: flex; align-items: flex-end; padding-bottom: 5%; overflow: hidden; margin-top: -100px; }
        .hero-bg { position: absolute; inset: 0; background-size: cover; background-position: 50% 20%; transition: transform 10s linear, opacity 1s ease; opacity: 0.6; transform: scale(1.05); }
        .hero-showcase:hover .hero-bg { transform: scale(1); opacity: 0.8; }
        .hero-vignette { position: absolute; inset: 0; background: radial-gradient(circle at 70% 50%, transparent 0%, #030303 100%), linear-gradient(to top, #030303 0%, rgba(3,3,3,0.2) 50%, rgba(3,3,3,0.8) 100%); }
        .hero-content { position: relative; z-index: 10; padding: 0 5%; width: 100%; max-width: 1600px; margin: 0 auto; }
        .hero-tag-premium { display: inline-flex; align-items: center; gap: 8px; background: rgba(229,9,20,0.15); backdrop-filter: blur(20px); border: 1px solid rgba(229,9,20,0.4); color: #fff; padding: 8px 20px; border-radius: 100px; font-weight: 800; font-size: 0.75rem; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 25px; box-shadow: 0 5px 20px rgba(229,9,20,0.2); }
        .hero-tag-premium i { color: #fff; font-size: 1rem; animation: pulse 2s infinite; }
        .hero-title { font-size: clamp(2.5rem, 5vw, 4.5rem); font-weight: 900; line-height: 1.1; margin-bottom: 20px; color: #fff; text-shadow: 0 10px 40px rgba(0,0,0,0.9); letter-spacing: -2px; }
        .hero-plot { font-size: 1.15rem; color: #d4d4d8; max-width: 650px; line-height: 1.7; margin-bottom: 35px; text-shadow: 0 2px 15px rgba(0,0,0,0.9); font-weight: 500; }
        .btn-play-massive { background: #fff; color: #000; font-size: 1.1rem; font-weight: 800; padding: 16px 45px; border-radius: 100px; border: none; display: inline-flex; align-items: center; gap: 12px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; box-shadow: 0 10px 30px rgba(255,255,255,0.2); }
        .btn-play-massive:hover { background: #e50914; color: #fff; transform: scale(1.05); box-shadow: 0 15px 40px rgba(229,9,20,0.5); }
        .section-header { font-size: 1.5rem; font-weight: 800; color: #f8fafc; margin-bottom: 20px; letter-spacing: -0.5px; }
        .grid-container { padding: 0 5%; max-width: 1600px; margin: 0 auto; }
        .category-scroller { display: flex; overflow-x: auto; scroll-behavior: smooth; gap: 15px; padding: 10px 0 20px 0; -ms-overflow-style: none; scrollbar-width: none; }
        .category-scroller::-webkit-scrollbar { display: none; }
        .cat-item { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #e4e4e7; padding: 10px 24px; border-radius: 100px; white-space: nowrap; cursor: pointer; transition: 0.3s; font-weight: 600; font-size: 0.95rem; backdrop-filter: blur(10px); }
        .cat-item.active, .cat-item:hover { background: #e50914; color: #fff; border-color: #e50914; box-shadow: 0 5px 15px rgba(229,9,20,0.4); }
        .match-card { aspect-ratio: 16/9; position: relative; border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); background: #0a0a0a; border: 1px solid rgba(255,255,255,0.03); display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .match-card:hover { transform: scale(1.05) translateY(-5px); z-index: 20; box-shadow: 0 25px 50px rgba(0,0,0,0.9); border-color: rgba(255,255,255,0.15); }
        .teams-wrapper { display: flex; align-items: center; justify-content: center; gap: 15px; width: 100%; height: 100%; position: absolute; inset: 0; opacity: 0.4; transition: 0.4s; }
        .match-card:hover .teams-wrapper { opacity: 0.8; transform: scale(1.1); }
        .team-logo { width: 70px; height: 70px; object-fit: contain; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.8)); }
        .vs-text { font-weight: 900; color: rgba(255,255,255,0.2); font-size: 1.5rem; font-style: italic; }
        .card-details { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.5) 60%, transparent 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 15px; opacity: 1; pointer-events: none; }
        .play-icon-glass { width: 45px; height: 45px; border-radius: 50%; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.3); transition: 0.4s; transform: translateY(15px); opacity: 0; }
        .match-card:hover .play-icon-glass { transform: translateY(0); background: #e50914; border-color: #e50914; box-shadow: 0 0 20px rgba(229,9,20,0.6); opacity: 1; }
        .card-title { color: #fff; font-weight: 800; font-size: 1rem; line-height: 1.3; margin-bottom: 5px; text-shadow: 0 2px 5px rgba(0,0,0,0.9); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .badge-live { position: absolute; top: 12px; right: 12px; background: #e50914; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; z-index: 3; box-shadow: 0 2px 10px rgba(229,9,20,0.5); animation: pulse 2s infinite; }
        .badge-past { position: absolute; top: 12px; right: 12px; background: #3f3f46; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; z-index: 3; }
        .badge-time { position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 800; z-index: 3; border: 1px solid rgba(255,255,255,0.1); }
        .skel-box-wide { width: 100%; aspect-ratio: 16/9; background: linear-gradient(90deg, #0a0a0a 25%, #171717 50%, #0a0a0a 75%); background-size: 200% 100%; animation: skeletonLoading 1.5s infinite; border-radius: 12px; border: 1px solid rgba(255,255,255,0.02); }
        @keyframes pulse { 0% { opacity: 1; box-shadow: 0 0 0 0 rgba(229,9,20,0.7); } 70% { opacity: 0.8; box-shadow: 0 0 0 10px rgba(229,9,20,0); } 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(229,9,20,0); } }
        @keyframes skeletonLoading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @media (max-width: 992px) { .hero-showcase { min-height: 500px; height: 70vh; } .hero-title { font-size: 2.5rem; } .hero-plot { font-size: 1rem; } }
    </style>

    <div id="mainContainers">
        <section class="hero-showcase" id="mainHero" style="display:none;">
            <div class="hero-bg" id="heroBgImg"></div>
            <div class="hero-vignette"></div>
            <div class="hero-content" id="heroInner">
                <div class="skel-box-wide" style="width: 160px; height: 35px; border-radius: 100px; margin-bottom: 25px;"></div>
                <div class="skel-box-wide" style="width: 60%; height: 70px; border-radius: 8px; margin-bottom: 20px;"></div>
                <div class="skel-box-wide" style="width: 40%; height: 24px; border-radius: 4px; margin-bottom: 40px;"></div>
                <div class="skel-box-wide" style="width: 200px; height: 55px; border-radius: 100px;"></div>
            </div>
        </section>

        <div class="grid-container mt-4 mb-4">
            <div class="category-scroller" id="sportsFilter">
                <button class="cat-item active" data-id="live"><i class="fa-solid fa-satellite-dish me-2"></i>Canlı Yayınlar</button>
                <button class="cat-item" data-id="past"><i class="fa-solid fa-clock-rotate-left me-2"></i>Geçmiş Yayınlar</button>
            </div>
        </div>

        <div class="grid-container mb-5 pb-5">
            <div class="section-header" id="gridHeading">Yayınlar Yükleniyor...</div>
            <div class="row g-4" id="matchLayout">
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
                <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-5 pt-5 pb-5" id="searchContainer" style="display:none; background: var(--bg-dark); min-height: 80vh;">
        <div class="grid-container">
            <div class="section-header" style="display:flex; justify-content:space-between; align-items:center;">
                Arama Sonuçları
                <button class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold" onclick="clearSearch()">Aramayı Kapat</button>
            </div>
            <div class="row g-4" id="searchGrid"></div>
        </div>
    </div>

    <script>
        window.STREAM_API = 'https://streamed.pk/api';
        window.LOGO_FALLBACK = 'https://ui-avatars.com/api/?name=VS&background=18181b&color=fff&rounded=true&size=72';
        window.idxSearchData = [];

        function buildAppIndex() {
            getCategoriesIndex();
            fetchContentIndex('live', 'Canlı Yayınlar');
            initSearchHome();
        }

        async function getCategoriesIndex() {
            try {
                const req = await fetch(`${window.STREAM_API}/sports`);
                const data = await req.json();
                const box = document.getElementById('sportsFilter');
                const sportIcons = { 'football': '<i class="fa-solid fa-futbol me-2"></i>Futbol', 'basketball': '<i class="fa-solid fa-basketball me-2"></i>Basketbol', 'tennis': '<i class="fa-solid fa-table-tennis-paddle-ball me-2"></i>Tenis' };
                
                data.forEach(s => {
                    const btn = document.createElement('button');
                    btn.className = 'cat-item';
                    btn.innerHTML = sportIcons[s.id] || s.name;
                    btn.dataset.id = s.id;
                    btn.onclick = (e) => {
                        document.querySelectorAll('.cat-item').forEach(b => b.classList.remove('active'));
                        e.currentTarget.classList.add('active');
                        fetchContentIndex(s.id, s.name);
                    };
                    box.appendChild(btn);
                });
            } catch(e) {}
        }

        async function fetchContentIndex(path, titleText) {
            const layout = document.getElementById('matchLayout');
            document.getElementById('gridHeading').textContent = titleText;
            layout.innerHTML = Array(8).fill('<div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12"><div class="skel-box-wide"></div></div>').join('');
            
            try {
                let reqUrl = (path === 'live') ? `${window.STREAM_API}/matches/live` : (path === 'past' ? `${window.STREAM_API}/matches/all` : `${window.STREAM_API}/matches/${path}`);
                const req = await fetch(reqUrl);
                let fetchedData = await req.json();
                
                if(path === 'past') fetchedData = fetchedData.filter(m => new Date(m.date).getTime() < Date.now()).slice(0, 24);
                window.idxSearchData = fetchedData;
                
                if(fetchedData.length > 0) {
                    drawHeroIndex(fetchedData[0], path);
                } else {
                    document.getElementById('mainHero').style.display = 'none';
                }
                drawGridIndex(fetchedData, path);
            } catch(e) {
                layout.innerHTML = '<div class="col-12 py-5 text-muted text-center"><i class="fa-solid fa-triangle-exclamation fs-1 mb-3"></i><br>Yayınlar şu an yüklenemiyor.</div>';
            }
        }

        function drawHeroIndex(target, currentPath) {
            document.getElementById('mainHero').style.display = 'flex';
            const inner = document.getElementById('heroInner');
            const bg = document.getElementById('heroBgImg');
            
            let cover = target.poster ? `https://streamed.pk${target.poster}.webp` : (target.teams ? `${window.STREAM_API}/images/badge/${target.teams.home.badge}.webp` : (window.FALLBACK_POSTER || ''));
            bg.style.backgroundImage = `url('${cover}')`;
            
            const rawJson = target.sources ? JSON.stringify(target.sources) : '[]';
            const sCode = encodeURIComponent(rawJson).replace(/'/g, "%27");
            
            let timeStr = target.date ? new Date(target.date).toLocaleString('tr-TR', {hour: '2-digit', minute:'2-digit'}) : '';
            
            inner.innerHTML = `
                <div class="hero-tag-premium"><i class="fa-solid fa-circle-dot"></i> ${currentPath === 'past' ? 'GEÇMİŞ YAYIN' : 'CANLI YAYIN'}</div>
                <h1 class="hero-title">${target.title}</h1>
                <p class="hero-plot">En yüksek kalitede kesintisiz premium yayın deneyimi. ${timeStr ? '<br><i class="fa-regular fa-clock text-primary"></i> <b>' + timeStr + '</b> itibarıyla başlıyor.' : ''}</p>
                <div class="d-flex gap-3 flex-wrap">
                    <button class="btn-play-massive" onclick="actionPlay('${target.id}', '${sCode}', 'sport')"><i class="fa-solid fa-play"></i> Hemen İzle</button>
                    <a href="paket.php" class="btn-play-massive" style="background: rgba(255,255,255,0.1); color: #fff; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); text-decoration:none;"><i class="fa-solid fa-crown text-warning"></i> VIP Ayrıcalıkları</a>
                    <a href="ossiqntv.apk" download class="btn-play-massive" style="background: #00A98F; color: #fff; border: none; text-decoration:none; box-shadow: 0 10px 30px rgba(0, 169, 143, 0.3);"><i class="fa-brands fa-android fs-4"></i> Uygulamayı İndir</a>
                </div>
            `;
        }

        function drawGridIndex(arr, currentPath) {
            const layout = document.getElementById('matchLayout');
            layout.innerHTML = '';
            if(!arr || arr.length === 0) { layout.innerHTML = '<div class="col-12 py-5 text-muted text-center"><i class="fa-solid fa-ghost fs-1 mb-3"></i><br>İçerik bulunamadı.</div>'; return; }
            
            arr.forEach(m => {
                const rawJson = m.sources ? JSON.stringify(m.sources) : '[]';
                const sCode = encodeURIComponent(rawJson).replace(/'/g, "%27");
                
                let coverHtml = '';
                if(m.teams && m.teams.home && m.teams.away) {
                    coverHtml = `
                        <div class="teams-wrapper">
                            <img src="${window.STREAM_API}/images/badge/${m.teams.home.badge}.webp" class="team-logo" onerror="this.src='${window.LOGO_FALLBACK}'">
                            <span class="vs-text">VS</span>
                            <img src="${window.STREAM_API}/images/badge/${m.teams.away.badge}.webp" class="team-logo" onerror="this.src='${window.LOGO_FALLBACK}'">
                        </div>`;
                } else if(m.poster) {
                    coverHtml = `<img src="https://streamed.pk${m.poster}.webp" style="width:100%; height:100%; object-fit:cover;" onerror="this.src=window.FALLBACK_POSTER">`;
                } else {
                    coverHtml = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-satellite-dish fs-1 text-muted"></i></div>`;
                }

                let badge = currentPath === 'past' ? '<div class="badge-past">Tekrar</div>' : '<div class="badge-live">Canlı</div>';
                
                let timeBadge = '';
                if(m.date) {
                    const matchDate = new Date(m.date);
                    const timeStr = matchDate.toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'});
                    timeBadge = `<div class="badge-time"><i class="fa-regular fa-clock text-primary"></i> ${timeStr}</div>`;
                }
                
                layout.innerHTML += `
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12">
                        <div class="match-card" onclick="actionPlay('${m.id}', '${sCode}', 'sport')">
                            ${coverHtml}
                            ${badge}
                            ${timeBadge}
                            <div class="card-details">
                                <div class="play-icon-glass"><i class="fa-solid fa-play"></i></div>
                                <div class="card-title">${m.title}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        function initSearchHome() {
            const inp = document.getElementById('navSearchInput');
            if(!inp) return;
            inp.addEventListener('input', (e) => {
                const val = e.target.value.toLowerCase();
                const main = document.getElementById('mainContainers');
                const sCont = document.getElementById('searchContainer');
                const sGrid = document.getElementById('searchGrid');
                
                if(val.length < 3) { main.style.display = 'block'; sCont.style.display = 'none'; return; }
                main.style.display = 'none'; sCont.style.display = 'block';
                
                let res = window.idxSearchData.filter(m => m.title.toLowerCase().includes(val));
                sGrid.innerHTML = res.length ? '' : '<div class="col-12 py-5 text-muted text-center"><i class="fa-solid fa-ghost fs-1 mb-3"></i><br>Sonuç bulunamadı.</div>';
                
                res.forEach(m => {
                    const rawJson = m.sources ? JSON.stringify(m.sources) : '[]';
                    const sCode = encodeURIComponent(rawJson).replace(/'/g, "%27");
                    
                    sGrid.innerHTML += `
                        <div class="col-xxl-3 col-xl-3 col-lg-4 col-sm-6 col-12">
                            <div class="match-card" onclick="actionPlay('${m.id}', '${sCode}', 'sport')">
                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-satellite-dish fs-1 text-muted"></i></div>
                                <div class="card-details"><div class="play-icon-glass"><i class="fa-solid fa-play"></i></div><div class="card-title">${m.title}</div></div>
                            </div>
                        </div>
                    `;
                });
            });
        }
        
        window.addEventListener('load', buildAppIndex);
    </script>
<?php require_once 'includes/footer.php'; ?>