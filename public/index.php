<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MailTicket\Models\Ticket;

$statusFilter = $_GET['status'] ?? 'All';
$priorityFilter = $_GET['priority'] ?? 'All';
$searchQuery = $_GET['search'] ?? null;

$tickets = Ticket::getAll(
    status: $statusFilter,
    search: $searchQuery,
    priority: $priorityFilter
);

$stats = Ticket::getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail-Ticket System | Dashboard</title>
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
            <a href="index.php" class="nav-link active">Dashboard</a>
            <a href="simulate.php" class="nav-link">✉️ Email Simulator</a>
            <a href="settings.php" class="nav-link">⚙️ Mail Settings</a>
            <a href="simulate.php" class="btn-primary"><span>+</span> New Mail Ticket</a>
        </div>
    </div>
</nav>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Support Tickets</h1>
            <p class="page-subtitle">Tickets automatically created from incoming email messages and attachments</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <div class="stat-title">Total Tickets</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-icon" style="color: #8b5cf6;">📬</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Open</div>
                <div class="stat-value" style="color: #60a5fa;"><?= $stats['open'] ?></div>
            </div>
            <div class="stat-icon" style="color: #60a5fa;">🔵</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">In Progress</div>
                <div class="stat-value" style="color: #fbbf24;"><?= $stats['in_progress'] ?></div>
            </div>
            <div class="stat-icon" style="color: #fbbf24;">⏳</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Resolved / Closed</div>
                <div class="stat-value" style="color: #34d399;"><?= $stats['resolved'] + $stats['closed'] ?></div>
            </div>
            <div class="stat-icon" style="color: #34d399;">✅</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="filter-bar">
        <div class="filter-tabs">
            <?php
            $statuses = ['All', 'Open', 'In Progress', 'Resolved', 'Closed'];
            foreach ($statuses as $st):
                $active = ($statusFilter === $st) ? 'active' : '';
                $url = "index.php?status=" . urlencode($st);
                if ($searchQuery) $url .= "&search=" . urlencode($searchQuery);
            ?>
                <a href="<?= $url ?>" class="filter-tab <?= $active ?>"><?= $st ?></a>
            <?php endforeach; ?>
        </div>

        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" 
                   id="search-input" 
                   class="search-input" 
                   placeholder="Search tickets, subject, sender..." 
                   value="<?= htmlspecialchars($searchQuery ?? '') ?>">
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="tickets-container">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div class="empty-icon">📥</div>
                <h3>No tickets found</h3>
                <p>Try clearing filters or send a test email using the <a href="simulate.php" style="color: var(--accent-primary);">Email Simulator</a>.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Sender (Username)</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Attachments</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td>
                                <span class="ticket-num"><?= htmlspecialchars($t['ticket_number']) ?></span>
                            </td>
                            <td>
                                <a href="ticket_view.php?id=<?= $t['id'] ?>" class="ticket-subject">
                                    <?= htmlspecialchars($t['subject']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="ticket-user">
                                    <div class="avatar"><?= strtoupper(substr($t['username'] ?? $t['sender_email'], 0, 1)) ?></div>
                                    <div>
                                        <strong style="display: block; font-size: 0.9rem;"><?= htmlspecialchars($t['username'] ?? 'User') ?></strong>
                                        <span style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($t['sender_email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-priority-<?= strtolower($t['priority']) ?>">
                                    <?= htmlspecialchars($t['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusSlug = strtolower(str_replace(' ', '-', $t['status']));
                                ?>
                                <span class="badge badge-<?= $statusSlug ?>">
                                    <?= htmlspecialchars($t['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($t['attachment_count'] > 0): ?>
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #a7f3d0;">
                                        📎 <?= $t['attachment_count'] ?> File<?= $t['attachment_count'] > 1 ? 's' : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">None</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?= date('M d, Y H:i', strtotime($t['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<footer class="footer">
    <p>PHP Mail-to-Ticket System &bull; Automatically creating tickets from emails & attachments</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>
