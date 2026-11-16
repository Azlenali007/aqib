-- SMM Panel Pro — Complete MySQL 8.0 / MariaDB 10.6+ Schema
-- Strict InnoDB, utf8mb4_unicode_ci, DECIMAL financial precision, Foreign Keys & Indexes

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Roles & Permissions (Admin RBAC)
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- 2. Admins
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- 3. Currencies & Exchange Rates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `currencies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` CHAR(3) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `symbol` VARCHAR(16) NOT NULL,
  `decimal_places` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `is_base` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_currencies_code` (`code`),
  KEY `idx_currencies_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exchange_rates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `base_currency` CHAR(3) NOT NULL,
  `target_currency` CHAR(3) NOT NULL,
  `rate` DECIMAL(18,8) NOT NULL,
  `source` VARCHAR(60) NOT NULL DEFAULT 'manual',
  `is_manual_override` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('valid','failed','disabled') NOT NULL DEFAULT 'valid',
  `fetched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_exchange_pair` (`base_currency`, `target_currency`),
  KEY `idx_exchange_target` (`target_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exchange_rate_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `base_currency` CHAR(3) NOT NULL,
  `target_currency` CHAR(3) NOT NULL,
  `rate` DECIMAL(18,8) NOT NULL,
  `source` VARCHAR(60) NOT NULL,
  `status` ENUM('success','failed') NOT NULL DEFAULT 'success',
  `error_message` VARCHAR(255) DEFAULT NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_erh_pair_time` (`base_currency`, `target_currency`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Users & Wallets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `mobile` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `preferred_currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `language` VARCHAR(10) NOT NULL DEFAULT 'en',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
  `referral_code` VARCHAR(32) NOT NULL,
  `referred_by_user_id` INT UNSIGNED DEFAULT NULL,
  `custom_rate_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME DEFAULT NULL,
  `email_verify_token` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
  `admin_notes` TEXT DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  UNIQUE KEY `uk_users_referral_code` (`referral_code`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_referred_by` (`referred_by_user_id`),
  CONSTRAINT `fk_users_referred_by` FOREIGN KEY (`referred_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_deposited` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_spent` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_refunded` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wallets_user` (`user_id`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('credit','debit','refund','referral','adjustment') NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `balance_before` DECIMAL(16,4) NOT NULL,
  `balance_after` DECIMAL(16,4) NOT NULL,
  `currency` CHAR(3) NOT NULL,
  `reference_type` VARCHAR(40) NOT NULL,
  `reference_id` VARCHAR(100) DEFAULT NULL,
  `idempotency_key` VARCHAR(120) DEFAULT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_by_admin_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wt_idempotency` (`idempotency_key`),
  KEY `idx_wt_user_created` (`user_id`, `created_at`),
  KEY `idx_wt_ref` (`reference_type`, `reference_id`),
  CONSTRAINT `fk_wt_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Sessions, Password Resets & Rate Limiting
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type` ENUM('user','admin','child_user') NOT NULL DEFAULT 'user',
  `user_id` INT UNSIGNED NOT NULL,
  `session_token` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `last_activity_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sessions_token` (`session_token`),
  KEY `idx_sessions_owner` (`user_type`, `user_id`, `is_revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `email` VARCHAR(160) NOT NULL,
  `token_hash` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` ENUM('user','admin','api') NOT NULL DEFAULT 'user',
  `identifier` VARCHAR(160) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `succeeded` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_la_lookup` (`scope`, `ip_address`, `attempted_at`),
  KEY `idx_la_ident` (`scope`, `identifier`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Categories, Providers & Services
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `platform` VARCHAR(40) NOT NULL DEFAULT 'other',
  `icon` VARCHAR(80) NOT NULL DEFAULT 'globe',
  `description` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `providers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `api_url` VARCHAR(255) NOT NULL,
  `api_key_enc` TEXT NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'USD',
  `balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `balance_currency` CHAR(3) DEFAULT NULL,
  `last_balance_sync_at` DATETIME DEFAULT NULL,
  `priority` INT NOT NULL DEFAULT 1,
  `sync_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_providers_status` (`status`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED DEFAULT NULL,
  `provider_service_id` VARCHAR(80) DEFAULT NULL,
  `name` VARCHAR(220) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `platform` VARCHAR(40) NOT NULL DEFAULT 'other',
  `icon` VARCHAR(80) NOT NULL DEFAULT 'globe',
  `service_type` VARCHAR(40) NOT NULL DEFAULT 'default',
  `min_qty` INT UNSIGNED NOT NULL DEFAULT 10,
  `max_qty` INT UNSIGNED NOT NULL DEFAULT 100000,
  `provider_price` DECIMAL(16,6) NOT NULL DEFAULT 0.000000,
  `provider_currency` CHAR(3) DEFAULT NULL,
  `provider_cost_base` DECIMAL(16,6) NOT NULL DEFAULT 0.000000,
  `provider_exchange_rate` DECIMAL(18,8) NOT NULL DEFAULT 1.00000000,
  `system_base_currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `profit_margin_percent` DECIMAL(8,2) NOT NULL DEFAULT 20.00,
  `selling_price` DECIMAL(16,6) NOT NULL DEFAULT 0.000000,
  `refill_supported` TINYINT(1) NOT NULL DEFAULT 0,
  `cancel_supported` TINYINT(1) NOT NULL DEFAULT 0,
  `dripfeed_supported` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `imported_at` DATETIME DEFAULT NULL,
  `last_synced_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_service` (`provider_id`, `provider_service_id`),
  KEY `idx_services_category_active` (`category_id`, `is_active`, `sort_order`),
  KEY `idx_services_featured` (`is_featured`, `is_active`),
  CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_services_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `provider_service_mappings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `external_service_id` VARCHAR(80) NOT NULL,
  `external_name` VARCHAR(255) NOT NULL,
  `external_category` VARCHAR(160) DEFAULT NULL,
  `external_rate` DECIMAL(16,6) NOT NULL,
  `external_currency` CHAR(3) NOT NULL,
  `auto_sync_price` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_sync_status` TINYINT(1) NOT NULL DEFAULT 1,
  `last_payload_json` MEDIUMTEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_psm_provider_ext` (`provider_id`, `external_service_id`),
  KEY `idx_psm_service` (`service_id`),
  CONSTRAINT `fk_psm_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psm_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `provider_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(60) NOT NULL,
  `request_summary` TEXT DEFAULT NULL,
  `response_code` INT DEFAULT NULL,
  `response_body` MEDIUMTEXT DEFAULT NULL,
  `is_error` TINYINT(1) NOT NULL DEFAULT 0,
  `error_message` VARCHAR(255) DEFAULT NULL,
  `duration_ms` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pl_provider_time` (`provider_id`, `created_at`),
  CONSTRAINT `fk_pl_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_favourites` (
  `user_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `service_id`),
  CONSTRAINT `fk_uf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uf_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Orders, Mass Orders, Refills & Logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mass_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `total_lines` INT UNSIGNED NOT NULL DEFAULT 0,
  `success_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `fail_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_charge` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mo_user` (`user_id`),
  CONSTRAINT `fk_mo_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED DEFAULT NULL,
  `mass_order_id` INT UNSIGNED DEFAULT NULL,
  `provider_order_id` VARCHAR(100) DEFAULT NULL,
  `link` VARCHAR(500) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `unit_price_per_1k` DECIMAL(16,6) NOT NULL,
  `charge` DECIMAL(16,4) NOT NULL,
  `provider_cost` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `profit` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `start_count` INT UNSIGNED DEFAULT NULL,
  `remains` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('pending','processing','in_progress','completed','partial','cancelled','refunded','failed') NOT NULL DEFAULT 'pending',
  `source` ENUM('web','mass','api') NOT NULL DEFAULT 'web',
  `refill_available` TINYINT(1) NOT NULL DEFAULT 0,
  `cancel_available` TINYINT(1) NOT NULL DEFAULT 0,
  `provider_error` VARCHAR(255) DEFAULT NULL,
  `last_synced_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user_status` (`user_id`, `status`, `created_at`),
  KEY `idx_orders_status` (`status`, `created_at`),
  KEY `idx_orders_provider` (`provider_id`, `provider_order_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_mass` FOREIGN KEY (`mass_order_id`) REFERENCES `mass_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `refill_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED DEFAULT NULL,
  `provider_refill_id` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending','processing','completed','rejected','error') NOT NULL DEFAULT 'pending',
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_refill_order` (`order_id`),
  KEY `idx_refill_user` (`user_id`),
  CONSTRAINT `fk_refill_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refill_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `actor_type` ENUM('system','user','admin','cron','provider') NOT NULL DEFAULT 'system',
  `actor_id` INT UNSIGNED DEFAULT NULL,
  `old_status` VARCHAR(40) DEFAULT NULL,
  `new_status` VARCHAR(40) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ol_order` (`order_id`),
  CONSTRAINT `fk_ol_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Payment Gateways, Deposits & Payments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_gateways` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `min_amount` DECIMAL(14,2) NOT NULL DEFAULT 10.00,
  `max_amount` DECIMAL(14,2) NOT NULL DEFAULT 100000.00,
  `fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `config_json` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pg_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deposits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `gateway_code` VARCHAR(40) NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `fee` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `net_credit` DECIMAL(16,4) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `gateway_order_id` VARCHAR(120) DEFAULT NULL,
  `gateway_payment_id` VARCHAR(120) DEFAULT NULL,
  `utr_reference` VARCHAR(120) DEFAULT NULL,
  `proof_file` VARCHAR(255) DEFAULT NULL,
  `user_note` VARCHAR(255) DEFAULT NULL,
  `admin_note` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','paid','approved','rejected','failed') NOT NULL DEFAULT 'pending',
  `credited_wallet_tx_id` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_by_admin_id` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_deposits_gateway_pay` (`gateway_code`, `gateway_payment_id`),
  KEY `idx_deposits_user` (`user_id`, `status`),
  KEY `idx_deposits_order` (`gateway_order_id`),
  CONSTRAINT `fk_deposits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `deposit_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `gateway_code` VARCHAR(40) NOT NULL,
  `external_id` VARCHAR(120) DEFAULT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `signature_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(40) NOT NULL,
  `raw_payload` MEDIUMTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_deposit` (`deposit_id`),
  KEY `idx_payments_ext` (`external_id`),
  CONSTRAINT `fk_payments_deposit` FOREIGN KEY (`deposit_id`) REFERENCES `deposits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Referrals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_user_id` INT UNSIGNED NOT NULL,
  `referred_user_id` INT UNSIGNED NOT NULL,
  `trigger_type` ENUM('deposit','order','signup') NOT NULL DEFAULT 'deposit',
  `trigger_reference_id` BIGINT UNSIGNED DEFAULT NULL,
  `base_amount` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `commission_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `commission_amount` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `signup_ip` VARCHAR(45) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `credited_wallet_tx_id` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_by_admin_id` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ref_referrer` (`referrer_user_id`, `status`),
  KEY `idx_ref_referred` (`referred_user_id`),
  CONSTRAINT `fk_ref_referrer` FOREIGN KEY (`referrer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_referred` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Support Tickets & Messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'general',
  `subject` VARCHAR(200) NOT NULL,
  `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open','pending','answered','closed') NOT NULL DEFAULT 'open',
  `assigned_admin_id` INT UNSIGNED DEFAULT NULL,
  `last_reply_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tickets_user` (`user_id`, `status`),
  KEY `idx_tickets_status` (`status`, `priority`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tickets_admin` FOREIGN KEY (`assigned_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('user','admin') NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tm_ticket` (`ticket_id`, `created_at`),
  CONSTRAINT `fk_tm_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Notifications & Announcements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `child_panel_id` INT UNSIGNED DEFAULT NULL,
  `type` ENUM('order','payment','wallet','refill','ticket','announcement','system') NOT NULL DEFAULT 'system',
  `title` VARCHAR(160) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_read` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(180) NOT NULL,
  `content` TEXT NOT NULL,
  `badge_type` ENUM('info','success','warning','promo') NOT NULL DEFAULT 'info',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_by_admin_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_active` (`is_active`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Child Panels (Strictly Isolated Tenants)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `child_panels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` INT UNSIGNED NOT NULL,
  `site_name` VARCHAR(120) NOT NULL,
  `tagline` VARCHAR(160) DEFAULT 'Grow Your Social Media',
  `primary_color` VARCHAR(20) NOT NULL DEFAULT '#7045D8',
  `logo_path` VARCHAR(255) DEFAULT NULL,
  `favicon_path` VARCHAR(255) DEFAULT NULL,
  `theme_slug` VARCHAR(40) NOT NULL DEFAULT 'classic',
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `default_profit_margin` DECIMAL(8,2) NOT NULL DEFAULT 25.00,
  `wallet_balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `api_key` VARCHAR(128) NOT NULL,
  `status` ENUM('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` VARCHAR(255) DEFAULT NULL,
  `settings_json` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cp_api_key` (`api_key`),
  KEY `idx_cp_owner` (`owner_user_id`),
  KEY `idx_cp_status` (`status`),
  CONSTRAINT `fk_cp_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `child_panel_domains` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_panel_id` INT UNSIGNED NOT NULL,
  `domain` VARCHAR(190) NOT NULL,
  `verification_token` VARCHAR(80) NOT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpd_domain` (`domain`),
  KEY `idx_cpd_panel` (`child_panel_id`),
  CONSTRAINT `fk_cpd_panel` FOREIGN KEY (`child_panel_id`) REFERENCES `child_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `child_panel_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_panel_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpu_username` (`child_panel_id`, `username`),
  UNIQUE KEY `uk_cpu_email` (`child_panel_id`, `email`),
  CONSTRAINT `fk_cpu_panel` FOREIGN KEY (`child_panel_id`) REFERENCES `child_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `child_panel_wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_panel_id` INT UNSIGNED NOT NULL,
  `child_user_id` INT UNSIGNED NOT NULL,
  `balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_spent` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpw_user` (`child_panel_id`, `child_user_id`),
  CONSTRAINT `fk_cpw_panel` FOREIGN KEY (`child_panel_id`) REFERENCES `child_panels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpw_user` FOREIGN KEY (`child_user_id`) REFERENCES `child_panel_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `child_panel_wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_panel_id` INT UNSIGNED NOT NULL,
  `child_user_id` INT UNSIGNED DEFAULT NULL,
  `type` ENUM('credit','debit','refund','owner_topup') NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `balance_after` DECIMAL(16,4) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpwt_panel` (`child_panel_id`, `created_at`),
  CONSTRAINT `fk_cpwt_panel` FOREIGN KEY (`child_panel_id`) REFERENCES `child_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `child_panel_services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_panel_id` INT UNSIGNED NOT NULL,
  `parent_service_id` INT UNSIGNED NOT NULL,
  `custom_name` VARCHAR(220) DEFAULT NULL,
  `profit_margin_percent` DECIMAL(8,2) NOT NULL DEFAULT 20.00,
  `custom_selling_price` DECIMAL(16,6) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cps_panel_service` (`child_panel_id`, `parent_service_id`),
  CONSTRAINT `fk_cps_panel` FOREIGN KEY (`child_panel_id`) REFERENCES `child_panels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cps_service` FOREIGN KEY (`parent_service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `child_panel_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `child_panel_id` INT UNSIGNED NOT NULL,
  `child_user_id` INT UNSIGNED NOT NULL,
  `parent_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `child_service_id` INT UNSIGNED NOT NULL,
  `link` VARCHAR(500) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `charge` DECIMAL(16,4) NOT NULL,
  `parent_cost` DECIMAL(16,4) NOT NULL,
  `panel_profit` DECIMAL(16,4) NOT NULL,
  `status` ENUM('pending','processing','in_progress','completed','partial','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpo_panel_user` (`child_panel_id`, `child_user_id`, `created_at`),
  CONSTRAINT `fk_cpo_panel` FOREIGN KEY (`child_panel_id`) REFERENCES `child_panels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpo_user` FOREIGN KEY (`child_user_id`) REFERENCES `child_panel_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpo_service` FOREIGN KEY (`child_service_id`) REFERENCES `child_panel_services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. API Keys, Activity Logs, Settings & Migrations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `api_key` VARCHAR(96) NOT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `last_used_ip` VARCHAR(45) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_keys_user` (`user_id`),
  UNIQUE KEY `uk_api_keys_key` (`api_key`),
  CONSTRAINT `fk_ak_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_type` ENUM('user','admin','system','cron','api') NOT NULL DEFAULT 'system',
  `actor_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(60) DEFAULT NULL,
  `entity_id` VARCHAR(80) DEFAULT NULL,
  `details_json` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_act_actor` (`actor_type`, `actor_id`, `created_at`),
  KEY `idx_act_action` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` MEDIUMTEXT DEFAULT NULL,
  `is_secret` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(160) NOT NULL,
  `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_migrations_name` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
