<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['chat_action'])) {
    header('Content-Type: application/json');
    $room = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['room'] ?? 'global');
    $file = __DIR__ . '/chat_' . $room . '.json';
    
    if ($_GET['chat_action'] === 'get') {
        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            echo json_encode([]);
        }
        exit;
    }
    
    if ($_GET['chat_action'] === 'send' && isset($_POST['msg']) && isset($_POST['user'])) {
        $msg = htmlspecialchars(strip_tags($_POST['msg']));
        $user = htmlspecialchars(strip_tags($_POST['user']));
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        $data[] = ['u' => $user, 'm' => $msg, 't' => date('H:i')];
        if (count($data) > 50) {
            array_shift($data);
        }
        file_put_contents($file, json_encode($data));
        echo json_encode(['status' => 'ok']);
        exit;
    }
}

if (isset($_GET['fetch_api'])) {
    header('Content-Type: application/json');
    $target = $_GET['fetch_api'];
    $url = 'https://streamed.pk/api/' . $target;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Referer: https://streamed.pk/',
        'Origin: https://streamed.pk',
        'Accept: application/json, text/plain, */*'
    ]);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode >= 400 || empty($response)) {
        echo json_encode(['error' => true, 'code' => $httpcode, 'msg' => 'Sunucu Proxy Engeli']);
    } else {
        echo $response;
    }
    exit;
}

require_once 'includes/header.php';

if (!$is_logged_in || !$is_vip) {
    echo "<script>window.location.href = 'paket.php';</script>";
    exit;
}

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? 'movie';
$sources = $_GET['s'] ?? '[]';
$season = isset($_GET['season']) ? (int)$_GET['season'] : 1;
$episode = isset($_GET['episode']) ? (int)$_GET['episode'] : 1;

if (empty($id)) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

$js_type = json_encode($type);
$js_id = json_encode($id);
$js_sources = json_encode($sources);
$js_season = json_encode($season);
$js_episode = json_encode($episode);
$js_user = json_encode($user_name);
?>
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <style>
        body { background-color: #0e0e0e; overflow-x: hidden; font-family: 'Inter', sans-serif; margin: 0; }
        
        .bg-ambient { position: fixed; top: -50px; left: -50px; right: -50px; bottom: -50px; z-index: -1; filter: blur(100px); opacity: 0.15; background-size: cover; background-position: center; transition: background-image 1.5s ease; pointer-events: none; }
        
        .layout-container { display: flex; gap: 25px; padding: 25px; max-width: 1800px; margin: 70px auto 0 auto; align-items: flex-start; }
        
        .main-col { flex: 1 1 0%; min-width: 0; display: flex; flex-direction: column; gap: 20px; transition: 0.4s; padding-bottom: 40px; }
        
        .side-col { width: 380px; flex-shrink: 0; background: #18181b; border-radius: 12px; position: sticky; top: 95px; height: calc(100vh - 120px); display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.5); transition: 0.4s; z-index: 100; }
        
        body.hide-sidebar .side-col { display: none; }
        body.cinema-mode-active .side-col { width: 0; flex: 0 0 0px; opacity: 0; pointer-events: none; border: none; margin: 0; padding: 0; }
        
        .player-placeholder { width: 100%; padding-top: 56.25%; position: relative; border-radius: 12px; margin-bottom: 5px; }
        .player-box { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #000; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.6); z-index: 90; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .player-box.pip-mode { position: fixed !important; top: auto; left: auto; bottom: 20px; right: 20px; width: 400px; height: 225px; z-index: 999999; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.9); border: 1px solid rgba(255,255,255,0.1); animation: pipEnter 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes pipEnter { from { transform: translateY(100px) scale(0.8); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
        @media (max-width: 768px) { .player-box.pip-mode { width: calc(100% - 40px); height: calc((100vw - 40px) * 0.5625); bottom: 80px; right: 20px; } }

        #videoWrapper { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; }
        #videoWrapper iframe { width: 100%; height: 100%; border: none; background: #000; pointer-events: auto; }
        .plyr { width: 100%; height: 100%; --plyr-color-main: #e50914; --plyr-video-background: #000; }
        
        .top-controls { position: absolute; top: 0; left: 0; width: 100%; padding: 20px; display: flex; justify-content: space-between; z-index: 100; pointer-events: none; }
        .control-btn { background: rgba(0,0,0,0.6); color: #fff; padding: 10px 24px; border-radius: 100px; text-decoration: none; font-weight: 700; font-size: 0.95rem; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.1); transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; pointer-events: auto; outline: none; }
        .control-btn:hover { background: #e50914; border-color: #e50914; transform: scale(1.05); }

        .cinema-btn { cursor: pointer; border:none; outline:none; }
        
        .loader-spin { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 4px solid rgba(255,255,255,0.1); border-top: 4px solid #e50914; border-radius: 50%; width: 55px; height: 55px; animation: spin 1s linear infinite; z-index: 50; }
        @keyframes spin { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        
        .info-box { padding: 30px; background: linear-gradient(to bottom, rgba(0,0,0,0.5) 0%, #030303 100%); border-radius: 12px; }
        .media-title { font-size: 2.2rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 12px; color: #fff; }
        .media-meta-badges { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .badge-custom { padding: 6px 12px; font-weight: 800; font-size: 0.8rem; border-radius: 6px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
        .bg-imdb { background: #f5c518; color: #000; }
        .bg-hd { background: transparent; border: 1px solid #a1a1aa; color: #a1a1aa; }
        
        .crew-section { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .crew-item { color: #d4d4d8; font-size: 0.95rem; }
        .crew-item span { color: #fff; font-weight: 700; margin-right: 5px; }
        .cast-tag { background: rgba(255,255,255,0.1); padding: 4px 10px; border-radius: 100px; font-size: 0.85rem; color: #fff; display: inline-block; margin-right: 5px; margin-bottom: 5px; border: 1px solid rgba(255,255,255,0.05); }

        .media-plot { color: #a1a1aa; font-size: 1.05rem; line-height: 1.7; max-width: 900px; margin-top: 15px; }
        
        .action-group { display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .action-left { display: flex; gap: 10px; flex-wrap: wrap; }
        .action-right { display: flex; gap: 10px; }
        .src-tab { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: 0.3s; font-weight: 700; font-size: 0.9rem; outline: none; display: inline-flex; align-items: center; gap: 8px; }
        .src-tab.active, .src-tab:hover { background: #e50914; border-color: #e50914; transform: translateY(-2px); }
        
        .rate-btn { background: transparent; border: 1px solid rgba(255,255,255,0.2); width: 42px; height: 42px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer; transition: 0.3s; outline: none; }
        .rate-btn:hover { background: rgba(255,255,255,0.1); transform: scale(1.1); }
        .rate-btn.active.up { background: #10b981; border-color: #10b981; box-shadow: 0 0 15px rgba(16,185,129,0.3); }
        .rate-btn.active.down { background: #ef4444; border-color: #ef4444; box-shadow: 0 0 15px rgba(239,68,68,0.3); }

        .similar-section { margin-top: 30px; position: relative; }
        .similar-title { color: #fff; font-size: 1.3rem; font-weight: 800; margin-bottom: 20px; }
        
        .similar-wrapper { position: relative; }
        .similar-row { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px; scroll-behavior: smooth; scrollbar-width: none; }
        .similar-row::-webkit-scrollbar { display: none; }
        
        .similar-slider-btn { position: absolute; top: 40%; transform: translateY(-50%); width: 45px; height: 45px; border-radius: 50%; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-size: 1.2rem; z-index: 20; opacity: 0; transition: 0.4s; cursor: pointer; display: flex; align-items: center; justify-content: center; outline: none; }
        .similar-section:hover .similar-slider-btn { opacity: 1; }
        .similar-slider-btn:hover { background: #e50914; border-color: #fff; transform: translateY(-50%) scale(1.1); box-shadow: 0 10px 25px rgba(229,9,20,0.5); }
        .similar-slider-btn.ctrl-left { left: -20px; }
        .similar-slider-btn.ctrl-right { right: -20px; }
        @media (max-width: 768px) { .similar-slider-btn { display: none; } }

        .similar-card { flex: 0 0 160px; aspect-ratio: 2/3; border-radius: 10px; overflow: hidden; position: relative; cursor: pointer; transition: 0.4s; background: #111; border: 1px solid rgba(255,255,255,0.05); }
        .similar-card img { width: 100%; height: 100%; object-fit: cover; transition: 0.4s; }
        .similar-card:hover { transform: scale(1.05) translateY(-5px); z-index: 10; border-color: #e50914; box-shadow: 0 15px 30px rgba(0,0,0,0.6); }
        .similar-card:hover img { opacity: 0.5; }
        .similar-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .similar-card:hover .similar-overlay { opacity: 1; }
        .similar-play { width: 45px; height: 45px; background: #e50914; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; box-shadow: 0 0 20px rgba(229,9,20,0.6); }

        .trailer-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(15px); z-index: 9999999; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.4s; }
        .trailer-modal-overlay.show { opacity: 1; visibility: visible; }
        .trailer-modal { width: 90%; max-width: 1000px; aspect-ratio: 16/9; background: #000; border-radius: 16px; overflow: hidden; position: relative; box-shadow: 0 30px 60px rgba(0,0,0,0.9); transform: scale(0.9); transition: 0.4s; }
        .trailer-modal-overlay.show .trailer-modal { transform: scale(1); }
        .trailer-close { position: absolute; top: -50px; right: 0; color: #fff; font-size: 2rem; background: none; border: none; cursor: pointer; transition: 0.3s; }
        .trailer-close:hover { color: #e50914; transform: rotate(90deg); }

        .episodes-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; gap: 12px; }
        .episodes-header h3 { color: #fff; font-size: 1.1rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px; }
        .season-selector { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 10px 15px; border-radius: 8px; font-weight: 700; outline: none; cursor: pointer; appearance: none; font-size: 0.95rem; transition: 0.3s; }
        .season-selector:focus { border-color: #e50914; background: rgba(0,0,0,0.8); }
        .ep-search { width: 100%; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); color: #fff; padding: 10px 15px; border-radius: 8px; font-size: 0.9rem; outline: none; transition: 0.3s; }
        .ep-search:focus { border-color: #e50914; background: rgba(0,0,0,0.5); }
        
        .episodes-list { flex: 1; overflow-y: auto; padding: 10px 0; }
        .episodes-list::-webkit-scrollbar { width: 5px; }
        .episodes-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        
        .ep-item { display: flex; align-items: center; gap: 15px; padding: 12px 20px; cursor: pointer; transition: 0.2s; border-left: 3px solid transparent; }
        .ep-item:hover { background: rgba(255,255,255,0.03); }
        .ep-item.active { background: rgba(229,9,20,0.05); border-left-color: #e50914; }
        
        .ep-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid #52525b; transition: 0.3s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ep-item:hover .ep-dot { border-color: #fff; }
        .ep-item.active .ep-dot { border-color: #e50914; background: #e50914; box-shadow: 0 0 10px rgba(229,9,20,0.5); }
        .ep-item.active .ep-dot::after { content: ''; width: 4px; height: 4px; background: #fff; border-radius: 50%; }
        
        .ep-title { color: #a1a1aa; font-weight: 600; font-size: 0.9rem; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: 0.3s; }
        .ep-item:hover .ep-title { color: #fff; }
        .ep-item.active .ep-title { color: #fff; font-weight: 700; }

        .skeleton { animation: pulseSkel 1.5s infinite ease-in-out; background: rgba(255,255,255,0.05); border-radius: 4px; }
        @keyframes pulseSkel { 0% { opacity: 0.4; } 50% { opacity: 0.8; } 100% { opacity: 0.4; } }

        .next-ep-popup { position: absolute; bottom: 80px; right: 30px; background: rgba(10,10,10,0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); padding: 15px 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; z-index: 100; transform: translateX(150%); transition: 0.5s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        .next-ep-popup.show { transform: translateX(0); }
        .next-ep-popup:hover { background: #e50914; border-color: #e50914; }
        .next-ep-icon { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; }
        .next-ep-popup:hover .next-ep-icon { background: #fff; color: #e50914; }
        .next-ep-text { display: flex; flex-direction: column; }
        .next-ep-title { color: #fff; font-weight: 800; font-size: 0.95rem; }
        .next-ep-sub { color: #a1a1aa; font-size: 0.8rem; font-weight: 600; margin-top: 2px; }

        .chat-area { display: flex; flex-direction: column; height: 100%; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 12px; }
        .chat-msg { background: rgba(255,255,255,0.03); padding: 12px; border-radius: 12px; font-size: 0.9rem; color: #d4d4d8; line-height: 1.4; border: 1px solid rgba(255,255,255,0.02); }
        .chat-msg b { color: #e50914; display: inline-block; margin-bottom: 4px; }
        .chat-msg span { font-size: 0.7rem; color: #71717a; float: right; margin-top: 2px; }
        .chat-input-area { padding: 15px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; gap: 10px; background: rgba(0,0,0,0.5); }
        .chat-input-area input { flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 10px 15px; color: #fff; outline: none; font-size: 0.9rem; }
        .chat-input-area input:focus { border-color: #e50914; }
        .chat-input-area button { background: #e50914; border: none; color: #fff; width: 42px; height: 42px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .chat-input-area button:hover { background: #dc2626; transform: scale(1.05); }

        @media (max-width: 1024px) {
            .layout-container { flex-direction: column; padding: 15px; }
            .side-col { width: 100%; max-width: 100%; height: 500px; border-left: none; position: static; }
            .cinema-btn { display: none; }
        }
        @media (max-width: 768px) { 
            .media-title { font-size: 1.8rem; }
            .layout-container { margin-top: 60px; padding: 10px; }
            .next-ep-popup { bottom: 20px; right: 10px; padding: 10px 15px; }
            .action-group { flex-direction: column; align-items: stretch; }
            .action-left { justify-content: center; }
            .action-right { justify-content: center; margin-top: 10px; }
        }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>

    <div class="bg-ambient" id="bgAmbient"></div>

    <div class="layout-container">
        <div class="main-col" id="mainSection">
            <div class="player-placeholder" id="playerPlaceholder">
                <div class="player-box" id="playerContainer">
                    <div class="top-controls">
                        <a href="javascript:history.back()" class="control-btn"><i class="fa-solid fa-arrow-left"></i> Geri</a>
                        <button class="control-btn cinema-btn" onclick="toggleCinemaMode()" id="cinemaBtn"><i class="fa-solid fa-expand"></i> Sinema</button>
                    </div>
                    
                    <div class="loader-spin" id="playerLoader"></div>
                    <div id="videoWrapper"></div>
                    
                    <div class="next-ep-popup" id="nextEpPopup" onclick="changeEpisode(1)">
                        <div class="next-ep-icon"><i class="fa-solid fa-forward-step"></i></div>
                        <div class="next-ep-text">
                            <span class="next-ep-title">Sıradaki Bölüm</span>
                            <span class="next-ep-sub" id="nextEpLabel">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-box" id="infoBottomArea">
                <h1 class="media-title" id="mediaTitle"><span class="skeleton" style="display:inline-block; width:300px; height:40px; border-radius:8px;"></span></h1>
                
                <div class="media-meta-badges" id="mediaMeta">
                    <span class="skeleton" style="display:inline-block; width:80px; height:24px; border-radius:4px;"></span>
                    <span class="skeleton" style="display:inline-block; width:60px; height:24px; border-radius:4px;"></span>
                </div>

                <div class="crew-section" id="crewPanel">
                    <div class="skeleton" style="width:250px; height:18px; margin-bottom:5px;"></div>
                    <div class="skeleton" style="width:350px; height:18px;"></div>
                </div>
                
                <div class="media-plot" id="mediaPlot">
                    <div class="skeleton" style="width:100%; height:16px; margin-bottom:8px;"></div>
                    <div class="skeleton" style="width:90%; height:16px; margin-bottom:8px;"></div>
                    <div class="skeleton" style="width:70%; height:16px;"></div>
                </div>

                <div class="action-group" id="actionButtons">
                    <div class="action-left">
                        <button class="src-tab" id="myListBtn" onclick="toggleMyList()"><i class="fa-solid fa-plus"></i> Listemde</button>
                        <button class="src-tab" onclick="openTrailer()" style="background:rgba(255,255,255,0.1);"><i class="fa-solid fa-play"></i> Fragman</button>
                        <button class="src-tab" id="nextEpBtnBottom" style="display:none; background:#e50914; border-color:#e50914;" onclick="changeEpisode(1)">Sonraki <i class="fa-solid fa-forward-step"></i></button>
                    </div>
                    <div class="action-right">
                        <button class="rate-btn" id="rateUp" onclick="rateContent('up')"><i class="fa-solid fa-thumbs-up"></i></button>
                        <button class="rate-btn" id="rateDown" onclick="rateContent('down')"><i class="fa-solid fa-thumbs-down"></i></button>
                    </div>
                </div>

                <div style="margin-top:25px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <span style="color:#a1a1aa; font-weight:700; font-size:0.9rem; margin-right:10px;">Sunucular:</span>
                    <div id="sourceSelector" style="display:flex; gap:10px;"></div>
                </div>
            </div>

            <div class="similar-section" id="similarContentSection" style="display:none;">
                <h2 class="similar-title">Bunlar Da İlgini Çekebilir</h2>
                <div class="similar-wrapper">
                    <button type="button" class="similar-slider-btn ctrl-left" onclick="slideSimilar(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="similar-row" id="similarRow"></div>
                    <button type="button" class="similar-slider-btn ctrl-right" onclick="slideSimilar(1)"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="side-col" id="rightSidebar">
            <div id="seriesSidebar" style="display:none; height: 100%; flex-direction: column;">
                <div class="episodes-header">
                    <h3><i class="fa-solid fa-list-ul"></i> Sezon Bölümleri</h3>
                    <select class="season-selector" id="seasonSelect" onchange="changeSeason(this.value)"></select>
                    <input type="text" class="ep-search" id="epSearchInput" placeholder="Bölüm ara..." autocomplete="off" onkeyup="filterEpisodes()">
                </div>
                <div class="episodes-list" id="episodesList"></div>
            </div>

            <div id="sportSidebar" style="display:none; height: 100%; flex-direction: column;" class="chat-area">
                <div class="episodes-header" style="background: rgba(0,0,0,0.2);">
                    <h3><i class="fa-solid fa-circle-dot fa-fade text-danger"></i> Canlı Sohbet</h3>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-area">
                    <input type="text" id="chatInput" placeholder="Mesaja katıl..." autocomplete="off" onkeypress="if(event.key==='Enter') sendChat()">
                    <button onclick="sendChat()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="trailer-modal-overlay" id="trailerModalOverlay" onclick="closeTrailer(event)">
        <div style="position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
            <div class="trailer-modal">
                <button class="trailer-close" onclick="closeTrailerForce()"><i class="fa-solid fa-xmark"></i></button>
                <iframe id="trailerIframe" width="100%" height="100%" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>
    
    <script>
        const playType = <?= $js_type ?>;
        const playId = <?= $js_id ?>;
        const sourcesJsonStr = <?= $js_sources ?>;
        let currentSeason = <?= $js_season ?>;
        let currentEpisode = <?= $js_episode ?>;
        const OMDB_KEY = window.OMDB_API_KEY || 'afc8e2fa';
        let currentSportSources = [];
        let currentSourceIndex = 0;
        let plyrInstance = null;
        let globalPoster = 'https://placehold.co/300x450/111/e50914?text=Yok';
        let globalTitle = '';
        let globalGenre = '';
        let currentProgress = 0;
        let chatInterval = null;
        let maxEpisodes = 0;
        
        document.addEventListener('DOMContentLoaded', () => {
            if (playType === 'movie') {
                document.body.classList.add('hide-sidebar');
                document.getElementById('cinemaBtn').style.display = 'none';
                loadCinematic(playType, playId);
            } else if (playType === 'series') {
                document.getElementById('seriesSidebar').style.display = 'flex';
                loadCinematic(playType, playId);
                buildSeriesSidebar(playId);
            } else if (playType === 'sport') {
                document.getElementById('sportSidebar').style.display = 'flex';
                document.getElementById('cinemaBtn').style.display = 'none';
                loadSport(playId, sourcesJsonStr);
                loadChat();
                chatInterval = setInterval(loadChat, 3000);
            }
            checkMyListStatus();
            initPiPObserver();
            checkRateStatus();
        });

        document.addEventListener('keydown', (e) => {
            if(e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if(playType === 'series') {
                if(e.key.toLowerCase() === 'n') changeEpisode(1);
                if(e.key.toLowerCase() === 'p') changeEpisode(-1);
            }
        });

        function slideSimilar(dir) {
            const el = document.getElementById('similarRow');
            if(el) {
                const scrollAmount = el.clientWidth * 0.75;
                el.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
            }
        }

        function initPiPObserver() {
            const observer = new IntersectionObserver((entries) => {
                const playerBox = document.getElementById('playerContainer');
                if (!entries[0].isIntersecting && entries[0].boundingClientRect.bottom < 0) {
                    playerBox.classList.add('pip-mode');
                } else {
                    playerBox.classList.remove('pip-mode');
                }
            }, { threshold: 0 });
            observer.observe(document.getElementById('playerPlaceholder'));
        }
        
        function toggleCinemaMode() {
            document.body.classList.toggle('cinema-mode-active');
            const btn = document.getElementById('cinemaBtn');
            if(document.body.classList.contains('cinema-mode-active')) {
                btn.innerHTML = `<i class="fa-solid fa-compress"></i> Paneli Aç`;
            } else {
                btn.innerHTML = `<i class="fa-solid fa-expand"></i> Sinema Modu`;
            }
            if(plyrInstance) {
                setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 400);
            }
        }

        function filterEpisodes() {
            let val = document.getElementById('epSearchInput').value.toLowerCase();
            let items = document.querySelectorAll('.ep-item');
            items.forEach(el => {
                let title = el.querySelector('.ep-title').innerText.toLowerCase();
                if(title.includes(val)) {
                    el.style.display = 'flex';
                } else {
                    el.style.display = 'none';
                }
            });
        }
        
        function saveToHistory(id, title, type, poster, prog = 0) {
            let list = JSON.parse(localStorage.getItem('ossiqn_continue') || '[]');
            let exist = list.find(i => i.id === id);
            let finalProg = exist && prog === 0 ? exist.progress : prog;
            list = list.filter(i => i.id !== id);
            list.unshift({ id, title, poster, type, progress: finalProg, date: Date.now() });
            localStorage.setItem('ossiqn_continue', JSON.stringify(list.slice(0, 30)));
        }

        function updateProgress(val) {
            currentProgress = val;
            saveToHistory(playId, globalTitle, playType, globalPoster, currentProgress);
        }

        async function openTrailer() {
            const modal = document.getElementById('trailerModalOverlay');
            const iframe = document.getElementById('trailerIframe');
            modal.classList.add('show');
            if(plyrInstance) plyrInstance.pause();
            
            try {
                const endPoint = playType === 'series' ? 'shows' : 'movies';
                const req = await fetch(`https://api.kinocheck.de/${endPoint}?imdb_id=${playId}&language=tr`);
                const data = await req.json();
                
                if(data && data.trailer && data.trailer.youtube_video_id) {
                    iframe.src = `https://www.youtube.com/embed/${data.trailer.youtube_video_id}?autoplay=1`;
                } else {
                    const reqEn = await fetch(`https://api.kinocheck.de/${endPoint}?imdb_id=${playId}`);
                    const dataEn = await reqEn.json();
                    if(dataEn && dataEn.trailer && dataEn.trailer.youtube_video_id) {
                        iframe.src = `https://www.youtube.com/embed/${dataEn.trailer.youtube_video_id}?autoplay=1`;
                    } else {
                        closeTrailerForce();
                        window.open(`https://www.youtube.com/results?search_query=${encodeURIComponent(globalTitle + ' fragman')}`, '_blank');
                    }
                }
            } catch(e) {
                closeTrailerForce();
                window.open(`https://www.youtube.com/results?search_query=${encodeURIComponent(globalTitle + ' fragman')}`, '_blank');
            }
        }

        function closeTrailer(e) {
            if(e.target === document.getElementById('trailerModalOverlay')) {
                closeTrailerForce();
            }
        }

        function closeTrailerForce() {
            const modal = document.getElementById('trailerModalOverlay');
            const iframe = document.getElementById('trailerIframe');
            modal.classList.remove('show');
            setTimeout(() => { iframe.src = ''; }, 400);
        }

        function rateContent(type) {
            const up = document.getElementById('rateUp');
            const down = document.getElementById('rateDown');
            if(type === 'up') {
                up.classList.add('active');
                down.classList.remove('active');
                localStorage.setItem(`ossiqn_rate_${playId}`, 'up');
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'İçeriği beğendiniz.', showConfirmButton: false, timer: 1500, background: '#0e0e0e', color: '#fff' });
            } else {
                down.classList.add('active');
                up.classList.remove('active');
                localStorage.setItem(`ossiqn_rate_${playId}`, 'down');
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'info', title: 'Fikriniz kaydedildi.', showConfirmButton: false, timer: 1500, background: '#0e0e0e', color: '#fff' });
            }
        }

        function checkRateStatus() {
            const status = localStorage.getItem(`ossiqn_rate_${playId}`);
            if(status === 'up') document.getElementById('rateUp').classList.add('active');
            if(status === 'down') document.getElementById('rateDown').classList.add('active');
        }

        async function generateSimilarContent(genreStr) {
            const sRow = document.getElementById('similarRow');
            const sSec = document.getElementById('similarContentSection');
            if(!genreStr) return;
            const firstGenre = genreStr.split(',')[0].trim();
            
            try {
                const res = await fetch(`https://www.omdbapi.com/?apikey=${OMDB_KEY}&s=${encodeURIComponent(firstGenre)}&type=${playType}`);
                const data = await res.json();
                if(data.Response === "True" && data.Search) {
                    sSec.style.display = 'block';
                    let html = '';
                    let count = 0;
                    data.Search.forEach(item => {
                        if(item.imdbID !== playId && item.Poster !== 'N/A' && count < 15) {
                            html += `
                                <div class="similar-card" onclick="window.location.href='watch.php?id=${item.imdbID}&type=${playType}'">
                                    <img src="${item.Poster}">
                                    <div class="similar-overlay">
                                        <div class="similar-play"><i class="fa-solid fa-play"></i></div>
                                    </div>
                                </div>
                            `;
                            count++;
                        }
                    });
                    sRow.innerHTML = html;
                }
            } catch(e) {}
        }

        async function loadChat() {
            try {
                let res = await fetch(`watch.php?chat_action=get&room=${playId}`);
                let data = await res.json();
                let box = document.getElementById('chatMessages');
                let isScrolledToBottom = box.scrollHeight - box.clientHeight <= box.scrollTop + 1;
                if(data.length === 0) {
                    box.innerHTML = '<div style="text-align:center; color:#71717a; padding:20px; font-size:0.85rem;">Henüz mesaj yok. İlk mesajı sen yaz!</div>';
                } else {
                    box.innerHTML = data.map(msg => `<div class="chat-msg"><b>${msg.u}</b> <span>${msg.t}</span><br>${msg.m}</div>`).join('');
                }
                if(isScrolledToBottom) box.scrollTop = box.scrollHeight;
            } catch(e) {}
        }

        async function sendChat() {
            let inp = document.getElementById('chatInput');
            if(!inp.value.trim()) return;
            let fd = new FormData();
            fd.append('msg', inp.value);
            fd.append('user', <?= $js_user ?>);
            inp.value = '';
            await fetch(`watch.php?chat_action=send&room=${playId}`, {method:'POST', body:fd});
            loadChat();
        }

        function checkMyListStatus() {
            let list = JSON.parse(localStorage.getItem('ossiqn_mylist') || '[]');
            const btn = document.getElementById('myListBtn');
            if(list.some(i => i.id === playId)) {
                btn.innerHTML = `<i class="fa-solid fa-check"></i> Listemde`;
                btn.style.background = '#10b981';
                btn.style.borderColor = '#10b981';
            } else {
                btn.innerHTML = `<i class="fa-solid fa-plus"></i> Listeme Ekle`;
                btn.style.background = 'rgba(255,255,255,0.05)';
                btn.style.borderColor = 'rgba(255,255,255,0.1)';
            }
        }

        function toggleMyList() {
            let list = JSON.parse(localStorage.getItem('ossiqn_mylist') || '[]');
            const exists = list.some(i => i.id === playId);
            if(exists) {
                list = list.filter(i => i.id !== playId);
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Listeden çıkarıldı.', showConfirmButton: false, timer: 1500, background: '#0e0e0e', color: '#fff' });
            } else {
                list.unshift({ id: playId, title: globalTitle, poster: globalPoster, type: playType, date: Date.now() });
                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: 'Listeye eklendi!', showConfirmButton: false, timer: 1500, background: '#0e0e0e', color: '#fff' });
            }
            localStorage.setItem('ossiqn_mylist', JSON.stringify(list));
            checkMyListStatus();
        }

        async function buildSeriesSidebar(imdbId) {
            try {
                const res = await fetch(`https://www.omdbapi.com/?apikey=${OMDB_KEY}&i=${imdbId}`);
                const data = await res.json();
                if(data.Response === "True" && data.totalSeasons) {
                    const seasonSelect = document.getElementById('seasonSelect');
                    let seasonOptions = '';
                    let totalS = parseInt(data.totalSeasons) || 1;
                    for(let i=1; i<=totalS; i++) {
                        seasonOptions += `<option value="${i}" ${i === currentSeason ? 'selected' : ''}>${i}. Sezon</option>`;
                    }
                    seasonSelect.innerHTML = seasonOptions;
                }
            } catch(e) {}
            renderEpisodes(imdbId, currentSeason);
        }

        function changeSeason(s) {
            window.location.href = `watch.php?id=${playId}&type=series&season=${s}&episode=1`;
        }

        function changeEpisode(direction) {
            let nextEp = currentEpisode + direction;
            if (nextEp < 1) nextEp = 1;
            if (maxEpisodes > 0 && nextEp > maxEpisodes) return;
            window.location.href = `watch.php?id=${playId}&type=series&season=${currentSeason}&episode=${nextEp}`;
        }

        function playSpecificEpisode(ep) {
            window.location.href = `watch.php?id=${playId}&type=series&season=${currentSeason}&episode=${ep}`;
        }

        async function renderEpisodes(imdbId, season) {
            const epList = document.getElementById('episodesList');
            let skelHtml = '';
            for(let i=0; i<8; i++) {
                skelHtml += `
                    <div class="ep-item" style="cursor:default;">
                        <div class="skeleton" style="width:12px;height:12px;border-radius:50%;"></div>
                        <div class="skeleton" style="width:70%;height:14px;margin-left:15px;"></div>
                    </div>
                `;
            }
            epList.innerHTML = skelHtml;
            
            try {
                const res = await fetch(`https://www.omdbapi.com/?apikey=${OMDB_KEY}&i=${imdbId}&Season=${season}`);
                const data = await res.json();
                
                let html = '';
                if(data.Response === "True" && data.Episodes) {
                    maxEpisodes = data.Episodes.length;
                    document.getElementById('nextEpLabel').innerText = `Sezon ${currentSeason} Bölüm ${currentEpisode + 1}`;
                    data.Episodes.forEach(ep => {
                        const epNum = parseInt(ep.Episode);
                        const isActive = epNum === currentEpisode ? 'active' : '';
                        const epTitle = ep.Title || `${epNum}. Bölüm`;
                        
                        html += `
                            <div class="ep-item ${isActive}" onclick="playSpecificEpisode(${epNum})">
                                <div class="ep-dot"></div>
                                <div class="ep-title" title="${epTitle}">${season}. Sezon ${epNum}. Bölüm - ${epTitle}</div>
                            </div>
                        `;
                    });
                } else {
                    maxEpisodes = 1;
                    html = '<div style="padding:20px; text-align:center; color:#a1a1aa;">Bölüm verileri bulunamadı.</div>';
                }
                epList.innerHTML = html;
                
                if(currentEpisode >= maxEpisodes) {
                    document.getElementById('nextEpBtnBottom').style.display = 'none';
                    document.getElementById('nextEpPopup').style.display = 'none';
                }
            } catch(e) {
                epList.innerHTML = '<div style="padding:20px; color:#ef4444; text-align:center;">Bölümler yüklenirken hata oluştu.</div>';
            }
        }

        async function loadCinematic(type, imdbId) {
            try {
                const res = await fetch(`https://www.omdbapi.com/?apikey=${OMDB_KEY}&i=${imdbId}`);
                const data = await res.json();
                if(data.Response === "True") {
                    globalTitle = data.Title;
                    globalPoster = (data.Poster && data.Poster !== 'N/A') ? data.Poster : globalPoster;
                    globalGenre = data.Genre;
                    
                    document.getElementById('bgAmbient').style.backgroundImage = `url('${globalPoster}')`;
                    
                    let titleDisplay = data.Title;
                    if(type === 'series') titleDisplay += ` (S${currentSeason} B${currentEpisode})`;
                    
                    document.getElementById('mediaTitle').innerText = titleDisplay;
                    document.getElementById('mediaPlot').innerText = data.Plot !== 'N/A' ? data.Plot : 'İçerik açıklaması bulunmuyor.';
                    
                    let metaHtml = `<span class="badge-custom bg-imdb"><i class="fa-solid fa-star"></i> IMDB: ${data.imdbRating}</span>`;
                    metaHtml += `<span class="badge-custom bg-hd">HD 1080P</span>`;
                    metaHtml += `<span class="badge-custom" style="border: 1px solid #e50914; color: #e50914;">${data.Year}</span>`;
                    if(data.Runtime && data.Runtime !== 'N/A') metaHtml += `<span class="badge-custom" style="background:rgba(255,255,255,0.1); color:#fff;"><i class="fa-regular fa-clock"></i> ${data.Runtime}</span>`;
                    document.getElementById('mediaMeta').innerHTML = metaHtml;

                    let castHtml = '';
                    if(data.Actors && data.Actors !== 'N/A') {
                        let actors = data.Actors.split(',').map(a => `<span class="cast-tag">${a.trim()}</span>`).join('');
                        castHtml += `<div class="crew-item"><span>Başroller:</span> ${actors}</div>`;
                    }
                    if(data.Director && data.Director !== 'N/A') {
                        castHtml += `<div class="crew-item"><span>Yönetmen:</span> ${data.Director}</div>`;
                    }
                    if(data.Genre && data.Genre !== 'N/A') {
                        castHtml += `<div class="crew-item"><span>Tür:</span> ${data.Genre}</div>`;
                    }
                    document.getElementById('crewPanel').innerHTML = castHtml;

                    saveToHistory(imdbId, data.Title, type, globalPoster);
                    generateSimilarContent(globalGenre);
                }
            } catch(e) {}

            if (type === 'series') {
                document.getElementById('nextEpBtnBottom').style.display = 'inline-flex';
            }

            let s1_url = type === 'movie' ? `https://vidsrc.net/embed/movie?imdb=${imdbId}` : `https://vidsrc.net/embed/tv?imdb=${imdbId}&season=${currentSeason}&episode=${currentEpisode}`;
            let s2_url = type === 'movie' ? `https://vidsrc.in/embed/movie?imdb=${imdbId}` : `https://vidsrc.in/embed/tv?imdb=${imdbId}&season=${currentSeason}&episode=${currentEpisode}`;
            let s3_url = type === 'movie' ? `https://vidsrc.pro/embed/movie/${imdbId}` : `https://vidsrc.pro/embed/tv/${imdbId}/${currentSeason}/${currentEpisode}`;

            const cinematicSources = [
                { name: 'VIP Sunucu 1', url: s1_url },
                { name: 'VIP Sunucu 2', url: s2_url },
                { name: 'Alternatif', url: s3_url }
            ];

            const selector = document.getElementById('sourceSelector');
            cinematicSources.forEach((src, index) => {
                const btn = document.createElement('button');
                btn.className = 'src-tab' + (index === 0 ? ' active' : '');
                btn.innerHTML = `<i class="fa-solid fa-server"></i> ${src.name}`;
                btn.onclick = (e) => {
                    document.querySelectorAll('.src-tab').forEach(b => b.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    switchCinematicSource(src.url);
                };
                selector.appendChild(btn);
            });

            switchCinematicSource(cinematicSources[0].url);
        }

        function switchCinematicSource(url) {
            const wrapper = document.getElementById('videoWrapper');
            const loader = document.getElementById('playerLoader');
            wrapper.style.display = 'none';
            loader.style.display = 'block';
            wrapper.innerHTML = `<iframe src="${url}" width="100%" height="100%" frameborder="0" scrolling="no" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" allow="autoplay; fullscreen; encrypted-media; picture-in-picture; display-capture; camera; microphone"></iframe>`;
            setTimeout(() => {
                loader.style.display = 'none';
                wrapper.style.display = 'block';
            }, 800);
        }
        
        async function loadSport(matchId, sJson) {
            document.getElementById('mediaTitle').innerText = "Canlı Yayın";
            document.getElementById('mediaPlot').innerText = "Premium spor yayınına bağlanılıyor...";
            document.getElementById('mediaMeta').innerHTML = `<span class="badge-custom" style="background:#e50914; color:#fff; animation: pulse 2s infinite;">CANLI</span>`;
            document.getElementById('crewPanel').innerHTML = '';
            
            try {
                try { currentSportSources = JSON.parse(sJson); } catch(err) { currentSportSources = []; }
                if(currentSportSources && currentSportSources.length > 0) {
                    const selector = document.getElementById('sourceSelector');
                    fetch('watch.php?fetch_api=matches/live').then(r=>r.json()).then(data => {
                        if(!data.error) {
                            const match = data.find(m => m.id == matchId);
                            if(match) {
                                globalTitle = match.title;
                                document.getElementById('mediaTitle').innerText = match.title;
                                saveToHistory(matchId, match.title, 'sport', globalPoster);
                            }
                        }
                    }).catch(e=>{});

                    currentSportSources.forEach((src, index) => {
                        const btn = document.createElement('button');
                        btn.className = 'src-tab' + (index === 0 ? ' active' : '');
                        btn.id = 'src-btn-' + index;
                        btn.innerHTML = `Kaynak ${index + 1}`;
                        btn.onclick = (e) => {
                            document.querySelectorAll('.src-tab').forEach(b => b.classList.remove('active'));
                            e.currentTarget.classList.add('active');
                            currentSourceIndex = index;
                            playSportSource(src);
                        };
                        selector.appendChild(btn);
                    });
                    currentSourceIndex = 0;
                    playSportSource(currentSportSources[0]);
                }
            } catch(e) {}
        }
        
        function playNextSource() {
            currentSourceIndex++;
            if (currentSourceIndex < currentSportSources.length) {
                document.querySelectorAll('.src-tab').forEach(b => b.classList.remove('active'));
                const nextBtn = document.getElementById('src-btn-' + currentSourceIndex);
                if(nextBtn) nextBtn.classList.add('active');
                playSportSource(currentSportSources[currentSourceIndex]);
            }
        }

        async function playSportSource(srcObj) {
            const wrapper = document.getElementById('videoWrapper');
            const loader = document.getElementById('playerLoader');
            wrapper.style.display = 'none';
            loader.style.display = 'block';
            if(plyrInstance) { plyrInstance.destroy(); plyrInstance = null; }
            try {
                let req = await fetch(`watch.php?fetch_api=stream/${srcObj.source}/${srcObj.id}`);
                if (!req.ok) req = await fetch(`https://streamed.pk/api/stream/${srcObj.source}/${srcObj.id}`);
                const data = await req.json();
                wrapper.innerHTML = ''; 
                const streamUrl = data[0].embedUrl;
                if(streamUrl.includes('.m3u8')) {
                    wrapper.innerHTML = `<video id="hlsVideo" controls crossorigin playsinline style="width:100%; height:100%; background:#000;"></video>`;
                    const video = document.getElementById('hlsVideo');
                    
                    const plyrOptions = { 
                        controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'],
                        fullscreen: { enabled: true, fallback: true, iosNative: true },
                        volume: 1, 
                        muted: false 
                    };

                    if (Hls.isSupported()) {
                        const hls = new Hls({
                            capLevelToPlayerSize: false,
                            startLevel: -1
                        });
                        hls.loadSource(streamUrl);
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, function (e, data) {
                            if(data.levels && data.levels.length > 0) {
                                hls.currentLevel = data.levels.length - 1;
                            }
                            
                            plyrInstance = new Plyr(video, plyrOptions);
                            plyrInstance.volume = 1;
                            plyrInstance.on('timeupdate', () => {
                                let pct = (plyrInstance.currentTime / plyrInstance.duration) * 100;
                                if(pct > 0 && pct <= 100) updateProgress(pct);
                            });
                            video.play().catch(e => {});
                        });
                        hls.on(Hls.Events.ERROR, function(e, d) {
                            if(d.fatal) playNextSource();
                        });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = streamUrl;
                        plyrInstance = new Plyr(video, plyrOptions);
                        plyrInstance.volume = 1;
                        plyrInstance.on('timeupdate', () => {
                            let pct = (plyrInstance.currentTime / plyrInstance.duration) * 100;
                            if(pct > 0 && pct <= 100) updateProgress(pct);
                        });
                        video.addEventListener('loadedmetadata', () => video.play());
                    }
                } else {
                    wrapper.innerHTML = `<iframe src="${streamUrl}" width="100%" height="100%" frameborder="0" scrolling="no" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" style="position:absolute; top:0; left:0; width:100%; height:100%; border:none; background:#000;"></iframe>`;
                    setInterval(() => {
                        if(playType === 'series' && currentEpisode < maxEpisodes) {
                            document.getElementById('nextEpPopup').classList.add('show');
                        }
                    }, 40000); 
                }
                loader.style.display = 'none';
                wrapper.style.display = 'block';
            } catch(e) {
                playNextSource();
            }
        }
    </script>
<?php require_once 'includes/footer.php'; ?>