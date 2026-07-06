<?php
include '../config/db.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="questions_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['id', 'category', 'topic', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'difficulty', 'explanation']);

$result = $conn->query("SELECT * FROM questions");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
?>