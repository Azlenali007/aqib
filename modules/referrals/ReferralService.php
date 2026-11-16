<?php
declare(strict_types=1);

class ReferralService
{
    public static function recordCommissionOnDeposit(int $referredUserId, int $depositId, string $depositAmount): void
    {
        if (get_setting('referral_enabled', '1') !== '1') {
            return;
        }
        $minDep = (string) get_setting('referral_min_deposit', '100.00');
        if (Currency::compare($depositAmount, $minDep, 4) < 0) {
            return;
        }

        $user = Database::fetchOne('SELECT id, referred_by_user_id, last_login_ip FROM users WHERE id = ? LIMIT 1', [$referredUserId]);
        if (!$user || empty($user['referred_by_user_id'])) {
            return;
        }
        $referrerId = (int) $user['referred_by_user_id'];
        if ($referrerId === $referredUserId) {
            return;
        }

        // Anti-abuse: prevent duplicate commission for the same deposit
        $exists = Database::fetchOne(
            "SELECT id FROM referrals WHERE trigger_type = 'deposit' AND trigger_reference_id = ? LIMIT 1",
            [$depositId]
        );
        if ($exists) {
            return;
        }

        $pct = (string) get_setting('referral_commission_percent', '5.00');
        $commission = Currency::mul($depositAmount, Currency::div($pct, '100', 6), 4);
        if (Currency::compare($commission, '0', 4) <= 0) {
            return;
        }

        $autoApprove = get_setting('referral_auto_approve', '0') === '1';
        $refId = (int) Database::insert(
            'INSERT INTO referrals (referrer_user_id, referred_user_id, trigger_type, trigger_reference_id, base_amount, commission_percent, commission_amount, currency, signup_ip, status)
             VALUES (?, ?, "deposit", ?, ?, ?, ?, ?, ?, "pending")',
            [$referrerId, $referredUserId, $depositId, $depositAmount, $pct, $commission, Currency::systemBase(), $user['last_login_ip']]
        );

        if ($autoApprove) {
            self::approve($refId, null);
        }
    }

    public static function approve(int $referralId, ?int $adminId = null): array
    {
        return Database::transaction(function () use ($referralId, $adminId) {
            $ref = Database::fetchOne('SELECT * FROM referrals WHERE id = ? FOR UPDATE', [$referralId]);
            if (!$ref || $ref['status'] !== 'pending') {
                return ['success' => false, 'error' => 'Pending referral record not found.'];
            }
            $credit = WalletService::credit(
                (int) $ref['referrer_user_id'],
                (string) $ref['commission_amount'],
                'referral',
                (string) $referralId,
                "Referral Commission #$referralId",
                'ref_comm_' . $referralId,
                'referral',
                $adminId
            );
            Database::execute(
                "UPDATE referrals SET status = 'approved', credited_wallet_tx_id = ?, reviewed_by_admin_id = ?, reviewed_at = NOW() WHERE id = ?",
                [$credit['tx_id'], $adminId, $referralId]
            );
            Notification::sendToUser((int) $ref['referrer_user_id'], 'wallet', 'Referral Commission Credited', 'You earned ' . format_money($ref['commission_amount']) . ' referral commission.');
            return ['success' => true];
        });
    }

    public static function reject(int $referralId, int $adminId): array
    {
        $aff = Database::execute(
            "UPDATE referrals SET status = 'rejected', reviewed_by_admin_id = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'",
            [$adminId, $referralId]
        );
        return $aff > 0 ? ['success' => true] : ['success' => false, 'error' => 'Pending referral not found.'];
    }
}
