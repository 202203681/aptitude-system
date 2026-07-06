<?php
include '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['responses'])) {
    foreach ($data['responses'] as $response) {
        $stmt = $conn->prepare("INSERT INTO offline_queue (user_id, action_type, data) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $response['user_id'], $response['action'], json_encode($response));
        $stmt->execute();
    }
    echo json_encode(['success' => true, 'synced' => count($data['responses'])]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data to sync']);
}
?>