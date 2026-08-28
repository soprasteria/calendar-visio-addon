<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

return [
    'routes' => [
        // Meeting creation — requires authentication + CSRF token (POST)
        ['name' => 'meeting#create',    'url' => '/api/v1/meeting/create', 'verb' => 'POST'],
        // Config status — read-only, no CSRF needed, authenticated
        ['name' => 'meeting#getConfig', 'url' => '/api/v1/config',         'verb' => 'GET'],
        // Admin settings save
        ['name' => 'settings#save',     'url' => '/admin/settings',        'verb' => 'POST'],
    ],
];
