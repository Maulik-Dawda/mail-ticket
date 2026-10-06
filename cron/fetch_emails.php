<?php

require_once __DIR__ . '/../src/Autoloader.php';

use MailTicket\Services\EmailFetcher;

echo "[" . date('Y-m-d H:i:s') . "] Starting Email Fetcher...\n";

try {
    $fetcher = new EmailFetcher();
    $result = $fetcher->fetchAndProcess();
    
    echo "Status: " . ($result['status'] ?? 'unknown') . "\n";
    echo "Message/Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "Error fetching emails: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Execution finished.\n";
