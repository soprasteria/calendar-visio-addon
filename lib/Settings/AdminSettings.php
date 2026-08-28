<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class AdminSettings implements ISettings
{

    public function __construct(
        private readonly IConfig $config,
    ) {
    }

    public function getForm(): TemplateResponse
    {
        $apiUrl       = $this->config->getSystemValue('visio_api_url', '');
        $clientId     = $this->config->getSystemValue('visio_client_id', '');
        $clientSecret = $this->config->getSystemValue('visio_client_secret', '') !== '' ? '********' : '';

        Util::addScript('calendar_visio_addon', 'admin-settings');

        return new TemplateResponse(
            'calendar_visio_addon',
            'admin',
            [
                'apiUrl'       => $apiUrl,
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
            ],
            TemplateResponse::RENDER_AS_BLANK
        );
    }

    public function getSection(): string
    {
        return 'calendar_visio_addon'; // matches AdminSection::getId()
    }

    public function getPriority(): int
    {
        return 50;
    }
}
