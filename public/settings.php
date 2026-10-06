<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MailTicket\Services\EmailFetcher;

$mailConfig = require __DIR__ . '/../config/mail.php';
$cronOutput = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_fetch'])) {
    $fetcher = new EmailFetcher();
    $cronOutput = $fetcher->fetchAndProcess();
}

// Build current host URL for webhook
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$webhookUrl = "{$protocol}://{$host}/api/incoming_mail.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Settings & Configuration | MailTicket</title>
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
            <a href="settings.php" class="nav-link active">⚙️ Mail Settings</a>
        </div>
    </div>
</nav>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Mail Integration Settings</h1>
            <p class="page-subtitle">Manage IMAP mailbox credentials, webhooks, and automatic email polling cron jobs.</p>
        </div>
    </div>

    <?php if ($cronOutput): ?>
        <div class="card" style="margin-bottom: 1.5rem; border-color: var(--accent-primary);">
            <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.5rem;">Fetch Execution Result</h3>
            <pre style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: var(--radius-sm); font-size: 0.85rem; color: #34d399; overflow-x: auto;"><?= htmlspecialchars(print_r($cronOutput, true)) ?></pre>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        
        <!-- Left: IMAP Config Overview -->
        <div class="card">
            <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 1rem;">📫 Incoming Mail Server (IMAP/POP3)</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.25rem;">
                Configured in <code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">config/mail.php</code>
            </p>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Fetching Status:</span>
                    <strong style="color: <?= $mailConfig['incoming']['enabled'] ? '#34d399' : '#f87171' ?>;">
                        <?= $mailConfig['incoming']['enabled'] ? 'Enabled' : 'Disabled' ?>
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Protocol:</span>
                    <strong style="color: #fff;"><?= strtoupper($mailConfig['incoming']['protocol']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">IMAP Host:</span>
                    <strong style="color: #fff;"><?= htmlspecialchars($mailConfig['incoming']['host']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Port / Encryption:</span>
                    <strong style="color: #fff;"><?= $mailConfig['incoming']['port'] ?> (<?= strtoupper($mailConfig['incoming']['encryption']) ?>)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Mailbox Username:</span>
                    <strong style="color: #fff;"><?= htmlspecialchars($mailConfig['incoming']['username']) ?></strong>
                </div>
            </div>

            <form action="settings.php" method="POST" style="margin-top: 1.5rem;">
                <button type="submit" name="test_fetch" value="1" class="btn-primary">
                    ⚡ Trigger Manual Email Poller
                </button>
            </form>
        </div>

        <!-- Right: Webhook API & Cron Guide -->
        <div class="card">
            <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 1rem;">🔗 Inbound Webhook API Endpoint</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                Use this URL to receive incoming emails automatically from SendGrid, Mailgun, Postmark, or custom email forwarders via HTTP POST:
            </p>
            <div style="background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.85rem; color: var(--accent-primary); word-break: break-all; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($webhookUrl) ?>
            </div>

            <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 1rem;">⏱️ Automatic Cron Command</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.75rem;">
                To run the email poller automatically every 5 minutes on Linux / Windows task scheduler:
            </p>
            <div style="background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.85rem; color: #fbbf24;">
                */5 * * * * php <?= htmlspecialchars(realpath(__DIR__ . '/../cron/fetch_emails.php')) ?>
            </div>
        </div>

    </div>

</main>

<footer class="footer">
    <p>PHP Mail-to-Ticket System &bull; Configuration & Integrations</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
