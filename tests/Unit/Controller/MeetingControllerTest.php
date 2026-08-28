<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Tests\Unit\Controller;

use OCA\CalendarVisioAddon\Controller\MeetingController;
use OCA\CalendarVisioAddon\Service\VisioMeetingService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\CalendarVisioAddon\Controller\MeetingController
 */
#[CoversClass(MeetingController::class)]
class MeetingControllerTest extends TestCase
{

    private MockObject&VisioMeetingService $visioService;
    private MockObject&IUserSession        $userSession;
    private MockObject&LoggerInterface     $logger;
    private MeetingController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->visioService = $this->createMock(VisioMeetingService::class);
        $this->userSession  = $this->createMock(IUserSession::class);
        $this->logger       = $this->createMock(LoggerInterface::class);

        $this->controller = new MeetingController(
            'calendar_visio_addon',
            $this->createMock(IRequest::class),
            $this->visioService,
            $this->userSession,
            $this->logger,
        );
    }

    // ─── create — authentication checks ──────────────────────────────────────

    public function testCreateReturns401WhenNotAuthenticated(): void
    {
        $this->userSession->method('getUser')->willReturn(null);

        $response = $this->controller->create();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
        $data = $response->getData();
        $this->assertFalse($data['success']);
    }

    public function testCreateReturns422WhenUserHasNoEmail(): void
    {
        $user = $this->createMock(IUser::class);
        $user->method('getEMailAddress')->willReturn('');
        $this->userSession->method('getUser')->willReturn($user);

        $response = $this->controller->create();

        $this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
        $data = $response->getData();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('email', $data['error']);
    }

    // ─── create — service failures ────────────────────────────────────────────

    public function testCreateReturns500WhenServiceReturnsNull(): void
    {
        $user = $this->createMock(IUser::class);
        $user->method('getEMailAddress')->willReturn('user@example.com');
        $this->userSession->method('getUser')->willReturn($user);

        $this->visioService->method('createMeeting')->willReturn(null);

        $response = $this->controller->create();

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertFalse($response->getData()['success']);
    }

    public function testCreateReturns500WhenServiceThrows(): void
    {
        $user = $this->createMock(IUser::class);
        $user->method('getEMailAddress')->willReturn('user@example.com');
        $this->userSession->method('getUser')->willReturn($user);

        $this->visioService->method('createMeeting')->willThrowException(new \RuntimeException('Boom'));

        $this->logger->expects($this->once())->method('error');

        $response = $this->controller->create();

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertFalse($response->getData()['success']);
    }

    // ─── create — success ─────────────────────────────────────────────────────

    public function testCreateReturnsUrlOnSuccess(): void
    {
        $url  = 'https://visio.example.org/rooms/abc-123';
        $user = $this->createMock(IUser::class);
        $user->method('getEMailAddress')->willReturn('user@example.com');
        $this->userSession->method('getUser')->willReturn($user);

        $this->visioService->method('createMeeting')->willReturn($url);

        $response = $this->controller->create();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertSame($url, $data['meetingUrl']);
    }

    public function testCreatePassesUserEmailToService(): void
    {
        $email = 'jane.doe@example.com';
        $user  = $this->createMock(IUser::class);
        $user->method('getEMailAddress')->willReturn($email);
        $this->userSession->method('getUser')->willReturn($user);

        $this->visioService
            ->expects($this->once())
            ->method('createMeeting')
            ->with($email)
            ->willReturn('https://visio.example.org/r/1');

        $this->controller->create();
    }

    // ─── getConfig ────────────────────────────────────────────────────────────

    public function testGetConfigReturnsConfiguredTrue(): void
    {
        $this->visioService->method('isConfigured')->willReturn(true);

        $response = $this->controller->getConfig();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertTrue($response->getData()['configured']);
    }

    public function testGetConfigReturnsConfiguredFalse(): void
    {
        $this->visioService->method('isConfigured')->willReturn(false);

        $response = $this->controller->getConfig();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertFalse($response->getData()['configured']);
    }
}
