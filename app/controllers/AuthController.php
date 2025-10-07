<?php

namespace app\controllers;

use app\core\Session;
use app\models\User;
use app\models\Settings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController
{
    private User $userModel;
    private Settings $settingsModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->settingsModel = new Settings();
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        // Check if registration is enabled
        if (!$this->settingsModel->isRegistrationEnabled()) {
            Session::setflash('Registration is currently disabled.', 'warning');
            redirect('/login');
            return;
        }

        view('auth.register');
    }

    /**
     * Handle registration
     */
    public function register(): void
    {
        // Check if registration is enabled
        if (!$this->settingsModel->isRegistrationEnabled()) {
            Session::setflash('Registration is currently disabled.', 'warning');
            redirect('/login');
            return;
        }

        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        // Check if email already exists
        if ($this->userModel->emailExists($email)) {
            $errors[] = 'This email is already registered.';
        }

        if (!empty($errors)) {
            Session::setflash(implode('<br>', $errors), 'danger');
            goback();
            return;
        }

        // Create user
        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 0
        ]);

        if ($userId) {
            Session::setflash('Registration successful! You can now log in.', 'success');
            redirect('/login');
        } else {
            Session::setflash('An error occurred during registration. Please try again.', 'danger');
            goback();
        }
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        view('auth.login');
    }

    /**
     * Handle login
     */
    public function login(): void
    {
        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($email) || empty($password)) {
            Session::setflash('Email and password are required.', 'danger');
            goback();
            return;
        }

        // Verify credentials
        $user = $this->userModel->verifyCredentials($email, $password);

        if (!$user) {
            Session::setflash('Invalid email or password.', 'danger');
            goback();
            return;
        }

        // Check if account is active
        if ($user->active == 0) {
            Session::setflash('Your account has been deactivated. Please contact the administrator.', 'danger');
            goback();
            return;
        }

        // Set session
        Session::setSession($user);

        // Check if user must change password
        if ($user->must_change_password == 1) {
            Session::setflash('You must change your password before continuing.', 'warning');
            redirect('/change-password');
            return;
        }

        Session::setflash('Welcome back, ' . $user->name . '!', 'success');
        redirect('/');
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        Session::destroy();
        Session::setflash('You have been logged out successfully.', 'success');
        redirect('/');
    }

    /**
     * Show change password form
     */
    public function showChangePassword(): void
    {
        // Middleware handles authentication

        view('auth.change-password');
    }

    /**
     * Handle change password
     */
    public function changePassword(): void
    {
        // Middleware handles authentication

        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($oldPassword)) {
            $errors[] = 'Current password is required.';
        }

        if (empty($newPassword)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }

        if (!empty($errors)) {
            Session::setflash(implode('<br>', $errors), 'danger');
            goback();
            return;
        }

        // Change password
        $userId = $_SESSION['id'];
        $success = $this->userModel->changePassword($userId, $oldPassword, $newPassword);

        if ($success) {
            // Reset must_change_password flag if it was set
            $this->userModel->updateUser($userId, ['must_change_password' => 0]);

            Session::setflash('Password changed successfully.', 'success');
            redirect('/');
        } else {
            Session::setflash('Current password is incorrect.', 'danger');
            goback();
        }
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        view('auth.forgot-password');
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(): void
    {
        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setflash('A valid email is required.', 'danger');
            goback();
            return;
        }

        // Generate reset token
        $token = $this->userModel->createPasswordResetToken($email);

        if (!$token) {
            // Don't reveal if email exists or not for security
            Session::setflash('If the email exists in our system, a password reset link has been sent.', 'info');
            redirect('/login');
            return;
        }

        // Send reset email
        $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password?email=' . urlencode($email) . '&token=' . $token;

        $sent = $this->sendPasswordResetEmail($email, $resetLink);

        if ($sent) {
            Session::setflash('If the email exists in our system, a password reset link has been sent.', 'info');
        } else {
            Session::setflash('An error occurred while sending the email. Please try again later.', 'danger');
        }

        redirect('/login');
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(): void
    {
        $email = $_GET['email'] ?? '';
        $token = $_GET['token'] ?? '';

        if (empty($email) || empty($token)) {
            Session::setflash('Invalid password reset link.', 'danger');
            redirect('/login');
            return;
        }

        view('auth.reset-password', [
            'email' => $email,
            'token' => $token
        ]);
    }

    /**
     * Handle reset password
     */
    public function resetPassword(): void
    {
        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($newPassword)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            Session::setflash(implode('<br>', $errors), 'danger');
            goback();
            return;
        }

        // Reset password
        $success = $this->userModel->resetPassword($email, $token, $newPassword);

        if ($success) {
            Session::setflash('Your password has been reset successfully. You can now log in.', 'success');
            redirect('/login');
        } else {
            Session::setflash('Invalid or expired password reset link.', 'danger');
            redirect('/forgot-password');
        }
    }

    /**
     * Send password reset email using PHPMailer
     */
    private function sendPasswordResetEmail(string $email, string $resetLink): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;

            // Recipients
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "
                <h2>Password Reset Request</h2>
                <p>You have requested to reset your password. Click the link below to reset it:</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request this, please ignore this email.</p>
            ";
            $mail->AltBody = "You have requested to reset your password. Copy and paste this link into your browser: {$resetLink}\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: {$mail->ErrorInfo}");
            return false;
        }
    }
}
