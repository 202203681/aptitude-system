<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_name = $data['student_name'] ?? '';
    $student_id = $data['student_id'] ?? '';
    $certificate_id = $data['certificate_id'] ?? '';
    $score = $data['score'] ?? '';
    $grade = $data['grade'] ?? '';
    
    // Build verification URL
    $verification_url = "https://" . $_SERVER['HTTP_HOST'] . "/sats/verify_certificate.php?cert_id=" . urlencode($certificate_id);
    
    // Create QR data
    $qr_data = json_encode([
        'certificate_id' => $certificate_id,
        'student_name' => $student_name,
        'student_id' => $student_id,
        'issue_date' => date('Y-m-d'),
        'score' => $score,
        'grade' => $grade,
        'verify_at' => $verification_url
    ]);
    
    // Generate QR code URL
    $qr_url = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($verification_url) . "&choe=UTF-8";
    
    echo json_encode([
        'success' => true,
        'qr_url' => $qr_url,
        'verification_url' => $verification_url,
        'qr_data' => $qr_data
    ]);
} else {
    // GET request - simple QR generator form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR Code Generator API</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="card" style="max-width: 500px; margin: auto;">
                <div class="card-header bg-primary text-white">
                    <h5>Quick QR Code Generator</h5>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="event.preventDefault(); generateQR()">
                        <input type="text" id="student_name" class="form-control mb-2" placeholder="Student Name" required>
                        <input type="text" id="student_id" class="form-control mb-2" placeholder="Student ID" required>
                        <input type="text" id="certificate_id" class="form-control mb-2" placeholder="Certificate ID" required>
                        <input type="text" id="score" class="form-control mb-2" placeholder="Score (%)" required>
                        <input type="text" id="grade" class="form-control mb-2" placeholder="Grade" required>
                        <button type="submit" class="btn btn-primary w-100">Generate QR Code</button>
                    </form>
                    <div id="result" class="mt-3 text-center"></div>
                </div>
            </div>
        </div>
        
        <script>
        async function generateQR() {
            const data = {
                student_name: document.getElementById('student_name').value,
                student_id: document.getElementById('student_id').value,
                certificate_id: document.getElementById('certificate_id').value,
                score: document.getElementById('score').value,
                grade: document.getElementById('grade').value
            };
            
            const response = await fetch('generate_qr.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('result').innerHTML = `
                    <img src="${result.qr_url}" class="img-fluid border p-2" style="max-width: 200px;">
                    <p class="mt-2 small">Verification URL:<br>
                    <a href="${result.verification_url}" target="_blank">${result.verification_url}</a></p>
                `;
            }
        }
        </script>
    </body>
    </html>
    <?php
}
?>