<?php
include '../config/db.php';
header('Content-Type: application/json');

$questions = $conn->query("SELECT COUNT(*) as c FROM questions WHERE active = 1")->fetch_assoc()['c'];
$students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'")->fetch_assoc()['c'];
$tests = $conn->query("SELECT COUNT(*) as c FROM results")->fetch_assoc()['c'];
$certificates = $conn->query("SELECT COUNT(*) as c FROM certificates")->fetch_assoc()['c'];

echo json_encode([
    'questions' => $questions,
    'students' => $students,
    'tests' => $tests,
    'certificates' => $certificates
]);
?>