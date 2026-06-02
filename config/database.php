<?php

class Database {

    private $host = "localhost";
    private $db_name = "ueargentina_nova1";
    private $username = "ueargentina_nova1_user";
    private $password = "Nova1@&123";
    public $conn;

    public function connect() {

        $this->conn = null;

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name
            );
            
            $this->conn->set_charset("utf8mb4");

            if ($this->conn->connect_error) {
                die("Error de conexión: " . $this->conn->connect_error);
            }

        } catch(Exception $e) {
            die("Error: " . $e->getMessage());
        }

        return $this->conn;
    }
}