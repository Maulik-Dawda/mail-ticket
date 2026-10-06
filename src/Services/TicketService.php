<?php

namespace MailTicket\Services;

use MailTicket\Models\Ticket;
use MailTicket\Models\Attachment;
use MailTicket\Helpers\Config;
use Exception;

class TicketService {
    /**
     * Process an incoming email payload and create a ticket with attachments
     */
    public static function createTicketFromEmail(
        string $subject,
        ?string $description,
        string $senderEmail,
        array $attachments = [],
        string $priority = 'Medium'
    ): array {
        if (empty(trim($senderEmail))) {
            throw new Exception("Sender email cannot be empty.");
        }

        if (empty(trim($subject))) {
            $subject = "(No Subject)";
        }

        // Create Ticket in DB
        $ticket = Ticket::create(
            $subject,
            $description,
            $senderEmail,
            $priority
        );

        $savedAttachments = [];
        $uploadConfig = Config::getMailConfig();
        $attachmentDir = $uploadConfig['storage']['attachment_dir'] ?? (__DIR__ . '/../../public/uploads/attachments');

        if (!is_dir($attachmentDir)) {
            mkdir($attachmentDir, 0777, true);
        }

        // Process attachments
        foreach ($attachments as $att) {
            $origName = $att['filename'] ?? $att['name'] ?? 'file_' . uniqid() . '.bin';
            $content = $att['content'] ?? null;
            $tmpPath = $att['tmp_name'] ?? null;
            $mimeType = $att['mime_type'] ?? $att['type'] ?? 'application/octet-stream';

            $fileExt = pathinfo($origName, PATHINFO_EXTENSION);
            $safeExt = !empty($fileExt) ? strtolower($fileExt) : 'bin';
            $storedFileName = 'att_' . $ticket['id'] . '_' . uniqid() . '.' . $safeExt;
            $destinationPath = $attachmentDir . '/' . $storedFileName;

            $saved = false;
            $fileSize = 0;

            if ($content !== null) {
                if (file_put_contents($destinationPath, $content) !== false) {
                    $saved = true;
                    $fileSize = strlen($content);
                }
            } elseif ($tmpPath !== null && file_exists($tmpPath)) {
                if (move_uploaded_file($tmpPath, $destinationPath) || copy($tmpPath, $destinationPath)) {
                    $saved = true;
                    $fileSize = filesize($destinationPath);
                }
            }

            if ($saved) {
                $savedAttachments[] = Attachment::create(
                    $ticket['id'],
                    $storedFileName,
                    $origName,
                    'uploads/attachments/' . $storedFileName,
                    $fileSize,
                    $mimeType
                );
            }
        }

        $ticket['attachments'] = $savedAttachments;
        return $ticket;
    }
}
