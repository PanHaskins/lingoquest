<?php

namespace App\Config;

use mysqli;
use Exception;

/**
 * MySQL Database Connection Manager
 */
class MySQL
{
    private static ?MySQL $instance = null;
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private int $port;
    private ?mysqli $connection = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->username = $_ENV['DB_USER'] ?? '';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->database = $_ENV['DB_DATABASE'] ?? '';
        $this->port = (int)($_ENV['DB_PORT'] ?? 3306);

        $this->connect();
    }

    /**
     * Get singleton instance of MySQL connection
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );

            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed: ' . $this->connection->connect_error);
            }

            $this->connection->set_charset('utf8mb4');
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Get the active database connection
     */
    public function getConnection(): mysqli
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Check if connection is active
     */
    public function isConnected(): bool
    {
        return $this->connection !== null && $this->connection->ping();
    }

    /**
     * Close the database connection
     */
    public function close(): void
    {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Execute a simple query and return result
     */
    public function query(string $sql, array $params = []): ?\mysqli_result
    {
        $stmt = $this->prepareStatement($sql, $params);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
        $stmt->close();
        return null;
    }

    /**
     * Prepare and bind parameters to a statement
     */
    public function prepareStatement(string $sql, array $params = []): \mysqli_stmt
    {
        $stmt = $this->getConnection()->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $this->getConnection()->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        return $stmt;
    }

    /**
     * Delete rows in a specific table where the column expires_at is less than the current date and time
     */
    public function deleteExpiredRows(string $table): bool
    {
        $sql = "DELETE FROM $table WHERE expires_at < NOW()";
        $stmt = $this->prepareStatement($sql);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {}
}

// Initialize connection for backward compatibility with global $connection
$connection = MySQL::getInstance()->getConnection();