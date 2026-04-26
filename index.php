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
<title>FaceScan — Biometric System</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;600;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<style>
  :root {
    --bg: #03050a;
    --surface: #080d18;
    --panel: #0b1220;
    --cyan: #00e5ff;
    --green: #00ff88;
    --red: #ff2244;
    --amber: #ffaa00;
    --text: #c8dff5;
    --dim: #4a6080;
    --border: rgba(0,229,255,0.18);
    --glow: 0 0 20px rgba(0,229,255,0.35);
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Exo 2', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
  }
  /* Animated grid background */
  body::before {
    content:'';
    position:fixed; inset:0;
    background-image:
      linear-gradient(rgba(0,229,255,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,229,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events:none; z-index:0;
    animation: gridMove 20s linear infinite;
  }
  @keyframes gridMove { from{background-position:0 0} to{background-position:40px 40px} }

  header {
    position: relative; z-index:10;
    border-bottom: 1px solid var(--border);
    padding: 18px 40px;
    display: flex; align-items: center; justify-content: space-between;
    backdrop-filter: blur(10px);
    background: rgba(8,13,24,0.85);
  }
  .logo {
    font-family: 'Share Tech Mono', monospace;
    font-size: 1.3rem;
    color: var(--cyan);
    letter-spacing: 4px;
    text-shadow: var(--glow);
  }
  .logo span { color: var(--green); }
  .header-status {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.75rem;
    color: var(--dim);
    letter-spacing: 2px;
  }
  .status-dot {
    display: inline-block; width:8px; height:8px;
    border-radius:50%; background: var(--green);
    margin-right:8px;
    animation: pulse 1.5s ease-in-out infinite;
    box-shadow: 0 0 8px var(--green);
  }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

  main {
    position: relative; z-index:1;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    padding: 30px 40px;
    max-width: 1200px;
    margin: 0 auto;
  }

  /* Camera panel */
  .cam-panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 0 40px rgba(0,229,255,0.07);
  }
  .cam-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 12px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.8rem;
    color: var(--cyan);
    letter-spacing: 2px;
  }
  .cam-body {
    position: relative;
    aspect-ratio: 4/3;
    background: #000;
    display: flex; align-items: center; justify-content: center;
  }
  #video {
    width:100%; height:100%;
    object-fit: cover;
    display:block;
    transform: scaleX(-1); /* mirror */
  }
  #overlay {
    position:absolute; inset:0;
    transform: scaleX(-1);
  }
  /* Corner brackets */
  .bracket { position:absolute; width:60px; height:60px; z-index:5; }
  .bracket-tl { top:20px; left:20px; border-top:2px solid var(--cyan); border-left:2px solid var(--cyan); }
  .bracket-tr { top:20px; right:20px; border-top:2px solid var(--cyan); border-right:2px solid var(--cyan); }
  .bracket-bl { bottom:20px; left:20px; border-bottom:2px solid var(--cyan); border-left:2px solid var(--cyan); }
  .bracket-br { bottom:20px; right:20px; border-bottom:2px solid var(--cyan); border-right:2px solid var(--cyan); }

  .scan-line {
    position:absolute; left:0; right:0; height:2px;
    background: linear-gradient(90deg, transparent, var(--cyan), transparent);
    box-shadow: 0 0 10px var(--cyan);
    z-index:6; display:none;
    animation: scanMove 2s linear infinite;
  }
  @keyframes scanMove { from{top:10%} to{top:90%} }
  .scanning .scan-line { display:block; }

  #placeholder {
    text-align:center; color: var(--dim);
    font-family: 'Share Tech Mono', monospace;
  }
  #placeholder svg { margin-bottom:16px; opacity:0.3; }
  #placeholder p { font-size:0.85rem; letter-spacing:2px; }

  .cam-footer {
    padding: 16px 20px;
    display:flex; gap:12px; align-items:center;
    border-top: 1px solid var(--border);
  }

  /* Buttons */
  .btn {
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.8rem;
    letter-spacing: 2px;
    padding: 10px 22px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
  }
  .btn-primary {
    background: var(--cyan);
    color: #000;
    font-weight: 700;
    box-shadow: 0 0 18px rgba(0,229,255,0.4);
  }
  .btn-primary:hover { box-shadow: 0 0 30px rgba(0,229,255,0.7); transform:translateY(-1px); }
  .btn-success {
    background: var(--green);
    color: #000;
    font-weight: 700;
    box-shadow: 0 0 18px rgba(0,255,136,0.4);
  }
  .btn-success:hover { box-shadow: 0 0 30px rgba(0,255,136,0.7); }
  .btn-danger {
    background: transparent;
    color: var(--red);
    border: 1px solid var(--red);
  }
  .btn-danger:hover { background: rgba(255,34,68,0.12); }
  .btn:disabled { opacity:0.35; cursor:not-allowed; transform:none !important; box-shadow:none !important; }

  #face-status {
    flex:1;
    font-family:'Share Tech Mono', monospace;
    font-size:0.78rem;
    color: var(--dim);
    letter-spacing:1px;
  }

  /* Right panel */
  .side-panel { display:flex; flex-direction:column; gap:16px; }

  .info-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius:10px;
    padding: 18px;
  }
  .card-title {
    font-family:'Share Tech Mono', monospace;
    font-size:0.72rem;
    color: var(--cyan);
    letter-spacing:3px;
    margin-bottom:14px;
    display:flex; align-items:center; gap:8px;
  }
  .card-title::after {
    content:''; flex:1; height:1px; background:var(--border);
  }

  .metric-row {
    display:flex; justify-content:space-between; align-items:center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size:0.82rem;
  }
  .metric-row:last-child { border-bottom:none; }
  .metric-label { color: var(--dim); font-size:0.75rem; letter-spacing:1px; }
  .metric-val {
    font-family:'Share Tech Mono', monospace;
    font-size:0.85rem;
    color: var(--text);
  }
  .metric-val.green { color: var(--green); }
  .metric-val.amber { color: var(--amber); }
  .metric-val.red { color: var(--red); }
  .metric-val.cyan { color: var(--cyan); }

  /* Expression bars */
  .expr-bar-wrap { margin-top:4px; }
  .expr-row { display:flex; align-items:center; gap:8px; margin-bottom:6px; font-size:0.72rem; }
  .expr-name { width:70px; color:var(--dim); letter-spacing:1px; flex-shrink:0; }
  .expr-track { flex:1; height:5px; background:rgba(255,255,255,0.07); border-radius:3px; overflow:hidden; }
  .expr-fill { height:100%; border-radius:3px; transition:width 0.3s; background: var(--cyan); }
  .expr-pct { width:38px; text-align:right; color:var(--text); font-family:'Share Tech Mono', monospace; font-size:0.7rem; }

  /* Scan log */
  .scan-log { max-height:220px; overflow-y:auto; }
  .scan-log::-webkit-scrollbar { width:4px; }
  .scan-log::-webkit-scrollbar-thumb { background:var(--border); border-radius:2px; }
  .log-entry {
    display:flex; align-items:flex-start; gap:10px;
    padding:8px 0;
    border-bottom:1px solid rgba(255,255,255,0.04);
    font-size:0.75rem;
  }
  .log-entry:last-child { border-bottom:none; }
  .log-thumb {
    width:40px; height:40px; border-radius:5px;
    background:#111; border:1px solid var(--border);
    object-fit:cover; flex-shrink:0;
  }
  .log-info { flex:1; min-width:0; }
  .log-time { color:var(--cyan); font-family:'Share Tech Mono', monospace; font-size:0.68rem; }
  .log-detail { color:var(--dim); margin-top:2px; line-height:1.5; }
  .log-empty { color:var(--dim); font-size:0.8rem; text-align:center; padding:20px 0; font-family:'Share Tech Mono', monospace; letter-spacing:2px; }

  /* Toast */
  #toast {
    position:fixed; top:50%; left:50%;
    transform:translate(-50%,-50%) scale(0.5);
    opacity:0; pointer-events:none;
    background:var(--panel); border:2px solid var(--green);
    color:var(--green); padding:24px 48px;
    border-radius:16px; font-family:'Share Tech Mono', monospace;
    font-size:1.2rem; letter-spacing:3px; text-align:center;
    box-shadow: 0 0 60px rgba(0,255,136,0.6), 0 0 120px rgba(0,255,136,0.3);
    transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
    z-index:9999; white-space:nowrap;
  }
  #toast.show { transform:translate(-50%,-50%) scale(1); opacity:1; }
  #toast.error { border-color:var(--red); color:var(--red); box-shadow:0 0 60px rgba(255,34,68,0.6); }
  /* Screen flash */
  #flash {
    position:fixed; inset:0; z-index:9998;
    background:white; opacity:0; pointer-events:none;
    transition: opacity 0.1s;
  }
  #flash.show { opacity:0.15; }

  /* Loading overlay */
  #loading {
    position:fixed; inset:0; z-index:999;
    background:var(--bg);
    display:flex; flex-direction:column;
    align-items:center; justify-content:center; gap:20px;
  }
  .loading-text {
    font-family:'Share Tech Mono', monospace;
    color:var(--cyan); letter-spacing:4px; font-size:0.85rem;
  }
  .loading-bar { width:220px; height:3px; background:rgba(0,229,255,0.15); border-radius:2px; overflow:hidden; }
  .loading-bar-fill { height:100%; background:var(--cyan); border-radius:2px; animation:load 2.5s ease-in-out forwards; box-shadow:0 0 10px var(--cyan); }
  @keyframes load { from{width:0} to{width:100%} }

  @media (max-width:800px) {
    main { grid-template-columns:1fr; padding:16px; }
    header { padding:14px 16px; }
  }
</style>
</head>
<body>

<div id="loading">
  <div class="logo">FACE<span>SCAN</span></div>
  <div class="loading-text" id="load-msg">INITIALIZING MODELS...</div>
  <div class="loading-bar"><div class="loading-bar-fill"></div></div>
</div>

<header>
  <div class="logo">FACE<span>SCAN</span> <span style="color:var(--dim);font-size:0.7rem;margin-left:16px;">v2.4</span></div>
  <div class="header-status"><span class="status-dot"></span>SYSTEM ONLINE &nbsp;|&nbsp; PHP <?= PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION ?> &nbsp;|&nbsp; SESSION: <?= strtoupper(substr(session_id(),0,8)) ?></div>
</header>

<main>
  <!-- Camera Panel -->
  <div class="cam-panel">
    <div class="cam-header">
      ◈ LIVE BIOMETRIC FEED &nbsp;—&nbsp; <span id="cam-res" style="color:var(--dim)">--</span>
    </div>
    <div class="cam-body" id="cam-body">
      <div class="bracket bracket-tl"></div>
      <div class="bracket bracket-tr"></div>
      <div class="bracket bracket-bl"></div>
      <div class="bracket bracket-br"></div>
      <div class="scan-line" id="scan-line"></div>

      <div id="placeholder">
        <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
          <rect x="10" y="10" width="60" height="60" rx="30" stroke="#00e5ff" stroke-width="1.5"/>
          <circle cx="28" cy="34" r="4" fill="#00e5ff"/>
          <circle cx="52" cy="34" r="4" fill="#00e5ff"/>
          <path d="M26 52 Q40 62 54 52" stroke="#00e5ff" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        </svg>
        <p>CAMERA OFFLINE</p>
        <p style="margin-top:8px;font-size:0.7rem">CLICK START TO ACTIVATE</p>
      </div>
      <video id="video" autoplay muted playsinline style="display:none"></video>
      <canvas id="overlay"></canvas>
    </div>
    <div class="cam-footer">
      <button class="btn btn-primary" id="btn-start" onclick="startCamera()">⏵ START</button>
      <button class="btn btn-success" id="btn-capture" onclick="captureFace()" disabled>◉ CAPTURE</button>
      <button class="btn btn-danger" id="btn-stop" onclick="stopCamera()" disabled>■ STOP</button>
      <div id="face-status">AWAITING ACTIVATION...</div>
    </div>
  </div>

  <!-- Side Panel -->
  <div class="side-panel">
    <!-- Live Metrics -->
    <div class="info-card">
      <div class="card-title">◆ LIVE METRICS</div>
      <div class="metric-row"><span class="metric-label">FACES DETECTED</span><span class="metric-val cyan" id="m-faces">0</span></div>
      <div class="metric-row"><span class="metric-label">CONFIDENCE</span><span class="metric-val green" id="m-conf">—</span></div>
      <div class="metric-row"><span class="metric-label">AGE ESTIMATE</span><span class="metric-val" id="m-age">—</span></div>
      <div class="metric-row"><span class="metric-label">GENDER</span><span class="metric-val" id="m-gender">—</span></div>
      <div class="metric-row"><span class="metric-label">TOTAL SCANS</span><span class="metric-val amber" id="m-scans"><?= $_SESSION['scan_count'] ?></span></div>
    </div>

    <!-- Expressions -->
    <div class="info-card">
      <div class="card-title">◆ EXPRESSIONS</div>
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
      <div class="card-title">◆ CAPTURE LOG</div>
      <div class="scan-log" id="scan-log">
        <?php if(empty($_SESSION['scans'])): ?>
          <div class="log-empty" id="log-empty">NO RECORDS YET</div>
        <?php else: ?>
          <?php foreach(array_reverse($_SESSION['scans']) as $s): ?>
          <div class="log-entry">
            <img class="log-thumb" src="<?=$s['thumb']?>" alt="">
            <div class="log-info">
              <div class="log-time"><?=$s['time']?></div>
              <div class="log-detail">Age ~<?=$s['age']?> · <?=$s['gender']?> · <?=$s['expr']?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php if(!empty($_SESSION['scans'])): ?>
      <div style="margin-top:12px;text-align:right">
        <a href="clear.php" style="font-family:'Share Tech Mono',monospace;font-size:0.7rem;color:var(--red);text-decoration:none;letter-spacing:2px;">✕ CLEAR LOG</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<div id="toast">✓ FACE CAPTURED</div>
<div id="flash"></div>

<script>
const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model/';
let videoEl, overlayEl, stream, detectInterval;
let scanning = false;

async function loadModels() {
  document.getElementById('load-msg').textContent = 'LOADING FACE DETECTION...';
  await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
  document.getElementById('load-msg').textContent = 'LOADING LANDMARKS...';
  await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
  document.getElementById('load-msg').textContent = 'LOADING EXPRESSIONS...';
  await faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL);
  document.getElementById('load-msg').textContent = 'LOADING AGE/GENDER...';
  await faceapi.nets.ageGenderNet.loadFromUri(MODEL_URL);
  document.getElementById('loading').style.opacity = '0';
  setTimeout(()=>document.getElementById('loading').style.display='none', 400);
}

async function startCamera() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({video:{width:640,height:480,facingMode:'user'}});
    videoEl = document.getElementById('video');
    overlayEl = document.getElementById('overlay');
    videoEl.srcObject = stream;
    videoEl.style.display = 'block';
    document.getElementById('placeholder').style.display = 'none';
    document.getElementById('btn-start').disabled = true;
    document.getElementById('btn-capture').disabled = false;
    document.getElementById('btn-stop').disabled = false;
    document.getElementById('cam-body').classList.add('scanning');
    setStatus('SCANNING FOR FACES...', 'cyan');

    videoEl.addEventListener('loadedmetadata', () => {
      overlayEl.width = videoEl.videoWidth;
      overlayEl.height = videoEl.videoHeight;
      document.getElementById('cam-res').textContent = videoEl.videoWidth + '×' + videoEl.videoHeight;
      startDetection();
    });
  } catch(e) {
    setStatus('CAMERA ACCESS DENIED: '+e.message, 'red');
  }
}

function stopCamera() {
  if(stream) stream.getTracks().forEach(t=>t.stop());
  clearInterval(detectInterval);
  videoEl.style.display='none';
  document.getElementById('placeholder').style.display='';
  document.getElementById('btn-start').disabled=false;
  document.getElementById('btn-capture').disabled=true;
  document.getElementById('btn-stop').disabled=true;
  document.getElementById('cam-body').classList.remove('scanning');
  const ctx = overlayEl.getContext('2d');
  ctx.clearRect(0,0,overlayEl.width,overlayEl.height);
  setStatus('CAMERA STOPPED', '');
  resetMetrics();
}

function startDetection() {
  const opts = new faceapi.TinyFaceDetectorOptions({inputSize:416, scoreThreshold:0.5});
  detectInterval = setInterval(async () => {
    if(!videoEl || videoEl.paused || videoEl.ended) return;
    const results = await faceapi.detectAllFaces(videoEl, opts)
      .withFaceLandmarks().withFaceExpressions().withAgeAndGender();

    const dims = {width: videoEl.videoWidth, height: videoEl.videoHeight};
    const ctx = overlayEl.getContext('2d');
    ctx.clearRect(0,0,overlayEl.width, overlayEl.height);

    const resized = faceapi.resizeResults(results, dims);
    drawDetections(ctx, resized);
    updateMetrics(results);
  }, 120);
}

function drawDetections(ctx, results) {
  results.forEach(r => {
    const box = r.detection.box;
    const score = (r.detection.score * 100).toFixed(0);

    // Box
    ctx.strokeStyle = '#00e5ff';
    ctx.lineWidth = 2;
    ctx.shadowBlur = 12;
    ctx.shadowColor = '#00e5ff';
    ctx.strokeRect(box.x, box.y, box.width, box.height);
    ctx.shadowBlur = 0;

    // Label
    ctx.fillStyle = 'rgba(0,229,255,0.85)';
    ctx.fillRect(box.x, box.y - 22, 120, 22);
    ctx.fillStyle = '#000';
    ctx.font = '700 11px Share Tech Mono, monospace';
    ctx.fillText(`FACE  ${score}%`, box.x+6, box.y - 7);

    // Landmarks
    if(r.landmarks) {
      ctx.fillStyle = 'rgba(0,255,136,0.7)';
      r.landmarks.positions.forEach(pt => {
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 1.5, 0, 2*Math.PI);
        ctx.fill();
      });
    }
  });
}

function updateMetrics(results) {
  document.getElementById('m-faces').textContent = results.length;
  if(results.length > 0) {
    const r = results[0];
    const conf = (r.detection.score*100).toFixed(1)+'%';
    document.getElementById('m-conf').textContent = conf;
    document.getElementById('m-age').textContent = '~' + Math.round(r.age) + ' yrs';
    document.getElementById('m-gender').textContent = r.gender.toUpperCase() + ' (' + (r.genderProbability*100).toFixed(0) + '%)';

    // expressions
    const exprs = r.expressions;
    ['neutral','happy','sad','angry','disgusted','fearful','surprised'].forEach(e => {
      const pct = ((exprs[e]||0)*100).toFixed(0);
      document.getElementById('expr-'+e).style.width = pct+'%';
      document.getElementById('expt-'+e).textContent = pct+'%';
    });
    setStatus('✔ FACE DETECTED — READY TO CAPTURE', 'green');
  } else {
    setStatus('◌ NO FACE IN FRAME...', '');
    resetMetrics(true);
  }
}

function resetMetrics(partial) {
  if(!partial) {
    document.getElementById('m-faces').textContent = '0';
    document.getElementById('m-conf').textContent = '—';
  }
  document.getElementById('m-age').textContent = '—';
  document.getElementById('m-gender').textContent = '—';
  ['neutral','happy','sad','angry','disgusted','fearful','surprised'].forEach(e => {
    document.getElementById('expr-'+e).style.width = '0%';
    document.getElementById('expt-'+e).textContent = '0%';
  });
}

async function captureFace() {
  if(!videoEl || videoEl.paused) return;
  const opts = new faceapi.TinyFaceDetectorOptions({inputSize:416, scoreThreshold:0.5});
  const results = await faceapi.detectAllFaces(videoEl, opts)
    .withFaceLandmarks().withFaceExpressions().withAgeAndGender();

  if(results.length === 0) {
    setStatus('⚠ NO FACE DETECTED — ALIGN FACE IN FRAME', 'amber');
    showToast('⚠ មុខមិននៅក្នុង Frame!', true);
    return;
  }
  const r = results[0];

  // Grab canvas snapshot
  const snap = document.createElement('canvas');
  snap.width = videoEl.videoWidth; snap.height = videoEl.videoHeight;
  const sCtx = snap.getContext('2d');
  sCtx.scale(-1,1); sCtx.drawImage(videoEl, -snap.width, 0); // un-mirror
  const dataURL = snap.toDataURL('image/jpeg', 0.7);

  // Top expression
  const exprs = r.expressions;
  const topExpr = Object.entries(exprs).sort((a,b)=>b[1]-a[1])[0][0];

  // POST to PHP
  const payload = {
    image: dataURL,
    age: Math.round(r.age),
    gender: r.gender,
    genderProb: (r.genderProbability*100).toFixed(0),
    expression: topExpr,
    confidence: (r.detection.score*100).toFixed(1)
  };

  const resp = await fetch('save.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const json = await resp.json();

  if(!json.success) {
    showToast('❌ Save បរាជ័យ! មើល Console', true);
    return;
  }
  if(json.success) {
    // Update scan count
    document.getElementById('m-scans').textContent = json.total;

    // Prepend to log
    const logEl = document.getElementById('scan-log');
    document.getElementById('log-empty')?.remove();
    const entry = document.createElement('div');
    entry.className = 'log-entry';
    entry.innerHTML = `
      <img class="log-thumb" src="${json.thumb}" alt="">
      <div class="log-info">
        <div class="log-time">${json.time}</div>
        <div class="log-detail">Age ~${json.age} · ${json.gender} · ${json.expression}</div>
      </div>`;
    logEl.prepend(entry);

    showToast('✓ បានរក្សាទុករួចហើយ! (#' + json.total + ')');
    setStatus('✔ CAPTURE SAVED — SCAN #' + json.total, 'green');
  }
}

function setStatus(msg, color) {
  const el = document.getElementById('face-status');
  el.textContent = msg;
  el.style.color = color === 'green' ? 'var(--green)' : color === 'cyan' ? 'var(--cyan)' : color === 'amber' ? 'var(--amber)' : color === 'red' ? 'var(--red)' : 'var(--dim)';
}

function showToast(msg, isError=false) {
  const t = document.getElementById('toast');
  const f = document.getElementById('flash');
  t.textContent = msg;
  t.classList.remove('error');
  if(isError) t.classList.add('error');

  // Flash effect
  f.classList.add('show');
  setTimeout(()=>f.classList.remove('show'), 150);

  // Show toast
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 2800);
}

// រង់ចាំឱ្យ face-api.js ផ្ទុករួចសិន
window.addEventListener('load', function() {
  if (typeof faceapi === 'undefined') {
    document.getElementById('load-msg').textContent = 'ERROR: face-api.js FAILED TO LOAD — CHECK INTERNET';
    document.getElementById('load-msg').style.color = '#ff2244';
    return;
  }
  loadModels();
});
</script>
</body>
</html>
