-- Migration 003: Safe Repair of Admin Roles, Permissions & Foreign Key Integrity
-- Preserves all existing users, orders, wallets, transactions, and application data.
-- Never disables foreign key checks.

SET NAMES utf8mb4;

-- 1. Ensure roles, permissions, role_permissions, and admins tables exist with compatible INT UNSIGNED keys
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(60) NOT NULL,
  `perm_key` VARCHAR(100) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_key` (`perm_key`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_username` (`username`),
  UNIQUE KEY `uk_admins_email` (`email`),
  KEY `idx_admins_role` (`role_id`),
  CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed all default roles idempotently (by unique slug, never assuming role ID = 1)
INSERT IGNORE INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Full unrestricted access to all modules and security settings', 1),
('Admin', 'admin', 'Full operational access excluding destructive system overrides', 1),
('Support Staff', 'support_staff', 'Manage support tickets, view orders and user profiles', 1),
('Finance Manager', 'finance_manager', 'Manage deposits, manual payment approvals, wallet ledger and financial reports', 1),
('Order Manager', 'order_manager', 'Manage orders, refills, services, categories and providers', 1);

-- 3. Ensure core permissions exist
INSERT IGNORE INTO `permissions` (`module`, `perm_key`, `label`, `description`) VALUES
('dashboard', 'dashboard.view', 'View Admin Dashboard', 'Access admin overview and KPIs'),
('users', 'users.view', 'View Users', 'View user directory and profiles'),
('users', 'users.create', 'Create Users', 'Create new user accounts'),
('users', 'users.edit', 'Edit Users', 'Update user status, verification, and details'),
('users', 'users.delete', 'Delete Users', 'Remove user accounts'),
('users', 'users.wallet', 'Manage User Wallet', 'Add or deduct user wallet balance'),
('orders', 'orders.view', 'View Orders', 'View all panel orders'),
('orders', 'orders.edit', 'Edit Orders', 'Update order status and details'),
('orders', 'orders.cancel', 'Cancel Orders', 'Cancel and refund orders'),
('orders', 'orders.refill', 'Manage Refills', 'Process order refill requests'),
('orders', 'orders.export', 'Export Orders', 'Export order reports'),
('categories', 'categories.manage', 'Manage Categories', 'Create, edit, sort, and delete categories'),
('services', 'services.view', 'View Services', 'View services catalog'),
('services', 'services.manage', 'Manage Services', 'Create, edit, delete, and bulk manage services'),
('providers', 'providers.view', 'View Providers', 'View API providers and logs'),
('providers', 'providers.manage', 'Manage Providers', 'Add, edit, test, and import from providers'),
('payments', 'payments.view', 'View Payments', 'View deposits and gateway logs'),
('payments', 'payments.approve', 'Approve Manual Payments', 'Approve or reject manual deposit requests'),
('gateways', 'gateways.manage', 'Manage Payment Gateways', 'Configure Razorpay and Manual gateways'),
('wallet', 'wallet.view', 'View Wallet Ledger', 'Inspect wallet transactions'),
('currency', 'currency.manage', 'Manage Currencies & Rates', 'Configure currencies, auto-update sources, and manual rates'),
('referrals', 'referrals.manage', 'Manage Referrals', 'Approve/reject referral commissions and settings'),
('child_panels', 'child_panels.manage', 'Manage Child Panels', 'Create, approve, suspend, and configure child panels'),
('tickets', 'tickets.view', 'View Support Tickets', 'Read user tickets'),
('tickets', 'tickets.reply', 'Reply & Manage Tickets', 'Reply, assign, and close tickets'),
('notifications', 'notifications.manage', 'Manage Notifications', 'Send notifications and manage announcements'),
('website', 'website.manage', 'Manage Public Website', 'Edit landing hero, FAQ, about, terms, privacy, SEO'),
('themes', 'themes.manage', 'Manage Themes', 'Configure Classic theme settings'),
('reports', 'reports.view', 'View & Export Reports', 'Access sales, profit, and audit reports'),
('roles', 'roles.manage', 'Manage Roles & Permissions', 'Create custom roles and assign permissions'),
('security', 'security.view', 'View Security & Logs', 'Inspect login attempts, activity logs, error logs'),
('settings', 'settings.manage', 'Manage System Settings', 'Configure SMTP, backups, maintenance, cron, health');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p WHERE r.`slug` = 'super_admin';

-- 4. Repair any existing admin rows pointing to a missing role_id (if any) without dropping data
UPDATE `admins` a
LEFT JOIN `roles` r ON r.`id` = a.`role_id`
SET a.`role_id` = (SELECT `id` FROM `roles` WHERE `slug` = 'super_admin' LIMIT 1)
WHERE r.`id` IS NULL;

-- 5. Insert initial default admin ONLY if username 'admin' does not already exist,
-- resolving Super Admin role_id dynamically from MySQL.
-- Default password hash below corresponds to 'admin123' (PASSWORD_DEFAULT); change immediately after login.
INSERT IGNORE INTO `admins` (`role_id`, `username`, `email`, `full_name`, `password_hash`, `status`, `two_factor_enabled`)
SELECT
    r.`id`,
    'admin',
    COALESCE(NULLIF((SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'support_email' LIMIT 1), ''), 'admin@example.com'),
    'Super Admin',
    '$2y$10$TfBA62hqYLUb3BZplKowWOy540cgYCU6Jr7QBHLsgxyDs5uMuV6r.',
    'active',
    0
FROM `roles` r
WHERE r.`slug` = 'super_admin'
LIMIT 1;

-- 6. Record migration marker
INSERT IGNORE INTO `migrations` (`migration`) VALUES ('003_fix_admin_roles_fk');
