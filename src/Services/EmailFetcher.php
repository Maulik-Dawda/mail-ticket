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

        if (empty($this->config['host']) || empty($this->config['username']) || empty($this->config['password'])) {
            return ['status' => 'config_incomplete', 'message' => 'IMAP Host, Username, or Password is missing. Please save settings on the Settings page.'];
        }

        if (function_exists('imap_open')) {
            try {
                return $this->fetchViaPhpImap();
            } catch (Exception $e) {
                // Fallback to raw socket connection if ext-imap fails
                return $this->fetchViaSocketStream();
            }
        }

        // Native Socket IMAP Client Fallback
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

        // Search for UNSEEN (unread) emails
        $emails = imap_search($connection, 'UNSEEN');
        $createdTickets = [];

        if ($emails) {
            foreach ($emails as $emailNumber) {
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
            'tickets' => $createdTickets,
            'message' => count($createdTickets) > 0 ? "Successfully imported " . count($createdTickets) . " email ticket(s)." : "No unread emails found in inbox."
        ];
    }

    private function fetchViaSocketStream(): array {
        $host = $this->config['host'] ?? '';
        $port = $this->config['port'] ?? 993;
        $sslPrefix = strtolower($this->config['encryption'] ?? 'ssl') === 'ssl' ? 'ssl://' : '';
        $username = trim($this->config['username'] ?? '');
        $password = $this->config['password'] ?? '';

        $timeout = 10;
        $fp = @fsockopen($sslPrefix . $host, $port, $errno, $errstr, $timeout);

        if (!$fp) {
            return [
                'status'  => 'connection_failed',
                'message' => "Could not connect to IMAP server {$host}:{$port} ({$errstr})"
            ];
        }

        // Read server greeting banner
        $greeting = fgets($fp, 1024);

        // Send IMAP Login Command with escaped double quotes
        $cleanUser = str_replace(['\\', '"'], ['\\\\', '\"'], $username);
        $cleanPass = str_replace(['\\', '"'], ['\\\\', '\"'], $password);
        
        fputs($fp, "A1 LOGIN \"{$cleanUser}\" \"{$cleanPass}\"\r\n");
        $loginResp = '';
        while ($line = fgets($fp, 1024)) {
            $loginResp .= $line;
            if (strpos($line, 'A1 ') === 0) break;
        }

        if (strpos($loginResp, 'A1 OK') === false) {
            fclose($fp);
            return [
                'status'  => 'login_failed',
                'message' => 'IMAP Authentication Failed for ' . htmlspecialchars($username) . '. Server output: ' . htmlspecialchars(trim($loginResp))
            ];
        }

        // Select INBOX
        fputs($fp, "A2 SELECT INBOX\r\n");
        while ($line = fgets($fp, 1024)) {
            if (strpos($line, 'A2 ') === 0) break;
        }

        // Search UNSEEN messages
        fputs($fp, "A3 SEARCH UNSEEN\r\n");
        $searchResp = '';
        while ($line = fgets($fp, 1024)) {
            $searchResp .= $line;
            if (strpos($line, 'A3 ') === 0) break;
        }

        preg_match('/\* SEARCH (.*)/i', $searchResp, $matches);
        $msgNums = !empty($matches[1]) ? array_filter(explode(' ', trim($matches[1]))) : [];

        $createdTickets = [];

        foreach ($msgNums as $msgNum) {
            $msgNum = trim($msgNum);
            if (!is_numeric($msgNum)) continue;

            fputs($fp, "A4 FETCH {$msgNum} (BODY[])\r\n");
            $rawEmail = '';
            while ($line = fgets($fp, 4096)) {
                if (strpos($line, 'A4 OK') === 0) break;
                $rawEmail .= $line;
            }

            if (!empty($rawEmail)) {
                $parsed = EmailParser::parseRawEmail($rawEmail);
                $ticket = TicketService::createTicketFromEmail(
                    $parsed['subject'],
                    $parsed['description'],
                    $parsed['from_email'],
                    $parsed['attachments']
                );
                $createdTickets[] = $ticket;

                // Mark as seen
                fputs($fp, "A5 STORE {$msgNum} +FLAGS (\\Seen)\r\n");
                fgets($fp, 1024);
            }
        }

        fputs($fp, "A6 LOGOUT\r\n");
        fclose($fp);

        return [
            'status' => 'success',
            'count'  => count($createdTickets),
            'tickets' => $createdTickets,
            'message' => count($createdTickets) > 0 ? "Successfully imported " . count($createdTickets) . " email ticket(s)!" : "No unread emails found in inbox."
        ];
    }
}
