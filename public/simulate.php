<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MailTicket\Services\TicketService;

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fromEmail   = $_POST['from_email'] ?? '';
        $subject     = $_POST['subject'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority    = $_POST['priority'] ?? 'Medium';

        $attachments = [];
        if (!empty($_FILES['attachments'])) {
            $files = $_FILES['attachments'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $attachments[] = [
                            'name' => $files['name'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'mime_type' => $files['type'][$i]
                        ];
                    }
                }
            }
        }

        $ticket = TicketService::createTicketFromEmail(
            subject: $subject,
            description: $description,
            senderEmail: $fromEmail,
            attachments: $attachments,
            priority: $priority
        );

        header("Location: ticket_view.php?id=" . $ticket['id']);
        exit;

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Ingestion Simulator | MailTicket</title>
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
            <a href="simulate.php" class="nav-link active">✉️ Email Simulator</a>
            <a href="settings.php" class="nav-link">⚙️ Mail Settings</a>
        </div>
    </div>
</nav>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Incoming Email Simulator</h1>
            <p class="page-subtitle">Simulate receiving an email with subject, description, sender email, and attachments to test ticket creation.</p>
        </div>
    </div>

    <?php if ($errorMessage): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div style="max-width: 800px; margin: 0 auto;">
        <div class="card">
            <form action="simulate.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label class="form-label">Mail From (Sender Email / Username): *</label>
                    <input type="email" 
                           name="from_email" 
                           class="form-control" 
                           placeholder="e.g. maulik.dawda@example.com" 
                           value="john.doe@acme-corp.com" 
                           required>
                    <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.3rem; display: block;">
                        The email address will become the ticket submitter email and the username will be auto-extracted (`john.doe`).
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Mail Subject (Ticket Subject): *</label>
                    <input type="text" 
                           name="subject" 
                           class="form-control" 
                           placeholder="e.g. Server Latency & Database Timeout Issue" 
                           value="Urgent: Payment Gateway API Error on Checkout" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mail Priority:</label>
                    <select name="priority" class="form-control">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mail Description / Body (Ticket Description):</label>
                    <textarea name="description" 
                              class="form-control" 
                              rows="5" 
                              placeholder="Describe the issue reported in the email body...">Hello Support Team,

We are experiencing intermittent HTTP 500 errors when customers attempt to complete payments via credit card on our portal.

Attached are the server log files and error screenshots for your review. Please resolve this at your earliest convenience.

Best regards,
John Doe</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Attachments (Select one or multiple files):</label>
                    <input type="file" 
                           name="attachments[]" 
                           id="simulator-file-input" 
                           class="form-control" 
                           multiple>
                    
                    <div id="file-list-preview" class="attachments-grid" style="margin-top: 0.75rem;"></div>
                </div>

                <div style="margin-top: 1.75rem; display: flex; gap: 1rem; align-items: center;">
                    <button type="submit" class="btn-primary" style="padding: 0.75rem 1.75rem; font-size: 1rem;">
                        ✉️ Simulate Mail Arrival & Create Ticket
                    </button>
                    <a href="index.php" class="btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>

</main>

<footer class="footer">
    <p>PHP Mail-to-Ticket System &bull; Interactive Email Simulator</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
