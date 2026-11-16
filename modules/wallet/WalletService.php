<?php
declare(strict_types=1);

class WalletService
{
    public static function ensureWallet(int $userId): array
    {
        $wallet = Database::fetchOne('SELECT * FROM wallets WHERE user_id = ? LIMIT 1', [$userId]);
        if ($wallet) {
            return $wallet;
        }
        $base = Currency::systemBase();
        Database::execute(
            'INSERT INTO wallets (user_id, currency, balance) VALUES (?, ?, 0.0000)',
            [$userId, $base]
        );
        return Database::fetchOne('SELECT * FROM wallets WHERE user_id = ? LIMIT 1', [$userId]);
    }

    public static function credit(
        int $userId,
        string $amount,
        string $referenceType,
        ?string $referenceId,
        string $description,
        ?string $idempotencyKey = null,
        string $txType = 'credit',
        ?int $adminId = null
    ): array {
        $amountNorm = Currency::normalizeDecimal($amount, 4);
        if (Currency::compare($amountNorm, '0', 4) <= 0) {
            return ['success' => false, 'error' => 'Credit amount must be positive.'];
        }

        return Database::transaction(function () use ($userId, $amountNorm, $referenceType, $referenceId, $description, $idempotencyKey, $txType, $adminId) {
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existing = Database::fetchOne('SELECT id FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1', [$idempotencyKey]);
                if ($existing) {
                    return ['success' => true, 'duplicate' => true, 'tx_id' => (int) $existing['id']];
                }
            }

            $wallet = Database::fetchOne('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE', [$userId]);
            if (!$wallet) {
                $base = Currency::systemBase();
                Database::execute('INSERT INTO wallets (user_id, currency, balance) VALUES (?, ?, 0.0000)', [$userId, $base]);
                $wallet = Database::fetchOne('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE', [$userId]);
            }

            $before = Currency::normalizeDecimal($wallet['balance'], 4);
            $after = Currency::add($before, $amountNorm, 4);

            $setDeposited = ($txType === 'credit' && $referenceType === 'deposit') ? ', total_deposited = total_deposited + ?' : '';
            $setRefunded = ($txType === 'refund') ? ', total_refunded = total_refunded + ?' : '';

            $params = [$after];
            if ($setDeposited !== '') $params[] = $amountNorm;
            if ($setRefunded !== '') $params[] = $amountNorm;
            $params[] = $wallet['id'];

            Database::execute("UPDATE wallets SET balance = ?{$setDeposited}{$setRefunded} WHERE id = ?", $params);

            $txId = (int) Database::insert(
                'INSERT INTO wallet_transactions (wallet_id, user_id, type, amount, balance_before, balance_after, currency, reference_type, reference_id, idempotency_key, description, created_by_admin_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $wallet['id'],
                    $userId,
                    $txType,
                    $amountNorm,
                    $before,
                    $after,
                    $wallet['currency'],
                    $referenceType,
                    $referenceId,
                    $idempotencyKey,
                    mb_substr($description, 0, 255),
                    $adminId,
                ]
            );

            return ['success' => true, 'duplicate' => false, 'tx_id' => $txId, 'balance_after' => $after];
        });
    }

    public static function debit(
        int $userId,
        string $amount,
        string $referenceType,
        ?string $referenceId,
        string $description,
        ?string $idempotencyKey = null,
        ?int $adminId = null,
        bool $countAsSpent = true
    ): array {
        $amountNorm = Currency::normalizeDecimal($amount, 4);
        if (Currency::compare($amountNorm, '0', 4) <= 0) {
            return ['success' => false, 'error' => 'Debit amount must be positive.'];
        }

        return Database::transaction(function () use ($userId, $amountNorm, $referenceType, $referenceId, $description, $idempotencyKey, $adminId, $countAsSpent) {
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existing = Database::fetchOne('SELECT id FROM wallet_transactions WHERE idempotency_key = ? LIMIT 1', [$idempotencyKey]);
                if ($existing) {
                    return ['success' => true, 'duplicate' => true, 'tx_id' => (int) $existing['id']];
                }
            }

            $wallet = Database::fetchOne('SELECT * FROM wallets WHERE user_id = ? FOR UPDATE', [$userId]);
            if (!$wallet) {
                return ['success' => false, 'error' => 'Wallet not found.'];
            }

            $before = Currency::normalizeDecimal($wallet['balance'], 4);
            if (Currency::compare($before, $amountNorm, 4) < 0) {
                return ['success' => false, 'error' => 'Insufficient wallet balance.'];
            }

            $after = Currency::sub($before, $amountNorm, 4);
            $spentSql = $countAsSpent ? ', total_spent = total_spent + ?' : '';
            $params = [$after];
            if ($countAsSpent) $params[] = $amountNorm;
            $params[] = $wallet['id'];

            Database::execute("UPDATE wallets SET balance = ?{$spentSql} WHERE id = ?", $params);

            $txId = (int) Database::insert(
                'INSERT INTO wallet_transactions (wallet_id, user_id, type, amount, balance_before, balance_after, currency, reference_type, reference_id, idempotency_key, description, created_by_admin_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $wallet['id'],
                    $userId,
                    $adminId ? 'adjustment' : 'debit',
                    $amountNorm,
                    $before,
                    $after,
                    $wallet['currency'],
                    $referenceType,
                    $referenceId,
                    $idempotencyKey,
                    mb_substr($description, 0, 255),
                    $adminId,
                ]
            );

            return ['success' => true, 'duplicate' => false, 'tx_id' => $txId, 'balance_after' => $after];
        });
    }
}
