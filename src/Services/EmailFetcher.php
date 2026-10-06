<?php

namespace MailTicket\Services;

use MailTicket\Helpers\Config;
use Exception;

class EmailFetcher {
    private array $config;

    public function __construct() {
        $mailConfig = Config::getMailConfig();
        $this->config = $mailConfig['incoming'] ?? [];
    }

    /**
     * Fetch unread emails and convert them to tickets
     * @return array Array of created tickets or status message
     */
    public function fetchAndProcess(): array {
        if (empty($this->config['enabled'])) {
            return ['status' => 'disabled', 'message' => 'Email fetching is disabled in configuration'];
        }

        if (function_exists('imap_open')) {
            return $this->fetchViaPhpImap();
        }

        // Socket-based fallback / status check
        return $this->fetchViaSocketStream();
    }

    private function fetchViaPhpImap(): array {
        $host = $this->config['host'] ?? '';
        $port = $this->config['port'] ?? 993;
        $ssl = strtolower($this->config['encryption'] ?? 'ssl') === 'ssl' ? '/ssl' : (strtolower($this->config['encryption'] ?? '') === 'tls' ? '/tls' : '');
        $validate = !empty($this->config['validate_cert']) ? '' : '/novalidate-cert';
        
        $mailbox = "{" . "{$host}:{$port}/imap{$ssl}{$validate}" . "}INBOX";
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        $connection = @imap_open($mailbox, $username, $password);
        if (!$connection) {
            throw new Exception("IMAP Connection Failed: " . imap_last_error());
        }

        $emails = imap_search($connection, 'UNSEEN');
        $createdTickets = [];

        if ($emails) {
            foreach ($emails as $emailNumber) {
                $overview = imap_fetch_overview($connection, (string)$emailNumber, 0)[0] ?? null;
                $structure = imap_fetchstructure($connection, $emailNumber);
                
                $header = imap_fetchheader($connection, $emailNumber);
                $body = imap_body($connection, $emailNumber);
                
                $rawEmail = $header . "\r\n" . $body;
                $parsed = EmailParser::parseRawEmail($rawEmail);

                $ticket = TicketService::createTicketFromEmail(
                    $parsed['subject'],
                    $parsed['description'],
                    $parsed['from_email'],
                    $parsed['attachments']
                );

                $createdTickets[] = $ticket;

                if (!empty($this->config['delete_after_import'])) {
                    imap_delete($connection, $emailNumber);
                } else {
                    imap_setflag_full($connection, (string)$emailNumber, "\\Seen");
                }
            }
        }

        imap_close($connection);
        return [
            'status' => 'success',
            'count'  => count($createdTickets),
            'tickets' => $createdTickets
        ];
    }

    private function fetchViaSocketStream(): array {
        // Direct socket connection status test
        $host = $this->config['host'] ?? '';
        $port = $this->config['port'] ?? 993;
        $sslPrefix = strtolower($this->config['encryption'] ?? 'ssl') === 'ssl' ? 'ssl://' : '';

        $timeout = 5;
        $fp = @fsockopen($sslPrefix . $host, $port, $errno, $errstr, $timeout);

        if (!$fp) {
            return [
                'status'  => 'connection_failed',
                'message' => "Could not connect to server {$host}:{$port} ({$errstr})"
            ];
        }

        fclose($fp);

        return [
            'status'  => 'notice',
            'message' => 'IMAP socket reachable. PHP imap extension is not enabled, but Mail Simulator and Webhook API are available for instant ticket ingestion.'
        ];
    }
}
