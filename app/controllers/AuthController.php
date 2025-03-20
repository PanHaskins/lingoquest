<?php
namespace app\controllers;

use app\config\MySQL;
use app\models\User;
use app\models\Routing;
use app\models\Emailer;

class AuthController extends User
{
    private MySQL $db;
    private Routing $routing;

    public function __construct()
    {
        $this->db = MySQL::getInstance();
        $this->routing = new Routing();
    }

    /**
     * Handles the login process.
     *
     * @param array $data The login data.
     * @return void
     */
    public function handleLogin(array $data): void
    {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $remember = $data['remember'] ?? false;

        if (!$this->isUsernameValid($username) || !$this->isPasswordValid($password)) {
            Routing::redirect(url: 'profile/login', queryParams: ['error' => 'invalid_login']);
        }

        if ($this->login($this->db->getConnection(), $username, $password, $remember)) {
            Routing::redirect(url: 'dashboard');
        }
        Routing::redirect(url: 'profile/login', queryParams: ['error' => 'invalid_login']);
    }

    /**
     * Handles the registration process.
     *
     * @param array $data The registration data.
     * @return void
     */
    public function handleRegistration(array $data): void
    {
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $lang = $this->routing->lang;

        if (!$this->isUsernameValid($username) || !$this->isEmailValid($email) || !$this->isPasswordValid($password)) {
            Routing::redirect(url: 'profile/register', queryParams: ['error' => 'invalid_input']);
        }

        $connection = $this->db->getConnection();
        $sql = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            Routing::redirect(url: 'profile/register', queryParams: ['error' => 'user_exists']);
        }
        $stmt->close();

        if ($this->register($connection, $username, $email, $password, $lang)) {
            Routing::redirect(url: 'profile/login', queryParams: ['success' => 'register_success']);
        }
        Routing::redirect(url: 'profile/register', queryParams: ['error' => 'register_failed']);
    }

    /**
     * Handles the logout process.
     *
     * @return void
     */
    public function handleLogout(): void
    {
        $user = self::checkLoginStatus();
        if ($user) {
            $user->logout($this->db->getConnection());
        }
        Routing::redirect(url: 'profile/login');
    }

    /**
     * Handles the forgot password process.
     *
     * @param array $data The data for password reset.
     * @return void
     */
    public function handleForgotPassword(array $data): void
    {
        $email = $data['email'] ?? '';
        $token = $data['token'] ?? '';

        if (!empty($token)) {
            // Verify token and delete it immediately
            $connection = $this->db->getConnection();
            $sql = "SELECT user_id, expires_at FROM password_resets WHERE token = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'invalid_token']);
            }

            $resetData = $result->fetch_assoc();
            $stmt->close();

            if (strtotime($resetData['expires_at']) < time()) {
                Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'invalid_token']);
            }

            // Token is valid, delete it from the database
            $sql = "DELETE FROM password_resets WHERE token = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();

            // Save user_id to session
            $_SESSION['user']['reset_id'] = $resetData['user_id'];
            Routing::redirect(url: 'profile/reset-password');
        }

        if (!empty($email)) {
            // Send email with reset token
            if (!$this->isEmailValid($email)) {
                Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'email_invalid']);
            }

            $connection = $this->db->getConnection();
            $sql = "SELECT id, username FROM users WHERE email = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'email_not_found']);
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            // Generate reset token
            $resetToken = bin2hex(random_bytes(16));
            $expirationTime = (int)($_ENV['PASSWORD_RESET_EXPIRATION']) * 60 * 60;
            $expiration = date('Y-m-d H:i:s', time() + $expirationTime);

            $this->db->deleteExpiredRows('password_resets');

            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("iss", $user['id'], $resetToken, $expiration);
            $stmt->execute();
            $stmt->close();

            $lang = $this->routing->lang;
            $emailer = Emailer::getInstance();
            $tokenLink = $_SERVER['SERVER_NAME'] . $this->routing->setURL(path: 'profile/reset-password', queryParams: ['token' => urlencode($resetToken)], resetQuery: true);
            $variables = [
                'username' => $user['username'],
                'tokenLink' => $tokenLink,
                'title' => __('reset_password')
            ];

            if ($emailer->sendEmail($email, 'profile/reset/' . $lang, $variables['title'], $variables)) {
                Routing::redirect(url: 'profile/reset-password', queryParams: ['success' => 'email_sended']);
            } else {
                Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'email_failed']);
            }
        }
    }

    /**
     * Handles the change password process.
     *
     * @param array $data The data for changing the password.
     * @param int $userId The user ID.
     * @return void
     */
    public function handleChangePassword(array $data, int $userId): void
    {
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (!$this->isPasswordValid($password) || empty($password) || empty($confirmPassword) || $password !== $confirmPassword) {
            Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'invalid_input']);
        }

        // Change password
        $connection = $this->db->getConnection();
        $user = new User();
        if ($user->changePassword($connection, $password, $userId)) {
            unset($_SESSION['user']['reset_id']);
            Routing::redirect(url: 'profile/login', queryParams: ['success' => 'password_changed']);
        } else {
            Routing::redirect(url: 'profile/reset-password', queryParams: ['error' => 'change_failed']);
        }
    }

    /**
     * Handles the profile update process including verification of token if provided.
     *
     * @param array $data The data for updating the profile or token verification (merged GET/POST).
     * @param User $user The currently logged-in user.
     * @return void
     */
    public function handleProfileUpdate(array $data, User $user): void
    {
        $connection = $this->db->getConnection();
        $token = $data['token'] ?? '';

        // Handle token verification (only for GET requests)
        if (!empty($token) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $sql = "SELECT changes, expires_at FROM profile_updates WHERE user_id = ? AND token = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("is", $user->getId(), $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'invalid_token']);
                return;
            }

            $tokenData = $result->fetch_assoc();
            $stmt->close();

            if (strtotime($tokenData['expires_at']) < time()) {
                $this->db->deleteExpiredRows('profile_updates');
                Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'invalid_token']);
                return;
            }

            $changes = json_decode($tokenData['changes'], true);

            // Apply changes
            $sql = "UPDATE users SET ";
            $types = "";
            $bindParams = [];
            $fields = [];

            if (!empty($changes['username'])) {
                $fields[] = "username = ?";
                $types .= "s";
                $bindParams[] = $changes['username'];
            }
            if (!empty($changes['email'])) {
                $fields[] = "email = ?";
                $types .= "s";
                $bindParams[] = $changes['email'];
            }
            if (!empty($changes['password'])) {
                $fields[] = "password = ?";
                $types .= "s";
                $bindParams[] = $changes['password'];
            }
            if (!empty($changes['lang'])) {
                $fields[] = "lang = ?";
                $types .= "s";
                $bindParams[] = $changes['lang'];
            }

            $sql .= implode(", ", $fields) . " WHERE id = ?";
            $types .= "i";
            $bindParams[] = $user->getId();

            $stmt = $connection->prepare($sql);
            $stmt->bind_param($types, ...$bindParams);
            $success = $stmt->execute();
            $stmt->close();

            // Delete expired rows (including the used token)
            $this->db->deleteExpiredRows('profile_updates');

            if ($success) {
                Routing::redirect(url: 'profile/settings', queryParams: ['success' => 'profile_updated']);
            } else {
                Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'update_failed']);
            }
            return;
        }

        // Handle profile update submission (only for POST requests with meaningful data)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $oldPassword = $data['old_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';
            $language = $data['language'] ?? '';

            // If no meaningful data is provided, exit silently
            if (empty($username) && empty($email) && empty($oldPassword) && empty($newPassword) && empty($language)) {
                return;
            }

            // Use current values as defaults if fields are empty
            $username = $username ?: $user->getUsername();
            $email = $email ?: $user->getEmail();
            $language = $language ?: $user->getLang();

            // Validate inputs only if they are provided and different
            if ((!empty($username) && $username !== $user->getUsername() && !$this->isUsernameValid($username)) ||
                (!empty($email) && $email !== $user->getEmail() && !$this->isEmailValid($email))) {
                Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'invalid_input']);
                return;
            }

            $changes = [];
            $requiresVerification = false;

            // Check username change
            if ($username !== $user->getUsername()) {
                $changes['username'] = $username;
                $requiresVerification = true;
            }

            // Check email change
            if ($email !== $user->getEmail()) {
                $changes['email'] = $email;
                $requiresVerification = true;
            }

            // Check password change
            if (!empty($newPassword)) {
                if (empty($oldPassword)) {
                    Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'invalid_old_password']);
                    return;
                }
                // Verify old password directly
                $sql = "SELECT password FROM users WHERE id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("i", $user->getId());
                $stmt->execute();
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
                $stmt->close();
                if (!password_verify($oldPassword, $userData['password'])) {
                    Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'invalid_old_password']);
                    return;
                }
                if (!$this->isPasswordValid($newPassword)) {
                    Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'invalid_new_password']);
                    return;
                }
                $changes['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            // Check language change
            if ($language !== $user->getLang()) {
                $changes['lang'] = $language;
            }

            // If no changes after processing, redirect only if data was submitted
            if (empty($changes)) {
                Routing::redirect(url: 'profile/settings', queryParams: ['success' => 'no_changes']);
                return;
            }

            // Handle direct updates (no verification needed)
            if (!$requiresVerification) {
                // If only password and/or language changed, update directly
                $success = true;
                if (!empty($changes['password'])) {
                    $success = $user->changePassword($connection, $newPassword, $user->getId());
                }
                if ($success && !empty($changes['lang'])) {
                    $sql = "UPDATE users SET lang = ? WHERE id = ?";
                    $stmt = $connection->prepare($sql);
                    $stmt->bind_param("si", $language, $user->getId());
                    $success = $stmt->execute();
                    $stmt->close();
                }

                if ($success) {
                    Routing::redirect(url: 'profile/settings', queryParams: ['success' => 'profile_updated']);
                } else {
                    Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'update_failed']);
                }
                return;
            }

            // Check for uniqueness of username/email (except current user)
            if (!empty($changes['username']) || !empty($changes['email'])) {
                $sqlCheck = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
                $stmtCheck = $connection->prepare($sqlCheck);
                $checkUsername = $changes['username'] ?? $user->getUsername();
                $checkEmail = $changes['email'] ?? $user->getEmail();
                $stmtCheck->bind_param("ssi", $checkUsername, $checkEmail, $user->getId());
                $stmtCheck->execute();
                $stmtCheck->store_result();
                if ($stmtCheck->num_rows > 0) {
                    $stmtCheck->close();
                    Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'user_exists']);
                    return;
                }
                $stmtCheck->close();
            }

            // If verification is required (username or email changed), send email with token
            if ($requiresVerification) {
                $token = bin2hex(random_bytes(16));
                $expirationMinutes = (int)($_ENV['PROFILE_UPDATE_EXPIRATION'] ?? 30);
                $expiration = date('Y-m-d H:i:s', time() + ($expirationMinutes * 60));

                // Clean up expired tokens
                $stmt = $connection->prepare("DELETE FROM profile_updates WHERE expires_at < NOW()");
                $stmt->execute();
                $stmt->close();

                // Store pending changes
                $sql = "INSERT INTO profile_updates (user_id, token, changes, expires_at) VALUES (?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $changesJson = json_encode($changes);
                $stmt->bind_param("isss", $user->getId(), $token, $changesJson, $expiration);
                $stmt->execute();
                $stmt->close();

                // Prepare email content
                $emailer = Emailer::getInstance();
                $targetEmail = !empty($changes['email']) ? $user->getEmail() : $email; // Send to old email if email changes
                $tokenLink = $_SERVER['SERVER_NAME'] . $this->routing->setURL(path: 'profile/settings', queryParams: ['token' => urlencode($token)], resetQuery: true);

                // Generate dynamic changes list
                $changesList = '<ul style="margin: 16px 0; padding-left: 20px; font-size: 16px;">';
                if (!empty($changes['username'])) {
                    $changesList .= '<li><strong>Používateľské meno:</strong> ' . htmlspecialchars($changes['username']) . '</li>';
                }
                if (!empty($changes['email'])) {
                    $changesList .= '<li><strong>Email:</strong> ' . htmlspecialchars($changes['email']) . '</li>';
                }
                if (!empty($changes['password'])) {
                    $maskedPassword = substr($newPassword, 0, 1) . str_repeat('*', strlen($newPassword) - 2) . substr($newPassword, -1);
                    $changesList .= '<li><strong>Heslo:</strong> ' . htmlspecialchars($maskedPassword) . '</li>';
                }
                if (!empty($changes['lang'])) {
                    $changesList .= '<li><strong>Jazyk:</strong> ' . htmlspecialchars($changes['lang']) . '</li>';
                }
                $changesList .= '</ul>';

                $variables = [
                    'oldUsername' => $user->getUsername(),
                    'tokenLink' => $tokenLink,
                    'title' => __('email_profile_update_verify'),
                    'changesList' => $changesList,
                ];

                if ($emailer->sendEmail($targetEmail, 'profile/update/' . $user->getLang(), $variables['title'], $variables)) {
                    Routing::redirect(url: 'profile/settings', queryParams: ['success' => 'email_sended']);
                } else {
                    Routing::redirect(url: 'profile/settings', queryParams: ['error' => 'email_failed']);
                }
                return;
            }
        }
        return;
    }

    /**
     * Handles the avatar upload process.
     *
     * @param int $userId The user ID.
     * @param array $file The uploaded file data from $_FILES.
     * @return void
     */
    public function handleAvatarUpload(int $userId, array $file): void
    {
    
        if ($file['error'] !== UPLOAD_ERR_OK) {
            header('HTTP/1.1 400 Bad Request');
            header('X-Upload-Status: error');
            header('X-Upload-Message: ' . rawurlencode(__('avatar_upload_error')));
            ob_end_clean();
            exit;
        }
    
        if ($file['size'] > 250 * 1024) {
            header('HTTP/1.1 400 Bad Request');
            header('X-Upload-Status: error');
            header('X-Upload-Message: ' . rawurlencode(__('file_size_limit')));
            ob_end_clean();
            exit;
        }
    
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        if (!in_array($file['type'], $allowedTypes)) {
            header('HTTP/1.1 400 Bad Request');
            header('X-Upload-Status: error');
            header('X-Upload-Message: ' . rawurlencode(__('invalid_file_type')));
            ob_end_clean();
            exit;
        }
    
        $uploadDir = __DIR__ . '/../../upload/profile/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    
        $targetPath = $uploadDir . $userId . '.webp';
    
        if ($file['type'] !== 'image/svg+xml') {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                header('HTTP/1.1 400 Bad Request');
                header('X-Upload-Status: error');
                header('X-Upload-Message: ' . rawurlencode(__('image_load_error')));
                ob_end_clean();
                exit;
            }
    
            [$width, $height] = $imageInfo;
            if ($width > 256 || $height > 256) {
                header('HTTP/1.1 400 Bad Request');
                header('X-Upload-Status: error');
                header('X-Upload-Message: ' . rawurlencode(__('image_dimension_limit')));
                ob_end_clean();
                exit;
            }
    
            switch ($file['type']) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($file['tmp_name']);
                    break;
                case 'image/webp':
                    $sourceImage = imagecreatefromwebp($file['tmp_name']);
                    break;
                default:
                    header('HTTP/1.1 400 Bad Request');
                    header('X-Upload-Status: error');
                    header('X-Upload-Message: ' . rawurlencode(__('invalid_file_type')));
                    ob_end_clean();
                    exit;
            }
    
            if ($sourceImage === false) {
                header('HTTP/1.1 400 Bad Request');
                header('X-Upload-Status: error');
                header('X-Upload-Message: ' . rawurlencode(__('image_load_error')));
                ob_end_clean();
                exit;
            }
    
            $quality = 80;
            if (imagewebp($sourceImage, $targetPath, $quality)) {
                imagedestroy($sourceImage);
                header('HTTP/1.1 200 OK');
                header('X-Upload-Status: success');
                header('X-Upload-Message: ' . rawurlencode(__('avatar_upload_success')));
            } else {
                imagedestroy($sourceImage);
                header('HTTP/1.1 500 Internal Server Error');
                header('X-Upload-Status: error');
                header('X-Upload-Message: ' . rawurlencode(__('avatar_upload_failed')));
            }
        } else {
            $svgTargetPath = $uploadDir . $userId . '.svg';
            if (move_uploaded_file($file['tmp_name'], $svgTargetPath)) {
                header('HTTP/1.1 200 OK');
                header('X-Upload-Status: success');
                header('X-Upload-Message: ' . rawurlencode(__('avatar_upload_success')));
            } else {
                header('HTTP/1.1 500 Internal Server Error');
                header('X-Upload-Status: error');
                header('X-Upload-Message: ' . rawurlencode(__('avatar_upload_failed')));
            }
        }
        exit;
    }

    /**
     * Handles the profile deletion process.
     *
     * @param int $userId The ID of the user to delete.
     * @return void
     */
    public function handleProfileDelete(int $userId): void
    {
        $connection = $this->db->getConnection();
        
        // Delete user from database
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            exit;
        }

        // Delete avatar files if they exist
        $uploadDir = __DIR__ . '/../../upload/profile/';
        $webpPath = $uploadDir . $userId . '.webp';
        $svgPath = $uploadDir . $userId . '.svg';

        if (file_exists($webpPath)) {
            unlink($webpPath);
        }
        if (file_exists($svgPath)) {
            unlink($svgPath);
        }

        // Logout user
        $user = self::checkLoginStatus();
        if ($user) {
            $user->logout($connection);
        }

        // Redirect to login page on success
        $this->routing->redirect('/profile/login');
        exit;
    }

    // Validation methods
    /**
     * Validates the username.
     *
     * @param string $username The username to validate.
     * @return bool True if the username is valid, false otherwise.
     */
    private function isUsernameValid(string $username): bool
    {
        return filter_var($username, FILTER_VALIDATE_REGEXP, [
            "options" => ["regexp" => '/^[a-zA-Z0-9_]+$/']
        ]) !== false;
    }

    /**
     * Validates the email.
     *
     * @param string $email The email to validate.
     * @return bool True if the email is valid, false otherwise.
     */
    private function isEmailValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validates the password.
     *
     * @param string $password The password to validate.
     * @return bool True if the password is valid, false otherwise.
     */
    private function isPasswordValid(string $password): bool
    {
        return filter_var($password, FILTER_VALIDATE_REGEXP, [
            "options" => ["regexp" => '/^(?=.*\d)(?=.*[^a-zA-Z0-9]).{6,}$/']
        ]) !== false;
    }
}