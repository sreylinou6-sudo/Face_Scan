const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model/';
let videoEl, overlayEl, stream, detectInterval;
let scanning = false;
let activeEffect = 'none';

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
  setTimeout(() => document.getElementById('loading').style.display = 'none', 400);
}

async function startCamera() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: 'user' } });
    videoEl = document.getElementById('video');
    overlayEl = document.getElementById('overlay');
    videoEl.srcObject = stream;
    videoEl.style.display = 'block';
    document.getElementById('placeholder').style.display = 'none';
    document.getElementById('btn-start').disabled = true;
    document.getElementById('btn-capture').disabled = false;
    document.getElementById('btn-stop').disabled = false;
    document.getElementById('cam-body').classList.add('scanning');
    setStatus('LOOKING FOR CUTIES... ✨', 'cyan');

    videoEl.onloadedmetadata = () => {
      videoEl.play();
      overlayEl.width = videoEl.videoWidth;
      overlayEl.height = videoEl.videoHeight;
      document.getElementById('cam-res').textContent = videoEl.videoWidth + '×' + videoEl.videoHeight;
      startDetection();
    };
  } catch (e) {
    console.error('Camera Access Error:', e);
    setStatus('❌ CAMERA ERROR: ' + e.message, 'red');
    showToast('❌ Camera failed! Check permissions.', true);
  }
}

function stopCamera() {
  if (stream) stream.getTracks().forEach(t => t.stop());
  clearInterval(detectInterval);
  videoEl.style.display = 'none';
  document.getElementById('placeholder').style.display = '';
  document.getElementById('btn-start').disabled = false;
  document.getElementById('btn-capture').disabled = true;
  document.getElementById('btn-stop').disabled = true;
  document.getElementById('cam-body').classList.remove('scanning');
  const ctx = overlayEl.getContext('2d');
  ctx.clearRect(0, 0, overlayEl.width, overlayEl.height);
  setStatus('CAMERA IS SLEEPING 💤', '');
  resetMetrics();
}

function startDetection() {
  const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 });
  detectInterval = setInterval(async () => {
    if (!videoEl || videoEl.paused || videoEl.ended) return;
    const results = await faceapi.detectAllFaces(videoEl, opts)
      .withFaceLandmarks().withFaceExpressions().withAgeAndGender();

    const dims = { width: videoEl.videoWidth, height: videoEl.videoHeight };
    const ctx = overlayEl.getContext('2d');
    ctx.clearRect(0, 0, overlayEl.width, overlayEl.height);

    const resized = faceapi.resizeResults(results, dims);
    drawDetections(ctx, resized);
    if (activeEffect !== 'none') drawOverlays(ctx, resized);
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
    ctx.fillText(`FACE  ${score}%`, box.x + 6, box.y - 7);

    // Landmarks
    if (r.landmarks) {
      ctx.fillStyle = 'rgba(0,255,136,0.7)';
      r.landmarks.positions.forEach(pt => {
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 1.5, 0, 2 * Math.PI);
        ctx.fill();
      });
    }
  });
}

function drawOverlays(ctx, results) {
  results.forEach(r => {
    const landmarks = r.landmarks;
    if (!landmarks) return;

    const jaw = landmarks.getJawOutline();
    const nose = landmarks.getNose();
    const mouth = landmarks.getMouth();
    const leftEye = landmarks.getLeftEye();
    const rightEye = landmarks.getRightEye();
    const leftEyebrow = landmarks.getLeftEyeBrow();
    const rightEyebrow = landmarks.getRightEyeBrow();

    // Helper for center of a point set
    const getCenter = (pts) => ({
      x: pts.reduce((sum, p) => sum + p.x, 0) / pts.length,
      y: pts.reduce((sum, p) => sum + p.y, 0) / pts.length
    });

    const noseCenter = getCenter(nose);
    const faceWidth = Math.abs(jaw[16].x - jaw[0].x);
    const topOfHead = leftEyebrow[0].y - (faceWidth * 0.2);

    ctx.save();
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    if (activeEffect === 'lovestruck') {
      const time = Date.now() / 400;
      ['💖', '💕', '💗'].forEach((emoji, i) => {
        const ox = Math.sin(time + i) * (faceWidth * 0.4);
        const oy = Math.cos(time + i) * 20 - (faceWidth * 0.4);
        ctx.font = `${faceWidth * 0.25}px serif`;
        ctx.fillText(emoji, noseCenter.x + ox, noseCenter.y + oy);
      });
      // Blushed cheeks
      ctx.fillStyle = 'rgba(255, 182, 193, 0.4)';
      [jaw[3], jaw[13]].forEach(pt => {
        ctx.beginPath();
        ctx.arc(pt.x, pt.y - 10, faceWidth * 0.1, 0, Math.PI * 2);
        ctx.fill();
      });
    }

    if (activeEffect === 'dizzy') {
      const time = Date.now() / 300;
      const radius = faceWidth * 0.4;
      ['💫', '⭐', '✨'].forEach((emoji, i) => {
        const angle = time + (i * (Math.PI * 2 / 3));
        const x = noseCenter.x + Math.cos(angle) * radius;
        const y = topOfHead + Math.sin(angle * 0.5) * 15;
        ctx.font = `${faceWidth * 0.2}px serif`;
        ctx.fillText(emoji, x, y);
      });
    }

    if (activeEffect === 'party') {
      const time = Date.now() / 200;
      ['🎉', '🎊', '🎈', '✨'].forEach((emoji, i) => {
        const ox = ((i % 2 === 0 ? 1 : -1) * (faceWidth * 0.6)) + Math.sin(time + i) * 10;
        const oy = (Math.sin(time * 0.5 + i) * 50) - (faceWidth * 0.5);
        ctx.font = `${faceWidth * 0.2}px serif`;
        ctx.fillText(emoji, noseCenter.x + ox, noseCenter.y + oy);
      });
    }

    if (activeEffect === 'cute_cat') {
      // Ears
      ctx.font = `${faceWidth * 0.5}px serif`;
      ctx.fillText('🐱', noseCenter.x, topOfHead);
      // Whiskers
      ctx.strokeStyle = 'rgba(0,0,0,0.3)';
      ctx.lineWidth = 2;
      [[-1, 1], [1, 1]].forEach(([side, dir]) => {
        for (let i = -1; i <= 1; i++) {
          ctx.beginPath();
          ctx.moveTo(noseCenter.x + (side * 10), noseCenter.y);
          ctx.lineTo(noseCenter.x + (side * faceWidth * 0.5), noseCenter.y + (i * 15));
          ctx.stroke();
        }
      });
    }

    if (activeEffect === 'angel') {
      ctx.font = `${faceWidth * 0.4}px serif`;
      ctx.fillText('😇', noseCenter.x, topOfHead - 20);
      ctx.font = `${faceWidth * 0.6}px serif`;
      ctx.globalAlpha = 0.6;
      ctx.fillText('🪽', noseCenter.x - (faceWidth * 0.5), noseCenter.y);
      ctx.fillText('🪽', noseCenter.x + (faceWidth * 0.5), noseCenter.y);
    }

    if (activeEffect === 'blush') {
      ctx.fillStyle = 'rgba(255, 105, 180, 0.3)';
      ctx.filter = 'blur(10px)';
      [jaw[2], jaw[14]].forEach(pt => {
        ctx.beginPath();
        ctx.arc(pt.x, pt.y - 15, faceWidth * 0.15, 0, Math.PI * 2);
        ctx.fill();
      });
      ctx.filter = 'none';
      ctx.font = `${faceWidth * 0.15}px serif`;
      ctx.fillText('🌸', jaw[2].x, jaw[2].y - 15);
      ctx.fillText('🌸', jaw[14].x, jaw[14].y - 15);
    }

    if (activeEffect === 'bubbles') {
      const time = Date.now() / 500;
      for (let i = 0; i < 6; i++) {
        const angle = (time + i) % (Math.PI * 2);
        const r = (faceWidth * 0.3) + (Math.sin(time + i) * 20);
        const x = noseCenter.x + Math.cos(angle * 2) * r;
        const y = noseCenter.y + Math.sin(angle) * r - 50;
        ctx.font = `${faceWidth * 0.15}px serif`;
        ctx.fillText('🫧', x, y);
      }
    }

    ctx.restore();
  });
}

function updateMetrics(results) {
  document.getElementById('m-faces').textContent = results.length;
  if (results.length > 0) {
    const r = results[0];
    const conf = (r.detection.score * 100).toFixed(1) + '%';
    document.getElementById('m-conf').textContent = conf;
    if (document.getElementById('m-age')) document.getElementById('m-age').textContent = '~' + Math.round(r.age) + ' yrs';
    document.getElementById('m-gender').textContent = r.gender.toUpperCase() + ' (' + (r.genderProbability * 100).toFixed(0) + '%)';

    // expressions
    const exprs = r.expressions;
    ['neutral', 'happy', 'sad', 'angry', 'disgusted', 'fearful', 'surprised'].forEach(e => {
      const pct = ((exprs[e] || 0) * 100).toFixed(0);
      document.getElementById('expr-' + e).style.width = pct + '%';
      document.getElementById('expt-' + e).textContent = pct + '%';
    });
    setStatus('✔ FACE DETECTED — READY TO CAPTURE', 'green');
  } else {
    setStatus('◌ NO FACE IN FRAME...', '');
    resetMetrics(true);
  }
}

function resetMetrics(partial) {
  if (!partial) {
    document.getElementById('m-faces').textContent = '0';
    document.getElementById('m-conf').textContent = '—';
  }
  if (document.getElementById('m-age')) document.getElementById('m-age').textContent = '—';
  document.getElementById('m-gender').textContent = '—';
  ['neutral', 'happy', 'sad', 'angry', 'disgusted', 'fearful', 'surprised'].forEach(e => {
    document.getElementById('expr-' + e).style.width = '0%';
    document.getElementById('expt-' + e).textContent = '0%';
  });
}

async function captureFace() {
  if (!videoEl || videoEl.paused) return;
  const opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 });
  const results = await faceapi.detectAllFaces(videoEl, opts)
    .withFaceLandmarks().withFaceExpressions().withAgeAndGender();

  if (results.length === 0) {
    setStatus('⚠ NO CUTIES FOUND! ALIGN YOUR FACE 🌸', 'amber');
    showToast('⚠ Face is not in the Frame!', true);
    return;
  }
  const r = results[0];

  // Grab canvas snapshot
  const snap = document.createElement('canvas');
  snap.width = videoEl.videoWidth; snap.height = videoEl.videoHeight;
  const sCtx = snap.getContext('2d');
  sCtx.scale(-1, 1); sCtx.drawImage(videoEl, -snap.width, 0); // un-mirror
  
  // Draw overlays on top for the save
  const opts2 = new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 });
  const resultsForSnap = await faceapi.detectAllFaces(videoEl, opts2).withFaceLandmarks();
  if (resultsForSnap.length > 0 && activeEffect !== 'none') {
    // We need to un-mirror landmarks for the snap
    const resizedSnap = faceapi.resizeResults(resultsForSnap, { width: snap.width, height: snap.height });
    // Adjust coordinates for the un-mirrored canvas
    resizedSnap.forEach(r => {
      r.landmarks.positions.forEach(pt => pt.x = snap.width - pt.x);
    });
    drawOverlays(sCtx, resizedSnap);
  }

  const dataURL = snap.toDataURL('image/jpeg', 0.8);

  // Top expression
  const exprs = r.expressions;
  const topExpr = Object.entries(exprs).sort((a, b) => b[1] - a[1])[0][0];

  // POST to PHP
  const payload = {
    image: dataURL,
    age: Math.round(r.age),
    gender: r.gender,
    genderProb: (r.genderProbability * 100).toFixed(0),
    expression: topExpr,
    confidence: (r.detection.score * 100).toFixed(1)
  };

  const resp = await fetch('save.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const json = await resp.json();

  if (!json.success) {
    showToast('❌ Save Failed! Check Console', true);
    return;
  }
  if (json.success) {
    // Update scan count
    document.getElementById('m-scans').textContent = json.total;

    // Prepend to log
    const logEl = document.getElementById('scan-log');
    document.getElementById('log-empty')?.remove();
    const entry = document.createElement('div');
    entry.className = 'log-entry';
    entry.id = `log-${json.id}`;
    entry.innerHTML = `
      <div class="btn-delete" onclick="deleteEntry('${json.id}', event)" title="Delete">&times;</div>
      <img class="log-thumb" src="${json.thumb}" alt="" onclick="openPreview('${json.thumb}', 'uploads/${json.filename}')">
      <div class="log-info">
        <div class="log-time">${json.time}</div>
        <div class="log-detail">${json.gender} · ${json.expression}</div>
      </div>`;
    logEl.prepend(entry);

    showToast('✓ Your Picture is saved! (#' + json.total + ')');
    setStatus('✔ CAPTURE SAVED — SCAN #' + json.total, 'green');
  }
}

async function deleteEntry(id, event) {
  if (event) event.stopPropagation();
  if (!confirm('Are you sure you want to delete this picture?')) return;

  try {
    const resp = await fetch('delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    });
    const json = await resp.json();

    if (json.success) {
      const el = document.getElementById(`log-${id}`);
      if (el) {
        el.style.transform = 'translateX(50px)';
        el.style.opacity = '0';
        setTimeout(() => {
          el.remove();
          if (json.total === 0) {
            document.getElementById('scan-log').innerHTML = '<div class="log-empty" id="log-empty">NO RECORDS YET</div>';
          }
        }, 300);
      }
      document.getElementById('m-scans').textContent = json.total;
      showToast('✓ Delete Successfully');
    } else {
      showToast('❌ Delete Failed', true);
    }
  } catch (e) {
    console.error('Delete failed', e);
    showToast('❌ Error during deletion', true);
  }
}

// Preview & Download Logic
function openPreview(thumb, fullUrl) {
  console.log("Opening preview for:", fullUrl);
  const modal = document.getElementById('img-modal');
  const modalImg = document.getElementById('modal-img');
  const resEl = document.getElementById('modal-res');

  if (!modal || !modalImg) {
    console.error("Modal elements not found!");
    return;
  }

  // Show thumb first
  modalImg.src = thumb;
  if (resEl) resEl.textContent = '...';
  modal.classList.add('show');

  // Setup download buttons
  const pngBtn = document.getElementById('dl-png');
  const jpgBtn = document.getElementById('dl-jpg');

  if (pngBtn) {
    pngBtn.disabled = true;
    pngBtn.style.opacity = '0.5';
    pngBtn.onclick = (e) => { e.stopPropagation(); downloadImage(modalImg.src, 'capture.png', 'image/png'); };
  }
  if (jpgBtn) {
    jpgBtn.disabled = true;
    jpgBtn.style.opacity = '0.5';
    jpgBtn.onclick = (e) => { e.stopPropagation(); downloadImage(modalImg.src, 'capture.jpg', 'image/jpeg'); };
  }

  // Load full image
  const fullImg = new Image();
  fullImg.onload = () => {
    console.log("Full image loaded:", fullUrl);
    modalImg.src = fullUrl;
    if (resEl) resEl.textContent = fullImg.width + '×' + fullImg.height;

    // Enable buttons
    if (pngBtn) { pngBtn.disabled = false; pngBtn.style.opacity = '1'; }
    if (jpgBtn) { jpgBtn.disabled = false; jpgBtn.style.opacity = '1'; }
  };
  fullImg.onerror = () => {
    console.warn("Failed to load full image, sticking with thumb:", fullUrl);
    const tempImg = new Image();
    tempImg.onload = () => {
      if (resEl) resEl.textContent = tempImg.width + '×' + tempImg.height + ' (THUMB)';
      // Enable anyway so they can at least save the thumb
      if (pngBtn) { pngBtn.disabled = false; pngBtn.style.opacity = '1'; }
      if (jpgBtn) { jpgBtn.disabled = false; jpgBtn.style.opacity = '1'; }
    };
    tempImg.src = thumb;
  };
  fullImg.src = (fullUrl && fullUrl !== 'uploads/') ? fullUrl : thumb;
}

function closeModal() {
  document.getElementById('img-modal').classList.remove('show');
}

async function downloadImage(sourceUrl, filename, type) {
  try {
    console.log("Downloading:", sourceUrl, "as", type);
    const img = new Image();

    // Ensure we can use the image in a canvas
    img.crossOrigin = "anonymous";

    await new Promise((resolve, reject) => {
      img.onload = resolve;
      img.onerror = () => reject(new Error("Failed to load image for download"));
      img.src = sourceUrl;
    });

    const canvas = document.createElement('canvas');
    canvas.width = img.width;
    canvas.height = img.height;
    const ctx = canvas.getContext('2d');

    // For JPEG, fill background with white (in case of transparent PNGs)
    if (type === 'image/jpeg') {
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    ctx.drawImage(img, 0, 0);

    const dataUrl = canvas.toDataURL(type, 0.9);
    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    showToast('✓ Saved ' + filename);
  } catch (e) {
    console.error('Download failed:', e);
    showToast('❌ Download Failed!', true);

    // Fallback: try direct download if canvas fails
    try {
      const a = document.createElement('a');
      a.href = sourceUrl;
      a.download = filename;
      a.click();
    } catch (err) { }
  }
}

// Close modal on outside click
window.onclick = function (event) {
  const modal = document.getElementById('img-modal');
  if (event.target == modal) {
    closeModal();
  }
}

function setStatus(msg, color) {
  const el = document.getElementById('face-status');
  el.textContent = msg;
  el.style.color = color === 'green' ? '#88d498' : color === 'cyan' ? '#a2d2ff' : color === 'amber' ? '#ffb347' : color === 'red' ? '#ff8fa3' : 'var(--dim)';
}

function showToast(msg, isError = false) {
  const t = document.getElementById('toast');
  const f = document.getElementById('flash');
  t.textContent = msg;
  t.classList.remove('error');
  if (isError) t.classList.add('error');

  // Flash effect
  f.classList.add('show');
  setTimeout(() => f.classList.remove('show'), 150);

  // Show toast
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

// Effect Handling
function toggleEffects() {
  const panel = document.getElementById('effects-panel');
  panel.classList.toggle('show');
  createParticles(event.clientX, event.clientY, ['🪄', '✨', '⭐']);
}

function setEffect(eff) {
  activeEffect = eff;
  document.querySelectorAll('.effect-item').forEach(item => {
    item.classList.remove('active');
    if (item.getAttribute('onclick').includes(eff)) item.classList.add('active');
  });
  showToast('🪄 EFFECT: ' + eff.toUpperCase());
}

// Particle System
function createParticles(x, y, symbols = ['💖', '✨', '🌸', '🍭']) {
  const container = document.getElementById('particles');
  for (let i = 0; i < 12; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.textContent = symbols[Math.floor(Math.random() * symbols.length)];
    p.style.left = x + 'px';
    p.style.top = y + 'px';
    p.style.fontSize = (Math.random() * 20 + 15) + 'px';
    
    const tx = (Math.random() - 0.5) * 300;
    const ty = (Math.random() - 0.5) * 300 - 100;
    const tr = (Math.random() - 0.5) * 720;
    
    p.style.setProperty('--tx', `${tx}px`);
    p.style.setProperty('--ty', `${ty}px`);
    p.style.setProperty('--tr', `${tr}deg`);
    
    container.appendChild(p);
    setTimeout(() => p.remove(), 1000);
  }
}

// Attach particles to all buttons
document.addEventListener('click', (e) => {
  if (e.target.closest('.btn')) {
    createParticles(e.clientX, e.clientY);
  }
});

// រង់ចាំឱ្យ face-api.js ផ្ទុករួចសិន
window.addEventListener('load', function () {
  if (typeof faceapi === 'undefined') {
    document.getElementById('load-msg').textContent = 'ERROR: face-api.js FAILED TO LOAD — CHECK INTERNET';
    document.getElementById('load-msg').style.color = '#ff2244';
    return;
  }
  loadModels();
});
