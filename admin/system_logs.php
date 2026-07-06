<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$logs = $conn->query("SELECT l.*, u.first_name, u.last_name FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history"></i> System Audit Logs</h2>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr>
                        </thead>
                        <tbody>
                            <?php while($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d M H:i:s', strtotime($log['timestamp'])) ?></td>
                                <td><?= $log['first_name'] ? $log['first_name'] . ' ' . $log['last_name'] : 'System' ?></td>
                                <td><?= $log['action'] ?></td>
                                <td><small><?= htmlspecialchars($log['details'] ?: '-') ?></small></td>
                                <td><?= $log['ip_address'] ?></td>
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