<?php

require_once __DIR__ . '/../src/Autoloader.php';

use MailTicket\Models\Ticket;

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$ticketId) {
    header('Location: index.php');
    exit;
}

// Handle AJAX Status / Priority updates or Reply Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? 'Open';
        Ticket::updateStatus($ticketId, $newStatus);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update_priority') {
        $newPriority = $_POST['priority'] ?? 'Medium';
        Ticket::updatePriority($ticketId, $newPriority);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add_reply') {
        $replyUser = $_POST['reply_user'] ?? 'support@admin.com';
        $replyMessage = $_POST['reply_message'] ?? '';

        if (!empty(trim($replyMessage))) {
            Ticket::addReply($ticketId, $replyUser, $replyMessage);
        }
        header("Location: ticket_view.php?id=" . $ticketId);
        exit;
    }
}

$ticket = Ticket::getById($ticketId);
if (!$ticket) {
    die("Ticket #{$ticketId} not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket <?= htmlspecialchars($ticket['ticket_number']) ?> | MailTicket</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <div class="logo-icon">🎫</div>
            <span>MailTicket</span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="simulate.php" class="nav-link">✉️ Email Simulator</a>
            <a href="settings.php" class="nav-link">⚙️ Mail Settings</a>
        </div>
    </div>
</nav>

<main class="main-content">

    <div style="margin-bottom: 1.5rem;">
        <a href="index.php" class="btn-secondary">&larr; Back to Dashboard</a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        
        <!-- Left Main Details Column -->
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <span class="ticket-num" style="font-size: 1rem;"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                        <h1 class="page-title" style="margin-top: 0.25rem; font-size: 1.6rem;"><?= htmlspecialchars($ticket['subject']) ?></h1>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 1rem 0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                    <div class="avatar" style="width: 44px; height: 44px; font-size: 1.1rem;"><?= strtoupper(substr($ticket['username'] ?? $ticket['sender_email'], 0, 1)) ?></div>
                    <div>
                        <div style="font-weight: 700; color: #fff; font-size: 1rem;"><?= htmlspecialchars($ticket['username'] ?? 'User') ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($ticket['sender_email']) ?></div>
                    </div>
                    <div style="margin-left: auto; text-align: right; color: var(--text-muted); font-size: 0.85rem;">
                        Received <?= date('F j, Y g:i A', strtotime($ticket['created_at'])) ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; color: #fff;">Mail Description / Body:</label>
                    <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; min-height: 120px; line-height: 1.7; color: #e5e7eb;">
                        <?= !empty($ticket['description']) ? $ticket['description'] : '<em>(No description provided in email)</em>' ?>
                    </div>
                </div>

                <!-- Attachments Section -->
                <?php if (!empty($ticket['attachments'])): ?>
                    <div style="margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                        <h3 style="font-size: 1rem; color: #fff; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>📎 Email Attachments</span>
                            <span class="badge" style="background: rgba(255,255,255,0.1); color: #fff;"><?= count($ticket['attachments']) ?></span>
                        </h3>
                        
                        <div class="attachments-grid">
                            <?php foreach ($ticket['attachments'] as $att): ?>
                                <a href="download.php?id=<?= $att['id'] ?>" class="attachment-card" title="Click to download file">
                                    <span class="att-icon">📄</span>
                                    <div class="att-info">
                                        <div class="att-name"><?= htmlspecialchars($att['original_name']) ?></div>
                                        <div class="att-size"><?= round($att['file_size'] / 1024, 1) ?> KB &bull; Download</div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ticket Reply Thread -->
            <div class="card">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; color: #fff;">Replies & Conversation</h3>

                <?php if (empty($ticket['replies'])): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">No replies added yet. Be the first to respond.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                        <?php foreach ($ticket['replies'] as $reply): ?>
                            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem 1.25rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <strong style="color: var(--accent-primary); font-size: 0.9rem;"><?= htmlspecialchars($reply['user_email']) ?></strong>
                                    <span style="font-size: 0.78rem; color: var(--text-muted);"><?= date('M d, Y H:i', strtotime($reply['created_at'])) ?></span>
                                </div>
                                <div style="font-size: 0.92rem; color: #e5e7eb; white-space: pre-wrap;"><?= htmlspecialchars($reply['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Post Reply Form -->
                <form action="ticket_view.php?id=<?= $ticket['id'] ?>" method="POST" style="border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                    <input type="hidden" name="action" value="add_reply">
                    
                    <div class="form-group">
                        <label class="form-label">Reply From (Email / Agent Name):</label>
                        <input type="email" name="reply_user" class="form-control" value="support@mailticket.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message:</label>
                        <textarea name="reply_message" class="form-control" rows="3" placeholder="Write your response to the user..." required></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Post Reply</button>
                </form>
            </div>
        </div>

        <!-- Right Control Panel Sidebar -->
        <div>
            <div class="card" style="position: sticky; top: 90px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; color: #fff;">Ticket Metadata</h3>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="ticket-status-select" data-ticket-id="<?= $ticket['id'] ?>" class="form-control">
                        <option value="Open" <?= $ticket['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
                        <option value="In Progress" <?= $ticket['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= $ticket['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Closed" <?= $ticket['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select id="ticket-priority-select" data-ticket-id="<?= $ticket['id'] ?>" class="form-control">
                        <option value="Low" <?= $ticket['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= $ticket['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= $ticket['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Urgent" <?= $ticket['priority'] === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    </select>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">
                    <div style="margin-bottom: 0.75rem;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Username</span>
                        <strong style="color: #fff; font-size: 0.95rem;"><?= htmlspecialchars($ticket['username'] ?? 'N/A') ?></strong>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Sender Email</span>
                        <strong style="color: #fff; font-size: 0.95rem; word-break: break-all;"><?= htmlspecialchars($ticket['sender_email']) ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Last Updated</span>
                        <span style="color: var(--text-secondary); font-size: 0.88rem;"><?= date('M d, Y H:i', strtotime($ticket['updated_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</main>

<footer class="footer">
    <p>PHP Mail-to-Ticket System &bull; Ticket Details & Conversation</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
