<?php

namespace app\controllers;

use app\core\Session as SE;
use app\models\Settings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MainController extends Controller
{
    private Settings $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new Settings();
    }

    public function guest()
    {
        view('main.guest', ['title' => 'Guest']);
    }

    public function home()
    {
        view('main.home', ['title' => 'Home']);
    }

    public function about()
    {
        view('main.about', ['title' => 'About']);
    }

    public function contact()
    {
        $contactInfo = $this->settingsModel->getContactSettings();
        view('main.contact', [
            'title' => 'Contact',
            'contactInfo' => $contactInfo
        ]);
    }

    public function submitContact()
    {
        // Verify CSRF token
        if (!csrf_verify()) {
            SE::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if (empty($subject)) {
            $errors[] = 'Subject is required.';
        }

        if (empty($message)) {
            $errors[] = 'Message is required.';
        }

        if (!empty($errors)) {
            SE::setflash(implode('<br>', $errors), 'danger');
            goback();
            return;
        }

        // Send email to company
        try {
            $emailSent = $this->sendContactEmail($name, $email, $subject, $message);

            if ($emailSent) {
                SE::setflash("Thank you for contacting us! Your message has been sent. We will get back to you soon.", 'success');
            } else {
                SE::setflash('Failed to send email. Please check the error log for details.', 'danger');
            }
        } catch (\Exception $e) {
            SE::setflash('Error sending email: ' . $e->getMessage(), 'danger');
        }

        redirect('/contact');
    }

    /**
     * Send contact form email to configured recipient
     */
    private function sendContactEmail(string $name, string $email, string $subject, string $message): bool
    {
        try {
            $mail = new PHPMailer(true);

            // Enable debug output if configured
            if (defined('MAIL_DEBUG') && MAIL_DEBUG) {
                $mail->SMTPDebug = MAIL_DEBUG;
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug (Level $level): $str");
                };
            }

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;

            // Get recipient email from .env configuration
            $recipientEmail = defined('CONTACT_MAIL_TO') ? CONTACT_MAIL_TO : MAIL_FROM_ADDRESS;

            // Get company name from settings for display
            $contactInfo = $this->settingsModel->getContactSettings();
            $recipientName = $contactInfo->company_name ?? 'Admin';

            // Log the configuration for debugging
            error_log("Contact Form Email - Sending to: $recipientEmail from: " . MAIL_FROM_ADDRESS);

            // Email settings - send to configured recipient
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($recipientEmail, $recipientName);
            $mail->addReplyTo($email, $name); // Allow direct reply to sender

            $mail->isHTML(true);
            $mail->Subject = 'Contact Form: ' . $subject;

            // Email body
            $mail->Body = "
                <h2>New Contact Form Submission</h2>
                <p><strong>From:</strong> {$name} ({$email})</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <hr>
                <h3>Message:</h3>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                <hr>
                <p style='color: #6c757d; font-size: 12px;'>
                    This message was sent via the contact form on " . $_SERVER['HTTP_HOST'] . "
                </p>
            ";

            $mail->AltBody = "New Contact Form Submission\n\n"
                . "From: {$name} ({$email})\n"
                . "Subject: {$subject}\n\n"
                . "Message:\n{$message}\n\n"
                . "---\n"
                . "This message was sent via the contact form on " . $_SERVER['HTTP_HOST'];

            $mail->send();
            error_log("Contact form email sent successfully to: $recipientEmail");
            return true;
        } catch (Exception $e) {
            error_log('Failed to send contact email: ' . $e->getMessage());
            error_log('PHPMailer Error Info: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
