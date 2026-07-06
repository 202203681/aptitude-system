<?php
include '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data']);
    exit();
}

$user_id = $data['user_id'] ?? 0;
$action = $data['action'] ?? 'unknown';
$details = json_encode($data['details'] ?? []);

// Insert into system_logs
$stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$stmt->bind_param("issss", $user_id, $action, $details, $ip, $user_agent);
$stmt->execute();

// If stress_metric, also store in stress_metrics table
if ($action == 'stress_metric' && isset($data['details']['test_id'])) {
    $test_id = $data['details']['test_id'];
    $hesitation = $data['details']['hesitation_count'] ?? 0;
    $changes = $data['details']['answer_changes'] ?? 0;
    $avg_time = $data['details']['avg_response_time'] ?? 0;
    
    $stmt2 = $conn->prepare("INSERT INTO stress_metrics (test_id, user_id, hesitation_count, avg_response_time, answer_changes) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("iiidd", $test_id, $user_id, $hesitation, $avg_time, $changes);
    $stmt2->execute();
}

echo json_encode(['success' => true]);
?>