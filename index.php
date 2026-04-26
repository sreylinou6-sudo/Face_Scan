<?php
session_start();
if (!isset($_SESSION['scan_count'])) $_SESSION['scan_count'] = 0;
if (!isset($_SESSION['scans'])) $_SESSION['scans'] = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FaceScan — ✨ Cute Biometric ✨</title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="particle-container" id="particles"></div>

<div id="loading">
  <div class="logo">FACE<span>SCAN</span></div>
  <div class="loading-text" id="load-msg">INITIALIZING MODELS...</div>
  <div class="loading-bar"><div class="loading-bar-fill"></div></div>
</div>

<header>
  <div class="logo">FACE<span>SCAN</span> <span style="color:var(--dim);font-size:0.7rem;margin-left:16px;">v2.4</span></div>
  <div class="header-status"><span class="status-dot"></span>SYSTEM ONLINE</div>
</header>

<main>
  <!-- Camera Panel -->
  <div class="cam-panel">
    <div class="cam-header">
      ✨ YOUR CUTE FACE ✨ &nbsp;—&nbsp; <span id="cam-res" style="color:var(--dim)">--</span>
    </div>
    <div class="cam-body" id="cam-body">
      <div class="scan-line" id="scan-line"></div>

      <div id="placeholder">
        <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
          <rect x="10" y="10" width="60" height="60" rx="30" stroke="var(--primary)" stroke-width="2"/>
          <circle cx="28" cy="34" r="4" fill="var(--primary)"/>
          <circle cx="52" cy="34" r="4" fill="var(--primary)"/>
          <path d="M26 52 Q40 62 54 52" stroke="var(--primary)" stroke-width="2" fill="none" stroke-linecap="round"/>
        </svg>
        <p>CAMERA IS NAPPING 💤</p>
        <p style="margin-top:8px;font-size:0.8rem">WAKE IT UP TO START!</p>
      </div>
      <video id="video" autoplay muted playsinline style="display:none"></video>
      <canvas id="overlay"></canvas>
      
      <!-- Effects Panel -->
      <div class="effects-panel" id="effects-panel">
        <div class="effect-item active" onclick="setEffect('none')">
          <div class="effect-icon">✨</div>
          <div class="effect-name">Normal</div>
        </div>
        <div class="effect-item" onclick="setEffect('lovestruck')">
          <div class="effect-icon">💖</div>
          <div class="effect-name">Love</div>
        </div>
        <div class="effect-item" onclick="setEffect('dizzy')">
          <div class="effect-icon">💫</div>
          <div class="effect-name">Dizzy</div>
        </div>
        <div class="effect-item" onclick="setEffect('party')">
          <div class="effect-icon">🎉</div>
          <div class="effect-name">Party</div>
        </div>
        <div class="effect-item" onclick="setEffect('cute_cat')">
          <div class="effect-icon">🐱</div>
          <div class="effect-name">Cat</div>
        </div>
        <div class="effect-item" onclick="setEffect('angel')">
          <div class="effect-icon">👼</div>
          <div class="effect-name">Angel</div>
        </div>
        <div class="effect-item" onclick="setEffect('blush')">
          <div class="effect-icon">😊</div>
          <div class="effect-name">Blush</div>
        </div>
        <div class="effect-item" onclick="setEffect('bubbles')">
          <div class="effect-icon">🫧</div>
          <div class="effect-name">Bubble</div>
        </div>
      </div>
    </div>
    <div class="cam-footer">
      <button class="btn btn-primary" id="btn-start" onclick="startCamera()">⏵ START</button>
      <button class="btn btn-success" id="btn-capture" onclick="captureFace()" disabled>◉ CAPTURE</button>
      <button class="btn btn-info" id="btn-effects" onclick="toggleEffects()">🪄 EFFECTS</button>
      <button class="btn btn-danger" id="btn-stop" onclick="stopCamera()" disabled>■ STOP</button>
      <div id="face-status">AWAITING ACTIVATION...</div>
    </div>
  </div>

  <!-- Side Panel -->
  <div class="side-panel">
    <!-- Live Metrics -->
    <div class="info-card">
      <div class="card-title">🎀 LIVE STATS</div>
      <div class="metric-row"><span class="metric-label">👥 FACES DETECTED</span><span class="metric-val cyan" id="m-faces">0</span></div>
      <div class="metric-row"><span class="metric-label">🎯 CONFIDENCE</span><span class="metric-val green" id="m-conf">—</span></div>
      <div class="metric-row"><span class="metric-label">🎀 GENDER</span><span class="metric-val" id="m-gender">—</span></div>
      <div class="metric-row"><span class="metric-label">📸 TOTAL SCANS</span><span class="metric-val amber" id="m-scans"><?= $_SESSION['scan_count'] ?></span></div>
    </div>

    <!-- Expressions -->
    <div class="info-card">
      <div class="card-title">🍭 FEELINGS</div>
      <div class="expr-bar-wrap" id="expr-bars">
        <?php foreach(['neutral','happy','sad','angry','disgusted','fearful','surprised'] as $e): ?>
        <div class="expr-row">
          <span class="expr-name"><?= strtoupper($e) ?></span>
          <div class="expr-track"><div class="expr-fill" id="expr-<?=$e?>" style="width:0%"></div></div>
          <span class="expr-pct" id="expt-<?=$e?>">0%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Scan Log -->
    <div class="info-card" style="flex:1">
      <div class="card-title">💖 MEMORY BOX</div>
      <div class="scan-log" id="scan-log">
        <?php if(empty($_SESSION['scans'])): ?>
          <div class="log-empty" id="log-empty">NO RECORDS YET</div>
        <?php else: ?>
          <?php foreach(array_reverse($_SESSION['scans']) as $s): ?>
          <div class="log-entry" id="log-<?=$s['id']?>">
            <div class="btn-delete" onclick="deleteEntry('<?=$s['id']?>', event)" title="Delete">&times;</div>
            <img class="log-thumb" src="<?=$s['thumb']?>" alt="" onclick="openPreview('<?=$s['thumb']?>', 'uploads/<?=$s['filename']?>')">
            <div class="log-info">
              <div class="log-time"><?=$s['time']?></div>
              <div class="log-detail"><?=$s['gender']?> · <?=$s['expr']?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php if(!empty($_SESSION['scans'])): ?>
      <div style="padding: 0 24px 24px 24px;">
        <button onclick="if(confirm('Are you sure you want to delete all the pictures?')) location.href='clear.php'" class="btn btn-danger" style="width:100%; font-size:0.8rem; padding:12px;">✕ CLEAR EVERYTHING</button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<div id="toast">✓ FACE CAPTURED</div>
<div id="flash"></div>

<!-- Image Preview Modal -->
<div id="img-modal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">◈ IMAGE PREVIEW &nbsp;—&nbsp; <span id="modal-res" style="color:var(--dim);font-size:0.75rem;">--</span></div>
      <div class="modal-close" onclick="closeModal()">&times;</div>
    </div>
    <div class="modal-body">
      <img id="modal-img" src="" alt="Preview">
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary download-btn" id="dl-png">PNG</button>
      <button class="btn btn-success download-btn" id="dl-jpg">JPG</button>
    </div>
  </div>
</div>

<script src="js/script.js?v=<?= time() ?>"></script>
</body>
</html>
