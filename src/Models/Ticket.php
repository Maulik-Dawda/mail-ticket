<?php

namespace MailTicket\Models;

use MailTicket\Database;
use PDO;

class Ticket {
    public static function create(
        string $subject,
        ?string $description,
        string $senderEmail,
        string $priority = 'Medium',
        string $status = 'Open'
    ): array {
        $db = Database::getInstance();
        
        // Find or create user to get user_id and normalized username
        $user = User::findOrCreateByEmail($senderEmail);
        $ticketNumber = 'TCK-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 4));

        $stmt = $db->prepare("
            INSERT INTO tickets (ticket_number, subject, description, sender_email, user_id, priority, status)
            VALUES (:ticket_number, :subject, :description, :sender_email, :user_id, :priority, :status)
        ");
        $stmt->execute([
            'ticket_number' => $ticketNumber,
            'subject' => trim($subject),
            'description' => $description ? trim($description) : '',
            'sender_email' => strtolower(trim($senderEmail)),
            'user_id' => $user['id'],
            'priority' => $priority,
            'status' => $status
        ]);

        $id = $db->lastInsertId();
        return self::getById((int)$id);
    }

    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT t.*, u.username, u.name as user_name 
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $ticket = $stmt->fetch();
        
        if ($ticket) {
            $ticket['attachments'] = Attachment::getByTicketId($id);
            $ticket['replies'] = self::getReplies($id);
        }

        return $ticket ?: null;
    }

    public static function getAll(?string $status = null, ?string $search = null, ?string $priority = null): array {
        $db = Database::getInstance();
        $query = "
            SELECT t.*, u.username, u.name as user_name,
                   (SELECT COUNT(*) FROM attachments WHERE ticket_id = t.id) as attachment_count,
                   (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as reply_count
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($status && $status !== 'All') {
            $query .= " AND t.status = :status";
            $params['status'] = $status;
        }

        if ($priority && $priority !== 'All') {
            $query .= " AND t.priority = :priority";
            $params['priority'] = $priority;
        }

        if ($search) {
            $query .= " AND (t.subject LIKE :search OR t.description LIKE :search OR t.sender_email LIKE :search OR t.ticket_number LIKE :search OR u.username LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY t.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE tickets SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function updatePriority(int $id, string $priority): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE tickets SET priority = :priority, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['priority' => $priority, 'id' => $id]);
    }

    public static function addReply(int $ticketId, string $userEmail, string $message): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO ticket_replies (ticket_id, user_email, message)
            VALUES (:ticket_id, :user_email, :message)
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_email' => strtolower(trim($userEmail)),
            'message' => trim($message)
        ]);

        // Touch ticket updated_at
        $db->prepare("UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $ticketId]);

        $replyId = $db->lastInsertId();
        $stmt = $db->prepare("SELECT * FROM ticket_replies WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $replyId]);
        return $stmt->fetch();
    }

    public static function getReplies(int $ticketId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM ticket_replies WHERE ticket_id = :ticket_id ORDER BY created_at ASC");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public static function getStats(): array {
        $db = Database::getInstance();
        $total = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
        $open = $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'Open'")->fetchColumn();
        $inProgress = $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'In Progress'")->fetchColumn();
        $resolved = $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'Resolved'")->fetchColumn();
        $closed = $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'Closed'")->fetchColumn();

        return [
            'total' => (int)$total,
            'open' => (int)$open,
            'in_progress' => (int)$inProgress,
            'resolved' => (int)$resolved,
            'closed' => (int)$closed,
        ];
    }
}
