<?php
include '../config/db.php';
header('Content-Type: application/json');

$status = [
    'system' => 'SATS v2.0',
    'database' => $conn->ping() ? 'connected' : 'disconnected',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => SYSTEM_VERSION
];

echo json_encode($status);
?>