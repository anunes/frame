<?php

namespace app\models;

use app\core\Database;

class Settings extends Database
{
    /**
     * Get a setting value by key
     */
    public function get(string $key, $default = null)
    {
        $sql = "SELECT * FROM settings WHERE setting_key = ? LIMIT 1";
        $result = $this->row($sql, [$key]);

        return $result ? $result->setting_value : $default;
    }

    /**
     * Set a setting value by key
     */
    public function set(string $key, $value): bool
    {
        // Check if setting exists
        $existing = $this->row("SELECT * FROM settings WHERE setting_key = ?", [$key]);

        if ($existing) {
            // Update existing
            $rowsAffected = $this->update('settings', [
                'setting_value' => $value
            ], ['setting_key' => $key]);

            return $rowsAffected >= 0;
        } else {
            // Insert new
            $id = $this->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value
            ]);

            return $id > 0;
        }
    }

    /**
     * Get contact settings
     */
    public function getContactSettings(): object
    {
        $sql = "SELECT * FROM settings WHERE setting_key = 'contact_info' LIMIT 1";
        $result = $this->row($sql);

        if ($result) {
            $data = json_decode($result->setting_value);
            return (object)[
                'company_name' => $data->company_name ?? '',
                'address' => $data->address ?? '',
                'postal_code' => $data->postal_code ?? '',
                'city' => $data->city ?? '',
                'email' => $data->email ?? '',
                'phone' => $data->phone ?? ''
            ];
        }

        // Return defaults if not found
        return (object)[
            'company_name' => '',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'email' => '',
            'phone' => ''
        ];
    }

    /**
     * Update contact settings
     */
    public function updateContactSettings(array $data): bool
    {
        $settingValue = json_encode($data);

        // Check if setting exists
        $existing = $this->row("SELECT * FROM settings WHERE setting_key = 'contact_info'");

        if ($existing) {
            // Update existing
            $rowsAffected = $this->update('settings', [
                'setting_value' => $settingValue
            ], ['setting_key' => 'contact_info']);

            return $rowsAffected > 0;
        } else {
            // Insert new
            $id = $this->insert('settings', [
                'setting_key' => 'contact_info',
                'setting_value' => $settingValue
            ]);

            return $id > 0;
        }
    }

    /**
     * Check if registration is enabled
     */
    public function isRegistrationEnabled(): bool
    {
        $sql = "SELECT * FROM settings WHERE setting_key = 'registration_enabled' LIMIT 1";
        $result = $this->row($sql);

        if ($result) {
            return (bool)$result->setting_value;
        }

        // Default to enabled if not set
        return true;
    }

    /**
     * Set registration enabled/disabled
     */
    public function setRegistrationEnabled(bool $enabled): bool
    {
        $settingValue = $enabled ? '1' : '0';

        // Check if setting exists
        $existing = $this->row("SELECT * FROM settings WHERE setting_key = 'registration_enabled'");

        if ($existing) {
            // Update existing
            $rowsAffected = $this->update('settings', [
                'setting_value' => $settingValue
            ], ['setting_key' => 'registration_enabled']);

            return $rowsAffected >= 0; // 0 is ok if value didn't change
        } else {
            // Insert new
            $id = $this->insert('settings', [
                'setting_key' => 'registration_enabled',
                'setting_value' => $settingValue
            ]);

            return $id > 0;
        }
    }

    /**
     * Get logo path
     */
    public function getLogo(): ?string
    {
        $sql = "SELECT * FROM settings WHERE setting_key = 'site_logo' LIMIT 1";
        $result = $this->row($sql);

        if ($result && $result->setting_value) {
            return $result->setting_value;
        }

        // Return default logo
        return '/assets/img/logo.png';
    }

    /**
     * Set logo path
     */
    public function setLogo(string $logoPath): bool
    {
        // Check if setting exists
        $existing = $this->row("SELECT * FROM settings WHERE setting_key = 'site_logo'");

        if ($existing) {
            // Update existing
            $rowsAffected = $this->update('settings', [
                'setting_value' => $logoPath
            ], ['setting_key' => 'site_logo']);

            return $rowsAffected >= 0;
        } else {
            // Insert new
            $id = $this->insert('settings', [
                'setting_key' => 'site_logo',
                'setting_value' => $logoPath
            ]);

            return $id > 0;
        }
    }

    /**
     * Get copyright text
     */
    public function getCopyrightText(): string
    {
        $sql = "SELECT * FROM settings WHERE setting_key = 'copyright_text' LIMIT 1";
        $result = $this->row($sql);

        if ($result && $result->setting_value) {
            return $result->setting_value;
        }

        // Return default copyright text
        return '&copy; 2006-' . date('Y') . ' @nunes.net';
    }

    /**
     * Set copyright text
     */
    public function setCopyrightText(string $copyrightText): bool
    {
        // Check if setting exists
        $existing = $this->row("SELECT * FROM settings WHERE setting_key = 'copyright_text'");

        if ($existing) {
            // Update existing
            $rowsAffected = $this->update('settings', [
                'setting_value' => $copyrightText
            ], ['setting_key' => 'copyright_text']);

            return $rowsAffected >= 0;
        } else {
            // Insert new
            $id = $this->insert('settings', [
                'setting_key' => 'copyright_text',
                'setting_value' => $copyrightText
            ]);

            return $id > 0;
        }
    }
}
