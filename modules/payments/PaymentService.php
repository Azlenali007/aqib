<?php
declare(strict_types=1);

class PaymentService
{
    public static function createRazorpayDeposit(int $userId, string $amount): array
    {
        $gateway = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = "razorpay" LIMIT 1');
        if (!$gateway || (int) $gateway['is_active'] !== 1) {
            return ['success' => false, 'error' => 'Razorpay gateway is currently disabled.'];
        }
        if (!is_numeric($amount)) {
            return ['success' => false, 'error' => 'Invalid deposit amount.'];
        }
        $amountNorm = Currency::normalizeDecimal($amount, 2);
        if ((float) $amountNorm < (float) $gateway['min_amount'] || (float) $amountNorm > (float) $gateway['max_amount']) {
            return ['success' => false, 'error' => "Amount must be between {$gateway['min_amount']} and {$gateway['max_amount']}."];
        }

        $baseCurrency = Currency::systemBase();
        $feePct = (string) ($gateway['fee_percent'] ?? '0');
        $fee = Currency::mul($amountNorm, Currency::div($feePct, '100', 6), 4);
        $totalPay = Currency::add($amountNorm, $fee, 2);

        $depositId = (int) Database::insert(
            'INSERT INTO deposits (user_id, gateway_code, amount, fee, net_credit, currency, status)
             VALUES (?, "razorpay", ?, ?, ?, ?, "pending")',
            [$userId, $totalPay, $fee, $amountNorm, $baseCurrency]
        );

        $rzp = Payment::razorpay();
        $rzpRes = $rzp->createOrder($totalPay, $baseCurrency, 'DEP_' . $depositId);
        if (!$rzpRes['success']) {
            Database::execute("UPDATE deposits SET status = 'failed', admin_note = ? WHERE id = ?", [mb_substr($rzpRes['error'], 0, 250), $depositId]);
            return $rzpRes;
        }

        $order = $rzpRes['order'];
        Database::execute('UPDATE deposits SET gateway_order_id = ? WHERE id = ?', [$order['id'], $depositId]);

        return [
            'success' => true,
            'deposit_id' => $depositId,
            'razorpay_order_id' => $order['id'],
            'key_id' => $rzp->keyId(),
            'amount_subunits' => $order['amount'],
            'currency' => $order['currency'],
        ];
    }

    public static function verifyAndCreditRazorpay(string $orderId, string $paymentId, string $signature, ?int $expectedUserId = null): array
    {
        $rzp = Payment::razorpay();
        if (!$rzp->verifyCheckoutSignature($orderId, $paymentId, $signature)) {
            return ['success' => false, 'error' => 'Razorpay payment signature verification failed.'];
        }

        return self::finalizeVerifiedRazorpay($orderId, $paymentId, 'checkout_verify', ['order_id' => $orderId, 'payment_id' => $paymentId], $expectedUserId);
    }

    public static function handleRazorpayWebhook(string $rawBody, string $headerSignature): array
    {
        $rzp = Payment::razorpay();
        if (!$rzp->verifyWebhookSignature($rawBody, $headerSignature)) {
            return ['success' => false, 'error' => 'Invalid webhook signature.'];
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return ['success' => false, 'error' => 'Malformed webhook JSON.'];
        }
        $event = (string) ($payload['event'] ?? '');
        if ($event !== 'payment.captured' && $event !== 'order.paid') {
            return ['success' => true, 'ignored' => true, 'event' => $event];
        }

        $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
        $orderId = (string) ($paymentEntity['order_id'] ?? '');
        $paymentId = (string) ($paymentEntity['id'] ?? '');
        if ($orderId === '' || $paymentId === '') {
            return ['success' => false, 'error' => 'Missing payment or order ID in webhook payload.'];
        }

        return self::finalizeVerifiedRazorpay($orderId, $paymentId, $event, $payload, null);
    }

    private static function finalizeVerifiedRazorpay(string $orderId, string $paymentId, string $eventType, array $rawPayload, ?int $expectedUserId): array
    {
        $res = Database::transaction(function () use ($orderId, $paymentId, $eventType, $rawPayload, $expectedUserId) {
            $deposit = Database::fetchOne('SELECT * FROM deposits WHERE gateway_order_id = ? FOR UPDATE', [$orderId]);
            if (!$deposit) {
                return ['success' => false, 'error' => 'Deposit record not found for Razorpay order.'];
            }
            if ($expectedUserId !== null && (int) $deposit['user_id'] !== $expectedUserId) {
                return ['success' => false, 'error' => 'Deposit ownership mismatch.'];
            }
            if ($deposit['status'] === 'paid' || $deposit['credited_wallet_tx_id'] !== null) {
                return ['success' => true, 'duplicate' => true, 'deposit_id' => (int) $deposit['id']];
            }

            $credit = WalletService::credit(
                (int) $deposit['user_id'],
                (string) $deposit['net_credit'],
                'deposit',
                (string) $deposit['id'],
                "Razorpay Deposit #{$deposit['id']} ($paymentId)",
                'rzp_' . $paymentId,
                'credit'
            );

            Database::execute(
                "UPDATE deposits SET status = 'paid', gateway_payment_id = ?, credited_wallet_tx_id = ?, reviewed_at = NOW() WHERE id = ?",
                [$paymentId, $credit['tx_id'], $deposit['id']]
            );

            Database::execute(
                'INSERT INTO payments (deposit_id, user_id, gateway_code, external_id, event_type, amount, currency, signature_verified, status, raw_payload)
                 VALUES (?, ?, "razorpay", ?, ?, ?, ?, 1, "captured", ?)',
                [$deposit['id'], $deposit['user_id'], $paymentId, $eventType, $deposit['amount'], $deposit['currency'], json_encode($rawPayload)]
            );

            return ['success' => true, 'duplicate' => false, 'deposit_id' => (int) $deposit['id'], 'user_id' => (int) $deposit['user_id'], 'net_credit' => $deposit['net_credit']];
        });

        if ($res['success'] && empty($res['duplicate'])) {
            Notification::sendToUser($res['user_id'], 'payment', 'Funds Added Successfully', format_money($res['net_credit']) . ' has been credited via Razorpay.', '/user/index.php?page=wallet');
            ReferralService::recordCommissionOnDeposit($res['user_id'], (int) $res['deposit_id'], (string) $res['net_credit']);
        }
        return $res;
    }

    public static function submitManualDeposit(int $userId, string $amount, string $utrRef, string $userNote, ?array $proofFile = null): array
    {
        $gateway = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = "manual" LIMIT 1');
        if (!$gateway || (int) $gateway['is_active'] !== 1) {
            return ['success' => false, 'error' => 'Manual payment gateway is currently disabled.'];
        }
        if (!is_numeric($amount)) {
            return ['success' => false, 'error' => 'Invalid amount.'];
        }
        $amountNorm = Currency::normalizeDecimal($amount, 2);
        if ((float) $amountNorm < (float) $gateway['min_amount'] || (float) $amountNorm > (float) $gateway['max_amount']) {
            return ['success' => false, 'error' => "Amount must be between {$gateway['min_amount']} and {$gateway['max_amount']}."];
        }
        $utrRef = trim($utrRef);
        if ($utrRef === '') {
            return ['success' => false, 'error' => 'Transaction / UTR reference number is required.'];
        }

        $dup = Database::fetchOne('SELECT id FROM deposits WHERE gateway_code = "manual" AND utr_reference = ? LIMIT 1', [$utrRef]);
        if ($dup) {
            return ['success' => false, 'error' => 'This UTR / transaction reference has already been submitted.'];
        }

        $proofPath = null;
        if ($proofFile && !empty($proofFile['tmp_name'])) {
            $up = Upload::store($proofFile, 'payments');
            if (!$up['success']) {
                return $up;
            }
            $proofPath = $up['path'];
        }

        $baseCurrency = Currency::systemBase();
        $depositId = (int) Database::insert(
            'INSERT INTO deposits (user_id, gateway_code, amount, fee, net_credit, currency, utr_reference, proof_file, user_note, status)
             VALUES (?, "manual", ?, 0.0000, ?, ?, ?, ?, ?, "pending")',
            [$userId, $amountNorm, $amountNorm, $baseCurrency, $utrRef, $proofPath, mb_substr(trim($userNote), 0, 250)]
        );

        Logger::activity('user', $userId, 'payment.manual_submit', 'deposits', (string) $depositId, ['amount' => $amountNorm, 'utr' => $utrRef]);
        return ['success' => true, 'deposit_id' => $depositId];
    }

    public static function approveManualDeposit(int $depositId, int $adminId, string $adminNote = ''): array
    {
        $res = Database::transaction(function () use ($depositId, $adminId, $adminNote) {
            $deposit = Database::fetchOne('SELECT * FROM deposits WHERE id = ? FOR UPDATE', [$depositId]);
            if (!$deposit) {
                return ['success' => false, 'error' => 'Deposit not found.'];
            }
            if ($deposit['status'] !== 'pending' || $deposit['credited_wallet_tx_id'] !== null) {
                return ['success' => false, 'error' => 'Deposit has already been processed.'];
            }

            $credit = WalletService::credit(
                (int) $deposit['user_id'],
                (string) $deposit['net_credit'],
                'deposit',
                (string) $deposit['id'],
                "Manual Deposit #{$deposit['id']} Approved (UTR: {$deposit['utr_reference']})",
                'manual_dep_' . $deposit['id'],
                'credit',
                $adminId
            );

            Database::execute(
                "UPDATE deposits SET status = 'approved', credited_wallet_tx_id = ?, admin_note = ?, reviewed_by_admin_id = ?, reviewed_at = NOW() WHERE id = ?",
                [$credit['tx_id'], mb_substr($adminNote, 0, 250), $adminId, $depositId]
            );

            Database::execute(
                'INSERT INTO payments (deposit_id, user_id, gateway_code, external_id, event_type, amount, currency, signature_verified, status)
                 VALUES (?, ?, "manual", ?, "manual.approved", ?, ?, 1, "approved")',
                [$depositId, $deposit['user_id'], $deposit['utr_reference'], $deposit['amount'], $deposit['currency']]
            );

            return ['success' => true, 'user_id' => (int) $deposit['user_id'], 'net_credit' => $deposit['net_credit']];
        });

        if ($res['success']) {
            Notification::sendToUser($res['user_id'], 'payment', 'Deposit Approved', 'Your manual deposit of ' . format_money($res['net_credit']) . ' has been approved and credited.', '/user/index.php?page=wallet', true);
            ReferralService::recordCommissionOnDeposit($res['user_id'], $depositId, (string) $res['net_credit']);
            Logger::activity('admin', $adminId, 'payment.manual_approve', 'deposits', (string) $depositId);
        }
        return $res;
    }

    public static function rejectManualDeposit(int $depositId, int $adminId, string $reason): array
    {
        $deposit = Database::fetchOne('SELECT * FROM deposits WHERE id = ? LIMIT 1', [$depositId]);
        if (!$deposit || $deposit['status'] !== 'pending') {
            return ['success' => false, 'error' => 'Pending deposit not found.'];
        }
        Database::execute(
            "UPDATE deposits SET status = 'rejected', admin_note = ?, reviewed_by_admin_id = ?, reviewed_at = NOW() WHERE id = ?",
            [mb_substr($reason, 0, 250), $adminId, $depositId]
        );
        Notification::sendToUser((int) $deposit['user_id'], 'payment', 'Deposit Rejected', "Manual deposit #$depositId was rejected: $reason", '/user/index.php?page=payments');
        Logger::activity('admin', $adminId, 'payment.manual_reject', 'deposits', (string) $depositId, ['reason' => $reason]);
        return ['success' => true];
    }
}
