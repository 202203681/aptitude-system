<?php
include '../config/db.php';
header('Content-Type: application/json');

function awardBadge($conn, $user_id, $badge_name, $badge_type, $verified_by = null) {
    // Check if already awarded
    $check = $conn->prepare("SELECT id FROM badges WHERE user_id = ? AND badge_name = ?");
    $check->bind_param("is", $user_id, $badge_name);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO badges (user_id, badge_name, badge_type, verified_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $badge_name, $badge_type, $verified_by);
    $stmt->execute();
    
    return $stmt->affected_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? 0;
    $percentage = $data['percentage'] ?? 0;
    $test_id = $data['test_id'] ?? 0;
    $streak = $data['streak'] ?? 0;
    
    $badges_awarded = [];
    
    // Score-based badges
    if ($percentage >= 90) {
        if (awardBadge($conn, $user_id, '🎓 Genius Level', 'aptitude')) {
            $badges_awarded[] = '🎓 Genius Level';
        }
    } elseif ($percentage >= 80) {
        if (awardBadge($conn, $user_id, '🏆 Distinction', 'aptitude')) {
            $badges_awarded[] = '🏆 Distinction';
        }
    } elseif ($percentage >= 70) {
        if (awardBadge($conn, $user_id, '⭐ Excellence', 'aptitude')) {
            $badges_awarded[] = '⭐ Excellence';
        }
    } elseif ($percentage >= 60) {
        if (awardBadge($conn, $user_id, '📘 Good Standing', 'aptitude')) {
            $badges_awarded[] = '📘 Good Standing';
        }
    }
    
    // First test badge
    $testCount = $conn->query("SELECT COUNT(*) as c FROM results WHERE user_id = $user_id")->fetch_assoc()['c'];
    if ($testCount == 1) {
        if (awardBadge($conn, $user_id, '🌱 First Step', 'aptitude')) {
            $badges_awarded[] = '🌱 First Step';
        }
    }
    
    // Streak badges
    if ($streak >= 10) {
        if (awardBadge($conn, $user_id, '💪 10-Day Streak', 'streak')) {
            $badges_awarded[] = '💪 10-Day Streak';
        }
    } elseif ($streak >= 5) {
        if (awardBadge($conn, $user_id, '🔥 5-Day Streak', 'streak')) {
            $badges_awarded[] = '🔥 5-Day Streak';
        }
    }
    
    echo json_encode([
        'success' => true,
        'badges_awarded' => $badges_awarded,
        'total_badges' => $conn->query("SELECT COUNT(*) as c FROM badges WHERE user_id = $user_id")->fetch_assoc()['c']
    ]);
} else {
    // GET request - fetch user badges
    $user_id = $_GET['user_id'] ?? 0;
    $badges = $conn->query("SELECT * FROM badges WHERE user_id = $user_id ORDER BY issued_date DESC");
    $badge_list = [];
    while ($badge = $badges->fetch_assoc()) {
        $badge_list[] = $badge;
    }
    echo json_encode(['badges' => $badge_list]);
}
?>