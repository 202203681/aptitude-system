<?php
include 'config/db.php';

echo "<h2>Database Check</h2>";

// Check users
$users = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $users->fetch_assoc()['count'];
echo "<p>Users: $user_count</p>";

// Check questions
$questions = $conn->query("SELECT COUNT(*) as count FROM questions");
$q_count = $questions->fetch_assoc()['count'];
echo "<p>Questions in database: $q_count</p>";

if ($q_count > 0) {
    echo "<h3>Sample Questions:</h3>";
    $sample = $conn->query("SELECT id, category, topic, question FROM questions LIMIT 5");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Category</th><th>Topic</th><th>Question</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['category']}</td>";
        echo "<td>{$row['topic']}</td>";
        echo "<td>" . htmlspecialchars(substr($row['question'], 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>No questions found! Please run the INSERT statements in install.sql</p>";
}

// Check categories
$categories = $conn->query("SELECT DISTINCT category FROM questions");
echo "<h3>Categories Available:</h3>";
while ($cat = $categories->fetch_assoc()) {
    $count = $conn->query("SELECT COUNT(*) as c FROM questions WHERE category = '{$cat['category']}'")->fetch_assoc()['c'];
    echo "<p>- {$cat['category']}: $count questions</p>";
}
?>