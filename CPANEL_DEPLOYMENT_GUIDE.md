# cPanel Shared Hosting & VPS Deployment Guide — SMM Panel Pro

## Requirements
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.6+ (InnoDB enabled)
- **Required PHP Extensions**: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json`, `fileinfo` (recommended: `bcmath`)
- **Zero Build Tools**: No Node.js, no npm, no Composer required.

---

## 1. Upload Project Files to cPanel
1. Upload the entire contents of this project directly into your domain's web root (e.g., `public_html/`).
2. Ensure `/admin/`, `/user/`, `/api/`, `/config/`, `/core/`, `/includes/`, `/modules/`, `/themes/`, `/database/`, `/cron/`, `/storage/`, `/install/`, `/public/`, `.htaccess`, and `index.php` sit directly inside `public_html/`.
3. Verify directory permissions:
   - Directories: `755` (`storage/` and all subdirectories `storage/logs`, `storage/cache`, `storage/backups`, `storage/sessions`, `storage/uploads` must be writable by PHP)
   - Files: `644`
   - Sensitive folders (`/config/`, `/storage/`, `/database/`, `/cron/`) are automatically blocked from HTTP access via `.htaccess` and Apache rules.

---

## 2. Create MySQL Database in cPanel
1. Open **cPanel -> MySQL Database Wizard**.
2. Create a database (e.g., `cpaneluser_smmpanel`).
3. Create a MySQL user with a strong password and grant **ALL PRIVILEGES** on that database.

---

## 3. Run the Built-in Web Installer
1. Visit `https://yourdomain.com/install/` in your browser.
2. **Step 1 (Requirements)**: Verify PHP version, extensions, and `storage/` + `config/` write permissions.
3. **Step 2 (Database)**: Enter your MySQL Host (`localhost` or `127.0.0.1`), Database Name, Username, Password. Click **Test & Save Database** — this generates `config/db_credentials.php` and imports `database/schema.sql` + `database/seeds/default_data.sql` automatically.
4. **Step 3 (Admin & Site Setup)**: Set your Site Name, Canonical Site URL, **System Base Accounting Currency** (default `INR`), and create your **Super Admin** account.
5. **Security**: Delete `/install/` (or leave `storage/install.lock` untouched — the installer locks automatically once completed).

---

## 4. Configure Currencies, Payments & Providers in Admin Panel
Log in at `https://yourdomain.com/admin/login.php`:
1. **Currencies & Exchange Rates** (`/admin/currency/index.php`):
   - Click **Update Rates Now** to fetch live cross-rates using Frankfurter / OpenER / Fawaz CDN (free, no API key needed).
   - Set any custom manual override rates if needed.
2. **Payment Gateways** (`/admin/gateways/index.php`):
   - **Razorpay**: Enter Key ID, Key Secret, Webhook Secret, enable Razorpay.
   - In your Razorpay Dashboard, configure Webhook URL:
     `https://yourdomain.com/api/razorpay-webhook.php`
     Subscribe to `payment.captured` and `order.paid` events.
   - **Manual Bank / UPI**: Enter UPI ID, Account Number, IFSC, and payment instructions, then enable.
3. **SMM Providers** (`/admin/providers/index.php`):
   - Add providers with their API URL, API Key, and **actual Provider Currency** (e.g., `INR`, `USD`, `EUR`).
   - Use **Bulk Import Services** (`/admin/providers/import.php`) to preview exact cost conversion, margin calculation, and batch-import services.
4. **SMTP Email** (`/admin/settings/index.php`):
   - Enter your cPanel mail/SMTP credentials for order notifications, password resets, and support replies.

---

## 5. Setup cPanel Cron Jobs
Open **cPanel -> Cron Jobs** and add these schedules (replace `/home/USER/public_html` with your actual server path):

```cron
# Sync active order statuses with provider APIs every 2 minutes
*/2 * * * * php /home/USER/public_html/cron/order-sync.php >/dev/null 2>&1

# Sync provider balances and service rates every 30 minutes
*/30 * * * * php /home/USER/public_html/cron/provider-sync.php >/dev/null 2>&1

# Refresh free exchange rates every 6 hours
0 */6 * * * php /home/USER/public_html/cron/currency-update.php >/dev/null 2>&1

# Sync refill statuses every 5 minutes
*/5 * * * * php /home/USER/public_html/cron/refill-check.php >/dev/null 2>&1

# Daily session, reset token, and log cleanup at 03:00 AM
0 3 * * * php /home/USER/public_html/cron/cleanup.php >/dev/null 2>&1
```

---

## 6. Reseller API v2 & Child Panels
- **Reseller API Endpoint**: `https://yourdomain.com/api/index.php` (`POST` actions: `services`, `add`, `status`, `multi_status`, `refill`, `cancel`, `balance`).
- **Child Panels Custom Domains**: Point the child panel's domain `A` record / `CNAME` to the same server/VirtualHost `public_html/`. `index.php` automatically detects registered verified Child Panel domains and serves the isolated portal (`public/child-panel-portal.php`).
