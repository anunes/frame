-- SQLite Database Schema
-- This schema is specifically designed for SQLite databases
-- Differences from MySQL:
--   - Uses AUTOINCREMENT instead of AUTO_INCREMENT
--   - Uses INTEGER PRIMARY KEY for auto-increment
--   - No ENGINE or CHARSET specifications
--   - Uses DATETIME instead of TIMESTAMP
--   - Different UNIQUE constraint syntax

-- Migrations tracking table
CREATE TABLE IF NOT EXISTS migrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  migration VARCHAR(255) NOT NULL,
  batch INTEGER NOT NULL,
  migrated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role INTEGER NOT NULL DEFAULT 0,  -- 0=user, 1=admin
  avatar VARCHAR(255) DEFAULT NULL,
  active INTEGER NOT NULL DEFAULT 1,  -- 0=inactive, 1=active
  must_change_password INTEGER NOT NULL DEFAULT 0,  -- 1=must change password on next login
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(100) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create index on token for faster lookups
CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Default settings (using INSERT OR IGNORE to avoid duplicates on re-run)
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
  ('contact_info', '{"company_name":"Your Company Name","address":"123 Main Street","postal_code":"12345","city":"Your City","email":"info@example.com","phone":"+1 (555) 123-4567"}'),
  ('copyright_text', '© 2006-{year} @nunes.net'),
  ('registration_enabled', '1'),
  ('site_logo', '/assets/img/logo.png');

-- Default admin user
-- Email: admin@an.com
-- Password: admin (CHANGE THIS IMMEDIATELY after first login!)
INSERT OR IGNORE INTO users (name, email, password, role, active) VALUES
  ('Administrator', 'admin@an.com', '$2y$12$omG.eXGrl3GXxXDHlhB8o.7lSYXMFe5q1uIoxohXUAwNofOEPhGRu', 1, 1);
