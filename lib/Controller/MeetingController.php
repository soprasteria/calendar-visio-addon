<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Controller;

use OCA\CalendarVisioAddon\Service\VisioMeetingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class MeetingController extends Controller
{

    public function __construct(
        string $appName,
        IRequest $request,
        private readonly VisioMeetingService $visioService,
        private readonly IUserSession $userSession,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Create a Visio meeting on behalf of the authenticated user.
     * The user email is retrieved from the active Nextcloud session.
     */
    #[NoAdminRequired]
    public function create(): JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(
                ['success' => false, 'error' => 'Not authenticated'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        $userEmail = $user->getEMailAddress();
        if (empty($userEmail)) {
            return new JSONResponse(
                ['success' => false, 'error' => 'No email address configured for your account. Please add an email in your Nextcloud profile.'],
                Http::STATUS_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $meetingUrl = $this->visioService->createMeeting($userEmail);

            if ($meetingUrl === null) {
                return new JSONResponse(
                    ['success' => false, 'error' => 'Failed to create Visio meeting — check server logs'],
                    Http::STATUS_INTERNAL_SERVER_ERROR
                );
            }

            return new JSONResponse(['success' => true, 'meetingUrl' => $meetingUrl]);

        } catch (\Throwable $e) {
            $this->logger->error('Unhandled error during Visio meeting creation', [
                'app'       => 'calendar_visio_addon',
                'exception' => $e,
            ]);
            return new JSONResponse(
                ['success' => false, 'error' => 'An internal error occurred'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Return whether the addon is configured.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getConfig(): JSONResponse
    {
        return new JSONResponse(['configured' => $this->visioService->isConfigured()]);
    }
}
