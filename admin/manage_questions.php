<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM questions WHERE id = $id");
    header("Location: manage_questions.php");
    exit();
}

$questions = $conn->query("SELECT * FROM questions ORDER BY category, topic");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-database"></i> Manage Questions</h2>
            <div>
                <a href="add_question.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New</a>
                <a href="dashboard.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>ID</th><th>Category</th><th>Topic</th><th>Question</th><th>Difficulty</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php while($q = $questions->fetch_assoc()): ?>
                            <tr>
                                <td><?= $q['id'] ?></td>
                                <td><?= $q['category'] ?></td>
                                <td><?= $q['topic'] ?></td>
                                <td><?= htmlspecialchars(substr($q['question'], 0, 50)) ?>...</td>
                                <td><?= number_format($q['difficulty'], 2) ?></td>
                                <td>
                                    <a href="edit_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?= $q['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>