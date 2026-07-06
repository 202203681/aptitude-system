<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Generate QR codes for all certificates that don't have them
$certificates = $conn->query("
    SELECT c.*, u.first_name, u.last_name, u.student_id, u.school, r.percentage, r.grade
    FROM certificates c
    JOIN results r ON c.result_id = r.id
    JOIN users u ON r.user_id = u.id
    WHERE c.qr_data IS NULL OR c.qr_data = ''
    ORDER BY c.issued_date DESC
    LIMIT 100
");

$updated = 0;
while ($cert = $certificates->fetch_assoc()) {
    $verification_url = "https://" . $_SERVER['HTTP_HOST'] . "/sats/verify_certificate.php?cert_id=" . urlencode($cert['certificate_code']);
    $qr_data = json_encode([
        'certificate_id' => $cert['certificate_code'],
        'student_name' => $cert['first_name'] . ' ' . $cert['last_name'],
        'student_id' => $cert['student_id'],
        'school' => $cert['school'],
        'score' => round($cert['percentage'], 1),
        'grade' => $cert['grade']
    ]);
    $qr_url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($verification_url) . "&choe=UTF-8";
    
    $stmt = $conn->prepare("UPDATE certificates SET qr_data = ? WHERE id = ?");
    $stmt->bind_param("si", $qr_url, $cert['id']);
    $stmt->execute();
    $updated++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk QR Generator - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-qrcode"></i> Bulk QR Code Generator</h5>
            </div>
            <div class="card-body text-center">
                <?php if($updated > 0): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Successfully generated QR codes for <?= $updated ?> certificate(s)!
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> All certificates already have QR codes.
                    </div>
                <?php endif; ?>
                
                <a href="dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
                <a href="../user/certificate.php" class="btn btn-success mt-3">View Sample Certificate</a>
            </div>
        </div>
        
        <!-- QR Code Preview for Recent Certificates -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Recent Certificates with QR Codes</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $recent = $conn->query("
                        SELECT c.*, u.first_name, u.last_name
                        FROM certificates c
                        JOIN results r ON c.result_id = r.id
                        JOIN users u ON r.user_id = u.id
                        WHERE c.qr_data IS NOT NULL
                        ORDER BY c.issued_date DESC
                        LIMIT 6
                    ");
                    while($row = $recent->fetch_assoc()):
                    ?>
                    <div class="col-md-4 mb-3 text-center">
                        <img src="<?= $row['qr_data'] ?>" alt="QR Code" style="width: 100px; height: 100px; border: 1px solid #ddd; padding: 5px;">
                        <p class="mt-2 small"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?><br>
                        <strong><?= $row['certificate_code'] ?></strong></p>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>