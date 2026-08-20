<?php
namespace Phlash;

use PDO;
use PDOStatement;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . PHLASH_DB_HOST . ';dbname=' . PHLASH_DB_NAME . ';charset=' . PHLASH_DB_CHARSET;
            self::$pdo = new PDO($dsn, PHLASH_DB_USER, PHLASH_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function setting(string $key, string $default = ''): string
    {
        $row = self::one('SELECT v FROM settings WHERE k = ?', [$key]);
        return $row ? (string) $row['v'] : $default;
    }

    public static function setSetting(string $key, string $value): void
    {
        self::query(
            'INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)',
            [$key, $value]
        );
    }
}
