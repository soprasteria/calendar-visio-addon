<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Service;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Handles the OAuth2 client-credentials flow against the Meet External API.
 *
 * Flow:
 *  1. POST {apiUrl}/external-api/v1.0/application/token/
 *     body: { client_id, client_secret, grant_type: "client_credentials", scope: <user_email> }
 *     → returns { access_token, token_type, expires_in }
 *
 *  2. POST {apiUrl}/external-api/v1.0/rooms/
 *     header: Authorization: Bearer <access_token>
 *     body: { name: "Réunion Nextcloud" }
 *     → returns { id, name, slug, url, ... }
 */
class VisioApiService
{

    public function __construct(
        private readonly IClientService $clientService,
        private readonly IConfig $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Resolve Meet base URL — env var takes priority over system config.
     */
    public function resolveBaseUrl(): string
    {
        $envUrl = (string) getenv('VISIO_API_URL');
        if ($envUrl !== '') {
            return rtrim($envUrl, '/');
        }
        return rtrim((string) $this->config->getSystemValue('visio_api_url', ''), '/');
    }

    public function getClientId(): string
    {
        $env = (string) getenv('VISIO_CLIENT_ID');
        if ($env !== '') {
            return $env;
        }
        return (string) $this->config->getSystemValue('visio_client_id', '');
    }

    public function getClientSecret(): string
    {
        $env = (string) getenv('VISIO_CLIENT_SECRET');
        if ($env !== '') {
            return $env;
        }
        return (string) $this->config->getSystemValue('visio_client_secret', '');
    }

    public function isConfigured(): bool
    {
        return $this->resolveBaseUrl() !== ''
            && $this->getClientId()     !== ''
            && $this->getClientSecret() !== '';
    }

    /**
     * Step 1 — Obtain a user-scoped JWT from the Meet External API.
     *
     * @param string $userEmail  Email of the Nextcloud user acting on their behalf
     * @return string|null       Bearer access_token, or null on failure
     */
    public function obtainToken(string $userEmail): ?string
    {
        $baseUrl      = $this->resolveBaseUrl();
        $tokenUrl     = $baseUrl . '/external-api/v1.0/application/token/';
        $clientId     = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        try {
            $client   = $this->clientService->newClient();
            $response = $client->post($tokenUrl, [
                'timeout' => 15,
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'body'    => json_encode([
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type'    => 'client_credentials',
                    'scope'         => $userEmail,
                ], JSON_THROW_ON_ERROR),
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (empty($data['access_token'])) {
                $this->logger->error('Meet token endpoint returned no access_token', [
                    'app' => 'calendar_visio_addon', 'response' => $body,
                ]);
                return null;
            }

            return (string) $data['access_token'];

        } catch (\Throwable $e) {
            $this->logger->error('Meet token request failed', [
                'app'       => 'calendar_visio_addon',
                'url'       => $tokenUrl,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Step 2 — Create a room via the Meet External API and return its join URL.
     *
     * @param string $accessToken  Bearer token obtained from obtainToken()
     * @param string $roomName     Display name for the room
     * @return string|null         Full join URL or null on failure
     */
    public function createRoom(string $accessToken, string $roomName = 'Réunion Nextcloud'): ?string
    {
        $baseUrl  = $this->resolveBaseUrl();
        $roomsUrl = $baseUrl . '/external-api/v1.0/rooms/';

        try {
            $client   = $this->clientService->newClient();
            $response = $client->post($roomsUrl, [
                'timeout' => 20,
                'headers' => [
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'body' => json_encode([
                    'name' => $roomName,
                ], JSON_THROW_ON_ERROR),
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->error('Meet rooms endpoint returned unexpected status', [
                    'app'        => 'calendar_visio_addon',
                    'statusCode' => $statusCode,
                ]);
                return null;
            }

            $body = (string) $response->getBody();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            // 'url' is the computed field added by RoomSerializer.to_representation()
            // Fallback: build URL from slug if APPLICATION_BASE_URL is not set on Meet side
            if (!empty($data['url'])) {
                return (string) $data['url'];
            }

            if (!empty($data['slug'])) {
                return rtrim($baseUrl, '/') . '/' . ltrim($data['slug'], '/');
            }

            $this->logger->error('Meet rooms response has no url or slug', [
                'app' => 'calendar_visio_addon', 'response' => $body,
            ]);
            return null;

        } catch (\Throwable $e) {
            $this->logger->error('Meet room creation failed', [
                'app'       => 'calendar_visio_addon',
                'url'       => $roomsUrl,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
