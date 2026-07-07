<?php
/**
 * Certificate Generator Module
 * Generates QR codes and certificate data
 */

class CertificateGenerator {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function generateCertificate($result_id, $user_id) {
        $result = $this->conn->query("SELECT * FROM results WHERE id = $result_id AND user_id = $user_id")->fetch_assoc();
        $user = $this->conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
        
        $certificate_id = 'SATS-' . date('Y') . '-' . str_pad($result_id, 6, '0', STR_PAD_LEFT);
        
        $certificate_data = [
            'certificate_id' => $certificate_id,
            'student_name' => $user['first_name'] . ' ' . $user['last_name'],
            'student_id' => $user['student_id'],
            'school' => $user['school'],
            'test_date' => $result['date'],
            'category' => $result['category'],
            'score' => $result['score'] . '/' . $result['total'],
            'percentage' => round($result['percentage'], 1),
            'grade' => $result['grade'],
            'ability_estimate' => $result['ability_estimate'],
            'percentile' => $result['percentile_rank']
        ];
        
        $stmt = $this->conn->prepare("INSERT INTO certificates (result_id, certificate_code, certificate_data, issued_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $result_id, $certificate_id, json_encode($certificate_data));
        $stmt->execute();
        
        return $certificate_data;
    }
    
    public function verifyCertificate($code) {
        $stmt = $this->conn->prepare("SELECT c.*, r.*, u.first_name, u.last_name FROM certificates c 
                                      JOIN results r ON c.result_id = r.id 
                                      JOIN users u ON r.user_id = u.id 
                                      WHERE c.certificate_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>