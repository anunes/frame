<?php

namespace app\controllers;

use app\core\Session;
use app\models\User;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Show user profile
     */
    public function showProfile(): void
    {
        // Middleware handles authentication - no need to check here

        // Get fresh user data from database to ensure avatar is current
        $user = $this->userModel->findById($_SESSION['id']);

        if ($user) {
            // Update session with fresh data
            Session::setSession($user);
        }

        view('user.profile');
    }

    /**
     * Show edit profile form
     */
    public function showEditProfile(): void
    {
        // Middleware handles authentication - no need to check here

        // Get fresh user data from database to ensure avatar is current
        $user = $this->userModel->findById($_SESSION['id']);

        if ($user) {
            // Update session with fresh data
            Session::setSession($user);
        }

        view('user.edit-profile');
    }

    /**
     * Handle profile update
     */
    public function updateProfile(): void
    {
        // Middleware handles authentication - no need to check here

        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $userId = $_SESSION['id'];

        // Validation
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        // Check if email is already taken by another user
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $existingUser->id != $userId) {
            $errors[] = 'This email is already registered to another account.';
        }

        if (!empty($errors)) {
            Session::setflash(implode('<br>', $errors), 'danger');
            goback();
            return;
        }

        $updateData = [
            'name' => $name,
            'email' => $email
        ];

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = $this->handleAvatarUpload($_FILES['avatar'], $userId);
            if ($avatarPath) {
                $updateData['avatar'] = $avatarPath;
            } else {
                Session::setflash('Failed to upload avatar. Profile updated without avatar.', 'warning');
            }
        }

        // Update user
        $success = $this->userModel->updateUser($userId, $updateData);

        if ($success) {
            // Update session data
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            if (isset($updateData['avatar'])) {
                $_SESSION['avatar'] = $updateData['avatar'];
            }

            Session::setflash('Profile updated successfully!', 'success');
            redirect('/profile');
        } else {
            Session::setflash('An error occurred while updating your profile. Please try again.', 'danger');
            goback();
        }
    }

    /**
     * Serve avatar image securely
     */
    public function serveAvatar(string $filename): void
    {
        $avatarPath = BASE_PATH . '/storage/uploads/avatars/' . basename($filename);

        // Security: Only allow files that exist and are in the avatars directory
        if (!file_exists($avatarPath) || !is_file($avatarPath)) {
            http_response_code(404);
            exit;
        }

        // Get file info
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $avatarPath);
        finfo_close($finfo);

        // Only serve image files
        if (!str_starts_with($mimeType, 'image/')) {
            http_response_code(403);
            exit;
        }

        // Set headers for image
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($avatarPath));
        header('Cache-Control: public, max-age=31536000'); // Cache for 1 year

        // Output image
        readfile($avatarPath);
        exit;
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

        // First check: client-provided MIME type
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        // Second check: verify actual file type using finfo (prevents MIME spoofing)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($actualMime, $allowedTypes)) {
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
     * Deactivate user account (soft delete)
     */
    public function deleteAccount(): void
    {
        // Middleware handles authentication - no need to check here

        // Verify CSRF token
        if (!csrf_verify()) {
            Session::setflash('Invalid security token. Please try again.', 'danger');
            goback();
            return;
        }

        $userId = $_SESSION['id'];

        // Deactivate account
        $success = $this->userModel->updateUser($userId, ['active' => 0]);

        if ($success) {
            // Log out the user
            Session::destroy();
            Session::setflash('Your account has been deactivated successfully.', 'info');
            redirect('/');
        } else {
            Session::setflash('An error occurred while deactivating your account. Please try again.', 'danger');
            goback();
        }
    }
}
