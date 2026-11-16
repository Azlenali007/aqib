<?php
declare(strict_types=1);

class ServiceManager
{
    public static function bulkAction(string $action, array $serviceIds, array $params = [], ?int $adminId = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $serviceIds), fn($id) => $id > 0)));
        if (empty($ids)) {
            return ['success' => false, 'error' => 'No valid services selected.'];
        }

        $successCount = 0;
        $failCount = 0;
        $failures = [];

        foreach ($ids as $sid) {
            try {
                switch ($action) {
                    case 'enable':
                        $successCount += Database::execute('UPDATE services SET is_active = 1 WHERE id = ?', [$sid]);
                        break;
                    case 'disable':
                        $successCount += Database::execute('UPDATE services SET is_active = 0 WHERE id = ?', [$sid]);
                        break;
                    case 'delete':
                        $hasOrders = (int) Database::fetchValue('SELECT COUNT(*) FROM orders WHERE service_id = ?', [$sid], 0);
                        if ($hasOrders > 0) {
                            $failCount++;
                            $failures[] = ['id' => $sid, 'reason' => 'Cannot delete service with existing orders (disable it instead).'];
                            continue 2;
                        }
                        $successCount += Database::execute('DELETE FROM services WHERE id = ?', [$sid]);
                        break;
                    case 'change_category':
                        $catId = (int) ($params['category_id'] ?? 0);
                        if ($catId <= 0) {
                            throw new RuntimeException('Invalid category selected.');
                        }
                        $successCount += Database::execute('UPDATE services SET category_id = ? WHERE id = ?', [$catId, $sid]);
                        break;
                    case 'change_provider':
                        $provId = !empty($params['provider_id']) ? (int) $params['provider_id'] : null;
                        $successCount += Database::execute('UPDATE services SET provider_id = ? WHERE id = ?', [$provId, $sid]);
                        break;
                    case 'change_margin':
                        $margin = (string) ($params['profit_margin_percent'] ?? '');
                        if (!is_numeric($margin)) {
                            throw new RuntimeException('Profit margin must be numeric.');
                        }
                        $svc = Database::fetchOne('SELECT provider_cost_base, selling_price FROM services WHERE id = ?', [$sid]);
                        if (!$svc) {
                            throw new RuntimeException('Service not found.');
                        }
                        $baseCost = (float) $svc['provider_cost_base'] > 0 ? $svc['provider_cost_base'] : $svc['selling_price'];
                        $mult = Currency::add('1', Currency::div($margin, '100', 6), 6);
                        $newSelling = Currency::mul((string) $baseCost, $mult, 6);
                        $successCount += Database::execute(
                            'UPDATE services SET profit_margin_percent = ?, selling_price = ? WHERE id = ?',
                            [Currency::normalizeDecimal($margin, 2), $newSelling, $sid]
                        );
                        break;
                    case 'change_price':
                        $price = (string) ($params['selling_price'] ?? '');
                        if (!is_numeric($price) || (float) $price < 0) {
                            throw new RuntimeException('Selling price must be non-negative.');
                        }
                        $successCount += Database::execute(
                            'UPDATE services SET selling_price = ? WHERE id = ?',
                            [Currency::normalizeDecimal($price, 6), $sid]
                        );
                        break;
                    case 'enable_refill':
                        $successCount += Database::execute('UPDATE services SET refill_supported = 1 WHERE id = ?', [$sid]);
                        break;
                    case 'disable_refill':
                        $successCount += Database::execute('UPDATE services SET refill_supported = 0 WHERE id = ?', [$sid]);
                        break;
                    case 'enable_cancel':
                        $successCount += Database::execute('UPDATE services SET cancel_supported = 1 WHERE id = ?', [$sid]);
                        break;
                    case 'disable_cancel':
                        $successCount += Database::execute('UPDATE services SET cancel_supported = 0 WHERE id = ?', [$sid]);
                        break;
                    default:
                        throw new RuntimeException("Unsupported bulk action: $action");
                }
            } catch (Throwable $e) {
                $failCount++;
                $failures[] = ['id' => $sid, 'reason' => $e->getMessage()];
            }
        }

        Logger::activity('admin', $adminId, 'services.bulk_' . $action, 'services', implode(',', array_slice($ids, 0, 20)), [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ]);

        return [
            'success' => true,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'failures' => $failures,
        ];
    }
}
