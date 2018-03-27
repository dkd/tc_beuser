<?php

/**
 * Definitions for routes provided by EXT:beuser
 */
return [
    // Dispatch the permissions actions
    'tcbeuser_access_permissions' => [
        'path' => '/tcbeusers/access/permissions',
        'target' => \Dkd\TcBeuser\Controller\PermissionAjaxController::class . '::dispatch'
    ]
];
