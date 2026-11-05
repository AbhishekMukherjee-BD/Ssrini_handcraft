<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer and Gmail SMTP.
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email HTML body
 * @return bool True if email sent successfully, false otherwise
 */
function sendEmailNotification($to, $subject, $body) {
    require_once __DIR__ . '/env.php';
    $env = get_app_env();
    
    // SMTP Configuration
    $smtpHost = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
    $smtpPort = $env['SMTP_PORT'] ?? 587;
    $smtpUser = $env['SMTP_USER'] ?? '';
    $smtpPass = $env['SMTP_PASS'] ?? '';
    
    $storeName = $env['STORE_NAME'] ?? 'Ssrini Handicrafts';
    $fromEmail = $smtpUser; // Use authenticated user as sender

    if (empty($smtpUser) || empty($smtpPass)) {
        error_log('SMTP credentials are not configured.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;

        // Recipients
        $mail->setFrom($fromEmail, $storeName);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
