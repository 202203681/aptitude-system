<?php
include '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    
    if ($category) {
        $topics = $conn->query("SELECT DISTINCT topic, category FROM questions WHERE category = '$category' AND active = 1");
    } else {
        $topics = $conn->query("SELECT DISTINCT topic, category FROM questions WHERE active = 1");
    }
    
    $topic_list = [];
    while ($row = $topics->fetch_assoc()) {
        $topic_list[] = $row;
    }
    
    echo json_encode(['topics' => $topic_list]);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data['action'] == 'next') {
        $adaptive = isset($_SESSION['adaptive_state']) ? unserialize($_SESSION['adaptive_state']) : null;
        
        if (!$adaptive) {
            $adaptive = new AdaptiveEngine($conn, $data['category']);
        }
        
        $next = $adaptive->selectNextItem();
        
        if ($next && !$adaptive->shouldStop()) {
            echo json_encode(['question' => $next, 'complete' => false]);
        } else {
            echo json_encode(['complete' => true]);
        }
        
        $_SESSION['adaptive_state'] = serialize($adaptive);
    }
}
?>