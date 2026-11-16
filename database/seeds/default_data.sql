-- Default Essential Configuration Data (No Fake Users, Orders, or Balances)
SET NAMES utf8mb4;

-- Roles
INSERT IGNORE INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Super Admin', 'super_admin', 'Full unrestricted access to all modules and security settings', 1),
(2, 'Admin', 'admin', 'Full operational access excluding destructive system overrides', 1),
(3, 'Support Staff', 'support_staff', 'Manage support tickets, view orders and user profiles', 1),
(4, 'Finance Manager', 'finance_manager', 'Manage deposits, manual payment approvals, wallet ledger and financial reports', 1),
(5, 'Order Manager', 'order_manager', 'Manage orders, refills, services, categories and providers', 1);

-- Permissions
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

-- Grant all permissions to Super Admin (role_id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Grant operational permissions to Admin (role_id = 2)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `perm_key` NOT IN ('roles.manage');

-- Support Staff (role_id = 3)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions` WHERE `perm_key` IN ('dashboard.view','users.view','orders.view','orders.refill','tickets.view','tickets.reply','notifications.manage');

-- Finance Manager (role_id = 4)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions` WHERE `perm_key` IN ('dashboard.view','users.view','users.wallet','payments.view','payments.approve','wallet.view','currency.manage','referrals.manage','reports.view');

-- Order Manager (role_id = 5)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, `id` FROM `permissions` WHERE `perm_key` IN ('dashboard.view','orders.view','orders.edit','orders.cancel','orders.refill','orders.export','categories.manage','services.view','services.manage','providers.view','providers.manage');

-- Currencies (INR is System Base Currency)
INSERT IGNORE INTO `currencies` (`code`, `name`, `symbol`, `decimal_places`, `is_base`, `is_active`, `sort_order`) VALUES
('INR', 'Indian Rupee', '₹', 2, 1, 1, 1),
('USD', 'US Dollar', '$', 2, 0, 1, 2),
('EUR', 'Euro', '€', 2, 0, 1, 3),
('GBP', 'British Pound', '£', 2, 0, 1, 4),
('AED', 'UAE Dirham', 'د.إ', 2, 0, 1, 5);

-- Default Identity Rate for INR->INR (never fake cross-currency rates; cross rates must be fetched or set by admin)
INSERT IGNORE INTO `exchange_rates` (`base_currency`, `target_currency`, `rate`, `source`, `is_manual_override`, `status`) VALUES
('INR', 'INR', 1.00000000, 'system_identity', 0, 'valid');

-- Payment Gateways (disabled until real credentials/details are configured by Admin)
INSERT IGNORE INTO `payment_gateways` (`code`, `name`, `description`, `min_amount`, `max_amount`, `fee_percent`, `config_json`, `is_active`, `sort_order`) VALUES
('razorpay', 'Razorpay (UPI / Cards / NetBanking)', 'Instant automated payment via Razorpay Checkout', 10.00, 100000.00, 0.00, '{"key_id":"","key_secret":"","webhook_secret":"","mode":"test"}', 0, 1),
('manual', 'Manual Bank / UPI Transfer', 'Pay via Bank Transfer or UPI and submit transaction proof for verification', 50.00, 200000.00, 0.00, '{"instructions":"Configure bank/UPI details in Admin > Payment Gateways before enabling.","upi_id":"","account_name":"","account_number":"","ifsc":""}', 0, 2);

-- Platform Categories (Real SMM platform categories)
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `platform`, `icon`, `description`, `sort_order`, `is_active`) VALUES
(1, 'Instagram Followers', 'instagram-followers', 'instagram', 'instagram', 'High quality Instagram followers services', 1, 1),
(2, 'Instagram Likes & Views', 'instagram-likes-views', 'instagram', 'instagram', 'Instagram likes, reels views, and story views', 2, 1),
(3, 'YouTube Subscribers & Views', 'youtube-subscribers-views', 'youtube', 'youtube', 'YouTube channel subscribers, watch time, and views', 3, 1),
(4, 'Facebook Page Likes & Followers', 'facebook-likes-followers', 'facebook', 'facebook', 'Facebook page likes, post reactions, and followers', 4, 1),
(5, 'Telegram Channel Members & Views', 'telegram-members-views', 'telegram', 'telegram', 'Telegram channel/group members and post views', 5, 1),
(6, 'TikTok Followers & Views', 'tiktok-followers-views', 'tiktok', 'tiktok', 'TikTok followers, likes, and video views', 6, 1),
(7, 'X (Twitter) Followers & Engagement', 'twitter-engagement', 'twitter', 'twitter', 'X followers, reposts, and likes', 7, 1);

-- Core Settings
INSERT IGNORE INTO `settings` (`setting_group`, `setting_key`, `setting_value`, `is_secret`) VALUES
('general', 'site_name', 'SMM Panel', 0),
('general', 'site_tagline', 'Grow Your Social Media', 0),
('general', 'site_url', '', 0),
('general', 'support_email', 'support@example.com', 0),
('general', 'system_base_currency', 'INR', 0),
('general', 'default_timezone', 'Asia/Kolkata', 0),
('general', 'maintenance_mode', '0', 0),
('general', 'maintenance_message', 'We are performing scheduled maintenance. Please check back shortly.', 0),
('general', 'registration_enabled', '1', 0),
('general', 'email_verification_required', '0', 0),
('general', 'whatsapp_number', '', 0),
('general', 'telegram_username', '', 0),
('theme', 'active_theme', 'classic', 0),
('theme', 'classic_primary_color', '#7045D8', 0),
('theme', 'classic_secondary_color', '#8B5CF6', 0),
('currency', 'auto_update_enabled', '1', 0),
('currency', 'primary_rate_source', 'frankfurter', 0),
('currency', 'fallback_rate_source', 'open_er', 0),
('currency', 'last_rate_update_at', '', 0),
('currency', 'last_rate_source', '', 0),
('referral', 'referral_enabled', '1', 0),
('referral', 'referral_commission_percent', '5.00', 0),
('referral', 'referral_min_deposit', '100.00', 0),
('referral', 'referral_auto_approve', '0', 0),
('child_panel', 'child_panel_enabled', '1', 0),
('child_panel', 'child_panel_creation_fee', '0.00', 0),
('security', 'login_max_attempts', '5', 0),
('security', 'login_lockout_minutes', '15', 0),
('security', 'session_lifetime_minutes', '120', 0),
('website', 'hero_title', 'Scale Every Social Channel From One Powerful SMM Panel', 0),
('website', 'hero_subtitle', 'Automated delivery, real-time provider syncing, multi-currency wallet, Razorpay checkout, and reseller API built for agencies and creators.', 0),
('website', 'about_content', 'We provide a reliable, automated social media marketing platform engineered for creators, brands, and digital agencies.', 0),
('website', 'terms_content', 'By registering and placing orders on this platform, you agree to comply with all applicable platform guidelines and service terms. Orders once processed cannot be reversed unless eligible for cancellation or partial refund.', 0),
('website', 'privacy_content', 'We respect your privacy. Account information, API keys, and payment records are encrypted and never shared with unauthorized third parties.', 0),
('website', 'seo_description', 'Professional Social Media Marketing Panel with automated API delivery, multi-currency billing, and reseller support.', 0),
('website', 'seo_keywords', 'smm panel, social media marketing, instagram followers, youtube views, reseller panel', 0),
('website', 'faq_json', '[{"q":"How do I place an order?","a":"Register an account, add funds to your wallet via an enabled payment method, choose a service in New Order, enter your target link and quantity, and submit."},{"q":"What currencies are supported?","a":"Our accounting base currency is configurable (INR by default), and you can view prices in INR, USD, EUR, GBP, or AED using verified exchange rates."},{"q":"Do you offer a Reseller API and Child Panels?","a":"Yes. Every user can generate an API key for standard SMM v2 endpoints and launch isolated Child Panels from the User Panel."}]', 0);
