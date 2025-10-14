<?php

namespace app\controllers;

use app\core\Session;
use app\models\User;
use app\models\Settings;

class AdminController
{
    private User $userModel;
    private Settings $settingsModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->settingsModel = new Settings();
    }

    /**
     * Show admin dashboard
     */
    public function index(): void
    {
        // Middleware handles authentication and admin check

        view('admin.dashboard');
    }

    /**
     * Get users with pagination and search
     */
    public function getUsers(): void
    {
        // Middleware handles authentication and admin check

        $search = trim($_GET['search'] ?? '');
        $page = (int)($_GET['page'] ?? 1);
        $perPage = $_GET['per_page'] ?? 5;
        $status = $_GET['status'] ?? 'active'; // Default to active only

        // Handle "all" option
        if ($perPage === 'all') {
            $perPage = PHP_INT_MAX;
            $page = 1;
        } else {
            $perPage = (int)$perPage;
        }

        $result = $this->userModel->getPaginated($page, $perPage, $search, $status);

        view('admin.users', [
            'users' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $_GET['per_page'] ?? 5,
            'totalPages' => $result['totalPages'],
            'search' => $search,
            'status' => $status
        ]);
    }

    /**
     * Show user edit form
     */
    public function editUser(string $id): void
    {
        // Middleware handles authentication and admin check

        $user = $this->userModel->findById((int)$id);

        if (!$user) {
            Session::setflash('User not found.', 'danger');
            redirect('/admin');
            return;
        }

        view('admin.user-edit', ['user' => $user]);
    }

    /**
     * Update user
     */
    public function updateUser(string $id): void
    {
        // Middleware handles authentication and admin check

        $id = (int)$id;

        if (!csrf_verify()) {
            Session::setflash('Invalid security token.', 'danger');
            goback();
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = (int)($_POST['role'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }

        // Check if email is taken by another user
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $existingUser->id != $id) {
            $errors[] = 'Email already in use.';
        }

        if (!empty($errors)) {
            Session::setflash(implode('<br>', $errors), 'danger');
            goback();
            return;
        }

        // Handle avatar upload
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = $this->handleAvatarUpload($_FILES['avatar'], $id);
            if (!$avatarPath) {
                Session::setflash('Failed to upload avatar.', 'danger');
                goback();
                return;
            }
        }

        $updateData = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'active' => $active
        ];

        if ($avatarPath) {
            $updateData['avatar'] = $avatarPath;
        }

        $success = $this->userModel->updateUser($id, $updateData);

        if ($success) {
            Session::setflash('User updated successfully!', 'success');
            redirect('/admin/users/' . $id);
        } else {
            Session::setflash('Failed to update user.', 'danger');
            goback();
        }
    }

    /**
     * Show create user form
     */
    public function showCreateUser(): void
    {
        // Middleware handles authentication and admin check

        view('admin.user-create');
    }

    /**
     * Handle user creation
     */
    public function createUser(): void
    {
        // Middleware handles authentication and admin check

        if (!csrf_verify()) {
            Session::setflash('Invalid security token.', 'danger');
            goback();
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = (int)($_POST['role'] ?? 0);

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
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

        // Generate random password
        $tempPassword = generate_random_password(6);

        // Create user
        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $tempPassword,
            'role' => $role
        ]);

        if ($userId) {
            // Set must_change_password flag
            $this->userModel->updateUser($userId, ['must_change_password' => 1]);

            // Send welcome email
            $emailSent = $this->sendWelcomeEmail($name, $email, $tempPassword);

            if ($emailSent) {
                Session::setflash('User created successfully! Welcome email sent to ' . $email, 'success');
            } else {
                Session::setflash('User created but failed to send email. Temporary password: ' . $tempPassword, 'warning');
            }

            redirect('/admin/users/' . $userId);
        } else {
            Session::setflash('Failed to create user.', 'danger');
            goback();
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(): void
    {
        // Disable error display to prevent HTML errors in JSON response
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');
        error_reporting(0);

        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Start fresh output buffer
        ob_start();

        try {
            // Set JSON header
            header('Content-Type: application/json');

            // Check admin without redirect for AJAX
            if (!Session::loggedIn() || !Session::user()->isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                ob_end_flush();
                exit;
            }

            // Verify CSRF token - check both possible field names and don't rotate
            $token = $_POST['csrf_token'] ?? $_POST['_csrf'] ?? null;
            if (!csrf_verify($token, false)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                ob_end_flush();
                exit;
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            $active = (int)($_POST['active'] ?? 0);

            if ($userId === 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                ob_end_flush();
                exit;
            }

            if ($userId === (int)$_SESSION['id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot deactivate yourself']);
                ob_end_flush();
                exit;
            }

            $success = $this->userModel->updateUser($userId, ['active' => $active]);

            echo json_encode(['success' => $success]);
            ob_end_flush();
            exit;
        } catch (\Throwable $e) {
            ob_end_clean();
            // Log the error for debugging
            error_log('Toggle user status error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode(['success' => false, 'message' => 'Server error occurred']);
            exit;
        } finally {
            // Restore error display setting
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    /**
     * Delete user permanently from database
     */
    public function deleteUser(): void
    {
        // Disable error display to prevent HTML errors in JSON response
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', '0');
        error_reporting(0);

        // Clean any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Start fresh output buffer
        ob_start();

        try {
            // Set JSON header
            header('Content-Type: application/json');

            // Check admin without redirect for AJAX
            if (!Session::loggedIn() || !Session::user()->isAdmin()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                ob_end_flush();
                exit;
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['csrf_token'] ?? null;
            $userId = (int)($input['user_id'] ?? 0);

            // Verify CSRF token - don't rotate
            if (!csrf_verify($token, false)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                ob_end_flush();
                exit;
            }

            if ($userId === 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                ob_end_flush();
                exit;
            }

            // Prevent deleting yourself
            if ($userId === (int)$_SESSION['id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
                ob_end_flush();
                exit;
            }

            // Get user info before deletion (for avatar cleanup)
            $user = $this->userModel->findById($userId);

            // Delete user from database
            $success = $this->userModel->deleteUser($userId);

            // Delete avatar file if exists
            if ($success && $user && $user->avatar) {
                $avatarPath = BASE_PATH . '/storage/uploads/avatars/' . $user->avatar;
                if (file_exists($avatarPath)) {
                    @unlink($avatarPath);
                }
            }

            echo json_encode(['success' => $success]);
            ob_end_flush();
            exit;
        } catch (\Throwable $e) {
            ob_end_clean();
            error_log('Delete user error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode(['success' => false, 'message' => 'Server error occurred']);
            exit;
        } finally {
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    /**
     * Show contact settings
     */
    public function showSettings(): void
    {
        // Middleware handles authentication and admin check

        $settings = $this->settingsModel->getContactSettings();
        $logo = $this->settingsModel->getLogo();
        $copyrightText = $this->settingsModel->getCopyrightText();

        view('admin.settings', [
            'settings' => $settings,
            'logo' => $logo,
            'copyrightText' => $copyrightText
        ]);
    }

    /**
     * Show registration settings
     */
    public function showRegistrationSettings(): void
    {
        // Middleware handles authentication and admin check

        $registrationEnabled = $this->settingsModel->isRegistrationEnabled();

        view('admin.registration-settings', [
            'registrationEnabled' => $registrationEnabled
        ]);
    }

    /**
     * Update contact settings
     */
    public function updateSettings(): void
    {
        // Middleware handles authentication and admin check

        if (!csrf_verify()) {
            Session::setflash('Invalid security token.', 'danger');
            goback();
            return;
        }

        // Update contact settings
        $data = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];

        $contactSuccess = $this->settingsModel->updateContactSettings($data);

        // Update copyright text
        $copyrightText = trim($_POST['copyright_text'] ?? '');
        $copyrightSuccess = true;
        if (!empty($copyrightText)) {
            $copyrightSuccess = $this->settingsModel->setCopyrightText($copyrightText);
        }

        // Handle logo upload
        $logoSuccess = true;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoPath = $this->handleLogoUpload($_FILES['logo']);
            if ($logoPath) {
                $logoSuccess = $this->settingsModel->setLogo($logoPath);
            } else {
                Session::setflash('Failed to upload logo. Settings updated without logo change.', 'warning');
            }
        }

        if ($contactSuccess && $logoSuccess && $copyrightSuccess) {
            Session::setflash('Settings updated successfully!', 'success');
        } else {
            Session::setflash('Some settings failed to update.', 'warning');
        }

        redirect('/admin');
    }

    /**
     * Update registration settings
     */
    public function updateRegistrationSettings(): void
    {
        // Middleware handles authentication and admin check

        if (!csrf_verify()) {
            Session::setflash('Invalid security token.', 'danger');
            goback();
            return;
        }

        // Update registration setting
        $registrationEnabled = isset($_POST['registration_enabled']) ? true : false;
        $success = $this->settingsModel->setRegistrationEnabled($registrationEnabled);

        if ($success) {
            Session::setflash('Registration settings updated successfully!', 'success');
        } else {
            Session::setflash('Failed to update registration settings.', 'danger');
        }

        redirect('/admin');
    }

    /**
     * Handle avatar upload with optimization
     */
    private function handleAvatarUpload(array $file, int $userId): ?string
    {
        $uploadDir = BASE_PATH . '/storage/uploads/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        // Delete old avatar if exists
        $user = $this->userModel->findById($userId);
        if ($user && $user->avatar) {
            $oldAvatar = $uploadDir . $user->avatar;
            if (file_exists($oldAvatar)) {
                unlink($oldAvatar);
            }
        }

        // Create image resource from uploaded file
        $imageResource = null;
        switch ($file['type']) {
            case 'image/jpeg':
                $imageResource = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imageResource = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $imageResource = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                $imageResource = imagecreatefromwebp($file['tmp_name']);
                break;
        }

        if (!$imageResource) {
            return null;
        }

        // Get original dimensions
        $originalWidth = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Set target size for avatars (300x300)
        $targetSize = 300;

        // Calculate crop dimensions to maintain aspect ratio (square crop)
        $minDimension = min($originalWidth, $originalHeight);
        $srcX = ($originalWidth - $minDimension) / 2;
        $srcY = ($originalHeight - $minDimension) / 2;

        // Create new image with target size
        $newImage = imagecreatetruecolor($targetSize, $targetSize);

        // Preserve transparency for PNG
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);

        // Resize and crop to square
        imagecopyresampled(
            $newImage,
            $imageResource,
            0, 0,
            $srcX, $srcY,
            $targetSize, $targetSize,
            $minDimension, $minDimension
        );

        // Determine output format (WebP if supported, otherwise JPG)
        $useWebP = function_exists('imagewebp');
        $extension = $useWebP ? 'webp' : 'jpg';
        $fileName = 'user_' . $userId . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $fileName;

        // Save optimized image
        $success = false;
        if ($useWebP) {
            $success = imagewebp($newImage, $destination, 85); // 85% quality
        } else {
            $success = imagejpeg($newImage, $destination, 85); // 85% quality
        }

        // Free memory
        imagedestroy($imageResource);
        imagedestroy($newImage);

        return $success ? $fileName : null;
    }

    /**
     * Handle logo upload
     */
    private function handleLogoUpload(array $file): ?string
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        // Check file size (max 2MB for logo)
        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        // Delete old logo if it's custom (not the default)
        $currentLogo = $this->settingsModel->getLogo();
        if ($currentLogo && $currentLogo !== '/assets/img/logo.png') {
            $oldLogo = $_SERVER['DOCUMENT_ROOT'] . $currentLogo;
            if (file_exists($oldLogo)) {
                unlink($oldLogo);
            }
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'logo_' . time() . '.' . $extension;
        $destination = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/assets/img/' . $fileName;
        }

        return null;
    }

    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail(string $name, string $email, string $tempPassword): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;

            // Email settings
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to ' . MAIL_FROM_NAME . ' - Your Account Details';

            // Build login URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $loginUrl = $protocol . $_SERVER['HTTP_HOST'] . '/login';

            // Email body
            $mail->Body = "
                <h2>Welcome to " . MAIL_FROM_NAME . "!</h2>
                <p>Hi <strong>{$name}</strong>,</p>
                <p>An account has been created for you. Here are your login credentials:</p>
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;'>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Temporary Password:</strong> <code style='background-color: #fff; padding: 5px 10px; border-radius: 3px;'>{$tempPassword}</code></p>
                </div>
                <p><strong>⚠️ Important:</strong> You will be required to change your password on your first login for security reasons.</p>
                <p style='margin: 30px 0;'>
                    <a href='{$loginUrl}' style='background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Login Now
                    </a>
                </p>
                <p>If you have any questions, please don't hesitate to contact us.</p>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #dee2e6;'>
                <p style='color: #6c757d; font-size: 12px;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            ";

            $mail->AltBody = "Welcome to " . MAIL_FROM_NAME . "!\n\n"
                . "Hi {$name},\n\n"
                . "An account has been created for you. Here are your login credentials:\n\n"
                . "Email: {$email}\n"
                . "Temporary Password: {$tempPassword}\n\n"
                . "IMPORTANT: You will be required to change your password on your first login for security reasons.\n\n"
                . "Login at: {$loginUrl}\n\n"
                . "If you have any questions, please don't hesitate to contact us.";

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }

}
