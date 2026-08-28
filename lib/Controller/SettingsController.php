<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller
{

    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IConfig $config,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Save admin settings (API URL, Client ID, Client Secret).
     *
     * @AuthorizedAdminSetting(settings=OCA\CalendarVisioAddon\Settings\AdminSettings)
     */
    public function save(string $apiUrl, string $clientId = '', string $clientSecret = ''): JSONResponse
    {
        if (!empty($apiUrl) && filter_var($apiUrl, FILTER_VALIDATE_URL) === false) {
            return new JSONResponse(['success' => false, 'error' => 'Invalid URL format'], Http::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->config->setSystemValue('visio_api_url', $apiUrl);
        $this->config->setSystemValue('visio_client_id', $clientId);

        // Only overwrite the secret if a non-empty value is provided
        // (empty submission = "keep existing secret")
        if ($clientSecret !== '') {
            $this->config->setSystemValue('visio_client_secret', $clientSecret);
        }

        return new JSONResponse(['success' => true]);
    }
}
