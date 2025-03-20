<?php
namespace app\models;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Emailer {
    private static $instance = null;
    private $mailer;

    /**
     * Private constructor to initialize PHPMailer with SMTP settings.
     */
    private function __construct() {
        $this->mailer = new PHPMailer(true);
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port = $_ENV['SMTP_PORT'];
            $this->mailer->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = "UTF-8";
        } catch (Exception $e) {
            throw new Exception("SMTP connection failed: " . $e->getMessage());
        }
    }

    /**
     * Singleton pattern - initializes the Emailer instance only once.
     *
     * @return Emailer The singleton instance of Emailer.
     */
    public static function getInstance(): Emailer {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Sends an email using a template.
     *
     * @param string $toEmail The recipient's email address.
     * @param string $templateName The name of the template (without .html, e.g., 'reset_password').
     * @param string $subject The subject of the email.
     * @param array $variables Associative array of variables to replace in the template.
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public function sendEmail(string $toEmail, string $templateName, string $subject, array $variables = []): bool {
        try {
            $templatePath = __DIR__ . '/../email/' . $templateName . '.html';
            if (!file_exists($templatePath)) {
                throw new Exception("Email template not found: " . $templatePath);
            }

            $template = file_get_contents($templatePath);
            $processedTemplate = $template;

            foreach ($variables as $key => $value) {
                $processedTemplate = str_replace('{{' . $key . '}}', $value, $processedTemplate);
            }

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $processedTemplate;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
}