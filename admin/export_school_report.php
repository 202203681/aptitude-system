<?php
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="school_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['School', 'Students', 'Tests', 'Average Score (%)', 'A Grades', 'B Grades', 'C Grades', 'D Grades', 'F Grades']);

$schools = $conn->query("
    SELECT 
        COALESCE(u.school, 'Not Specified') as school,
        COUNT(DISTINCT u.id) as students,
        COUNT(r.id) as tests,
        ROUND(AVG(r.percentage), 1) as avg_score,
        SUM(CASE WHEN r.grade = 'A' THEN 1 ELSE 0 END) as a_count,
        SUM(CASE WHEN r.grade = 'B' THEN 1 ELSE 0 END) as b_count,
        SUM(CASE WHEN r.grade = 'C' THEN 1 ELSE 0 END) as c_count,
        SUM(CASE WHEN r.grade = 'D' THEN 1 ELSE 0 END) as d_count,
        SUM(CASE WHEN r.grade = 'F' THEN 1 ELSE 0 END) as f_count
    FROM users u
    LEFT JOIN results r ON u.id = r.user_id
    GROUP BY school
    ORDER BY avg_score DESC
");

while ($row = $schools->fetch_assoc()) {
    fputcsv($output, [
        $row['school'],
        $row['students'],
        $row['tests'],
        $row['avg_score'],
        $row['a_count'],
        $row['b_count'],
        $row['c_count'],
        $row['d_count'],
        $row['f_count']
    ]);
}

fclose($output);
?>