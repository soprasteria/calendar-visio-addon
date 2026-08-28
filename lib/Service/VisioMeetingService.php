<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Service;

use Psr\Log\LoggerInterface;

/**
 * Orchestrates the full Visio meeting creation flow:
 *   1. Obtain a user-scoped JWT via VisioApiService::obtainToken($userEmail)
 *   2. Create a room via VisioApiService::createRoom($token, $name)
 *   3. Return the join URL
 *
 * The userEmail is provided at call-time by the controller
 * (retrieved from the Nextcloud session by MeetingController).
 */
class VisioMeetingService
{

    public function __construct(
        private readonly VisioApiService $apiService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiService->isConfigured();
    }

    /**
     * Create a Visio meeting on behalf of the given user.
     *
     * @param string $userEmail  Email of the authenticated Nextcloud user
     * @return string|null       Join URL or null on failure
     */
    public function createMeeting(string $userEmail): ?string
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Visio API is not fully configured (URL/clientId/clientSecret)', [
                'app' => 'calendar_visio_addon',
            ]);
            return null;
        }

        // Step 1 — obtain delegation token
        $accessToken = $this->apiService->obtainToken($userEmail);
        if ($accessToken === null) {
            $this->logger->error('Failed to obtain Visio access token', [
                'app'   => 'calendar_visio_addon',
                'email' => $userEmail,
            ]);
            return null;
        }

        // Step 2 — create room
        return $this->apiService->createRoom($accessToken);
    }
}
