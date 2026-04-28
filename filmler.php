<?php require_once 'includes/header.php'; ?>
    <style>
        body { background-color: #030303; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .hero-showcase { position: relative; height: 90vh; min-height: 650px; display: flex; align-items: flex-end; padding-bottom: 5%; overflow: hidden; margin-top: -100px; }
        .hero-bg { position: absolute; inset: 0; background-size: cover; background-position: 50% 10%; transition: transform 10s linear, opacity 1s ease; opacity: 0.6; transform: scale(1.05); }
        .hero-showcase:hover .hero-bg { transform: scale(1); opacity: 0.8; }
        .hero-vignette { position: absolute; inset: 0; background: radial-gradient(circle at 70% 50%, transparent 0%, #030303 100%), linear-gradient(to top, #030303 0%, rgba(3,3,3,0.2) 50%, rgba(3,3,3,0.8) 100%); }
        .hero-content { position: relative; z-index: 10; padding: 0 5%; width: 100%; max-width: 1600px; margin: 0 auto; }
        .hero-tag-premium { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 8px 20px; border-radius: 100px; font-weight: 800; font-size: 0.75rem; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .hero-tag-premium i { color: #e50914; font-size: 1rem; }
        .hero-title { font-size: clamp(3rem, 6vw, 5.5rem); font-weight: 900; line-height: 1.1; margin-bottom: 20px; color: #fff; text-shadow: 0 10px 40px rgba(0,0,0,0.9); letter-spacing: -2px; }
        .hero-plot { font-size: 1.15rem; color: #d4d4d8; max-width: 650px; line-height: 1.7; margin-bottom: 35px; text-shadow: 0 2px 15px rgba(0,0,0,0.9); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-weight: 400; }
        .btn-play-massive { background: #fff; color: #000; font-size: 1.1rem; font-weight: 800; padding: 16px 45px; border-radius: 100px; border: none; display: inline-flex; align-items: center; gap: 12px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; box-shadow: 0 10px 30px rgba(255,255,255,0.2); outline: none; }
        .btn-play-massive:hover, .btn-play-massive:focus { background: #e50914; color: #fff; transform: scale(1.05); box-shadow: 0 15px 40px rgba(229,9,20,0.5); }
        .content-row { margin-bottom: 4rem; position: relative; max-width: 1600px; margin-left: auto; margin-right: auto; }
        .row-header { font-size: 1.5rem; font-weight: 800; color: #f8fafc; margin-bottom: 20px; padding: 0 5%; display: flex; justify-content: space-between; align-items: center; letter-spacing: -0.5px; }
        .row-slider { display: flex; gap: 15px; overflow-x: auto; padding: 10px 5% 30px 5%; scroll-behavior: smooth; -ms-overflow-style: none; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
        .row-slider::-webkit-scrollbar { display: none; }
        .series-card { flex: 0 0 calc(100% / 6 - 20px); aspect-ratio: 2/3; position: relative; border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); background: #0a0a0a; border: 1px solid rgba(255,255,255,0.03); outline: none; }
        .series-card:hover, .series-card:focus { transform: scale(1.08) translateY(-10px); z-index: 20; box-shadow: 0 30px 60px rgba(0,0,0,0.9); border-color: #e50914; }
        .series-card img { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.5s, transform 0.5s; opacity: 0.7; }
        .series-card:hover img, .series-card:focus img { opacity: 1; transform: scale(1.05); }
        .card-details { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.5) 70%, transparent 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 15px; opacity: 1; transition: opacity 0.4s ease; }
        .play-icon-glass { width: 45px; height: 45px; border-radius: 50%; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.3); transition: 0.4s; transform: translateY(20px); opacity: 0; }
        .series-card:hover .play-icon-glass, .series-card:focus .play-icon-glass { transform: translateY(0); background: #e50914; border-color: #e50914; box-shadow: 0 0 30px rgba(229,9,20,0.6); color: #fff; opacity: 1; }
        .card-title { color: #fff; font-weight: 800; font-size: 1rem; line-height: 1.2; margin-bottom: 6px; text-shadow: 0 2px 10px rgba(0,0,0,0.9); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; color: #a1a1aa; }
        .match-badge { color: #4ade80; font-weight: 900; }
        .slider-controls { position: absolute; top: 40%; transform: translateY(-50%); width: 50px; height: 50px; border-radius: 50%; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-size: 1.2rem; z-index: 99; opacity: 0; transition: 0.4s; cursor: pointer; display: flex; align-items: center; justify-content: center; outline: none; }
        .content-row:hover .slider-controls { opacity: 1; }
        .slider-controls:hover, .slider-controls:focus { background: #e50914; border-color: #fff; transform: translateY(-50%) scale(1.1); box-shadow: 0 10px 25px rgba(229,9,20,0.5); }
        .ctrl-left { left: 2%; } .ctrl-right { right: 2%; }
        .skel-card { flex: 0 0 calc(100% / 6 - 20px); aspect-ratio: 2/3; background: linear-gradient(90deg, #0a0a0a 25%, #171717 50%, #0a0a0a 75%); background-size: 200% 100%; animation: skeletonLoading 1.5s infinite; border-radius: 12px; border: 1px solid rgba(255,255,255,0.02); }
        @keyframes skeletonLoading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @media (min-width: 1800px) { .series-card, .skel-card { flex: 0 0 calc(100% / 7 - 20px); } }
        @media (max-width: 1400px) { .series-card, .skel-card { flex: 0 0 calc(100% / 5 - 15px); } }
        @media (max-width: 1100px) { .series-card, .skel-card { flex: 0 0 calc(100% / 4 - 15px); } }
        @media (max-width: 768px) { .series-card, .skel-card { flex: 0 0 calc(100% / 3 - 15px); } .slider-controls { display: none; } .hero-showcase { min-height: 500px; height: 70vh; } }
        @media (max-width: 500px) { .series-card, .skel-card { flex: 0 0 140px; } .hero-title { font-size: 2.5rem; } .row-slider { padding-left: 15px; padding-right: 15px; } .row-header { padding-left: 15px; padding-right: 15px; } }
    </style>

    <div id="mainContainers">
        <section class="hero-showcase" id="heroShowcase">
            <div class="hero-bg" id="heroBgImg"></div>
            <div class="hero-vignette"></div>
            <div class="hero-content" id="heroInner">
                <div class="skel-card" style="width: 180px; height: 35px; border-radius: 100px; margin-bottom: 25px;"></div>
                <div class="skel-card" style="width: 60%; height: 70px; border-radius: 8px; margin-bottom: 20px;"></div>
                <div class="skel-card" style="width: 45%; height: 24px; border-radius: 4px; margin-bottom: 40px;"></div>
                <div class="skel-card" style="width: 220px; height: 55px; border-radius: 100px;"></div>
            </div>
        </section>

        <div class="content-row mt-5">
            <div class="row-header">Gişe Rekortmenleri <i class="fa-solid fa-fire text-warning ms-2" style="font-size: 1.2rem;"></i></div>
            <button type="button" class="slider-controls ctrl-left" tabindex="0" onclick="slideRow('row1', -1)"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="row-slider" id="row1">
                <div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div>
            </div>
            <button type="button" class="slider-controls ctrl-right" tabindex="0" onclick="slideRow('row1', 1)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <div class="content-row">
            <div class="row-header">Aksiyon & Macera <i class="fa-solid fa-burst text-danger ms-2" style="font-size: 1.2rem;"></i></div>
            <button type="button" class="slider-controls ctrl-left" tabindex="0" onclick="slideRow('row2', -1)"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="row-slider" id="row2">
                <div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div>
            </div>
            <button type="button" class="slider-controls ctrl-right" tabindex="0" onclick="slideRow('row2', 1)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <div class="content-row">
            <div class="row-header">Bilimkurgu Başyapıtları <i class="fa-solid fa-meteor text-info ms-2" style="font-size: 1.2rem;"></i></div>
            <button type="button" class="slider-controls ctrl-left" tabindex="0" onclick="slideRow('row3', -1)"><i class="fa-solid fa-chevron-left"></i></button>
            <div class="row-slider" id="row3">
                <div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div><div class="skel-card"></div>
            </div>
            <button type="button" class="slider-controls ctrl-right" tabindex="0" onclick="slideRow('row3', 1)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>

    <div class="container-fluid mt-5 pt-5 pb-5" id="searchContainer" style="display:none; background: #030303; min-height: 80vh;">
        <div style="padding: 0 5%; max-width: 1600px; margin: 0 auto;">
            <div class="row-header mb-4" style="padding: 0;">
                Arama Sonuçları 
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold" tabindex="0" onclick="clearSearch()">Aramayı Kapat</button>
            </div>
            <div class="row g-4" id="searchGrid"></div>
        </div>
    </div>

    <script>
        const LOCAL_OMDB_KEY = 'afc8e2fa';
        const FALLBACK_POSTER = 'https://placehold.co/500x750/111111/e50914.png?text=Afi%C5%9F+Yok';
        
        const localDatabaseMovies = {
            'row1': [
                { id: 'tt1375666', title: 'Inception', year: '2010', match: 99, poster: 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_SX300.jpg' },
                { id: 'tt0816692', title: 'Interstellar', year: '2014', match: 98, poster: 'https://m.media-amazon.com/images/M/MV5BZjdkOTU3MDktN2IxOS00OGEyLWFmMjktY2FiMmZkNWIyODZiXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_SX300.jpg' },
                { id: 'tt0468569', title: 'The Dark Knight', year: '2008', match: 99, poster: 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_SX300.jpg' },
                { id: 'tt0068646', title: 'The Godfather', year: '1972', match: 97, poster: 'https://m.media-amazon.com/images/M/MV5BM2MyNjYxNmUtYTAwNi00MTYxLWJmNWYtYzZlODY3ZTk3OTFlXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_SX300.jpg' },
                { id: 'tt0110912', title: 'Pulp Fiction', year: '1994', match: 96, poster: 'https://m.media-amazon.com/images/M/MV5BNGNhMDIzZTItMDM2NC00MWEzLTkwMzYtZTg5ZWI2NjMyM2Y5XkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_SX300.jpg' },
                { id: 'tt0109830', title: 'Forrest Gump', year: '1994', match: 95, poster: 'https://m.media-amazon.com/images/M/MV5BNWIwODRlZTUtY2U3ZS00Yzg1LWExNWYtODE0NjAwOWQ5Yjc0XkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_SX300.jpg' }
            ],
            'row2': [
                { id: 'tt4154796', title: 'Avengers: Endgame', year: '2019', match: 99, poster: 'https://m.media-amazon.com/images/M/MV5BMTc5MDE2ODcwNV5BMl5BanBnXkFtZTgwMzI2NzQ2NzM@._V1_SX300.jpg' },
                { id: 'tt10872600', title: 'Spider-Man: No Way Home', year: '2021', match: 98, poster: 'https://m.media-amazon.com/images/M/MV5BZWMyYzFjYTYtNTRjYi00OGExLWE2YzgtOGRmYjAxZTU3NzBiXkEyXkFqcGdeQXVyMzQ0MzA0NTM@._V1_SX300.jpg' },
                { id: 'tt2802144', title: 'John Wick', year: '2014', match: 97, poster: 'https://m.media-amazon.com/images/M/MV5BMTU2NjA1ODgzMF5BMl5BanBnXkFtZTgwMTM2MTI4MjE@._V1_SX300.jpg' },
                { id: 'tt1392190', title: 'Mad Max: Fury Road', year: '2015', match: 96, poster: 'https://m.media-amazon.com/images/M/MV5BN2EwM2I5OWMtMGQyMi00Zjg1LWJkNTctZTdjYTA4OGUwZjMyXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_SX300.jpg' },
                { id: 'tt0172495', title: 'Gladiator', year: '2000', match: 95, poster: 'https://m.media-amazon.com/images/M/MV5BMDliMmNhNDEtODUyOS00MjNlLTgxODItNTg1OTNiZjc3YTBiXkEyXkFqcGdeQXVyMjQzMzQzODY@._V1_SX300.jpg' },
                { id: 'tt0133093', title: 'The Matrix', year: '1999', match: 98, poster: 'https://m.media-amazon.com/images/M/MV5BNzQzOTk3OTAtNDQ0Zi00ZTVkLWI0MTEtMDllZjNkYzNjNTc4L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_SX300.jpg' }
            ],
            'row3': [
                { id: 'tt1160419', title: 'Dune', year: '2021', match: 97, poster: 'https://m.media-amazon.com/images/M/MV5BMDQ0NjgyN2YtNWViNS00YjA3LTkxNDktYzFkZTExZGUxZjltXkEyXkFqcGdeQXVyODE5NzE3OTE@._V1_SX300.jpg' },
                { id: 'tt0499549', title: 'Avatar', year: '2009', match: 96, poster: 'https://m.media-amazon.com/images/M/MV5BMjEyNDMxNjgwN15BMl5BanBnXkFtZTcwNTczNDI2Mw@@._V1_SX300.jpg' },
                { id: 'tt15398776', title: 'Oppenheimer', year: '2023', match: 99, poster: 'https://m.media-amazon.com/images/M/MV5BMDBmYTZjNjUtN2M1MS00MTQ2LTk2ODgtNzc2M2QyZGE5NTVjXkEyXkFqcGdeQXVyNzAwMjU2MTY@._V1_SX300.jpg' },
                { id: 'tt7286456', title: 'Joker', year: '2019', match: 98, poster: 'https://m.media-amazon.com/images/M/MV5BMGQ1ZGZhNDItMTA2MS00ODhjLTlhMDYtZDdhNmViODhiYzQwXkEyXkFqcGdeQXVyMzQwMTY2Nzk@._V1_SX300.jpg' },
                { id: 'tt1856101', title: 'Blade Runner 2049', year: '2017', match: 95, poster: 'https://m.media-amazon.com/images/M/MV5BNzA1Njg4NzYxOV5BMl5BanBnXkFtZTgwODk5NjU3MzI@._V1_SX300.jpg' },
                { id: 'tt0078748', title: 'Alien', year: '1979', match: 96, poster: 'https://m.media-amazon.com/images/M/MV5BOGQzZTBjMjQtOTVmMS00NGE5LWEyYmMtOGQ1ZGZjNmRkYjFhXkEyXkFqcGdeQXVyMjUzOTY1NTc@._V1_SX300.jpg' }
            ]
        };

        const heroDataMovies = {
            id: 'tt15398776',
            title: 'Oppenheimer',
            poster: 'https://m.media-amazon.com/images/M/MV5BMDBmYTZjNjUtN2M1MS00MTQ2LTk2ODgtNzc2M2QyZGE5NTVjXkEyXkFqcGdeQXVyNzAwMjU2MTY@._V1_SX300.jpg',
            imdb: '8.4',
            year: '2023',
            genre: 'Biyografi, Dram',
            plot: 'Amerikalı bilim insanı J. Robert Oppenheimer\'ın ve atom bombasının geliştirilmesindeki rolünün çarpıcı hikayesi. Cillian Murphy\'nin eşsiz performansıyla sinema tarihine geçen dev bir başyapıt.'
        };

        function actionPlay(id, sCode, type) {
            window.location.href = `watch.php?id=${id}&type=${type}&s=${sCode || '%5B%5D'}`;
        }

        function handleKeyPlay(e, id, sCode, type) {
            if (e.key === 'Enter') {
                actionPlay(id, sCode, type);
            }
        }

        function slideRow(id, dir) {
            const el = document.getElementById(id);
            if(el) {
                const scrollAmount = el.clientWidth * 0.75;
                el.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
            }
        }

        async function fetchPoster(img, id) {
            img.onerror = null;
            if(img.dataset.fetched === 'true') {
                img.src = FALLBACK_POSTER;
                return;
            }
            img.dataset.fetched = 'true';
            try {
                const res = await fetch(`https://www.omdbapi.com/?apikey=${LOCAL_OMDB_KEY}&i=${id}`);
                const data = await res.json();
                if(data.Response === "True" && data.Poster && data.Poster !== "N/A") {
                    localStorage.setItem('omdb_poster_' + id, data.Poster);
                    img.src = data.Poster;
                } else {
                    img.src = FALLBACK_POSTER;
                }
            } catch(e) { img.src = FALLBACK_POSTER; }
        }

        function buildMoviesApp() {
            drawHero(heroDataMovies);

            for (const [rowId, movieArray] of Object.entries(localDatabaseMovies)) {
                const rowEl = document.getElementById(rowId);
                let htmlContent = '';
                
                movieArray.forEach(m => {
                    let cachedImg = localStorage.getItem('omdb_poster_' + m.id) || m.poster;
                    htmlContent += `
                        <div class="series-card" tabindex="0" onkeydown="handleKeyPlay(event, '${m.id}', '', 'movie')" onclick="actionPlay('${m.id}', '', 'movie')">
                            <img src="${cachedImg}" onerror="fetchPoster(this, '${m.id}')">
                            <div class="card-details">
                                <div class="play-icon-glass"><i class="fa-solid fa-play"></i></div>
                                <div class="card-title">${m.title}</div>
                                <div class="card-meta">
                                    <span class="match-badge">%${m.match} Eşleşme</span>
                                    <span>${m.year}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                rowEl.innerHTML = htmlContent;
            }

            initSearchMovies();
        }

        function drawHero(target) {
            const inner = document.getElementById('heroInner');
            const bg = document.getElementById('heroBgImg');
            
            let cachedHero = localStorage.getItem('omdb_poster_' + target.id);
            if(cachedHero) {
                bg.style.backgroundImage = `url('${cachedHero}')`;
            } else {
                bg.style.backgroundImage = `url('${target.poster}')`;
                fetch(`https://www.omdbapi.com/?apikey=${LOCAL_OMDB_KEY}&i=${target.id}`)
                .then(r=>r.json()).then(d=>{
                    if(d.Response==="True" && d.Poster && d.Poster!=="N/A"){
                        localStorage.setItem('omdb_poster_'+target.id, d.Poster);
                        bg.style.backgroundImage = `url('${d.Poster}')`;
                    }
                }).catch(e=>{});
            }
            
            inner.innerHTML = `
                <div class="hero-tag-premium"><i class="fa-solid fa-gem text-primary"></i> PREMIUM ÖZEL</div>
                <h1 class="hero-title">${target.title}</h1>
                <p class="hero-plot">${target.plot}</p>
                <div class="d-flex align-items-center gap-4 mb-4" style="color:#d4d4d8; font-weight:700;">
                    <span><i class="fa-solid fa-star text-warning"></i> IMDB: ${target.imdb}</span>
                    <span><i class="fa-solid fa-film text-primary"></i> ${target.genre}</span>
                    <span><i class="fa-regular fa-calendar"></i> ${target.year}</span>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn-play-massive" tabindex="0" onkeydown="handleKeyPlay(event, '${target.id}', '', 'movie')" onclick="actionPlay('${target.id}', '', 'movie')"><i class="fa-solid fa-play"></i> Filmi İzle</button>
                    <button class="btn-play-massive" tabindex="0" style="background: rgba(255,255,255,0.1); color: #fff; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2);"><i class="fa-solid fa-plus"></i> Listeme Ekle</button>
                </div>
            `;
        }

        function clearSearch() {
            const navInp = document.getElementById('navSearchInput');
            const mobInp = document.getElementById('mobileSearchInput');
            if(navInp) navInp.value = '';
            if(mobInp) mobInp.value = '';
            document.getElementById('mainContainers').style.display = 'block';
            document.getElementById('searchContainer').style.display = 'none';
        }

        function initSearchMovies() {
            const inp = document.getElementById('navSearchInput');
            const mobInp = document.getElementById('mobileSearchInput');
            let searchTimeout;

            function doSearch(e) {
                const val = e.target.value.toLowerCase();
                const main = document.getElementById('mainContainers');
                const sGrid = document.getElementById('searchGrid');
                const sCont = document.getElementById('searchContainer');

                clearTimeout(searchTimeout);

                if(val.length < 3) {
                    main.style.display = 'block';
                    sCont.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(async () => {
                    main.style.display = 'none';
                    sCont.style.display = 'block';
                    sGrid.innerHTML = '<div class="col-12 py-5 text-center"><i class="fa-solid fa-circle-notch fa-spin fa-3x text-primary"></i></div>';
                    
                    try {
                        const req = await fetch(`https://www.omdbapi.com/?apikey=${LOCAL_OMDB_KEY}&s=${encodeURIComponent(val)}&type=movie`);
                        const data = await req.json();
                        
                        sGrid.innerHTML = '';
                        if(data.Response === "False" || !data.Search) {
                            sGrid.innerHTML = '<div class="col-12 py-5 text-muted text-center"><i class="fa-solid fa-ghost fa-4x mb-3"></i><br><h3 class="fw-bold">Sonuç bulunamadı.</h3><p>Farklı bir film arayın.</p></div>';
                            return;
                        }

                        data.Search.forEach(r => {
                            let safePoster = (r.Poster && r.Poster !== 'N/A') ? r.Poster : FALLBACK_POSTER;
                            sGrid.innerHTML += `
                                <div class="col-xxl-2 col-xl-2 col-lg-3 col-md-4 col-sm-4 col-6">
                                    <div class="series-card" tabindex="0" onkeydown="handleKeyPlay(event, '${r.imdbID}', '', 'movie')" style="aspect-ratio: 2/3;" onclick="actionPlay('${r.imdbID}', '', 'movie')">
                                        <img src="${safePoster}" onerror="this.onerror=null; this.src='${FALLBACK_POSTER}'">
                                        <div class="card-details">
                                            <div class="play-icon-glass"><i class="fa-solid fa-play"></i></div>
                                            <div class="card-title">${r.Title}</div>
                                            <div class="card-meta"><span>${r.Year}</span></div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    } catch(err) { sGrid.innerHTML = '<div class="col-12 py-5 text-danger text-center">Bağlantı hatası.</div>'; }
                }, 800);
            }

            if(inp) inp.addEventListener('input', doSearch);
            if(mobInp) mobInp.addEventListener('input', doSearch);
        }

        window.addEventListener('load', buildMoviesApp);
    </script>
<?php require_once 'includes/footer.php'; ?>