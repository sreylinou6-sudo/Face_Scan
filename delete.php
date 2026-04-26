<?php
session_start();
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$idToDelete = $data['id'];
$found = false;

if (isset($_SESSION['scans']) && is_array($_SESSION['scans'])) {
    foreach ($_SESSION['scans'] as $key => $scan) {
        if ($scan['id'] === $idToDelete) {
            // Delete physical file
            if (!empty($scan['filename'])) {
                $filepath = 'uploads/' . $scan['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            // Remove from session
            unset($_SESSION['scans'][$key]);
            $_SESSION['scans'] = array_values($_SESSION['scans']); // re-index
            $_SESSION['scan_count'] = count($_SESSION['scans']);
            $found = true;
            break;
        }
    }
}

echo json_encode(['success' => $found, 'total' => $_SESSION['scan_count']]);
