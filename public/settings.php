<?php

require_once __DIR__ . '/../src/Autoloader.php';

use MailTicket\Services\EmailFetcher;
use MailTicket\Helpers\Config;

$mailConfig = Config::getMailConfig();
$dbConfig = Config::getDatabaseConfig();

$successMsg = '';
$errorMsg = '';
$cronOutput = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (isset($_POST['save_mail_settings'])) {
        $mailConfig['incoming'] = [
            'enabled'             => true,
            'protocol'            => $_POST['protocol'] ?? 'imap',
            'host'                => trim($_POST['host'] ?? ''),
            'port'                => (int)($_POST['port'] ?? 993),
            'encryption'          => $_POST['encryption'] ?? 'ssl',
            'username'            => trim($_POST['username'] ?? ''),
            'password'            => $_POST['password'] ?? '',
            'validate_cert'       => false,
            'delete_after_import' => !empty($_POST['delete_after_import']),
        ];

        if (Config::saveMailConfig($mailConfig)) {
            $successMsg = "Mail settings saved successfully!";
        } else {
            $errorMsg = "Failed to save mail settings.";
        }
    }

    if (isset($_POST['save_db_settings'])) {
        $driver = $_POST['driver'] ?? 'sqlite';
        $dbConfig['driver'] = $driver;
        if ($driver === 'mysql') {
            $dbConfig['mysql'] = [
                'host'     => trim($_POST['mysql_host'] ?? 'localhost'),
                'port'     => (int)($_POST['mysql_port'] ?? 3306),
                'database' => trim($_POST['mysql_database'] ?? ''),
                'username' => trim($_POST['mysql_username'] ?? ''),
                'password' => $_POST['mysql_password'] ?? '',
                'charset'  => 'utf8mb4',
            ];
        }

        if (Config::saveDatabaseConfig($dbConfig)) {
            $successMsg = "Database settings saved successfully!";
        } else {
            $errorMsg = "Failed to save database settings.";
        }
    }

    if (isset($_POST['test_fetch'])) {
        $fetcher = new EmailFetcher();
        $cronOutput = $fetcher->fetchAndProcess();
    }
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
    <title>Mail & Database Settings | MailTicket</title>
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
            <h1 class="page-title">System & Mail Integration Settings</h1>
            <p class="page-subtitle">Manage incoming IMAP email credentials, MySQL database connection, and automatic cron jobs.</p>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            ✅ <?= htmlspecialchars($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            ⚠️ <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($cronOutput): ?>
        <div class="card" style="margin-bottom: 1.5rem; border-color: var(--accent-primary);">
            <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.5rem;">Fetch Execution Result</h3>
            <pre style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: var(--radius-sm); font-size: 0.85rem; color: #34d399; overflow-x: auto;"><?= htmlspecialchars(print_r($cronOutput, true)) ?></pre>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Left: Mail Settings Form -->
        <div class="card">
            <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 1.25rem;">📫 Incoming Mail Server (IMAP/POP3)</h3>
            
            <form action="settings.php" method="POST">
                <input type="hidden" name="save_mail_settings" value="1">

                <div class="form-group">
                    <label class="form-label">IMAP Host Server:</label>
                    <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($mailConfig['incoming']['host'] ?? 'imap.hostinger.com') ?>" placeholder="e.g. imap.hostinger.com or imap.gmail.com" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Port:</label>
                        <input type="number" name="port" class="form-control" value="<?= (int)($mailConfig['incoming']['port'] ?? 993) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Encryption:</label>
                        <select name="encryption" class="form-control">
                            <option value="ssl" <?= ($mailConfig['incoming']['encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL (993)</option>
                            <option value="tls" <?= ($mailConfig['incoming']['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (143)</option>
                            <option value="none" <?= ($mailConfig['incoming']['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Company Email Address (Username):</label>
                    <input type="email" name="username" class="form-control" value="<?= htmlspecialchars($mailConfig['incoming']['username'] ?? '') ?>" placeholder="e.g. support@samtechhelp.ae" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mailbox Password / App Password:</label>
                    <input type="password" name="password" class="form-control" value="<?= htmlspecialchars($mailConfig['incoming']['password'] ?? '') ?>" placeholder="Enter email password or app password">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 0.5rem;">
                    💾 Save Mail Settings
                </button>
            </form>

            <form action="settings.php" method="POST" style="margin-top: 1rem;">
                <button type="submit" name="test_fetch" value="1" class="btn-secondary" style="width: 100%;">
                    ⚡ Test & Trigger Email Fetcher Now
                </button>
            </form>
        </div>

        <!-- Right: Database & Integration Settings -->
        <div class="card">
            <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 1.25rem;">🗄️ Database Connection Settings</h3>
            
            <form action="settings.php" method="POST">
                <input type="hidden" name="save_db_settings" value="1">

                <div class="form-group">
                    <label class="form-label">Database Driver:</label>
                    <select name="driver" class="form-control">
                        <option value="sqlite" <?= ($dbConfig['driver'] ?? 'sqlite') === 'sqlite' ? 'selected' : '' ?>>SQLite (Zero-config File)</option>
                        <option value="mysql" <?= ($dbConfig['driver'] ?? '') === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">MySQL Host:</label>
                        <input type="text" name="mysql_host" class="form-control" value="<?= htmlspecialchars($dbConfig['mysql']['host'] ?? 'localhost') ?>" placeholder="localhost">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Port:</label>
                        <input type="number" name="mysql_port" class="form-control" value="<?= (int)($dbConfig['mysql']['port'] ?? 3306) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">MySQL Database Name:</label>
                    <input type="text" name="mysql_database" class="form-control" value="<?= htmlspecialchars($dbConfig['mysql']['database'] ?? 'u601208843_mail_ticket') ?>" placeholder="e.g. u601208843_mail_ticket">
                </div>

                <div class="form-group">
                    <label class="form-label">MySQL Username:</label>
                    <input type="text" name="mysql_username" class="form-control" value="<?= htmlspecialchars($dbConfig['mysql']['username'] ?? 'u601208843_mail') ?>" placeholder="e.g. u601208843_mail">
                </div>

                <div class="form-group">
                    <label class="form-label">MySQL Password:</label>
                    <input type="password" name="mysql_password" class="form-control" value="<?= htmlspecialchars($dbConfig['mysql']['password'] ?? '') ?>" placeholder="Enter MySQL Password">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 0.5rem;">
                    💾 Save Database Settings
                </button>
            </form>
        </div>

    </div>

    <!-- Bottom: Webhook API & Cron Info -->
    <div class="card">
        <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 0.75rem;">🔗 Inbound Webhook API Endpoint</h3>
        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 0.5rem;">
            Use this webhook URL to stream incoming emails from SendGrid, Mailgun, Postmark, or custom cURL forwarders:
        </p>
        <div style="background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.85rem; color: var(--accent-primary); word-break: break-all; margin-bottom: 1.25rem;">
            <?= htmlspecialchars($webhookUrl) ?>
        </div>

        <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 0.75rem;">⏱️ Automatic Cron Command</h3>
        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 0.5rem;">
            Add this cron job in Hostinger cPanel &rarr; Cron Jobs (run every 5 minutes):
        </p>
        <div style="background: rgba(0,0,0,0.5); border: 1px solid var(--border-color); padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.85rem; color: #fbbf24;">
            */5 * * * * php <?= htmlspecialchars(realpath(__DIR__ . '/../cron/fetch_emails.php') ?: '/home/public_html/cron/fetch_emails.php') ?>
        </div>
    </div>

</main>

<footer class="footer">
    <p>PHP Mail-to-Ticket System &bull; System Configuration</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
