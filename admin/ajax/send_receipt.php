<?php
// Correct the path to config.php
require_once '../../config.php';

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$donation_id = isset($input['donation_id']) ? intval($input['donation_id']) : 0;

if (!$donation_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid donation ID']);
    exit();
}

try {
    // Fetch donation details
    $stmt = $pdo->prepare("
        SELECT d.*, c.title as campaign_title, u.email as user_email, u.username 
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id 
        LEFT JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$donation_id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$donation) {
        echo json_encode(['success' => false, 'message' => 'Donation not found']);
        exit();
    }
    
    // Determine recipient email
    $recipient_email = $donation['donor_email'] ?? $donation['user_email'] ?? '';
    
    if (empty($recipient_email)) {
        echo json_encode(['success' => false, 'message' => 'No email address found for this donor']);
        exit();
    }
    
    // Here you would implement actual email sending
    // For now, just log and return success
    
    // Log the action
    if (isset($pdo)) {
        $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
        $log_stmt->execute([
            $_SESSION['admin_id'],
            'send_receipt',
            "Receipt sent for donation #$donation_id to $recipient_email"
        ]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Receipt would be sent to: ' . $recipient_email
    ]);
    
} catch (PDOException $e) {
    error_log("Error in send_receipt.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>