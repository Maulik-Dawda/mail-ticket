<?php

namespace MailTicket\Models;

use MailTicket\Database;
use PDO;

class Attachment {
    public static function create(
        int $ticketId,
        string $fileName,
        string $originalName,
        string $filePath,
        int $fileSize,
        string $mimeType
    ): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO attachments (ticket_id, file_name, original_name, file_path, file_size, mime_type)
            VALUES (:ticket_id, :file_name, :original_name, :file_path, :file_size, :mime_type)
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType
        ]);

        $id = $db->lastInsertId();
        return self::getById((int)$id);
    }

    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM attachments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function getByTicketId(int $ticketId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM attachments WHERE ticket_id = :ticket_id ORDER BY created_at ASC");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }
}
