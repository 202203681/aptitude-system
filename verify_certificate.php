<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - SATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #003399 0%, #001a66 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .verified {
            color: #28a745;
        }
        .invalid {
            color: #dc3545;
        }
        .cert-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-label { font-weight: 600; color: #555; }
        .detail-value { color: #333; }
        .seal {
            width: 80px;
            height: 80px;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <i class="fas fa-certificate fa-4x mb-3" style="color: #003399;"></i>
        <h2>Certificate Verification</h2>
        
        <form method="GET" class="mt-4">
            <div class="input-group">
                <input type="text" name="cert_id" class="form-control" placeholder="Enter Certificate ID" value="<?= htmlspecialchars($_GET['cert_id'] ?? '') ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Verify</button>
            </div>
        </form>
        
        <?php if(isset($_GET['cert_id']) && !empty($_GET['cert_id'])): 
            $cert_id = $conn->real_escape_string($_GET['cert_id']);
            
            // Try to find certificate
            $cert = $conn->query("
                SELECT c.*, r.*, u.first_name, u.last_name, u.student_id, u.school
                FROM certificates c
                JOIN results r ON c.result_id = r.id
                JOIN users u ON r.user_id = u.id
                WHERE c.certificate_code = '$cert_id'
            ");
            
            if($cert && $cert->num_rows > 0):
                $data = $cert->fetch_assoc();
        ?>
            <div class="verified mt-4">
                <i class="fas fa-check-circle fa-3x"></i>
                <h4 class="mt-2">Valid Certificate</h4>
                <p>This certificate is authentic and verified by the Ministry of Education</p>
            </div>
            
            <div class="cert-details">
                <div class="detail-row">
                    <span class="detail-label">Certificate ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($data['certificate_code']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Student Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Student ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($data['student_id']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">School:</span>
                    <span class="detail-value"><?= htmlspecialchars($data['school'] ?: 'Not Specified') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issue Date:</span>
                    <span class="detail-value"><?= date('d F Y', strtotime($data['issued_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Overall Score:</span>
                    <span class="detail-value"><?= round($data['percentage'], 1) ?>%</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Grade:</span>
                    <span class="detail-value"><strong><?= $data['grade'] ?></strong></span>
                </div>
            </div>
            
            <div class="seal">
                <i class="fas fa-stamp fa-4x" style="color: #CE9F32;"></i>
            </div>
            <p class="text-muted small">This verification is valid at the time of checking.<br>For official purposes, please contact the Ministry of Education.</p>
            
        <?php else: ?>
            <div class="invalid mt-4">
                <i class="fas fa-times-circle fa-3x"></i>
                <h4 class="mt-2">Invalid Certificate</h4>
                <p>No matching certificate found. Please check the certificate ID and try again.</p>
            </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <hr class="my-4">
        <p class="text-muted small">
            <i class="fas fa-lock"></i> Secure verification powered by SATS<br>
            Kingdom of Eswatini - Ministry of Education & Training
        </p>
    </div>
</body>
</html>