<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Infrastructure\Config\AppConfig;
use PDO;
use PDOException;

final class PDOConnection
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = AppConfig::getInstance();
            $dsn = (string) $config->get('db.dsn');
            $user = (string) $config->get('db.user');
            $password = (string) $config->get('db.password');

            try {
                self::$connection = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$connection;
    }
}

