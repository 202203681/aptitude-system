<?php
include '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
}

$certificates = $conn->query("
    SELECT 
        c.*,
        r.category,
        r.score,
        r.total,
        r.percentage,
        r.grade,
        r.date as test_date
    FROM certificates c
    JOIN results r ON c.result_id = r.id
    WHERE r.user_id = $student_id
    ORDER BY c.issued_date DESC
");

$cert_list = [];
while ($cert = $certificates->fetch_assoc()) {
    $cert_list[] = $cert;
}

echo json_encode([
    'success' => true,
    'certificates' => $cert_list
]);
?>