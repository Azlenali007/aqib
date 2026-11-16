<?php
declare(strict_types=1);

$rows = Database::fetchAll(
    'SELECT s.id AS service, s.name, s.service_type AS type, c.name AS category,
            s.selling_price AS rate, s.min_qty AS min, s.max_qty AS max,
            s.refill_supported AS refill, s.cancel_supported AS cancel,
            s.dripfeed_supported AS dripfeed, s.system_base_currency AS currency
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1 AND c.is_active = 1
     ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC'
);

foreach ($rows as &$r) {
    $r['service'] = (int) $r['service'];
    $r['min'] = (int) $r['min'];
    $r['max'] = (int) $r['max'];
    $r['refill'] = (bool) $r['refill'];
    $r['cancel'] = (bool) $r['cancel'];
    $r['dripfeed'] = (bool) $r['dripfeed'];
}
Response::json($rows);
