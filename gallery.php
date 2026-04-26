<?php
session_start();
$scans = array_reverse($_SESSION['scans'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FaceScan — Gallery</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Exo+2:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/gallery.css">
</head>
<body>
<a class="back" href="index.php">← BACK TO SCANNER</a>
<h1>◈ CAPTURE GALLERY — <?= count($scans) ?> RECORDS</h1>
<?php if(empty($scans)): ?>
  <div class="empty">NO CAPTURES YET</div>
<?php else: ?>
<div class="grid">
  <?php foreach($scans as $s): ?>
  <div class="card">
    <img src="<?= htmlspecialchars($s['filename'] ? 'uploads/'.$s['filename'] : '') ?>" alt="Capture">
    <div class="card-info">
      <div class="card-time"><?= htmlspecialchars($s['time']) ?></div>
      <div class="card-detail">
        Age ~<?= $s['age'] ?><br>
        <?= htmlspecialchars($s['gender']) ?><br>
        <?= htmlspecialchars($s['expr']) ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</body>
</html>
