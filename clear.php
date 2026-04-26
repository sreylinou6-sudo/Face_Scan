<?php
session_start();
$_SESSION['scans'] = [];
$_SESSION['scan_count'] = 0;
header('Location: index.php');
exit;
