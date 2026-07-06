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

// Get student details
$student = $conn->query("
    SELECT * FROM users WHERE id = $student_id AND role = 'user'
")->fetch_assoc();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_tests,
        ROUND(AVG(percentage), 1) as avg_score,
        MAX(percentage) as best_score,
        COUNT(CASE WHEN grade = 'A' THEN 1 END) as a_count,
        (SELECT COUNT(*) FROM certificates c WHERE c.result_id IN (SELECT id FROM results WHERE user_id = $student_id)) as certificate_count
    FROM results 
    WHERE user_id = $student_id
")->fetch_assoc();

// Get recent results
$recent_results = $conn->query("
    SELECT * FROM results 
    WHERE user_id = $student_id 
    ORDER BY date DESC LIMIT 5
");

$results_list = [];
while ($row = $recent_results->fetch_assoc()) {
    $results_list[] = $row;
}

echo json_encode([
    'success' => true,
    'id' => $student['id'],
    'first_name' => $student['first_name'],
    'last_name' => $student['last_name'],
    'student_id' => $student['student_id'],
    'email' => $student['email'],
    'school' => $student['school'],
    'region' => $student['region'],
    'year' => $student['year'],
    'profile_picture' => $student['profile_picture'],
    'created_at' => $student['created_at'],
    'total_tests' => $stats['total_tests'] ?? 0,
    'avg_score' => $stats['avg_score'] ?? 0,
    'best_score' => $stats['best_score'] ?? 0,
    'a_count' => $stats['a_count'] ?? 0,
    'certificate_count' => $stats['certificate_count'] ?? 0,
    'recent_results' => $results_list
]);
?>