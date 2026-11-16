<?php
declare(strict_types=1);

class OrderService
{
    public static function calculateCharge(array $service, int $quantity, array $user = []): string
    {
        $ratePer1k = (string) $service['selling_price'];
        $discountPct = (float) ($user['custom_rate_percent'] ?? 0);
        if ($discountPct > 0 && $discountPct < 100) {
            $factor = Currency::sub('1', Currency::div((string) $discountPct, '100', 6), 6);
            $ratePer1k = Currency::mul($ratePer1k, $factor, 6);
        }
        $raw = Currency::div(Currency::mul($ratePer1k, (string) $quantity, 6), '1000', 4);
        return $raw;
    }

    public static function placeOrder(int $userId, int $serviceId, string $link, int $quantity, string $source = 'web', ?int $massOrderId = null): array
    {
        $link = trim($link);
        if ($link === '' || mb_strlen($link) > 500) {
            return ['success' => false, 'error' => 'Please enter a valid target link.'];
        }

        $user = Database::fetchOne('SELECT * FROM users WHERE id = ? AND status = "active" LIMIT 1', [$userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'User account is inactive or not found.'];
        }

        $service = Database::fetchOne('SELECT * FROM services WHERE id = ? AND is_active = 1 LIMIT 1', [$serviceId]);
        if (!$service) {
            return ['success' => false, 'error' => 'Selected service is unavailable.'];
        }

        if ($quantity < (int) $service['min_qty'] || $quantity > (int) $service['max_qty']) {
            return ['success' => false, 'error' => "Quantity must be between {$service['min_qty']} and {$service['max_qty']}."];
        }

        $charge = self::calculateCharge($service, $quantity, $user);
        if (Currency::compare($charge, '0', 4) <= 0) {
            return ['success' => false, 'error' => 'Calculated order charge is invalid.'];
        }

        $providerCost = Currency::div(Currency::mul((string) $service['provider_cost_base'], (string) $quantity, 6), '1000', 4);
        $profit = Currency::sub($charge, $providerCost, 4);
        $baseCurrency = Currency::systemBase();

        // Atomic debit + order insertion
        $txResult = Database::transaction(function () use ($userId, $service, $link, $quantity, $charge, $providerCost, $profit, $baseCurrency, $source, $massOrderId) {
            $orderId = (int) Database::insert(
                'INSERT INTO orders (user_id, service_id, provider_id, mass_order_id, link, quantity, unit_price_per_1k, charge, provider_cost, profit, currency, remains, status, source, refill_available, cancel_available)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", ?, ?, ?)',
                [
                    $userId,
                    $service['id'],
                    $service['provider_id'],
                    $massOrderId,
                    $link,
                    $quantity,
                    $service['selling_price'],
                    $charge,
                    $providerCost,
                    $profit,
                    $baseCurrency,
                    $quantity,
                    $source,
                    (int) $service['refill_supported'],
                    (int) $service['cancel_supported'],
                ]
            );

            $debit = WalletService::debit(
                $userId,
                $charge,
                'order',
                (string) $orderId,
                "Order #$orderId - {$service['name']} (Qty: $quantity)",
                'order_debit_' . $orderId
            );

            if (!$debit['success']) {
                throw new RuntimeException($debit['error']);
            }

            Database::execute(
                'INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message)
                 VALUES (?, "user", ?, NULL, "pending", "Order placed and wallet charged.")',
                [$orderId, $userId]
            );

            return ['order_id' => $orderId, 'balance_after' => $debit['balance_after']];
        });

        $orderId = $txResult['order_id'];

        // Forward to Provider API if configured
        if (!empty($service['provider_id']) && !empty($service['provider_service_id'])) {
            $providerRow = Database::fetchOne('SELECT * FROM providers WHERE id = ? AND status = "active" LIMIT 1', [$service['provider_id']]);
            if ($providerRow) {
                try {
                    $client = Provider::client($providerRow);
                    $apiRes = $client->addOrder((string) $service['provider_service_id'], $link, $quantity);
                    if ($apiRes['success']) {
                        Database::execute(
                            'UPDATE orders SET provider_order_id = ?, status = "processing", last_synced_at = NOW() WHERE id = ?',
                            [$apiRes['provider_order_id'], $orderId]
                        );
                        Database::execute(
                            'INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message)
                             VALUES (?, "provider", "pending", "processing", ?)',
                            [$orderId, 'Forwarded to provider. External Order ID: ' . $apiRes['provider_order_id']]
                        );
                    } else {
                        Database::execute(
                            'UPDATE orders SET provider_error = ? WHERE id = ?',
                            [mb_substr((string) $apiRes['error'], 0, 250), $orderId]
                        );
                        Database::execute(
                            'INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message)
                             VALUES (?, "provider", "pending", "pending", ?)',
                            [$orderId, 'Provider API error: ' . mb_substr((string) $apiRes['error'], 0, 200)]
                        );
                    }
                } catch (Throwable $e) {
                    Logger::file('provider_forward.log', "Order #$orderId forward exception: " . $e->getMessage());
                }
            }
        }

        Notification::sendToUser($userId, 'order', "Order #$orderId Placed", "Your order for {$service['name']} ($quantity) has been received.", "/user/index.php?page=order-details&id=$orderId");
        Logger::activity('user', $userId, 'order.create', 'orders', (string) $orderId, ['charge' => $charge, 'service_id' => $serviceId]);

        return ['success' => true, 'order_id' => $orderId, 'charge' => $charge, 'balance_after' => $txResult['balance_after']];
    }

    public static function placeMassOrder(int $userId, string $rawLines): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawLines))));
        if (empty($lines)) {
            return ['success' => false, 'error' => 'Please enter at least one order line.'];
        }
        if (count($lines) > 100) {
            return ['success' => false, 'error' => 'Maximum 100 lines per mass order submission.'];
        }

        $baseCurrency = Currency::systemBase();
        $massId = (int) Database::insert(
            'INSERT INTO mass_orders (user_id, total_lines, currency) VALUES (?, ?, ?)',
            [$userId, count($lines), $baseCurrency]
        );

        $results = [];
        $successCount = 0;
        $failCount = 0;
        $totalCharge = '0.0000';

        foreach ($lines as $idx => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3) {
                $failCount++;
                $results[] = ['line' => $idx + 1, 'raw' => $line, 'success' => false, 'error' => 'Invalid format. Expected: service_id | link | quantity'];
                continue;
            }
            [$svcId, $link, $qty] = [$parts[0], $parts[1], $parts[2]];
            if (!ctype_digit($svcId) || !ctype_digit($qty)) {
                $failCount++;
                $results[] = ['line' => $idx + 1, 'raw' => $line, 'success' => false, 'error' => 'Service ID and quantity must be positive integers.'];
                continue;
            }
            try {
                $res = self::placeOrder($userId, (int) $svcId, $link, (int) $qty, 'mass', $massId);
                if ($res['success']) {
                    $successCount++;
                    $totalCharge = Currency::add($totalCharge, $res['charge'], 4);
                    $results[] = ['line' => $idx + 1, 'order_id' => $res['order_id'], 'charge' => $res['charge'], 'success' => true];
                } else {
                    $failCount++;
                    $results[] = ['line' => $idx + 1, 'raw' => $line, 'success' => false, 'error' => $res['error']];
                }
            } catch (Throwable $e) {
                $failCount++;
                $results[] = ['line' => $idx + 1, 'raw' => $line, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        Database::execute(
            'UPDATE mass_orders SET success_count = ?, fail_count = ?, total_charge = ? WHERE id = ?',
            [$successCount, $failCount, $totalCharge, $massId]
        );

        return [
            'success' => $successCount > 0,
            'mass_order_id' => $massId,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'total_charge' => $totalCharge,
            'results' => $results,
        ];
    }

    public static function requestRefill(int $orderId, int $userId): array
    {
        $order = Database::fetchOne('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1', [$orderId, $userId]);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }
        if ((int) $order['refill_available'] !== 1 || $order['status'] !== 'completed') {
            return ['success' => false, 'error' => 'This order is not eligible for refill.'];
        }
        $openRefill = Database::fetchOne("SELECT id FROM refill_requests WHERE order_id = ? AND status IN ('pending','processing') LIMIT 1", [$orderId]);
        if ($openRefill) {
            return ['success' => false, 'error' => 'A refill request is already in progress for this order.'];
        }

        $providerRefillId = null;
        $status = 'pending';
        if (!empty($order['provider_id']) && !empty($order['provider_order_id'])) {
            $prov = Database::fetchOne('SELECT * FROM providers WHERE id = ? AND status = "active" LIMIT 1', [$order['provider_id']]);
            if ($prov) {
                $res = Provider::client($prov)->requestRefill((string) $order['provider_order_id']);
                if ($res['success']) {
                    $providerRefillId = $res['refill_id'] ?: null;
                    $status = 'processing';
                }
            }
        }

        $refillId = (int) Database::insert(
            'INSERT INTO refill_requests (order_id, user_id, provider_id, provider_refill_id, status) VALUES (?, ?, ?, ?, ?)',
            [$orderId, $userId, $order['provider_id'], $providerRefillId, $status]
        );
        Notification::sendToUser($userId, 'refill', "Refill Requested #$refillId", "Refill request submitted for Order #$orderId.");
        return ['success' => true, 'refill_id' => $refillId];
    }

    public static function cancelAndRefund(int $orderId, string $actorType, int $actorId, string $reason = 'Order cancelled'): array
    {
        return Database::transaction(function () use ($orderId, $actorType, $actorId, $reason) {
            $order = Database::fetchOne('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if (!$order) {
                return ['success' => false, 'error' => 'Order not found.'];
            }
            if (in_array($order['status'], ['cancelled', 'refunded', 'completed'], true)) {
                return ['success' => false, 'error' => 'Order cannot be cancelled in its current state.'];
            }
            if ($actorType === 'user') {
                if ((int) $order['user_id'] !== $actorId || (int) $order['cancel_available'] !== 1) {
                    return ['success' => false, 'error' => 'Cancellation is not supported for this order.'];
                }
                if (!empty($order['provider_id']) && !empty($order['provider_order_id'])) {
                    $prov = Database::fetchOne('SELECT * FROM providers WHERE id = ? LIMIT 1', [$order['provider_id']]);
                    if ($prov) {
                        Provider::client($prov)->cancelOrder((string) $order['provider_order_id']);
                    }
                }
            }

            $old = $order['status'];
            Database::execute("UPDATE orders SET status = 'cancelled', remains = quantity WHERE id = ?", [$orderId]);
            $refund = WalletService::credit(
                (int) $order['user_id'],
                (string) $order['charge'],
                'order_refund',
                (string) $orderId,
                "Refund for cancelled Order #$orderId",
                'order_refund_' . $orderId,
                'refund',
                $actorType === 'admin' ? $actorId : null
            );

            Database::execute(
                'INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message) VALUES (?, ?, ?, ?, "cancelled", ?)',
                [$orderId, $actorType, $actorId, $old, $reason]
            );
            Notification::sendToUser((int) $order['user_id'], 'order', "Order #$orderId Cancelled", "Order #$orderId was cancelled and " . format_money($order['charge']) . " refunded to your wallet.");

            return ['success' => true, 'refund' => $refund];
        });
    }
}
