<?php

namespace MailTicket\Models;

use MailTicket\Database;
use PDO;

class User {
    public static function findOrCreateByEmail(string $email, ?string $name = null): array {
        $db = Database::getInstance();
        $email = strtolower(trim($email));

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }

        // Extract username from email (e.g. john.doe@domain.com -> john.doe)
        $username = explode('@', $email)[0];
        $displayName = $name ?: ucfirst(str_replace(['.', '_', '-'], ' ', $username));

        $stmt = $db->prepare("INSERT INTO users (email, username, name) VALUES (:email, :username, :name)");
        $stmt->execute([
            'email' => $email,
            'username' => $username,
            'name' => $displayName
        ]);

        $id = $db->lastInsertId();
        return self::getById((int)$id);
    }

    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function getAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
}
