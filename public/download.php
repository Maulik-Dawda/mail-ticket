<?php

require_once __DIR__ . '/../src/Autoloader.php';

use MailTicket\Models\Attachment;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("Invalid attachment ID.");
}

$attachment = Attachment::getById($id);
if (!$attachment) {
    die("Attachment not found.");
}

$filePath = __DIR__ . '/uploads/attachments/' . basename($attachment['file_name']);

if (!file_exists($filePath)) {
    die("File does not exist on disk: " . htmlspecialchars($attachment['file_name']));
}

$mimeType = $attachment['mime_type'] ?: 'application/octet-stream';
$originalName = $attachment['original_name'] ?: basename($filePath);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($originalName) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($filePath);
exit;
