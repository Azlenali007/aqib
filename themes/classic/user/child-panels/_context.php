<?php
declare(strict_types=1);
// Helper for Child Panel sub-navigation and strict ownership verification
$cpId = (int) ($_GET['panel_id'] ?? $_GET['id'] ?? 0);
$cpPanel = $cpId > 0 ? ChildPanelService::getOwnedPanel($cpId, (int) $user['id']) : null;
if (!$cpPanel) {
    Session::flash('error', 'Child panel not found or access denied.');
    Response::redirect('/user/index.php?page=child-panels');
}

function render_child_panel_tabs(int $panelId, string $activePage): void
{
    $tabs = [
        'child-panels/details' => 'Overview',
        'child-panels/domains' => 'Domains',
        'child-panels/branding' => 'Branding',
        'child-panels/pricing' => 'Pricing',
        'child-panels/services' => 'Services',
        'child-panels/users' => 'Users',
        'child-panels/orders' => 'Orders',
        'child-panels/wallet' => 'Wallet',
        'child-panels/transactions' => 'Transactions',
        'child-panels/settings' => 'Settings',
    ];
    echo '<div class="cp-tabs">';
    echo '<a class="btn btn-outline btn-sm" href="/user/index.php?page=child-panels">&larr; All Panels</a>';
    foreach ($tabs as $p => $label) {
        $cls = $activePage === $p ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
        echo '<a class="' . $cls . '" href="/user/index.php?page=' . $p . '&panel_id=' . $panelId . '">' . e($label) . '</a>';
    }
    echo '</div>';
}
