<?php

namespace app\controllers;

use app\core\Session as SE;
use app\models\Settings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MainController extends Controller
{
    private const CONTACT_CAPTCHA_SESSION_KEY = '_contact_captcha';
    private const CONTACT_OLD_INPUT_SESSION_KEY = '_contact_old_input';
    private const CONTACT_CAPTCHA_TTL = 600;

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
        $oldInput = $_SESSION[self::CONTACT_OLD_INPUT_SESSION_KEY] ?? [];
        unset($_SESSION[self::CONTACT_OLD_INPUT_SESSION_KEY]);

        view('main.contact', [
            'title' => 'Contact',
            'contactInfo' => $contactInfo,
            'oldInput' => is_array($oldInput) ? $oldInput : [],
        ]);
    }

    public function contactCaptcha()
    {
        $code = $this->issueContactCaptchaCode();

        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        if (function_exists('imagecreatetruecolor')) {
            $this->renderContactCaptchaPng($code);
            return;
        }

        $this->renderContactCaptchaSvg($code);
    }

    public function submitContact()
    {
        // Verify CSRF token
        if (!csrf_verify()) {
            SE::setflash('Invalid security token. Please try again.', 'danger');
            redirect('/contact');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $captcha = strtoupper(trim((string) ($_POST['captcha'] ?? '')));

        $this->storeContactOldInput([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

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

        if (!$this->validateContactCaptcha($captcha)) {
            $errors[] = 'The CAPTCHA code is incorrect or has expired. Please try again.';
        }

        if (!empty($errors)) {
            SE::setflash(implode(' ', $errors), 'danger');
            redirect('/contact');
            return;
        }

        // Send email to company
        try {
            $emailSent = $this->sendContactEmail($name, $email, $subject, $message);

            if ($emailSent) {
                unset($_SESSION[self::CONTACT_OLD_INPUT_SESSION_KEY]);
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
                    This message was sent via the contact form on " . app_host() . "
                </p>
            ";

            $mail->AltBody = "New Contact Form Submission\n\n"
                . "From: {$name} ({$email})\n"
                . "Subject: {$subject}\n\n"
                . "Message:\n{$message}\n\n"
                . "---\n"
                . "This message was sent via the contact form on " . app_host();

            $mail->send();
            error_log("Contact form email sent successfully to: $recipientEmail");
            return true;
        } catch (Exception $e) {
            error_log('Failed to send contact email: ' . $e->getMessage());
            error_log('PHPMailer Error Info: ' . $mail->ErrorInfo);
            return false;
        }
    }

    private function storeContactOldInput(array $data): void
    {
        $_SESSION[self::CONTACT_OLD_INPUT_SESSION_KEY] = [
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'subject' => trim((string) ($data['subject'] ?? '')),
            'message' => trim((string) ($data['message'] ?? '')),
        ];
    }

    private function issueContactCaptchaCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxIndex = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }

        $_SESSION[self::CONTACT_CAPTCHA_SESSION_KEY] = [
            'code' => $code,
            'generated_at' => time(),
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return $code;
    }

    private function validateContactCaptcha(string $input): bool
    {
        $captcha = $_SESSION[self::CONTACT_CAPTCHA_SESSION_KEY] ?? null;
        unset($_SESSION[self::CONTACT_CAPTCHA_SESSION_KEY]);

        if (!is_array($captcha)) {
            return false;
        }

        $code = strtoupper((string) ($captcha['code'] ?? ''));
        $generatedAt = (int) ($captcha['generated_at'] ?? 0);
        $input = strtoupper(preg_replace('/\s+/', '', $input) ?? '');

        if ($code === '' || $generatedAt <= 0) {
            return false;
        }

        if ((time() - $generatedAt) > self::CONTACT_CAPTCHA_TTL) {
            return false;
        }

        return $input !== '' && hash_equals($code, $input);
    }

    private function renderContactCaptchaPng(string $code): void
    {
        $width = 170;
        $height = 56;
        $image = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($image, 248, 249, 250);
        $border = imagecolorallocate($image, 206, 212, 218);
        $text = imagecolorallocate($image, 33, 37, 41);
        $line = imagecolorallocate($image, 173, 181, 189);
        $dot = imagecolorallocate($image, 108, 117, 125);

        imagefill($image, 0, 0, $background);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);

        for ($i = 0; $i < 6; $i++) {
            imageline(
                $image,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $line
            );
        }

        for ($i = 0; $i < 140; $i++) {
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $dot);
        }

        $font = 5;
        $x = 18;
        $chars = str_split($code);

        foreach ($chars as $char) {
            imagestring($image, $font, $x, random_int(14, 22), $char, $text);
            $x += 28;
        }

        if (!headers_sent()) {
            header('Content-Type: image/png');
        }

        imagepng($image);
        imagedestroy($image);
    }

    private function renderContactCaptchaSvg(string $code): void
    {
        if (!headers_sent()) {
            header('Content-Type: image/svg+xml; charset=UTF-8');
        }

        $lines = '';
        for ($i = 0; $i < 6; $i++) {
            $lines .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#adb5bd" stroke-width="1" />',
                random_int(0, 170),
                random_int(0, 56),
                random_int(0, 170),
                random_int(0, 56)
            );
        }

        echo sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="170" height="56" viewBox="0 0 170 56" role="img" aria-label="CAPTCHA image"><rect width="170" height="56" fill="#f8f9fa" stroke="#ced4da"/>%s<text x="18" y="36" font-family="monospace" font-size="26" letter-spacing="6" fill="#212529">%s</text></svg>',
            $lines,
            htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
        );
    }
}
