<?php
// mail_helper.php
//
// Reusable Gmail SMTP sender. Upload PHPMailer's src/ folder next to this
// file (or adjust the require paths below), then call sendEmail() from
// anywhere in the app.
//
// Gmail credentials live in config.php (gitignored, never shared) —
// see config.example.php for the template.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../src/Exception.php';
require __DIR__ . '/../src/PHPMailer.php';
require __DIR__ . '/../src/SMTP.php';

/**
 * Sends an email via Gmail SMTP.
 *
 * @param string $to       Recipient email address
 * @param string $subject  Subject line
 * @param string $bodyHtml HTML body
 * @param string $fromName Display name shown to the recipient
 * @return true|string     true on success, or an error message string on failure
 */
function sendEmail($to, $subject, $bodyHtml, $fromName = 'Org Track') {
    $config = require __DIR__ . '/../config.php';
    $gmailUsername    = $config['mail']['gmail_username'];
    $gmailAppPassword = $config['mail']['gmail_app_password'];

    $mail = new PHPMailer(true);

    try {
        // --- Server settings ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmailUsername;
        $mail->Password   = $gmailAppPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- Recipients ---
        $mail->setFrom($gmailUsername, $fromName);
        $mail->addAddress($to);

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // $mail->ErrorInfo has the underlying SMTP error detail
        return "Mail failed: {$mail->ErrorInfo}";
    }
}