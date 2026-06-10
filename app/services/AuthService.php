<?php

class AuthService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->findActiveUserByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->startUserSession($user);
        $this->updateLastLogin((int) $user['id']);

        return true;
    }

    private function findActiveUserByUsername(string $username): ?array
    {
        $sql = "
            SELECT 
                u.id,
                u.role_id,
                u.username,
                u.password_hash,
                u.first_name,
                u.last_name,
                r.name AS role_name
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.username = ?
              AND u.status = 'active'
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        return $user ?: null;
    }

    private function startUserSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role_id'] = (int) $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
    }

    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET last_login_at = NOW() 
            WHERE id = ?
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }
}
