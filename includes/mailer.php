<?php
/**
 * Mailer Helper Class
 * 
 * This class serves as a wrapper for email sending functionality.
 * It is designed to be easily integrated with PHPMailer.
 * 
 * INSTRUCTIONS FOR PHPMAILER INTEGRATION:
 * 1. Download PHPMailer via Composer or manually.
 * 2. If using Composer, require 'vendor/autoload.php' in this file.
 * 3. Uncomment the PHPMailer code in the send() method and configure SMTP settings.
 */

class Mailer {
    private $host;
    private $username;
    private $password;
    private $port;
    private $from_email;
    private $from_name;

    public function __construct() {
        // Load SMTP settings from config
        if (file_exists(__DIR__ . '/config.php')) {
            include_once __DIR__ . '/config.php';
        }
        $this->host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.example.com';
        $this->username = defined('SMTP_USER') ? SMTP_USER : '';
        $this->password = defined('SMTP_PASS') ? SMTP_PASS : '';
        $this->port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $this->from_email = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'autajoy2003@gmail.com';
        $this->from_name = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Topaz International School';
        // Try to load Composer autoload (PHPMailer)
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
    }

    /**
     * Get Email Template
     */
    private function getTemplate($body) {
        $year = date('Y');
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #002147; margin-bottom: 20px; }
                .header h1 { color: #002147; margin: 0; }
                .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$this->from_name}</h1>
                </div>
                <div class='content'>
                    $body
                </div>
                <div class='footer'>
                    &copy; $year {$this->from_name}. All rights reserved.<br>
                    This is an automated message, please do not reply.
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Send an email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool True on success, False on failure
     */
    public function send($to, $subject, $body) {
        // Apply Template
        $body = $this->getTemplate($body);

        // Prefer PHPMailer if available
        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $this->host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $this->username;
                $mail->Password   = $this->password;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $this->port;
                $mail->setFrom($this->from_email, $this->from_name);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->AltBody = strip_tags($body);
                $mail->send();
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        // Fallback: native mail()
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . $this->from_name . " <" . $this->from_email . ">\r\n";
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Send Welcome Email to New Student
     */
    public function sendWelcomeEmail($to, $name, $admission_no, $password) {
        $subject = "Welcome to Topaz International School";
        $body = "
            <h2>Welcome, $name!</h2>
            <p>You have been successfully registered at Topaz International School.</p>
            <p><strong>Your Login Credentials:</strong></p>
            <ul>
                <li><strong>Admission No:</strong> $admission_no</li>
                <li><strong>Password:</strong> $password</li>
            </ul>
            <p>Please login and change your password immediately.</p>
            <p>Regards,<br>School Administration</p>
        ";
        return $this->send($to, $subject, $body);
    }

    /**
     * Send Payment Receipt
     */
    public function sendPaymentReceipt($to, $name, $amount, $receipt_no, $purpose) {
        $subject = "Payment Receipt - $receipt_no";
        $body = "
            <h2>Payment Receipt</h2>
            <p>Dear $name,</p>
            <p>We have received your payment of <strong>₦" . number_format($amount, 2) . "</strong>.</p>
            <p><strong>Details:</strong></p>
            <ul>
                <li><strong>Receipt No:</strong> $receipt_no</li>
                <li><strong>Purpose:</strong> $purpose</li>
                <li><strong>Date:</strong> " . date('d M Y') . "</li>
            </ul>
            <p>Thank you.</p>
        ";
        return $this->send($to, $subject, $body);
    }

    /**
     * Send OTP
     */
    public function sendOTP($to, $name, $otp) {
        $subject = "Login Verification Code";
        $body = "
            <h2>Login Verification</h2>
            <p>Dear $name,</p>
            <p>A login attempt was detected from a new device or location.</p>
            <p>Your verification code is:</p>
            <h1 style='color: #003366; letter-spacing: 5px; font-size: 32px; background: #eee; padding: 10px; display: inline-block; border-radius: 5px;'>$otp</h1>
            <p>This code expires in 10 minutes.</p>
            <p>If this wasn't you, please change your password immediately.</p>
        ";
        return $this->send($to, $subject, $body);
    }
}
?>