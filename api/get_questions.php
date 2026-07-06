<?php
include '../config/db.php';
header('Content-Type: application/json');

// Handle GET request - Fetch topics
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';
    
    if ($category && $category != 'all') {
        // Get distinct topics for specific category
        $sql = "SELECT DISTINCT topic, category FROM questions WHERE category = ? AND active = 1 ORDER BY topic";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Get all distinct topics
        $sql = "SELECT DISTINCT topic, category FROM questions WHERE active = 1 ORDER BY category, topic";
        $result = $conn->query($sql);
    }
    
    $topics = [];
    while ($row = $result->fetch_assoc()) {
        $topics[] = [
            'topic' => $row['topic'],
            'category' => $row['category']
        ];
    }
    
    // If no topics found, return sample/default topics
    if (empty($topics)) {
        $topics = [
            ['topic' => 'Percentages', 'category' => 'Quantitative Aptitude'],
            ['topic' => 'Algebra', 'category' => 'Quantitative Aptitude'],
            ['topic' => 'Number Series', 'category' => 'Logical Reasoning'],
            ['topic' => 'Blood Relations', 'category' => 'Logical Reasoning'],
            ['topic' => 'Synonyms', 'category' => 'Verbal Ability'],
            ['topic' => 'Spellings', 'category' => 'Verbal Ability']
        ];
    }
    
    echo json_encode(['topics' => $topics, 'count' => count($topics)]);
    exit();
}

// Handle POST request - Get next adaptive question
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] == 'next') {
        $category = isset($data['category']) ? $data['category'] : '';
        $answered = isset($data['answered']) ? $data['answered'] : [];
        
        // Build query to get next question
        $sql = "SELECT id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, explanation 
                FROM questions 
                WHERE active = 1";
        
        if ($category && $category != 'all') {
            $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
        }
        
        if (!empty($answered)) {
            $answered_ids = array_keys($answered);
            $ids = implode(',', array_map('intval', $answered_ids));
            $sql .= " AND id NOT IN ($ids)";
        }
        
        $sql .= " ORDER BY RAND() LIMIT 1";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $question = $result->fetch_assoc();
            echo json_encode([
                'question' => $question,
                'complete' => false
            ]);
        } else {
            echo json_encode(['complete' => true]);
        }
    } else {
        // Get random questions for quick test
        $limit = isset($data['limit']) ? intval($data['limit']) : 10;
        $category = isset($data['category']) ? $data['category'] : '';
        
        $sql = "SELECT id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, explanation 
                FROM questions 
                WHERE active = 1";
        
        if ($category && $category != 'all') {
            $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
        }
        
        $sql .= " ORDER BY RAND() LIMIT $limit";
        
        $result = $conn->query($sql);
        $questions = [];
        
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
        
        echo json_encode(['questions' => $questions]);
    }
    exit();
}
?>