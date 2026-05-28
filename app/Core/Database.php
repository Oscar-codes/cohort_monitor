<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database
 *
 * Singleton PDO wrapper for MySQL connections.
 * Reads configuration from config/database.php.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Private constructor — use Database::getInstance().
     */
    private function __construct()
    {
        $config = require APP_ROOT . '/config/database.php';

        $host = trim((string) ($config['host'] ?? ''));
        $port = trim((string) ($config['port'] ?? '3306'));
        $database = trim((string) ($config['database'] ?? ''));
        $charset = trim((string) ($config['charset'] ?? 'utf8mb4'));
        $socket = trim((string) ($config['unix_socket'] ?? ''));

        if ($host === '' || strtolower($host) === 'localhost') {
            // Force TCP by default to avoid socket-based resolution failures on containers.
            $host = '127.0.0.1';
        }

        $dsnParts = [];
        if ($socket !== '') {
            $dsnParts[] = 'unix_socket=' . $socket;
        } else {
            $dsnParts[] = 'host=' . $host;
            $dsnParts[] = 'port=' . $port;
        }
        $dsnParts[] = 'dbname=' . $database;
        $dsnParts[] = 'charset=' . $charset;
        $dsn = 'mysql:' . implode(';', $dsnParts);

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Reinforce UTF-8 session settings for environments that ignore DSN charset.
            $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage() . ' [dsn=' . $dsn . ']');
        }
    }

    /**
     * Get the singleton Database instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the underlying PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared query and return results.
     *
     * @param string $sql    SQL statement with placeholders
     * @param array  $params Bound parameters
     * @return array          Result rows
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a prepared statement (INSERT, UPDATE, DELETE).
     *
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Prevent cloning of the singleton.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the singleton.
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton.');
    }
}
