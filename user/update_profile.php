<?php
include '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$school = trim($_POST['school'] ?? '');
$region = trim($_POST['region'] ?? '');
$year = trim($_POST['year'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists for another user
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
    exit();
}

// Build update query
if (!empty($password)) {
    // Validate password strength
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, school = ?, region = ?, year = ?, password = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $school, $region, $year, $password, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, school = ?, region = ?, year = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $school, $region, $year, $user_id);
}

if ($stmt->execute()) {
    // Update session variables
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['user_email'] = $email;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>