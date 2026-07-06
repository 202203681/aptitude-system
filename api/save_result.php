<?php
include '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($data['action'] == 'response') {
    $stmt = $conn->prepare("INSERT INTO responses (test_id, question_id, user_answer, response_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $data['test_id'], $data['question_id'], $data['answer'], $data['time_taken']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
}
?>