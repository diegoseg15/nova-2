<?php

require_once __DIR__ . '/../app/core/env.php';

class Database
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;

    public mysqli $conn;

    public function __construct()
    {
        $this->host = env('DB_HOST', 'localhost');
        $this->dbName = env('DB_DATABASE', '');
        $this->username = env('DB_USERNAME', '');
        $this->password = env('DB_PASSWORD', '');
    }

    public function connect(): mysqli
    {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->dbName
            );

            $this->conn->set_charset('utf8mb4');

            return $this->conn;
        } catch (Throwable $e) {
            error_log('Database connection error: ' . $e->getMessage());

            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
    }
}
