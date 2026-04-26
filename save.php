<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['image'])) {
    echo json_encode(['success'=>false,'error'=>'No data received']);
    exit;
}

// Decode base64 image
$imageData = $data['image'];
$base64Raw = $imageData; // keep original for thumbnail

if (strpos($imageData, 'data:image') === 0) {
    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
}
$imageData = base64_decode($imageData);

// Save full image to uploads/
if (!is_dir('uploads')) mkdir('uploads', 0755, true);
if (!is_dir('logs'))   mkdir('logs',    0755, true);

$filename = 'face_' . time() . '_' . rand(1000,9999) . '.jpg';
$filepath = 'uploads/' . $filename;
file_put_contents($filepath, $imageData);

// Build thumbnail as base64 data URL (no file path issues!)
$thumbDataURL = '';
if (function_exists('imagecreatefromstring')) {
    $src = @imagecreatefromstring($imageData);
    if ($src) {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $ts = 80;
        $cropX    = (int)max(0, ($sw - $sh) / 2);
        $cropSize = min($sw, $sh);
        $thumbImg = imagecreatetruecolor($ts, $ts);
        imagecopyresampled($thumbImg, $src, 0, 0, $cropX, 0, $ts, $ts, $cropSize, $sh);
        ob_start();
        imagejpeg($thumbImg, null, 75);
        $thumbBin = ob_get_clean();
        $thumbDataURL = 'data:image/jpeg;base64,' . base64_encode($thumbBin);
        imagedestroy($src);
        imagedestroy($thumbImg);
    }
}

// fallback: use original image as thumbnail
if (!$thumbDataURL) {
    $thumbDataURL = $base64Raw;
}

// Build scan record
$time = date('H:i:s');
$age  = intval($data['age']        ?? 0);
$gender = ucfirst($data['gender']  ?? 'Unknown');
$expr   = ucfirst($data['expression'] ?? 'neutral');
$conf   = floatval($data['confidence'] ?? 0);

$record = [
    'id'         => uniqid(),
    'time'       => $time,
    'filename'   => $filename,
    'thumb'      => $thumbDataURL,   // base64 — ដំណើរការគ្រប់ host
    'age'        => $age,
    'gender'     => $gender,
    'genderProb' => $data['genderProb'] ?? 0,
    'expr'       => $expr,
    'confidence' => $conf,
    'datetime'   => date('Y-m-d H:i:s'),
];

if (!isset($_SESSION['scans']))      $_SESSION['scans']      = [];
if (!isset($_SESSION['scan_count'])) $_SESSION['scan_count'] = 0;
$_SESSION['scans'][] = $record;
$_SESSION['scan_count']++;

// Log
$logLine = date('Y-m-d H:i:s')." | Age:{$age} | {$gender} | Expr:{$expr} | Conf:{$conf}% | File:{$filename}\n";
file_put_contents('logs/scans.log', $logLine, FILE_APPEND);

echo json_encode([
    'success'    => true,
    'total'      => $_SESSION['scan_count'],
    'time'       => $time,
    'thumb'      => $thumbDataURL,
    'age'        => $age,
    'gender'     => $gender,
    'expression' => $expr,
    'filename'   => $filename,
]);
