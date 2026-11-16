<?php
declare(strict_types=1);

function admin_can(string $permission): bool
{
    return AdminAuth::can($permission);
}
