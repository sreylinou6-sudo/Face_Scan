<?php
session_start();

// Delete physical files
if (isset($_SESSION['scans']) && is_array($_SESSION['scans'])) {
    foreach ($_SESSION['scans'] as $scan) {
        if (!empty($scan['filename'])) {
            $filepath = 'uploads/' . $scan['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }
}

// Clear session
$_SESSION['scans'] = [];
$_SESSION['scan_count'] = 0;

header('Location: index.php');
exit;
