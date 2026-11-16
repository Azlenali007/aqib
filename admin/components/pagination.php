<?php
declare(strict_types=1);
function admin_pagination(int $page, int $totalPages, string $baseUrl): void
{
    if ($totalPages <= 1) return;
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    echo '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:14px">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $cls = $i === $page ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
        echo '<a class="' . $cls . '" href="' . e($baseUrl . $sep . 'p=' . $i) . '">' . $i . '</a>';
    }
    echo '</div>';
}
