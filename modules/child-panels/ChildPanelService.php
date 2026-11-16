<?php
declare(strict_types=1);

class ChildPanelService
{
    public static function getOwnedPanel(int $panelId, int $ownerUserId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM child_panels WHERE id = ? AND owner_user_id = ? LIMIT 1',
            [$panelId, $ownerUserId]
        );
    }

    public static function createPanel(int $ownerUserId, string $siteName, string $domain, string $currency, string $margin = '25.00'): array
    {
        $siteName = trim($siteName);
        $domain = strtolower(trim((string) preg_replace('#^https?://#i', '', $domain)));
        $domain = rtrim($domain, '/');
        if ($siteName === '' || $domain === '' || !str_contains($domain, '.')) {
            return ['success' => false, 'error' => 'Valid site name and domain (e.g. panel.yourdomain.com) are required.'];
        }

        $dup = Database::fetchOne('SELECT id FROM child_panel_domains WHERE domain = ? LIMIT 1', [$domain]);
        if ($dup) {
            return ['success' => false, 'error' => 'This domain is already registered with another child panel.'];
        }

        $fee = (string) get_setting('child_panel_creation_fee', '0.00');
        $apiKey = 'cp_' . bin2hex(random_bytes(24));
        $verifyToken = 'smm-verify-' . bin2hex(random_bytes(8));

        return Database::transaction(function () use ($ownerUserId, $siteName, $domain, $currency, $margin, $fee, $apiKey, $verifyToken) {
            if ((float) $fee > 0) {
                $debit = WalletService::debit($ownerUserId, $fee, 'child_panel_fee', null, "Child Panel Creation Fee ($domain)");
                if (!$debit['success']) {
                    return $debit;
                }
            }

            $panelId = (int) Database::insert(
                'INSERT INTO child_panels (owner_user_id, site_name, currency, default_profit_margin, api_key, status)
                 VALUES (?, ?, ?, ?, ?, "pending")',
                [$ownerUserId, mb_substr($siteName, 0, 120), strtoupper($currency), Currency::normalizeDecimal($margin, 2), $apiKey]
            );

            Database::execute(
                'INSERT INTO child_panel_domains (child_panel_id, domain, verification_token, is_verified, is_primary)
                 VALUES (?, ?, ?, 0, 1)',
                [$panelId, $domain, $verifyToken]
            );

            // Seed initial enabled services for this child panel using default profit margin
            $parentServices = Database::fetchAll('SELECT id, name, selling_price FROM services WHERE is_active = 1');
            $mult = Currency::add('1', Currency::div($margin, '100', 6), 6);
            foreach ($parentServices as $ps) {
                $childPrice = Currency::mul((string) $ps['selling_price'], $mult, 6);
                Database::execute(
                    'INSERT IGNORE INTO child_panel_services (child_panel_id, parent_service_id, custom_name, profit_margin_percent, custom_selling_price, is_enabled)
                     VALUES (?, ?, ?, ?, ?, 1)',
                    [$panelId, $ps['id'], $ps['name'], Currency::normalizeDecimal($margin, 2), $childPrice]
                );
            }

            Logger::activity('user', $ownerUserId, 'child_panel.create', 'child_panels', (string) $panelId, ['domain' => $domain]);
            return ['success' => true, 'panel_id' => $panelId, 'verification_token' => $verifyToken];
        });
    }

    public static function verifyDomainDns(int $domainId, int $panelId): array
    {
        $row = Database::fetchOne('SELECT * FROM child_panel_domains WHERE id = ? AND child_panel_id = ? LIMIT 1', [$domainId, $panelId]);
        if (!$row) {
            return ['success' => false, 'error' => 'Domain record not found for this panel.'];
        }
        $records = function_exists('dns_get_record') ? @dns_get_record($row['domain'], DNS_TXT) : [];
        $matched = false;
        if (is_array($records)) {
            foreach ($records as $rec) {
                if (isset($rec['txt']) && str_contains((string) $rec['txt'], $row['verification_token'])) {
                    $matched = true;
                    break;
                }
            }
        }
        if (!$matched) {
            return ['success' => false, 'error' => "TXT record '{$row['verification_token']}' not detected yet on {$row['domain']}."];
        }
        Database::execute('UPDATE child_panel_domains SET is_verified = 1, verified_at = NOW() WHERE id = ?', [$domainId]);
        return ['success' => true];
    }

    public static function addFundsToPanel(int $panelId, int $ownerUserId, string $amount): array
    {
        if (!is_numeric($amount) || (float) $amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than zero.'];
        }
        $amountNorm = Currency::normalizeDecimal($amount, 4);
        return Database::transaction(function () use ($panelId, $ownerUserId, $amountNorm) {
            $panel = Database::fetchOne('SELECT * FROM child_panels WHERE id = ? AND owner_user_id = ? FOR UPDATE', [$panelId, $ownerUserId]);
            if (!$panel) {
                return ['success' => false, 'error' => 'Child panel not found.'];
            }
            $debit = WalletService::debit($ownerUserId, $amountNorm, 'child_panel_topup', (string) $panelId, "Transfer to Child Panel #{$panelId} ({$panel['site_name']})", null, null, false);
            if (!$debit['success']) {
                return $debit;
            }
            $newBal = Currency::add((string) $panel['wallet_balance'], $amountNorm, 4);
            Database::execute('UPDATE child_panels SET wallet_balance = ? WHERE id = ?', [$newBal, $panelId]);
            Database::execute(
                'INSERT INTO child_panel_wallet_transactions (child_panel_id, type, amount, balance_after, description) VALUES (?, "owner_topup", ?, ?, ?)',
                [$panelId, $amountNorm, $newBal, "Owner wallet transfer"]
            );
            return ['success' => true, 'panel_balance' => $newBal];
        });
    }

    /**
     * Strictly isolated Child Panel user creation & wallet credit.
     */
    public static function createChildUser(int $panelId, string $username, string $email, string $fullName, string $password): array
    {
        $exists = Database::fetchOne(
            'SELECT id FROM child_panel_users WHERE child_panel_id = ? AND (username = ? OR email = ?) LIMIT 1',
            [$panelId, $username, strtolower($email)]
        );
        if ($exists) {
            return ['success' => false, 'error' => 'Username or email already exists in this child panel.'];
        }
        $uid = Database::transaction(function () use ($panelId, $username, $email, $fullName, $password) {
            $id = (int) Database::insert(
                'INSERT INTO child_panel_users (child_panel_id, username, email, full_name, password_hash) VALUES (?, ?, ?, ?, ?)',
                [$panelId, trim($username), strtolower(trim($email)), trim($fullName), Security::hashPassword($password)]
            );
            Database::execute(
                'INSERT INTO child_panel_wallets (child_panel_id, child_user_id, balance) VALUES (?, ?, 0.0000)',
                [$panelId, $id]
            );
            return $id;
        });
        return ['success' => true, 'child_user_id' => $uid];
    }

    public static function creditChildUserWallet(int $panelId, int $childUserId, string $amount, string $description): array
    {
        $amountNorm = Currency::normalizeDecimal($amount, 4);
        return Database::transaction(function () use ($panelId, $childUserId, $amountNorm, $description) {
            $wallet = Database::fetchOne(
                'SELECT * FROM child_panel_wallets WHERE child_panel_id = ? AND child_user_id = ? FOR UPDATE',
                [$panelId, $childUserId]
            );
            if (!$wallet) {
                return ['success' => false, 'error' => 'Child user wallet not found in this panel.'];
            }
            $newBal = Currency::add((string) $wallet['balance'], $amountNorm, 4);
            Database::execute('UPDATE child_panel_wallets SET balance = ? WHERE id = ?', [$newBal, $wallet['id']]);
            Database::execute(
                'INSERT INTO child_panel_wallet_transactions (child_panel_id, child_user_id, type, amount, balance_after, description) VALUES (?, ?, "credit", ?, ?, ?)',
                [$panelId, $childUserId, $amountNorm, $newBal, $description]
            );
            return ['success' => true, 'balance' => $newBal];
        });
    }

    /**
     * Strictly isolated Child Panel order placement: verifies childUserId & childServiceId belong to $panelId.
     */
    public static function placeChildOrder(int $panelId, int $childUserId, int $childServiceId, string $link, int $quantity): array
    {
        return Database::transaction(function () use ($panelId, $childUserId, $childServiceId, $link, $quantity) {
            $panel = Database::fetchOne('SELECT * FROM child_panels WHERE id = ? AND status = "active" FOR UPDATE', [$panelId]);
            if (!$panel) {
                return ['success' => false, 'error' => 'Child panel is not active.'];
            }
            $cUser = Database::fetchOne('SELECT * FROM child_panel_users WHERE id = ? AND child_panel_id = ? AND status = "active" LIMIT 1', [$childUserId, $panelId]);
            if (!$cUser) {
                return ['success' => false, 'error' => 'Child panel user does not belong to this panel or is suspended.'];
            }
            $cSvc = Database::fetchOne(
                'SELECT cps.*, s.min_qty, s.max_qty, s.selling_price AS parent_selling_price
                 FROM child_panel_services cps
                 INNER JOIN services s ON s.id = cps.parent_service_id
                 WHERE cps.id = ? AND cps.child_panel_id = ? AND cps.is_enabled = 1 AND s.is_active = 1 LIMIT 1',
                [$childServiceId, $panelId]
            );
            if (!$cSvc) {
                return ['success' => false, 'error' => 'Service unavailable in this child panel.'];
            }
            if ($quantity < (int) $cSvc['min_qty'] || $quantity > (int) $cSvc['max_qty']) {
                return ['success' => false, 'error' => "Quantity must be between {$cSvc['min_qty']} and {$cSvc['max_qty']}."];
            }

            $charge = Currency::div(Currency::mul((string) $cSvc['custom_selling_price'], (string) $quantity, 6), '1000', 4);
            $parentCost = Currency::div(Currency::mul((string) $cSvc['parent_selling_price'], (string) $quantity, 6), '1000', 4);
            $panelProfit = Currency::sub($charge, $parentCost, 4);

            $cWallet = Database::fetchOne('SELECT * FROM child_panel_wallets WHERE child_panel_id = ? AND child_user_id = ? FOR UPDATE', [$panelId, $childUserId]);
            if (!$cWallet || Currency::compare((string) $cWallet['balance'], $charge, 4) < 0) {
                return ['success' => false, 'error' => 'Insufficient child user wallet balance.'];
            }
            if (Currency::compare((string) $panel['wallet_balance'], $parentCost, 4) < 0) {
                return ['success' => false, 'error' => 'Child panel wholesale balance insufficient.'];
            }

            $newChildUserBal = Currency::sub((string) $cWallet['balance'], $charge, 4);
            Database::execute('UPDATE child_panel_wallets SET balance = ?, total_spent = total_spent + ? WHERE id = ?', [$newChildUserBal, $charge, $cWallet['id']]);
            $newPanelBal = Currency::sub((string) $panel['wallet_balance'], $parentCost, 4);
            Database::execute('UPDATE child_panels SET wallet_balance = ? WHERE id = ?', [$newPanelBal, $panelId]);

            $cOrderId = (int) Database::insert(
                'INSERT INTO child_panel_orders (child_panel_id, child_user_id, child_service_id, link, quantity, charge, parent_cost, panel_profit, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")',
                [$panelId, $childUserId, $childServiceId, $link, $quantity, $charge, $parentCost, $panelProfit]
            );

            Database::execute(
                'INSERT INTO child_panel_wallet_transactions (child_panel_id, child_user_id, type, amount, balance_after, description)
                 VALUES (?, ?, "debit", ?, ?, ?)',
                [$panelId, $childUserId, $charge, $newChildUserBal, "Child Order #$cOrderId"]
            );

            return ['success' => true, 'child_order_id' => $cOrderId, 'charge' => $charge, 'panel_profit' => $panelProfit];
        });
    }
}
