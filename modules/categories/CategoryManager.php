<?php
declare(strict_types=1);

class CategoryManager
{
    public static function allActive(): array
    {
        return Database::fetchAll('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    }

    public static function slugify(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        return $slug ?: ('cat-' . bin2hex(random_bytes(3)));
    }
}
