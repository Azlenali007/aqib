<?php
declare(strict_types=1);
function render_pagination(int $pageNum, int $totalPages, string $baseUrl): void
{
    if ($totalPages <= 1) return;
    echo '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:16px;justify-content:center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i > 1 && $i < $totalPages && abs($i - $pageNum) > 2) {
            if ($i === 2 || $i === $totalPages - 1) echo '<span style="padding:6px 8px;color:#777083">...</span>';
            continue;
        }
        $cls = $i === $pageNum ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
        echo '<a class="' . $cls . '" href="' . e($baseUrl . '&p=' . $i) . '">' . $i . '</a>';
    }
    echo '</div>';
}
