<?php

namespace MailTicket;

use PDO;
use PDOException;
use MailTicket\Helpers\Config;

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = Config::getDatabaseConfig();
            $driver = $config['driver'] ?? 'sqlite';

            try {
                if ($driver === 'sqlite') {
                    $dbFile = $config['sqlite']['database'];
                    $dir = dirname($dbFile);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    self::$instance = new PDO("sqlite:" . $dbFile);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec('PRAGMA foreign_keys = ON;');
                } else {
                    $mysql = $config['mysql'];
                    $dsn = "mysql:host={$mysql['host']};port={$mysql['port']};dbname={$mysql['database']};charset={$mysql['charset']}";
                    self::$instance = new PDO($dsn, $mysql['username'], $mysql['password']);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                }

                self::migrateSchema($driver);

            } catch (PDOException $e) {
                // Return clear HTML page instead of HTTP 500 error
                http_response_code(200);
                echo "
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Database Configuration Required</title>
                    <style>
                        body { background: #0b0f19; color: #f3f4f6; font-family: system-ui, sans-serif; padding: 3rem; text-align: center; }
                        .card { background: #121826; border: 1px solid rgba(255,255,255,0.1); max-width: 650px; margin: 0 auto; padding: 2rem; border-radius: 12px; text-align: left; }
                        h2 { color: #f87171; margin-top: 0; }
                        code { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; color: #fbbf24; }
                        pre { background: #000; padding: 1rem; border-radius: 6px; color: #34d399; overflow-x: auto; }
                    </style>
                </head>
                <body>
                    <div class='card'>
                        <h2>⚠️ Database Connection Error</h2>
                        <p>Unable to connect to the database. Please verify your settings in <code>config/database.php</code> or the Settings page.</p>
                        <p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                        <hr style='border-color: rgba(255,255,255,0.1); margin: 1.5rem 0;'>
                        <p style='font-size: 0.9rem; color: #9ca3af;'>Tip: If using MySQL on Hostinger / cPanel, check that your DB user, DB name, and password in <code>config/database.php</code> match your hosting panel.</p>
                    </div>
                </body>
                </html>";
                exit;
            }
        }

        return self::$instance;
    }

    private static function migrateSchema(string $driver): void {
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            if ($driver === 'sqlite') {
                $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
                $sql = str_replace('INT AUTOINCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            }
            try {
                self::$instance->exec($sql);
            } catch (PDOException $ex) {
                // Table might already exist
            }
        }
    }
}
