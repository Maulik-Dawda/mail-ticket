<?php

namespace MailTicket\Services;

class EmailParser {
    /**
     * Parse raw MIME email source string into structured email object
     */
    public static function parseRawEmail(string $rawContent): array {
        $headers = [];
        $body = '';
        $htmlBody = '';
        $attachments = [];

        // Separate headers and body by double newline
        $parts = preg_split("/\r?\n\r?\n/", $rawContent, 2);
        $headerRaw = $parts[0] ?? '';
        $bodyRaw = $parts[1] ?? '';

        // Parse headers
        $headerLines = preg_split("/\r?\n/", $headerRaw);
        $currentHeader = '';

        foreach ($headerLines as $line) {
            if (preg_match('/^\s+/', $line)) {
                // Continuation line
                $headers[$currentHeader] .= ' ' . trim($line);
            } elseif (strpos($line, ':') !== false) {
                list($key, $val) = explode(':', $line, 2);
                $key = strtolower(trim($key));
                $headers[$key] = trim($val);
                $currentHeader = $key;
            }
        }

        // Extract From details
        $fromRaw = $headers['from'] ?? '';
        $fromEmail = self::extractEmailAddress($fromRaw);
        $fromName = self::extractName($fromRaw);

        // Extract Subject
        $subject = isset($headers['subject']) ? self::decodeHeader($headers['subject']) : '(No Subject)';

        // Check for MIME Multipart boundary
        $contentType = $headers['content-type'] ?? '';
        
        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $matches)) {
            $boundary = trim($matches[1]);
            $mimeParts = explode('--' . $boundary, $bodyRaw);

            foreach ($mimeParts as $part) {
                if (trim($part) === '' || trim($part) === '--') {
                    continue;
                }

                $subParsed = self::parseMimePart($part);
                if ($subParsed['is_attachment']) {
                    $attachments[] = $subParsed['attachment'];
                } elseif (!empty($subParsed['html'])) {
                    $htmlBody = $subParsed['html'];
                } elseif (!empty($subParsed['text'])) {
                    $body = $subParsed['text'];
                }
            }
        } else {
            // Single part email
            if (strpos(strtolower($contentType), 'text/html') !== false) {
                $htmlBody = $bodyRaw;
                $body = strip_tags($bodyRaw);
            } else {
                $body = $bodyRaw;
            }
        }

        $description = !empty($htmlBody) ? $htmlBody : (!empty($body) ? nl2br(htmlspecialchars($body)) : '');

        return [
            'from_email' => $fromEmail,
            'from_name'  => $fromName,
            'subject'    => $subject,
            'description' => $description,
            'plain_text'  => $body,
            'attachments' => $attachments
        ];
    }

    private static function parseMimePart(string $part): array {
        $subParts = preg_split("/\r?\n\r?\n/", $part, 2);
        $headerSection = $subParts[0] ?? '';
        $contentSection = $subParts[1] ?? '';

        $partHeaders = [];
        foreach (preg_split("/\r?\n/", $headerSection) as $l) {
            if (strpos($l, ':') !== false) {
                list($k, $v) = explode(':', $l, 2);
                $partHeaders[strtolower(trim($k))] = trim($v);
            }
        }

        $disposition = $partHeaders['content-disposition'] ?? '';
        $contentType = $partHeaders['content-type'] ?? '';
        $transferEncoding = strtolower($partHeaders['content-transfer-encoding'] ?? '');

        // Decode content
        if ($transferEncoding === 'base64') {
            $decodedContent = base64_decode($contentSection);
        } elseif ($transferEncoding === 'quoted-printable') {
            $decodedContent = quoted_printable_decode($contentSection);
        } else {
            $decodedContent = $contentSection;
        }

        // Check if attachment
        $isAttachment = false;
        $fileName = '';

        if (preg_match('/filename="?([^";\r\n]+)"?/i', $disposition, $m) ||
            preg_match('/name="?([^";\r\n]+)"?/i', $contentType, $m)) {
            $isAttachment = true;
            $fileName = self::decodeHeader(trim($m[1]));
        }

        if ($isAttachment && !empty($fileName)) {
            return [
                'is_attachment' => true,
                'attachment' => [
                    'filename' => $fileName,
                    'content'  => $decodedContent,
                    'size'     => strlen($decodedContent),
                    'mime_type' => explode(';', $contentType)[0]
                ]
            ];
        }

        if (strpos(strtolower($contentType), 'text/html') !== false) {
            return ['is_attachment' => false, 'html' => $decodedContent, 'text' => ''];
        } else {
            return ['is_attachment' => false, 'html' => '', 'text' => $decodedContent];
        }
    }

    public static function extractEmailAddress(string $string): string {
        if (preg_match('/<([^>]+)>/', $string, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return strtolower(trim($string));
    }

    public static function extractName(string $string): string {
        if (preg_match('/^"?([^"<]+)"?\s*</', $string, $matches)) {
            return trim($matches[1]);
        }
        return explode('@', self::extractEmailAddress($string))[0];
    }

    public static function decodeHeader(string $string): string {
        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($string);
        }
        return iconv_mime_decode($string, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $string;
    }
}
