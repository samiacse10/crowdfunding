<?php
require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to view messages";
    redirect('login.php');
}

$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$donation_id = isset($_GET['donation_id']) ? intval($_GET['donation_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$campaign_id) {
    $_SESSION['error'] = "Invalid campaign";
    redirect('index.php');
}

// Fetch campaign details
$campaignStmt = $pdo->prepare("SELECT c.*, u.username as organizer_name 
                               FROM campaigns c 
                               LEFT JOIN users u ON c.user_id = u.id 
                               WHERE c.id = ?");
$campaignStmt->execute([$campaign_id]);
$campaign = $campaignStmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found";
    redirect('index.php');
}

// Check if user is the campaign owner
$isOwner = ($campaign['user_id'] == $_SESSION['user_id']);

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_message'])) {
        // Send message to organizer
        $message = trim($_POST['message']);
        $subject = trim($_POST['subject']);
        
        if (empty($message)) {
            $error = "Please enter a message";
        } else {
            // Insert message into database
            $insertStmt = $pdo->prepare("INSERT INTO messages (campaign_id, sender_id, receiver_id, subject, message, created_at) 
                                         VALUES (?, ?, ?, ?, ?, NOW())");
            $success = $insertStmt->execute([$campaign_id, $_SESSION['user_id'], $campaign['user_id'], $subject, $message]);
            
            if ($success) {
                $_SESSION['success'] = "Your message has been sent successfully!";
                redirect("message.php?campaign_id=" . $campaign_id);
            } else {
                $error = "Failed to send message. Please try again.";
            }
        }
    } elseif (isset($_POST['reply_message'])) {
        // Reply to a message
        $parent_id = intval($_POST['parent_id']);
        $message = trim($_POST['reply']);
        $receiver_id = intval($_POST['receiver_id']);
        
        if (empty($message)) {
            $error = "Please enter a reply";
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO messages (campaign_id, sender_id, receiver_id, parent_id, message, created_at) 
                                         VALUES (?, ?, ?, ?, ?, NOW())");
            $success = $insertStmt->execute([$campaign_id, $_SESSION['user_id'], $receiver_id, $parent_id, $message]);
            
            if ($success) {
                $_SESSION['success'] = "Your reply has been sent!";
                redirect("message.php?campaign_id=" . $campaign_id);
            } else {
                $error = "Failed to send reply. Please try again.";
            }
        }
    } elseif (isset($_POST['mark_read'])) {
        // Mark message as read
        $message_id = intval($_POST['message_id']);
        $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
        $updateStmt->execute([$message_id, $_SESSION['user_id']]);
        redirect("message.php?campaign_id=" . $campaign_id);
    }
}

// Fetch messages for this campaign
if ($isOwner) {
    // Owner sees all messages for their campaign
    $messagesStmt = $pdo->prepare("SELECT m.*, 
                                   u.username as sender_name, u.email as sender_email,
                                   (SELECT username FROM users WHERE id = m.receiver_id) as receiver_name
                                   FROM messages m 
                                   LEFT JOIN users u ON m.sender_id = u.id 
                                   WHERE m.campaign_id = ? 
                                   ORDER BY m.created_at DESC");
    $messagesStmt->execute([$campaign_id]);
} else {
    // Regular user sees only their messages
    $messagesStmt = $pdo->prepare("SELECT m.*, 
                                   u.username as sender_name, u.email as sender_email,
                                   (SELECT username FROM users WHERE id = m.receiver_id) as receiver_name
                                   FROM messages m 
                                   LEFT JOIN users u ON m.sender_id = u.id 
                                   WHERE m.campaign_id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
                                   ORDER BY m.created_at DESC");
    $messagesStmt->execute([$campaign_id, $_SESSION['user_id'], $_SESSION['user_id']]);
}
$messages = $messagesStmt->fetchAll();

// Mark messages as read when viewed
$updateReadStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE campaign_id = ? AND receiver_id = ? AND is_read = 0");
$updateReadStmt->execute([$campaign_id, $_SESSION['user_id']]);

// Get unread count
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE campaign_id = ? AND receiver_id = ? AND is_read = 0");
$unreadStmt->execute([$campaign_id, $_SESSION['user_id']]);
$unread_count = $unreadStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo htmlspecialchars($campaign['title']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        body.dark-mode {
            background-color: #1a1a1a;
            color: #fff;
        }
        
        /* Message Container */
        .message-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Message Cards */
        .message-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        body.dark-mode .message-card {
            background: #2d2d2d;
            color: #fff;
        }
        
        .message-card.unread {
            border-left-color: #0072ff;
            background: linear-gradient(to right, rgba(0,114,255,0.05), white);
        }
        
        body.dark-mode .message-card.unread {
            background: linear-gradient(to right, rgba(0,114,255,0.15), #2d2d2d);
        }
        
        .message-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Message Header */
        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .message-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #141e30, #243b55);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        /* Message Content */
        .message-subject {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .message-body {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        body.dark-mode .message-body {
            color: #ccc;
        }
        
        /* Message Meta */
        .message-meta {
            font-size: 0.85rem;
            color: #999;
            display: flex;
            gap: 1rem;
        }
        
        /* Reply Section */
        .reply-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        body.dark-mode .reply-section {
            border-top-color: #444;
        }
        
        .reply-form {
            display: none;
            margin-top: 1rem;
        }
        
        .reply-form.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Compose Message */
        .compose-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        body.dark-mode .compose-card {
            background: #2d2d2d;
        }
        
        /* Buttons */
        .btn-message {
            background: linear-gradient(135deg, #141e30, #243b55);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,114,255,0.3);
            color: white;
        }
        
        .btn-outline-message {
            border: 2px solid #141e30;
            color: #141e30;
            background: transparent;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        body.dark-mode .btn-outline-message {
            border-color: #fff;
            color: #fff;
        }
        
        .btn-outline-message:hover {
            background: #141e30;
            color: white;
        }
        
        /* Badge */
        .unread-badge {
            background: #0072ff;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
        }
        
        body.dark-mode .empty-state {
            background: #2d2d2d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .message-header {
                flex-direction: column;
                text-align: center;
            }
            
            .message-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .message-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="message-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>
                    <i class="fa-regular fa-envelope me-2 text-primary"></i>
                    Messages
                </h2>
                <p class="text-muted">
                    <a href="campaign.php?id=<?php echo $campaign_id; ?>" class="text-decoration-none">
                        <i class="fa-regular fa-arrow-left me-1"></i>
                        Back to <?php echo htmlspecialchars($campaign['title']); ?>
                    </a>
                </p>
            </div>
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge">
                    <i class="fa-regular fa-circle me-1"></i>
                    <?php echo $unread_count; ?> unread
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-regular fa-circle-check me-2"></i>
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-regular fa-circle-exclamation me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Compose Message (for donors) -->
        <?php if (!$isOwner): ?>
        <div class="compose-card">
            <h5 class="mb-3">
                <i class="fa-regular fa-pen-to-square me-2 text-primary"></i>
                Send Message to Organizer
            </h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" 
                           class="form-control" 
                           id="subject" 
                           name="subject" 
                           placeholder="Enter subject"
                           required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" 
                              id="message" 
                              name="message" 
                              rows="4" 
                              placeholder="Type your message here..."
                              required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn btn-message">
                    <i class="fa-regular fa-paper-plane me-2"></i>
                    Send Message
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Messages List -->
        <div class="messages-list">
            <h5 class="mb-3">
                <i class="fa-regular fa-message me-2 text-primary"></i>
                Conversation History
                <?php if (count($messages) > 0): ?>
                    <small class="text-muted">(<?php echo count($messages); ?> messages)</small>
                <?php endif; ?>
            </h5>
            
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message-card <?php echo (!$message['is_read'] && $message['receiver_id'] == $_SESSION['user_id']) ? 'unread' : ''; ?>">
                        <!-- Message Header -->
                        <div class="message-header">
                            <div class="message-avatar">
                                <?php echo strtoupper(substr($message['sender_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars($message['sender_name'] ?? 'Unknown User'); ?>
                                    <?php if ($message['sender_id'] == $campaign['user_id']): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary ms-2">
                                            <i class="fa-regular fa-circle-check me-1"></i>Organizer
                                        </span>
                                    <?php endif; ?>
                                </h6>
                                <div class="message-meta">
                                    <span>
                                        <i class="fa-regular fa-clock me-1"></i>
                                        <?php echo date('M d, Y \a\t h:i A', strtotime($message['created_at'])); ?>
                                    </span>
                                    <?php if (!empty($message['subject'])): ?>
                                        <span>
                                            <i class="fa-regular fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!$message['is_read'] && $message['receiver_id'] == $_SESSION['user_id']): ?>
                                <form method="POST" action="" class="ms-auto">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-regular fa-envelope-open"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Message Body -->
                        <?php if (!empty($message['subject'])): ?>
                            <div class="message-subject">
                                <?php echo htmlspecialchars($message['subject']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-body">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        
                        <!-- Reply Button -->
                        <button class="btn btn-sm btn-outline-message reply-toggle" data-message-id="<?php echo $message['id']; ?>">
                            <i class="fa-regular fa-reply me-1"></i>
                            Reply
                        </button>
                        
                        <!-- Reply Form -->
                        <div class="reply-form" id="reply-form-<?php echo $message['id']; ?>">
                            <form method="POST" action="">
                                <input type="hidden" name="parent_id" value="<?php echo $message['id']; ?>">
                                <input type="hidden" name="receiver_id" value="<?php echo $message['sender_id']; ?>">
                                <div class="mb-2">
                                    <textarea class="form-control" 
                                              name="reply" 
                                              rows="2" 
                                              placeholder="Type your reply..."
                                              required></textarea>
                                </div>
                                <button type="submit" name="reply_message" class="btn btn-sm btn-message">
                                    <i class="fa-regular fa-paper-plane me-1"></i>
                                    Send Reply
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary cancel-reply" data-message-id="<?php echo $message['id']; ?>">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fa-regular fa-message"></i>
                    <h5>No messages yet</h5>
                    <p class="text-muted">
                        <?php if ($isOwner): ?>
                            When donors contact you, their messages will appear here.
                        <?php else: ?>
                            Use the form above to send a message to the campaign organizer.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle reply form
document.querySelectorAll('.reply-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const messageId = this.dataset.messageId;
        const replyForm = document.getElementById('reply-form-' + messageId);
        
        // Hide all other reply forms
        document.querySelectorAll('.reply-form').forEach(form => {
            if (form.id !== 'reply-form-' + messageId) {
                form.classList.remove('show');
            }
        });
        
        // Toggle current form
        replyForm.classList.toggle('show');
    });
});

// Cancel reply
document.querySelectorAll('.cancel-reply').forEach(button => {
    button.addEventListener('click', function() {
        const messageId = this.dataset.messageId;
        const replyForm = document.getElementById('reply-form-' + messageId);
        replyForm.classList.remove('show');
    });
});

// Dark mode detection
if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.body.classList.add('dark-mode');
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
    if (event.matches) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
});

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
require_once 'footer.php';
?>