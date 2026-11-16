<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
AdminAuth::requireAuth('services.view');
$rows = Database::fetchAll('SELECT s.id, c.name AS category, s.name, s.platform, s.min_qty, s.max_qty, s.provider_price, s.provider_currency, s.provider_cost_base, s.selling_price, s.profit_margin_percent, s.is_active FROM services s INNER JOIN categories c ON c.id=s.category_id ORDER BY s.id ASC');
Response::csv('services_' . date('Ymd_His') . '.csv', ['ID', 'Category', 'Name', 'Platform', 'Min', 'Max', 'Provider Price', 'Provider Currency', 'Base Cost', 'Selling Price', 'Margin %', 'Active'], $rows);
