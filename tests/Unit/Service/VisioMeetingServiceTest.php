<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Tests\Unit\Service;

use OCA\CalendarVisioAddon\Service\VisioApiService;
use OCA\CalendarVisioAddon\Service\VisioMeetingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\CalendarVisioAddon\Service\VisioMeetingService
 */
#[CoversClass(VisioMeetingService::class)]
class VisioMeetingServiceTest extends TestCase
{

    private MockObject&VisioApiService $apiService;
    private MockObject&LoggerInterface $logger;
    private VisioMeetingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiService = $this->createMock(VisioApiService::class);
        $this->logger     = $this->createMock(LoggerInterface::class);
        $this->service    = new VisioMeetingService($this->apiService, $this->logger);
    }

    // ─── isConfigured ────────────────────────────────────────────────────────

    public function testIsConfiguredDelegatesToApiService(): void
    {
        $this->apiService->method('isConfigured')->willReturn(true);
        $this->assertTrue($this->service->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenApiServiceSaysSo(): void
    {
        $this->apiService->method('isConfigured')->willReturn(false);
        $this->assertFalse($this->service->isConfigured());
    }

    // ─── createMeeting — not configured ──────────────────────────────────────

    public function testCreateMeetingReturnsNullWhenNotConfigured(): void
    {
        $this->apiService->method('isConfigured')->willReturn(false);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('not fully configured'));

        $this->assertNull($this->service->createMeeting('user@example.com'));
    }

    // ─── createMeeting — token failure ───────────────────────────────────────

    public function testCreateMeetingReturnsNullWhenTokenFails(): void
    {
        $this->apiService->method('isConfigured')->willReturn(true);
        $this->apiService->method('obtainToken')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to obtain'));

        $this->assertNull($this->service->createMeeting('user@example.com'));
    }

    // ─── createMeeting — room creation failure ───────────────────────────────

    public function testCreateMeetingReturnsNullWhenRoomCreationFails(): void
    {
        $this->apiService->method('isConfigured')->willReturn(true);
        $this->apiService->method('obtainToken')->willReturn('valid-token');
        $this->apiService->method('createRoom')->willReturn(null);

        $this->assertNull($this->service->createMeeting('user@example.com'));
    }

    // ─── createMeeting — success ─────────────────────────────────────────────

    public function testCreateMeetingReturnsUrlOnSuccess(): void
    {
        $expectedUrl = 'https://visio.example.org/rooms/abc-123';

        $this->apiService->method('isConfigured')->willReturn(true);
        $this->apiService->method('obtainToken')->willReturn('valid-jwt');
        $this->apiService->method('createRoom')->willReturn($expectedUrl);

        $result = $this->service->createMeeting('user@example.com');

        $this->assertSame($expectedUrl, $result);
    }

    public function testCreateMeetingPassesUserEmailToTokenMethod(): void
    {
        $email = 'john.doe@example.com';

        $this->apiService->method('isConfigured')->willReturn(true);
        $this->apiService
            ->expects($this->once())
            ->method('obtainToken')
            ->with($email)
            ->willReturn('jwt');
        $this->apiService->method('createRoom')->willReturn('https://visio.example.org/r/x');

        $this->service->createMeeting($email);
    }

    public function testCreateMeetingPassesTokenToCreateRoom(): void
    {
        $token = 'my-access-token-abc';

        $this->apiService->method('isConfigured')->willReturn(true);
        $this->apiService->method('obtainToken')->willReturn($token);
        $this->apiService
            ->expects($this->once())
            ->method('createRoom')
            ->with($token)
            ->willReturn('https://visio.example.org/r/y');

        $this->service->createMeeting('user@example.com');
    }
}
