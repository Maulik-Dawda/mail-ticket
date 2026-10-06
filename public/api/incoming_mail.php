<?php

require_once __DIR__ . '/../../src/Autoloader.php';

use MailTicket\Services\TicketService;
use MailTicket\Services\EmailParser;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Send a POST request.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);

    $senderEmail = '';
    $subject = '';
    $description = '';
    $attachments = [];
    $priority = 'Medium';

    if (!empty($jsonData)) {
        // Handle JSON Webhook payload (SendGrid / Mailgun / Custom)
        $senderEmail = $jsonData['from'] ?? $jsonData['sender'] ?? $jsonData['sender_email'] ?? '';
        $subject     = $jsonData['subject'] ?? '(No Subject)';
        $description = $jsonData['body'] ?? $jsonData['text'] ?? $jsonData['html'] ?? $jsonData['description'] ?? '';
        $priority    = $jsonData['priority'] ?? 'Medium';

        if (isset($jsonData['attachments']) && is_array($jsonData['attachments'])) {
            foreach ($jsonData['attachments'] as $att) {
                if (isset($att['content']) && isset($att['name'])) {
                    $attachments[] = [
                        'filename' => $att['name'],
                        'content' => base64_decode($att['content']),
                        'mime_type' => $att['type'] ?? 'application/octet-stream'
                    ];
                }
            }
        }
    } else {
        // Handle Form Data POST request
        $senderEmail = $_POST['from'] ?? $_POST['sender_email'] ?? $_POST['email'] ?? '';
        $subject     = $_POST['subject'] ?? '(No Subject)';
        $description = $_POST['description'] ?? $_POST['body'] ?? '';
        $priority    = $_POST['priority'] ?? 'Medium';

        // Process standard $_FILES uploads
        if (!empty($_FILES['attachment'])) {
            $files = $_FILES['attachment'];
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
            } else if ($files['error'] === UPLOAD_ERR_OK) {
                $attachments[] = [
                    'name' => $files['name'],
                    'tmp_name' => $files['tmp_name'],
                    'mime_type' => $files['type']
                ];
            }
        }
    }

    // Clean sender email
    $senderEmail = EmailParser::extractEmailAddress($senderEmail);

    if (empty($senderEmail)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing sender email parameter.']);
        exit;
    }

    $ticket = TicketService::createTicketFromEmail(
        $subject,
        $description,
        $senderEmail,
        $attachments,
        $priority
    );

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Ticket created successfully from email',
        'ticket' => $ticket
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
