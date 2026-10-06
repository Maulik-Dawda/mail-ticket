<?php

namespace MailTicket\Helpers;

class Config {
    public static function getMailConfig(): array {
        $jsonPath = __DIR__ . '/../../config/mail.json';
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if (is_array($data)) {
                return $data;
            }
        }

        $phpPath = __DIR__ . '/../../config/mail.php';
        if (file_exists($phpPath)) {
            return require $phpPath;
        }

        return require __DIR__ . '/../../config/mail.php.example';
    }

    public static function saveMailConfig(array $config): bool {
        $jsonPath = __DIR__ . '/../../config/mail.json';
        return file_put_contents($jsonPath, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }

    public static function getDatabaseConfig(): array {
        $jsonPath = __DIR__ . '/../../config/database.json';
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if (is_array($data)) {
                return $data;
            }
        }

        $phpPath = __DIR__ . '/../../config/database.php';
        if (file_exists($phpPath)) {
            return require $phpPath;
        }

        return require __DIR__ . '/../../config/database.php.example';
    }

    public static function saveDatabaseConfig(array $config): bool {
        $jsonPath = __DIR__ . '/../../config/database.json';
        return file_put_contents($jsonPath, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }
}
