<?php
namespace app\models;

use app\config\MySQL;

class User
{
    private static ?self $currentUser = null;

    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $role = 'default';
    private ?string $lang = null;
    private ?\DateTime $registeredAt = null;
    private ?\DateTime $lastLogin = null;
    private array $stats = [];
    private bool $isActive = false;

    /**
     * Constructor to initialize the User object with data.
     *
     * @param ?array $data Optional array of user data to initialize the object.
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->initializeFromData($data);
        }
    }

    /**
     * Initializes the User object with data from an array.
     *
     * @param array $data Array of user data.
     */
    private function initializeFromData(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->role = $data['role'] ?? 'default';
        $this->lang = $data['lang'] ?? null;
        $this->registeredAt = isset($data['registered_at']) ? new \DateTime($data['registered_at']) : null;
        $this->lastLogin = isset($data['last_login']) ? new \DateTime($data['last_login']) : null;
        $this->stats = isset($data['stats']) ? json_decode($data['stats'], true) ?? [] : [];
        $this->isActive = $data['is_active'] ?? false;
    }

    /**
     * Checks the login status of the current user.
     *
     * @return ?self The current User object if logged in, null otherwise.
     */
    public static function checkLoginStatus(): ?self
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
    
        $connection = MySQL::getInstance()->getConnection();
        $sessionId = $_SESSION['user']['id'] ?? null;
        $rawToken = $_COOKIE['remember_token'] ?? null;
    
        if (!$sessionId && !$rawToken) {
            return null;
        }
    
        if ($sessionId) {
            $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("i", $sessionId);
        } elseif ($rawToken) {
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $tokenHash = hash('sha256', $rawToken . $currentUserAgent);
    
            $sql = "SELECT u.* 
                    FROM users u 
                    INNER JOIN remember_tokens rt ON u.id = rt.user_id 
                    WHERE rt.token = ? AND rt.expires_at > NOW() AND rt.user_agent = ? 
                    LIMIT 1";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ss", $tokenHash, $currentUserAgent);

            MySQL::getInstance()->deleteExpiredRows('remember_tokens');
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $data = $result->fetch_assoc()) {
            self::$currentUser = new self($data);
            if (!$sessionId && $rawToken) {
                $_SESSION['user'] = ['id' => self::$currentUser->id];
            }
            $stmt->close();
            return self::$currentUser;
        }
    
        $stmt->close();
        return null;
    }

    /**
     * Logs in the user with the given username and password.
     *
     * @param \mysqli $connection The MySQL connection object.
     * @param string $username The username of the user.
     * @param string $password The password of the user.
     * @param bool $remember Whether to remember the user for future logins.
     * @return bool True if login is successful, false otherwise.
     */
    public function login(\mysqli $connection, string $username, string $password, bool $remember = false): bool
    {
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $this->initializeFromData($user);
                $this->isActive = true;
                $this->lastLogin = new \DateTime();

                $updateSql = "UPDATE users SET last_login = ? WHERE id = ?";
                $updateStmt = $connection->prepare($updateSql);
                $lastLoginStr = $this->lastLogin->format('Y-m-d H:i:s');
                $updateStmt->bind_param("si", $lastLoginStr, $this->id);
                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION['user'] = ['id' => $this->id];
                if ($remember) {
                    $this->setRememberToken($connection);
                }
                $stmt->close();
                return true;
            }
        }
        $stmt->close();
        return false;
    }

    /**
     * Registers a new user with the given details.
     *
     * @param \mysqli $connection The MySQL connection object.
     * @param string $username The username of the new user.
     * @param string $email The email of the new user.
     * @param string $password The password of the new user.
     * @param string $lang The preferred language of the new user.
     * @return bool True if registration is successful, false otherwise.
     */
    public function register(\mysqli $connection, string $username, string $email, string $password, string $lang): bool
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, lang) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $lang);
        $success = $stmt->execute();

        $stmt->close();
        return $success;
    }

    /**
     * Logs out the current user and clears the session and remember token.
     *
     * @param \mysqli $connection The MySQL connection object.
     */
    public function logout(\mysqli $connection): void
    {
        if (isset($_COOKIE['remember_token'])) {
            $rawToken = $_COOKIE['remember_token'];
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $tokenHash = hash('sha256', $rawToken . $currentUserAgent);

            $sql = "DELETE FROM remember_tokens WHERE token = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("s", $tokenHash);
            $stmt->execute();
            $stmt->close();
            setcookie('remember_token', '', time() - 3600, '/');
        }
        self::$currentUser = null;
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * Sets a remember token for the user to enable persistent login.
     *
     * @param \mysqli $connection The MySQL connection object.
     */
    private function setRememberToken(\mysqli $connection): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $tokenHash = hash('sha256', $rawToken . $userAgent);
    
        $expirationTime = (int)($_ENV['REMEMBER_TOKEN_EXPIRATION']) * 24 * 60 * 60;
        $expiresAt = date('Y-m-d H:i:s', time() + $expirationTime);
    
        $sql = "INSERT INTO remember_tokens (user_id, token, user_agent, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("isss", $this->id, $tokenHash, $userAgent, $expiresAt);
        $stmt->execute();
        $stmt->close();
    
        setcookie('remember_token', $rawToken, [
            'expires' => time() + $expirationTime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => true
        ]);
    }

    /**
     * Changes the user's password, deletes all remember tokens, and logs the user out if they are currently logged in.
     *
     * @param \mysqli $connection The MySQL connection object.
     * @param string $newPassword The new password to set.
     * @param int $userId The ID of the user whose password is being changed.
     * @return bool True if the password change is successful, false otherwise.
     */
    public function changePassword(\mysqli $connection, string $newPassword, int $userId): bool
    {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Start a transaction to ensure atomicity
        $connection->begin_transaction();

        try {
            // Update the password in the users table
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("si", $hashedPassword, $userId);
            $success = $stmt->execute();
            $stmt->close();

            if (!$success) {
                throw new \Exception("Failed to update password.");
            }

            // Delete all remember tokens for this user
            $sql = "DELETE FROM remember_tokens WHERE user_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            // Check if the user with this ID is currently logged in and log them out
            $currentUser = self::checkLoginStatus();
            if ($currentUser && $currentUser->getId() === $userId) {
                $this->logout($connection);
            }

            // Commit the transaction
            $connection->commit();
            return true;
        } catch (\Exception $e) {
            // Rollback the transaction on failure
            $connection->rollback();
            return false;
        }
    }

    // Getters and Setters
    public static function getCurrentUser(): ?self { return self::$currentUser ?? self::checkLoginStatus(); }
    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getRole(): ?string { return $this->role; }
    public function getLang(): ?string { return $this->lang; }
    public function getRegisteredAt(): ?\DateTime { return $this->registeredAt; }
    public function getLastLogin(): ?\DateTime { return $this->lastLogin; }
    public function getStats(): array { return $this->stats; }
    public function isActive(): bool { return $this->isActive; }
    public function setStats(array $stats): void { $this->stats = $stats; }
    public function setLang(string $lang): void { $this->lang = $lang; }
}