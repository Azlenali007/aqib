<?php
declare(strict_types=1);

function csrf_field(string $scope = 'default'): string
{
    return '<input type="hidden" name="_csrf" value="' . Security::e(Security::csrfToken($scope)) . '">';
}

function csrf_token(string $scope = 'default'): string
{
    return Security::csrfToken($scope);
}
